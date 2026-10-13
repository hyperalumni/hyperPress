# Media settings page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the one `hyperpress-media` Customizer setting (`hyperpress_nggallery_photos_page`, the page that displays NextGen photos, used for breadcrumbs) to a "Media" Meta Box settings page under the hyperPress menu, switch the NextGen breadcrumbs to the new option, and delete the plugin's Customizer section. This is the last of the settings-page plans.

**Architecture:** Same recipe as Social's contact page picker: a `Settings` class with a strict page-id accessor, `SettingsPage` and `MetaBoxes` registering the page and one `post` (page) picker field, and `NextGen\Breadcrumbs` reading through `Settings`. The plugin gains the `hyperpress-utils` and `meta-box-aio` requirements.

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box (stubbed in tests), PHPUnit through `docker compose run --rm php composer ...` from `plugins/` (and `themes/hyperpress-foundation/` for the theme tests).

**Spec:** `docs/superpowers/specs/2026-10-12-settings-pages-design.md` (row "Media"). Closest model: `docs/superpowers/plans/2026-10-13-settings-pages-social.md` (its contact page field); earlier plans' "Outcome / execution notes" list the rulings that still apply.

## Global Constraints

- Option and page id `hyperpress_media`; box `hyperpress_media_box` titled "NextGen Gallery"; menu and page title `Media`; `parent => AdminMenu::SLUG`, `manage_options`, `style => no-boxes`. Read with `get_option` (Meta Box is not installed in tests). No fallback read of the old theme mod `hyperpress_nggallery_photos_page`, no migration code.
- One field: id `hyperpress_media_photos_page` (`hyperpress_<plugin>_<field>`, so it differs from the old theme mod id), a Meta Box `post` field (`post_type` `page`, `field_type` `select_advanced`, single value, `placeholder` "Select a page", `query_args` `post_status` `publish` and `posts_per_page` `-1`, same keys as `plugins/hyperpress-social/src/MetaBoxes.php`'s contact field), `sanitize_callback` `absint`, label "Page Displaying Photos", description "For breadcrumb generation" (both from the old control).
- `Settings::photos_page(): int` returns the saved value when it is a positive integer or an all-digit string, and `0` for everything else (negative, float, padded, mixed text, arrays, missing key, non-array option).
- Breadcrumb behaviour unchanged: with `0`, an unpublished/missing page or a request that is not that page, `NextGen\Breadcrumbs::add()` returns the breadcrumbs untouched. `Plugin::boot()` keeps registering `Breadcrumbs` only when NextGEN's classes exist; the settings page and boxes register unconditionally (as the old Customizer did).
- `hyperpress-media.php` header becomes `Requires Plugins: hyperpress-utils, meta-box-aio, safe-svg` and `Convention::REQUIRES_PLUGINS['hyperpress-media']` becomes `array( 'hyperpress-utils', 'meta-box-aio', 'safe-svg' )` (`plugins/tests/src/Support/Convention.php`; `PluginHeadersTest` checks it). The plugin's Description text mentions "Customizer options": reword it to "settings page".
- The theme suite has a mode that skips the extracted plugins (`HYPERPRESS_TEST_WITHOUT_EXTRACTED=1`, skipping hyperpress-shortcode and hyperpress-media): it must still pass.
- Classes loaded by unit tests use the guard `defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`.
- Tooling runs in Docker only, from `plugins/` and `themes/hyperpress-foundation/`: `docker compose run --rm php composer lint|test:unit|test:integration` (append `-- --filter <Name>`). Lint stays at 0; check its exit code explicitly. Never read `.env*` files.
- Commit with explicit paths: `git add <paths>` then `git commit -m "..." -- <paths>`. Tick each task's boxes in this file in the same commit as its code.

## Review Focus

- The option holds a non-array value or the key holds an array/negative/text: `Settings::photos_page()` returns 0 and breadcrumbs are untouched, no warnings.
- The saved page is later unpublished or deleted: `is_page()` is false for it, breadcrumbs untouched.
- NextGEN not installed: no breadcrumb hook, and the settings page still builds.
- With the media plugin inactive the theme renders without NextGen breadcrumbs and without errors; with only the extracted plugins skipped (`HYPERPRESS_TEST_WITHOUT_EXTRACTED=1`) the theme suite still passes.
- Nothing else reads the old theme mod or the Customizer section `hyperpress_media_nextgen` (grep).

## File Structure

- `plugins/hyperpress-media/src/NextGen/Settings.php`, `SettingsPage.php`, `MetaBoxes.php` (new; namespace `HyperPress\Media\NextGen` like the existing classes there), `src/NextGen/Breadcrumbs.php` (reader), `src/Plugin.php` (wiring), `src/NextGen/Customizer.php` deleted.

---

### Task 1: Media settings page and reader switch

**Files:**
- Create: `plugins/hyperpress-media/src/NextGen/Settings.php`, `SettingsPage.php`, `MetaBoxes.php`
- Modify: `plugins/hyperpress-media/src/NextGen/Breadcrumbs.php` (line ~118 `get_theme_mod( 'hyperpress_nggallery_photos_page' )` becomes `Settings::photos_page()`), `plugins/hyperpress-media/src/Plugin.php` (`boot()` registers `SettingsPage` and `MetaBoxes` in place of `NextGen\Customizer::register()`), `plugins/hyperpress-media/hyperpress-media.php` (header and description), `plugins/tests/src/Support/Convention.php`
- Delete: `plugins/hyperpress-media/src/NextGen/Customizer.php`, `plugins/tests/integration/Media/NextGenCustomizerTest.php` (after porting)
- Test: create `plugins/tests/integration/Media/MediaSettingsPageTest.php`, `MediaSettingsTest.php`; modify `plugins/tests/integration/Media/MediaTest.php` (the two Customizer tests around lines 24-43 and any breadcrumb test that sets the theme mod: find them with a grep for `hyperpress_nggallery_photos_page` and `set_theme_mod` in `plugins/tests`), `themes/hyperpress-foundation/tests/integration/ExtractedMediaTest.php` (line ~25 asserts the `NextGen\Customizer` class exists: assert `NextGen\Settings` instead)

**Interfaces:**
- Produces: `Settings::OPTION = 'hyperpress_media'`, `Settings::PHOTOS_PAGE = 'hyperpress_media_photos_page'`, `Settings::photos_page(): int`; `SettingsPage::ID = 'hyperpress_media'`, `register()`, `pages( array ): array`; `MetaBoxes::register()`, `boxes( array ): array`.

- [x] **Step 1: Write failing tests.** `MediaSettingsPageTest`: `pages()` values; `boxes()` has exactly one box `hyperpress_media_box` with the one field and its keys per Global Constraints (type `post`, `post_type` `page`, `select_advanced`, not multiple, query args, `absint`, label, description); `absint` callback gives 12 for `'12abc'`-style input as the old `NextGenCustomizerTest` expected (`'12abc'` gives 12, `-3` gives 3, `'junk'` gives 0). `MediaSettingsTest`: `photos_page()` is 0 with nothing saved, 12 for `12` and `'12'`, and 0 for `'12abc'`, `' 12'`, `'0'`, `-5`, `'-5'`, `1.5`, `true`, `array( 12 )`, `null`, `''`, a non-array option; `007` gives 7. Breadcrumb tests: the existing tests that set the theme mod now write `update_option( 'hyperpress_media', array( 'hyperpress_media_photos_page' => $id ) )` (keep every assertion). New `MediaReadersTest` (or in `MediaTest`): a `WP_Customize_Manager` after `do_action( 'customize_register', $manager )` has no section `hyperpress_media_nextgen` and no setting `hyperpress_nggallery_photos_page`; the file `src/NextGen/Customizer.php` does not exist. `PluginHeadersTest` must fail first because of the Convention change.
- [x] **Step 2:** Run `composer test:unit` and `composer test:integration -- --filter Media` from `plugins/`. Expected: FAIL.
- [x] **Step 3:** Implement to the interfaces, switch the reader and wiring, delete the Customizer and its test (the sanitizer and default intents live in `MediaSettingsPageTest`/`MediaSettingsTest`; the section priority test in `MediaTest` is replaced by the absence test), update the header, description and Convention entry. Afterwards grep `plugins/` and `themes/` (exclude `vendor/`, `node_modules/`, `docs/`, `.superpowers/`) for `hyperpress_nggallery_photos_page` and `NextGen\Customizer`: no hit except the absence assertions.
- [x] **Step 4:** Run the full `composer test:unit`, `test:integration` and `lint` from `plugins/`, and `composer test:integration`, `test:unit` and `lint` from the theme; run the theme integration suite once with `docker compose run --rm -e HYPERPRESS_TEST_WITHOUT_EXTRACTED=1 php composer test:integration` and once with `-e HYPERPRESS_TEST_WITHOUT_PLUGINS=1` (known baseline there: exactly 2 failures, both `LabelsBreadcrumbsGuardTest` season-crumb cases). Expected: pass.
- [x] **Step 5:** Commit `feat(media): move the NextGen photos page to a settings page`.

### Task 2: Verify in a browser and close out

**Files:** this plan; completed plans that mention changed files.

- [ ] **Step 1 (manual):** hyperPress menu lists Media. Pick a page, save, reload; it stays. Clear it, save, reload; it stays empty. With NextGEN active, open an album or gallery under that page and check the breadcrumb trail includes the NextGen levels as before; with no page selected the trail is unchanged. Confirm the Customizer no longer has a "NextGen Gallery" section.
- [ ] **Step 2 (dev data, optional):** after `cd docker/<site> && docker compose --profile local run --rm wpcli db export -`, copy the old value once: `wpcli eval '$v = absint( get_theme_mod( "hyperpress_nggallery_photos_page", 0 ) ); if ( $v ) { update_option( "hyperpress_media", array( "hyperpress_media_photos_page" => $v ) ); }'`.
- [ ] **Step 3:** Grep `docs/superpowers/plans/`, `specs/` and `audit/` for `hyperpress_nggallery_photos_page`, `NextGen\Customizer`, `hyperpress_media_nextgen`, the "NextGen Gallery" Customizer section and `hyperpress-media` + "Requires Plugins"; append a dated note under "Outcome / execution notes" of each completed plan whose statement became untrue; update the `REQUIRES` row for media in `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md` (it lists safe-svg only); leave the rest.
- [ ] **Step 4:** Tick the boxes, add `> **STATUS: ✅ COMPLETED** (date, commit range)` at the top and commit `docs(plans): complete media settings page`.

## Outcome / execution notes
