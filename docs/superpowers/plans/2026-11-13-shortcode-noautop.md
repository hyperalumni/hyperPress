# `hyperpress_noautop` Shortcode Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add `[hyperpress_noautop]` to `hyperpress-shortcode`, which keeps `wpautop` and `wptexturize` out of its content.

**Architecture:** A `the_content` filter at priority 9 swaps each block for a placeholder; a filter at priority 10, registered after core's `wpautop`, restores it; the shortcode callback returns `do_shortcode( $content )`.

**Tech Stack:** PHP 8.3, WordPress, PHPUnit (integration suite), PHPCS; run through `docker compose run --rm php composer ...` from `plugins/`.

**Spec:** `docs/superpowers/specs/2026-11-13-shortcode-noautop-design.md`

## Global Constraints

- Tag: `hyperpress_noautop`. Class: `HyperPress\Shortcode\Shortcodes\NoAutop` in `plugins/hyperpress-shortcode/src/Shortcodes/NoAutop.php`.
- `hyperpress_raw` (`Raw.php`) is not modified.
- WordPress coding standard (tabs, `array()`), text domain `hyperpress`, `defined( 'ABSPATH' ) || exit;` first in the file.

## Review Focus

- Escaped tag `[[hyperpress_noautop]x[/hyperpress_noautop]]` is not stashed and renders as literal text.
- Two blocks in one post both restore correctly (placeholders do not collide, e.g. index 1 vs 10).
- A post with no block is unchanged and the store stays empty.
- Self-closing / empty body `[hyperpress_noautop][/hyperpress_noautop]` and a null body return the same string.
- Text outside the block still gets paragraphs.

---

### Task 1: NoAutop shortcode, registration and tests

**Files:**
- Create: `plugins/hyperpress-shortcode/src/Shortcodes/NoAutop.php`
- Modify: `plugins/hyperpress-shortcode/src/Plugin.php` (add `Shortcodes\NoAutop::register();` next to `Raw::register()`)
- Modify: `plugins/hyperpress-shortcode/hyperpress-shortcode.php` (header description: mention "unautop'd output")
- Modify: `plugins/README.md` (the `hyperpress-shortcode` row: add `hyperpress_noautop`)
- Modify: `plugins/tests/integration/Shortcodes/PluginIdentifiersTest.php` (add the tag to `tags()` and `NoAutop` to the class list)
- Modify: `plugins/tests/integration/Shortcodes/NullContentTest.php` (add `'hyperpress_noautop' => array( 'hyperpress_noautop', array() )` to `callbacks()`)
- Test: `plugins/tests/integration/Shortcodes/NoAutopTest.php` (create; follow the base class and style of the neighbouring tests in that directory)

**Interfaces:**
- Produces: `NoAutop::register(): void`, `NoAutop::stash( string $content ): string`, `NoAutop::restore( string $content ): string`, `NoAutop::render( $atts = null, $content = '' ): string`.

- [ ] **Step 1: Write the failing tests** in `NoAutopTest.php`, each rendering through `apply_filters( 'the_content', $input )`:
  - `test_block_content_is_not_autop_d`: input `"[hyperpress_noautop]<ul>\n\t<li>\n\t\tindented text\n\t</li>\n</ul>[/hyperpress_noautop]"`; assert the output contains the exact inner string unchanged and contains no `<p>` and no `<br />`.
  - `test_text_outside_the_block_is_still_autop_d`: `"Before.\n\n[hyperpress_noautop]<div>\n x\n</div>[/hyperpress_noautop]\n\nAfter."`; assert `<p>Before.</p>` and `<p>After.</p>` are present and the div text is untouched.
  - `test_nested_shortcodes_still_render`: wrap `[hyperpress_emphasis]in block[/hyperpress_emphasis]` and assert the rendered emphasis output (same expectation as the existing `emphasis_*` case) appears and the literal tag does not.
  - `test_the_invest_page_shape_has_no_stray_paragraphs`: a trimmed fixture of the Invest page structure (a `<section>`, a self-closing `[hyperpress_banner title="T" subtitle="S" anchor="a" color="#0B67B2" /]`, a Foundation `ul.accordion` with `li.accordion-item` whose `div.accordion-content` text sits on its own indented lines, and `[hyperpress_date_distance date="1995-09-01" suffix="false" inclusive="true" /]`); assert no `<p></p>`, no `<p>` inside `.accordion`, and no `<br />` anywhere in the output.
  - `test_two_blocks_both_restore`: eleven blocks `[hyperpress_noautop]n1[/hyperpress_noautop]` ... `n11` in one content string; assert every `nN` appears exactly once and no placeholder text remains.
  - `test_escaped_tag_is_left_as_literal_text`: `'[[hyperpress_noautop]x[/hyperpress_noautop]]'` outputs `[hyperpress_noautop]x[/hyperpress_noautop]`.
  - `test_content_without_a_block_is_unchanged_by_the_filters`: `NoAutop::stash( 'plain' )` returns `'plain'` and `restore( 'plain' )` returns `'plain'`.
  - `test_empty_and_null_bodies_match`: `NoAutop::render( array(), null )` equals `NoAutop::render( array(), '' )`.

- [ ] **Step 2: Run the new tests and confirm they fail.**
  Run (from `plugins/`): `docker compose run --rm php composer test:integration -- --filter NoAutopTest`
  Expected: FAIL (class `NoAutop` not found).

- [ ] **Step 3: Implement `NoAutop`** with the signatures above: `register()` calls `add_shortcode( 'hyperpress_noautop', array( self::class, 'render' ) )`, `add_filter( 'the_content', array( self::class, 'stash' ), 9 )` and `add_filter( 'the_content', array( self::class, 'restore' ), 10 )`. `stash()` uses `preg_replace_callback` with `get_shortcode_regex( array( 'hyperpress_noautop' ) )`, returns an escaped match (group 1 is `[`) unchanged, otherwise stores the full match in a private static array and returns a placeholder made of a per-request alphanumeric prefix and the index with a non-digit terminator, so index 1 does not match inside 10. `restore()` replaces `<p>PLACEHOLDER</p>` (optional whitespace) and bare placeholders from the store, then empties it; it returns early when the store is empty. `render()` returns `do_shortcode( (string) $content )`.

- [ ] **Step 4: Register** `Shortcodes\NoAutop::register();` in `Plugin::register()`; update `PluginIdentifiersTest`, `NullContentTest`, the plugin header description and `plugins/README.md` as listed under Files.

- [ ] **Step 5: Run the whole shortcode verification.**
  Run (from `plugins/`): `docker compose run --rm php composer test:integration` then `composer test:unit` then `composer lint` (each through `docker compose run --rm php`).
  Expected: all pass, PHPCS reports no errors in the changed files. If `ShortcodeParityTest` compares against legacy theme output, do not add the new tag there.

- [ ] **Step 6: Tick this plan's checkboxes and add the plan banner** (`> **STATUS: ✅ COMPLETED** (date). ...`). Do not commit unless the owner asks; list the paths for the owner:
  `plugins/hyperpress-shortcode/src/Shortcodes/NoAutop.php`, `plugins/hyperpress-shortcode/src/Plugin.php`, `plugins/hyperpress-shortcode/hyperpress-shortcode.php`, `plugins/README.md`, the three test files, and the spec and plan under `docs/superpowers/`.

## Outcome / execution notes

(filled in on completion)

## Known follow-ups

- Owner: after deploying the plugin, change the Invest page's `hyperpress_raw` tags to `hyperpress_noautop`.
