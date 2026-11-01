# Link shortcode and mailto/tel/anchor handling Implementation Plan

> **STATUS: ✅ COMPLETED** (2026-11-01). Task 1 (`Link` handles mailto/tel/#) and Task 2 (`hyperpress_link` shortcode, utils dependency) done on `feat/link-shortcode`. `composer lint`, `test:unit` (176) and `test:integration` (1497, 6 skipped as before) pass in `plugins/`.
> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `[hyperpress_link]` shortcode that sets `target` and `rel` through `HyperPress\Utils\Link`, after teaching `Link` that `mailto:`, `tel:` and `#anchor` URLs stay in the same tab.

**Architecture:** Fix `Link::target()` / `Link::is_internal()` in `hyperpress-utils` first (Task 1), then add a `Shortcodes\Anchor` class to `hyperpress-shortcode` that calls the static `Link` methods directly, and make `hyperpress-shortcode` require `hyperpress-utils` (Task 2).

**Tech Stack:** PHP 8.3, WordPress 6.9, PHPUnit (WP_UnitTestCase integration tests), PHPCS. PHP runs in Docker only.

**Spec:** `docs/superpowers/specs/2026-11-01-plugins-link-shortcode-design.md`

## Global Constraints

- Run all PHP tooling from `plugins/` as `docker compose run --rm php composer <script>`; never composer on the host. Extra PHPUnit args go after `--`, e.g. `composer test:integration -- --filter LinkTest`.
- Files use tabs and the WordPress coding standard; `composer lint` must pass.
- Namespace `HyperPress\Shortcode\Shortcodes` for the shortcode, `HyperPress\Utils` for `Link`; text domain `hyperpress`.
- Shortcode tag is exactly `hyperpress_link`; class is `Anchor`.
- Only `mailto`, `tel` and `#` are added to `Link`. No `class_exists` guards in the shortcode.
- `Requires Plugins` in the `hyperpress-shortcode` header must equal `hyperpress-utils` and match `Convention::REQUIRES_PLUGINS`.
- Commit from the repo root: `git add <paths>` then `git commit -m "..." -- <paths>`; never a bare commit. Commit the plan file's ticked checkboxes with each task's code.

## Review Focus

- `tel:5551234` (digits only): PHP's URL parser reads it as host `tel`, port `5551234`; must still be `_self`. Pinned in Task 1.
- `MAILTO:A@B.EXAMPLE` (upper case scheme): `_self`. Pinned in Task 1.
- `mailto:a@b.example?subject=x.pdf`: not treated as a PDF, `_self`. Pinned in Task 1.
- `url="javascript:alert(1)"`: rendered as text only, no link. Pinned in Task 2.
- Enclosed content with a `<script>` tag or a nested shortcode: script stripped, nested shortcode output kept. Pinned in Task 2.

---

### Task 1: `Link` handles mailto, tel and #anchor

**Files:**
- Modify: `plugins/hyperpress-utils/src/Link.php` (`is_internal()` at lines 17-26, `target()` at lines 34-42, and their docblocks)
- Test: `plugins/tests/integration/Utils/LinkTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `Link::target( string $url ): string` now returns `_self` for `mailto:` / `tel:` URLs; `Link::is_internal( string $url ): bool` now returns true for URLs starting with `#`. Signatures unchanged.

- [x] **Step 1: Write the failing tests** in `LinkTest.php`
  - In `urls()` add `'fragment only' => array( '#top', true )` and `'bare hash' => array( '#', true )`.
  - In `targets()` add `_self` cases: `'anchor' => '#top'`, `'mailto' => 'mailto:a@b.example'`, `'mailto upper case' => 'MAILTO:A@B.EXAMPLE'`, `'mailto with pdf in query' => 'mailto:a@b.example?subject=x.pdf'`, `'tel' => 'tel:+31201234567'`, `'tel digits only' => 'tel:5551234'`.
  - In `test_target_and_rel()` add `$this->assertSame( '', Link::rel( Link::target( 'mailto:a@b.example' ) ) );`.
- [x] **Step 2: Run to verify failure:** `docker compose run --rm php composer test:integration -- --filter LinkTest` from `plugins/`. Expected: FAIL on the new `#`, `mailto` and `tel` cases; the existing cases still pass.
- [x] **Step 3: Implement.**
  - `target()`: first statement returns `'_self'` when `preg_match( '/^(mailto|tel):/i', $url )` is 1. The comment must say it is a regex and not `wp_parse_url()` because of the digits-only host/port parse.
  - `is_internal()`: first statement returns true when `0 === strpos( $url, '#' )` (same page).
  - Update both docblocks to mention the new cases.
- [x] **Step 4: Run to verify pass:** the Step 2 command. Expected: `OK`. Then `docker compose run --rm php composer lint`. Expected: no errors.
- [x] **Step 5: Commit** (tick this task's boxes in this plan first):

```bash
git add plugins/hyperpress-utils/src/Link.php plugins/tests/integration/Utils/LinkTest.php docs/superpowers/plans/2026-11-01-plugins-link-shortcode.md
git commit -m "feat(utils): keep mailto, tel and anchor links in the same tab" -- plugins/hyperpress-utils/src/Link.php plugins/tests/integration/Utils/LinkTest.php docs/superpowers/plans/2026-11-01-plugins-link-shortcode.md
```

### Task 2: `[hyperpress_link]` shortcode and the utils dependency

**Files:**
- Create: `plugins/hyperpress-shortcode/src/Shortcodes/Anchor.php`
- Create: `plugins/tests/integration/Shortcodes/AnchorShortcodeTest.php`
- Modify: `plugins/hyperpress-shortcode/src/Plugin.php:14-24` (add `Shortcodes\Anchor::register();`)
- Modify: `plugins/hyperpress-shortcode/hyperpress-shortcode.php` (header: add `Requires Plugins:    hyperpress-utils` in the same column format as the other plugins, e.g. `hyperpress-contact/hyperpress-contact.php:19`)
- Modify: `plugins/tests/src/Support/Convention.php:66` (`'hyperpress-shortcode' => array( 'hyperpress-utils' )`, aligned like its neighbours)

**Interfaces:**
- Consumes: `HyperPress\Utils\Link::target( string ): string` and `Link::rel( string ): string` (Task 1 behavior for mailto/tel/#).
- Produces: shortcode `hyperpress_link` with attributes `url` and `text`; `Anchor::register(): void` and `Anchor::render( $atts = array(), $content = '' ): string`, same shape as `Emphasis`.

- [x] **Step 1: Write the failing header test change.** Change the convention entry in `Convention.php` as above. Run `docker compose run --rm php composer test:unit`. Expected: FAIL in `PluginHeadersTest` for `hyperpress-shortcode: Requires Plugins`.
- [x] **Step 2: Add the `Requires Plugins` header line** to `hyperpress-shortcode.php`. Re-run `composer test:unit`. Expected: pass.
- [x] **Step 3: Write the failing shortcode tests** in `AnchorShortcodeTest.php` (`namespace HyperPress\Tests\Integration\Shortcodes`, `final class AnchorShortcodeTest extends WP_UnitTestCase`, assertions on `do_shortcode()` output):
  - `shortcode_exists( 'hyperpress_link' )` is true.
  - external: `[hyperpress_link url="https://other.example/x"]Text[/hyperpress_link]` gives exactly `<a href="https://other.example/x" target="_blank" rel="noopener noreferrer">Text</a>`.
  - internal: `[hyperpress_link url="/agenda"]Agenda[/hyperpress_link]` gives exactly `<a href="/agenda" target="_self">Agenda</a>` (no `rel`).
  - internal PDF `/files/a.pdf` gives `target="_blank"` and `rel="noopener noreferrer"`.
  - `mailto:a@b.example` and `tel:5551234` give `target="_self"` and no `rel` (data provider with `#top` too).
  - `text` attribute: `[hyperpress_link url="/a" text="Go"]` renders `Go`; with both attribute and enclosed content, `[hyperpress_link url="/a" text="Attr"]Body[/hyperpress_link]` renders `Body`.
  - `text="Tom & Jerry"` renders `Tom &amp; Jerry`.
  - enclosed `<em>Hi</em>` is kept; enclosed `<script>alert(1)</script>Hi` output does not contain `<script`.
  - nested `[hyperpress_link url="/a"][hyperpress_emphasis]Hi[/hyperpress_emphasis][/hyperpress_link]` output contains `<span class="emphasis">Hi</span>`.
  - `url="javascript:alert(1)"` with text `Hi` renders `Hi` with no `<a`; empty `url` with text `Hi` renders `Hi` with no `<a`.
  - empty text with `url="/a"` renders `<a href="/a" target="_self">/a</a>`.
- [x] **Step 4: Run to verify failure:** `docker compose run --rm php composer test:integration -- --filter AnchorShortcodeTest`. Expected: FAIL (shortcode not registered).
- [x] **Step 5: Implement `Anchor`** (`final class`, ABSPATH guard like `Emphasis`). `register()` calls `add_shortcode( 'hyperpress_link', array( self::class, 'render' ) )`. `render()`: `shortcode_atts` for `url` and `text`; text is enclosed content (`wp_kses_post( do_shortcode( $content ) )`, trimmed) when non-empty, else `esc_html( text )`, else the escaped URL; the href is `esc_url( $url )`; if the href is empty return the text only; target and rel come from `\HyperPress\Utils\Link`; omit `rel` when empty. Add `Shortcodes\Anchor::register();` to `Plugin::register()`.
- [x] **Step 6: Run to verify pass:** the Step 4 command, then the full checks from `plugins/`: `composer lint`, `composer test:unit`, `composer test:integration`. Expected: all green. If `ShortcodeParityTest` or another test fails because of the new tag, report it rather than weakening the test.
- [x] **Step 7: Commit** (tick this task's boxes first, and add a short "Outcome" note at the bottom of this plan plus the COMPLETED banner at the top):

```bash
git add plugins/hyperpress-shortcode/src/Shortcodes/Anchor.php plugins/hyperpress-shortcode/src/Plugin.php plugins/hyperpress-shortcode/hyperpress-shortcode.php plugins/tests/src/Support/Convention.php plugins/tests/integration/Shortcodes/AnchorShortcodeTest.php docs/superpowers/plans/2026-11-01-plugins-link-shortcode.md
git commit -m "feat(shortcode): add the hyperpress_link shortcode and require utils" -- plugins/hyperpress-shortcode plugins/tests/src/Support/Convention.php plugins/tests/integration/Shortcodes/AnchorShortcodeTest.php docs/superpowers/plans/2026-11-01-plugins-link-shortcode.md
```

## Known follow-ups

- Other schemes (`sms:`) and query-only URLs are not handled by `Link`.
- The plugin `Description:` header does not mention links.

## Outcome

- `Link::target()` uses a regex for mailto/tel (not `wp_parse_url()`), pinned by the digits-only `tel:5551234` case.
- `Anchor` runs enclosed content through `do_shortcode()` + `wp_kses_post()`; the `text` attribute is `esc_html()`-ed.
- `plugins/hyperpress-shortcode` Requires Plugins is now `hyperpress-utils`; `2026-10-02-plugins-04-remaining-plugins.md` carries a note about the changed requirement.
