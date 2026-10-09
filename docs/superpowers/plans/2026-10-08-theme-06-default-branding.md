# Theme 06: Default Branding (Logo, Site Icon, Login Logo)

> **STATUS: ✅ COMPLETED** (2026-10-08). Tasks 1-5 on branch feature/default-branding; unit, integration (both modes) and lint green. Pending: the logged-in manual checks in Task 5 Step 2 (user to run).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a default logo and site icon inside the theme, make them the Customizer defaults (shown as selected, replaceable, restored when removed), and reuse the site logo on `wp-login.php`.

**Architecture:** On `admin_init` and `after_switch_theme`, a version-gated, idempotent, locked seeder copies the bundled files into uploads and creates attachments. Two read filters (`theme_mod_custom_logo`, `option_site_icon` plus `default_option_site_icon`) return the seeded attachment ID whenever the stored value is empty, so core controls, `has_custom_logo()`, `has_site_icon()` and `wp_site_icon()` need no further changes. Until seeding has run, `get_custom_logo` falls back to the bundled SVG so the front end never shows a hole. The login page restyles core's `.login h1 a` background from the same logo.

**Tech Stack:** PHP 8.3, WordPress 6.9, PHPUnit 9.6 with `WP_UnitTestCase` (integration), SCSS via gulp.

**Prerequisite:** theme-05-audit is not a dependency. The owner supplies three files before Task 1 (see Task 1, Step 1). The working tree was clean on `cleanup/convention-and-tests` when this plan was written; decide the branch before executing.

**Spec:** none separate. The design is the Architecture paragraph above plus the Global Constraints and Decisions below, agreed in conversation on 2026-10-08. Related: `docs/superpowers/specs/2026-10-02-theme-cleanup-and-extraction-design.md` (theme stays presentation-only, no media or SVG upload code lands in the theme).

## Global Constraints

- PHP 8.3, WP 6.9, text domain `hyperpress`, function prefix `hyperpress_` (no `hyper_` or `foundationpress_`).
- Every new library file starts with `defined( 'ABSPATH' ) || exit;`. No `function_exists` guards (`NoRedeclarationGuardsTest` enforces this).
- WPCS tab indentation. `docker compose run --rm php composer lint` must report 0 violations.
- PHP tooling runs in Docker from the theme dir, never composer on the host. Run integration tests in both modes: default, and `-e HYPERPRESS_TEST_WITHOUT_EXTRACTED=1`.
- Commit per task with an explicit pathspec, never `git add -A`. Include this plan file (ticked boxes) in each task's commit. Never edit `dist/`.
- Do not read or print `.env`. No secrets in the repo.

## Decisions

- **Bundled files live in `library/branding-assets/`** (`logo.svg`, `icon.svg`, `icon.png`), not `src/assets/images/`. `dist/` is generated and git-ignored, so a seeder reading from it would fail in tests and on unbuilt checkouts. `library/` is committed and shipped.
- **Seeding uses `copy()` plus `wp_insert_attachment()`**, which does not validate mime types. This keeps the theme independent of `hyperpress-media` and any SVG upload allowlist. The files are the theme's own, so no sanitization is needed.
- **Default icon is the PNG** (core's cropper and the apple-touch-icon need a raster). `icon.svg` is added as an extra `<link rel="icon" type="image/svg+xml">` while the default icon is in effect.
- **Sizing is left to CSS.** No width or height is baked into attachment metadata for the SVG. The login height (84px) is the only fixed value.
- **Login page reuses the site logo.** No separate Customizer control (the logo is dark and colour on transparent, so it reads on the light grey login background).
- **Names:** options `hyperpress_branding_ids` (array keyed `logo` and `icon`), `hyperpress_branding_version`, `hyperpress_branding_lock`; attachment meta `_hyperpress_default` with value `logo` or `icon`.

## Review Focus

- Two requests seed at once: only one set of attachments may exist (Task 1 lock and idempotence tests).
- Uploads directory not writable: the seeder returns quietly, leaves the version unset so it retries, and the front end still shows a logo (Task 1 `upload_dir` test, Task 2 fallback test).
- A default attachment is deleted from the Media Library: it is detected and re-seeded, not left as a dead ID (Task 1 self-heal test).
- The user sets their own site icon or logo: theirs wins, and the extra SVG favicon link is dropped (Task 2 and Task 3 tests).
- `wp-login.php` with no seeded logo and no custom logo: the bundled SVG is used, and the header link and text point at the site, not wordpress.org (Task 4 tests).

---

## Task 1: Assets and seeder

**Files:**
- Create: `library/branding-assets/logo.svg`, `library/branding-assets/icon.svg`, `library/branding-assets/icon.png`
- Create: `library/default-branding.php`
- Modify: `functions.php` (add `require_once get_template_directory() . '/library/default-branding.php';` after the `theme-support.php` line)
- Test: `tests/integration/DefaultBrandingSeedTest.php`

**Interfaces:**
- Produces:
  - `hyperpress_default_branding_files(): array`, keyed by `'logo'` and `'icon'`, each `array{file: string, mime: string}` (`logo.svg` as `image/svg+xml`, `icon.png` as `image/png`).
  - `hyperpress_default_branding_version(): string`, returns `'1'`. Bump it when a bundled file changes.
  - `hyperpress_default_branding_id( string $key ): int`, the stored ID only if the post exists, is an attachment, and has `_hyperpress_default` equal to `$key`; otherwise `0`.
  - `hyperpress_seed_default_branding_asset( string $key ): int`, reuses an existing tagged attachment (looked up by meta) or creates one; returns its ID, or `0` on failure.
  - `hyperpress_seed_default_branding(): bool`, takes the lock, seeds both, stores the IDs, sets `hyperpress_branding_version` only if both IDs are non-zero, releases the lock, returns success. A lock younger than 300 seconds makes it return `false` without doing anything; an older lock is stale and is taken over.
  - `hyperpress_maybe_seed_default_branding(): void`, returns early when the stored version equals the current one and both IDs are valid; otherwise calls the seeder. Hooked to `admin_init` and `after_switch_theme`.

- [x] **Step 1: Place the three owner-supplied files** at `library/branding-assets/logo.svg`, `icon.svg` and `icon.png` (icon PNG at least 512×512 if possible). Stop and ask if any is missing.
- [x] **Step 2: Write the failing tests** in `tests/integration/DefaultBrandingSeedTest.php` (`final class DefaultBrandingSeedTest extends WP_UnitTestCase`, namespace `HyperPress\ThemeTests\Integration`):
  - `test_seed_creates_tagged_attachments`: after `hyperpress_seed_default_branding()` returns true, both IDs are > 0, `get_post_mime_type()` is `image/svg+xml` and `image/png`, and `get_post_meta( $id, '_hyperpress_default', true )` is `logo` / `icon`.
  - `test_seed_is_idempotent`: seed twice; same IDs, and `get_posts( [ 'post_type' => 'attachment', 'meta_key' => '_hyperpress_default', 'fields' => 'ids', 'numberposts' => -1 ] )` has exactly 2 items.
  - `test_fresh_lock_blocks_seeding`: `add_option( 'hyperpress_branding_lock', (string) time() )`, then seeding returns false and `hyperpress_default_branding_id( 'logo' )` is 0.
  - `test_stale_lock_is_taken_over`: lock value `(string) ( time() - 600 )`; seeding returns true.
  - `test_unwritable_uploads_fails_quietly`: add a filter on `upload_dir` returning `array_merge( $dirs, [ 'error' => 'blocked' ] )`; seeding returns false, no attachment exists, `get_option( 'hyperpress_branding_version' )` is false.
  - `test_deleted_default_is_detected_and_reseeded`: seed, `wp_delete_attachment( $id, true )`, assert `hyperpress_default_branding_id( 'logo' )` is 0, run `hyperpress_maybe_seed_default_branding()`, assert a valid non-zero ID again.
  - `test_maybe_seed_skips_when_current`: after a seed, wrap a counter on `upload_dir`; `hyperpress_maybe_seed_default_branding()` does not trigger it.
- [x] **Step 3: Run to verify failure:** `docker compose run --rm php composer test:integration -- --filter DefaultBrandingSeedTest`. Expected: FAIL (functions not defined).
- [x] **Step 4: Implement** the functions above in `library/default-branding.php`. Approach: copy the bundled file into `wp_upload_dir()` under `wp_unique_filename()`; bail with `0` if `wp_upload_dir()['error']` is truthy or `copy()` fails; call `wp_insert_attachment()` with `post_mime_type` from `hyperpress_default_branding_files()`; for the PNG only, `require_once ABSPATH . 'wp-admin/includes/image.php'` and `wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $file ) )`. Take the lock with `add_option( 'hyperpress_branding_lock', (string) time(), '', false )` and release with `delete_option` in a `finally`. If PHPCS flags `copy()`, add `// phpcs:ignore WordPress.WP.AlternativeFunctions -- copying a bundled theme file`.
- [x] **Step 5: Add the hooks** at the bottom of the file: `add_action( 'admin_init', 'hyperpress_maybe_seed_default_branding' )` and `add_action( 'after_switch_theme', 'hyperpress_maybe_seed_default_branding' )`. Add the `require_once` to `functions.php`.
- [x] **Step 6: Run to verify pass,** both modes:
  `docker compose run --rm php composer test:integration -- --filter DefaultBrandingSeedTest` and the same with `-e HYPERPRESS_TEST_WITHOUT_EXTRACTED=1`. Expected: PASS.
- [x] **Step 7: Lint:** `docker compose run --rm php composer lint`. Expected: exit 0.
- [x] **Step 8: Commit**

```bash
git add library/branding-assets library/default-branding.php functions.php tests/integration/DefaultBrandingSeedTest.php docs/superpowers/plans/2026-10-08-theme-06-default-branding.md
git commit -m "feat: seed default logo and site icon attachments" -- library/branding-assets library/default-branding.php functions.php tests/integration/DefaultBrandingSeedTest.php docs/superpowers/plans/2026-10-08-theme-06-default-branding.md
```

---

## Task 2: Customizer fallback filters and unseeded front-end fallback

**Files:**
- Modify: `library/default-branding.php`
- Modify: `src/assets/scss/modules/_navigation.scss` (rule at line 147, `.site-desktop-title a`)
- Test: `tests/integration/DefaultBrandingFallbackTest.php`, `tests/unit/BrandingStylesTest.php`

**Interfaces:**
- Consumes: `hyperpress_default_branding_id( string $key ): int`, `hyperpress_seed_default_branding(): bool`, `hyperpress_default_branding_files(): array`.
- Produces:
  - `hyperpress_default_logo_fallback( $value )`: returns `$value` when truthy, else `hyperpress_default_branding_id( 'logo' )` when non-zero, else `$value`. Hooked to `theme_mod_custom_logo`.
  - `hyperpress_default_icon_fallback( $value )`: the same with key `'icon'`. Hooked to both `option_site_icon` and `default_option_site_icon` (core skips `option_*` when the option row is absent).
  - `hyperpress_custom_logo_fallback( string $html ): string`: returns `$html` when non-empty, else an anchor with class `custom-logo-link` and `rel="home"` wrapping `<img class="custom-logo" src="…/library/branding-assets/logo.svg" alt="{site name}">`. Hooked to `get_custom_logo`.

- [x] **Step 1: Write the failing tests** in `DefaultBrandingFallbackTest` (each begins with `hyperpress_seed_default_branding()` unless stated):
  - `test_empty_custom_logo_returns_default`: `remove_theme_mod( 'custom_logo' )`; `get_theme_mod( 'custom_logo' )` equals `hyperpress_default_branding_id( 'logo' )`; `has_custom_logo()` is true.
  - `test_custom_logo_set_to_empty_string_returns_default`: `set_theme_mod( 'custom_logo', '' )`; same assertion.
  - `test_user_logo_wins`: create an attachment with `$this->factory()->attachment->create()`, `set_theme_mod( 'custom_logo', $id )`; `get_theme_mod( 'custom_logo' )` equals `$id`.
  - `test_site_icon_default_when_option_deleted_or_empty`: for each of `delete_option( 'site_icon' )` and `update_option( 'site_icon', '' )`, `get_option( 'site_icon' )` equals the default icon ID and `has_site_icon()` is true.
  - `test_user_site_icon_wins`: `update_option( 'site_icon', $other_id )`; `get_option( 'site_icon' )` equals `$other_id`.
  - `test_unseeded_logo_falls_back_to_bundled_svg` (no seeding): `get_custom_logo()` contains `custom-logo-link`, `class="custom-logo"` and `branding-assets/logo.svg`.
  - `test_seeded_logo_uses_attachment_not_fallback`: after seeding, `get_custom_logo()` does not contain `branding-assets/logo.svg` and does contain the attachment URL from `wp_get_attachment_url()`.
- [x] **Step 2: Write the failing unit test** `BrandingStylesTest::test_header_logo_is_sized_in_css` (pattern of `tests/unit/AdminBarStylesTest.php`): `_navigation.scss` contains `.site-desktop-title .custom-logo`.
- [x] **Step 3: Run to verify failure:** `composer test:integration -- --filter DefaultBrandingFallbackTest` and `composer test:unit -- --filter BrandingStylesTest` (via `docker compose run --rm php`). Expected: FAIL.
- [x] **Step 4: Implement** the three functions and their `add_filter` calls in `library/default-branding.php`.
- [x] **Step 5: Add the SCSS rule** in `_navigation.scss` next to the existing `.site-desktop-title a` rule: `.site-desktop-title .custom-logo { width: auto; height: auto; max-height: 3rem; }`.
- [x] **Step 6: Run to verify pass,** both modes for integration. Expected: PASS.
- [x] **Step 7: Lint,** then `pnpm build` (after `source ~/.nvm/nvm.sh && nvm use`) to confirm the SCSS compiles. Expected: both exit 0.
- [x] **Step 8: Commit**

```bash
git add library/default-branding.php src/assets/scss/modules/_navigation.scss tests/integration/DefaultBrandingFallbackTest.php tests/unit/BrandingStylesTest.php docs/superpowers/plans/2026-10-08-theme-06-default-branding.md
git commit -m "feat: fall back to the default logo and site icon" -- library/default-branding.php src/assets/scss/modules/_navigation.scss tests/integration/DefaultBrandingFallbackTest.php tests/unit/BrandingStylesTest.php docs/superpowers/plans/2026-10-08-theme-06-default-branding.md
```

---

## Task 3: SVG favicon link

**Files:**
- Modify: `library/default-branding.php`
- Test: `tests/integration/DefaultBrandingFaviconTest.php`

**Interfaces:**
- Consumes: `hyperpress_default_branding_id( string $key ): int`.
- Produces: `hyperpress_default_icon_svg_link(): void`, which echoes `<link rel="icon" type="image/svg+xml" href="…">` (URL from `get_theme_file_uri( 'library/branding-assets/icon.svg' )`, escaped with `esc_url`) only when `(int) get_option( 'site_icon' )` equals the non-zero default icon ID. Hooked to `wp_head` (priority 20) and `login_head` (priority 20).

- [x] **Step 1: Write the failing tests** (output captured with `ob_start()` around `hyperpress_default_icon_svg_link()`):
  - `test_link_printed_while_default_icon_in_effect`: seed, `delete_option( 'site_icon' )`; output contains `type="image/svg+xml"` and `branding-assets/icon.svg`.
  - `test_link_omitted_when_user_icon_set`: seed, `update_option( 'site_icon', $other_id )`; output is an empty string.
  - `test_link_omitted_when_not_seeded`: output is an empty string.
  - `test_link_hooked_to_head_actions`: `has_action( 'wp_head', 'hyperpress_default_icon_svg_link' )` and the same for `login_head` are not false.
- [x] **Step 2: Run to verify failure,** then **Step 3: Implement** the function and the two `add_action` calls.
- [x] **Step 4: Run to verify pass** (both modes) and **lint.** Expected: PASS, exit 0.
- [x] **Step 5: Commit**

```bash
git add library/default-branding.php tests/integration/DefaultBrandingFaviconTest.php docs/superpowers/plans/2026-10-08-theme-06-default-branding.md
git commit -m "feat: add an SVG favicon link for the default site icon" -- library/default-branding.php tests/integration/DefaultBrandingFaviconTest.php docs/superpowers/plans/2026-10-08-theme-06-default-branding.md
```

---

## Task 4: Login page branding

**Files:**
- Create: `library/login-branding.php`
- Modify: `functions.php` (require the new file directly after `default-branding.php`)
- Test: `tests/integration/LoginBrandingTest.php`

**Interfaces:**
- Consumes: the `custom_logo` theme mod (already default-aware from Task 2).
- Produces:
  - `hyperpress_login_logo_url(): string`: `wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' )` when that returns a string, else `get_theme_file_uri( 'library/branding-assets/logo.svg' )`.
  - `hyperpress_login_logo_css( string $url ): string`: one rule for `.login h1 a` with `background-image: url( {esc_url($url)} )`, `background-size: contain`, `background-position: center`, `width: 100%`, `height: 84px`.
  - `hyperpress_login_enqueue_styles(): void`: `wp_add_inline_style( 'login', hyperpress_login_logo_css( hyperpress_login_logo_url() ) )`, hooked to `login_enqueue_scripts`.
  - `hyperpress_login_header_url(): string` returns `home_url( '/' )`, hooked to `login_headerurl`.
  - `hyperpress_login_header_text(): string` returns `get_bloginfo( 'name' )`, hooked to `login_headertext`.

- [x] **Step 1: Write the failing tests:**
  - `test_logo_url_uses_custom_logo_attachment`: create an image attachment, `set_theme_mod( 'custom_logo', $id )`; `hyperpress_login_logo_url()` equals `wp_get_attachment_image_url( $id, 'full' )`.
  - `test_logo_url_falls_back_to_bundled_svg_when_unseeded`: no theme mod, no seeding; the URL ends with `library/branding-assets/logo.svg`.
  - `test_css_targets_login_heading_link`: the string from `hyperpress_login_logo_css( 'https://example.org/l.svg' )` contains `.login h1 a`, `https://example.org/l.svg`, `background-size: contain` and `height: 84px`.
  - `test_enqueue_adds_inline_style`: `wp_register_style( 'login', false )`; `hyperpress_login_enqueue_styles()`; `wp_styles()->get_data( 'login', 'after' )` is a non-empty array containing `.login h1 a`.
  - `test_header_url_and_text_point_at_the_site`: `apply_filters( 'login_headerurl', 'https://wordpress.org/' )` equals `home_url( '/' )`; `apply_filters( 'login_headertext', 'Powered by WordPress' )` equals `get_bloginfo( 'name' )`.
- [x] **Step 2: Run to verify failure,** then **Step 3: Implement** with the four `add_action` / `add_filter` registrations.
- [x] **Step 4: Run to verify pass** (both modes) and **lint.** Expected: PASS, exit 0.
- [x] **Step 5: Commit**

```bash
git add library/login-branding.php functions.php tests/integration/LoginBrandingTest.php docs/superpowers/plans/2026-10-08-theme-06-default-branding.md
git commit -m "feat: reuse the site logo on the login page" -- library/login-branding.php functions.php tests/integration/LoginBrandingTest.php docs/superpowers/plans/2026-10-08-theme-06-default-branding.md
```

---

## Task 5: Full verification and manual check

**Files:** Modify this plan only (status banner, outcome notes).

- [x] **Step 1: Full automated run:** `docker compose run --rm php composer lint`, `composer test:unit`, and `composer test:integration` in both modes. Expected: lint exit 0; every suite passes (skips only where the existing tests already skip).
- [ ] **Step 2: Manual check in `hyperalumni`** (`cd ../hyperalumni && docker compose --profile local up`), after loading any wp-admin page once so `admin_init` seeds:
  - Customizer, Site Identity: logo and site icon show as selected; header logo and favicon render.
  - Replace the logo: yours shows. Remove it: the default returns on the front end and in the preview.
  - Delete a default attachment in the Media Library, reload wp-admin: it is re-created.
  - `/wp-login.php`: the logo is shown on the light background, the link goes to the site home, and hover text is the site name.
  - Check view-source for the `image/svg+xml` icon link while on the default icon, and that it disappears after you set your own icon.
- [x] **Step 3: Update this plan:** add a `> **STATUS: ✅ COMPLETED** (YYYY-MM-DD). <commit range, test counts>` banner at the top, tick every box, and add an "Outcome / execution notes" section for anything that differed (for example, if the 84px login height or the 3rem header max-height needed adjusting).
- [x] **Step 4: Commit**

```bash
git commit -m "docs(plans): complete theme-06 default branding" -- docs/superpowers/plans/2026-10-08-theme-06-default-branding.md
```

## Outcome / execution notes

Deviations from the original plan text:

- Seeded attachment IDs live in a single array option, `hyperpress_branding_ids` (amended in Task 1), not one option per asset.
- The seeding lock value now carries an owner token, and the lock is best-effort.
- Both options autoload.
- The unwritable-uploads test uses an uncreatable `/proc` path.
- Environment note: `pnpm build` fails here with `ERR_PNPM_BAD_RUNTIME_VERSION` (Node pin 26.10.0 vs nvm 26.11.0), so `node_modules/.bin/gulp build --production` was used. The pin was not changed.
- Two extra tests were added to `LoginBrandingTest` in Task 5: `test_logo_url_for_svg_attachment_without_metadata` (pins the seeded-SVG production path) and `test_css_cannot_break_out_of_the_declaration`. Both passed without library changes.
- Task 5 Step 2: the `hyperalumni` stack is not installed (`/wp-login.php` redirects to `wp-admin/install.php`), so the read-only curl checks could not verify anything. The Customizer replace/remove, media-delete re-seed, login page and icon-link checks are for the user to run.

Final-review fixes (after the whole-branch review):

- The seeder now re-copies the bundled file over an existing tagged attachment's file (and regenerates the PNG metadata), so bumping `hyperpress_default_branding_version()` actually refreshes the files. `hyperpress_maybe_seed_default_branding()` also treats a default whose file is missing on disk as needing seeding (healing a database cloned without uploads); the disk check is not done in front-end read paths.
- `admin_init` now calls `hyperpress_maybe_seed_default_branding_on_admin()`, which seeds only for users with `edit_theme_options` (no seeding from unauthenticated admin-ajax.php / admin-post.php). `after_switch_theme` still calls the seeder directly. A failed attempt stores `time()` in the autoloaded option `hyperpress_branding_failed_at`, and `hyperpress_maybe_seed_default_branding()` does nothing for 300 s afterwards (cleared on success; a held fresh lock is not a failure).
- The `theme_mod_custom_logo` fallback is registered at priority 20. Core's Customizer preview filter (`WP_Customize_Setting::_preview_filter`, priority 10) returns the empty post value after "Remove", so the fallback has to run after it. The site icon fallback cannot do the same: core previews options through `pre_option_site_icon`, which short-circuits the `option_*` filters, so removing the icon in the preview shows none until saved (documented in the docblock).
- The login logo CSS is now `background-image: url( "..." )` with `esc_url_raw()`, so `(`, `)`, `;` and `&` cannot break out of or be mangled inside the declaration.
- The pre-seed fallbacks (favicon link, login logo) use `get_template_directory_uri()`, matching the logo fallback markup and the seeder's parent-theme source.
- The seeder deletes the just-copied file if `wp_insert_attachment()` fails.
- Left for the user: strip the DOCTYPE from the bundled SVGs, and check visually whether the 3rem header logo cap (vs 4rem) is right.
