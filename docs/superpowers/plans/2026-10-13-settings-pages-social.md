# Social settings page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the 12 `hyperpress_social_*` Customizer theme mods (eleven URLs and the contact page) of `hyperpress-social` to a "Social" Meta Box settings page under the hyperPress menu, switch `Links` to the new option, delete the plugin's Customizer section, and make the plugin require `hyperpress-utils` and `meta-box-aio`.

**Architecture:** Same recipe as Season and the banner subtitles: `Settings` owns the option `hyperpress_social` and the ordered list of networks, `SettingsPage` and `MetaBoxes` register the page and its 12 fields, and `Links` reads through `Settings`. Unlike the earlier pages there are no non-empty defaults, so no `default_option` filter is needed.

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box (stubbed in tests), PHPUnit through `docker compose run --rm php composer ...` from `plugins/` (and `themes/hyperpress-foundation/` for the theme tests).

**Spec:** `docs/superpowers/specs/2026-10-12-settings-pages-design.md` (row "Social"). Closest models: `docs/superpowers/plans/2026-10-13-settings-pages-banner-subtitles.md` and `docs/superpowers/plans/2026-10-12-settings-pages-season.md`; their "Outcome / execution notes" list the rulings that still apply.

## Global Constraints

- Meta Box field ids and option array keys are `hyperpress_<plugin>_<field>` (`IdentifierConventionTest`): option and page id `hyperpress_social`; box `hyperpress_social_box`; the 12 field ids are exactly the old theme mod ids `hyperpress_social_<key>` for keys `firstinspires`, `thebluealliance`, `facebook`, `instagram`, `youtube`, `twitter`, `github`, `snapchat`, `tiktok`, `discord`, `contact`, `add_calendar` (Customizer order, which is also the field order).
- Page: menu and page title `Social`, `parent => AdminMenu::SLUG`, `manage_options`, `style => no-boxes`. Read with `get_option` (Meta Box is not installed in tests). No fallback reads of the old theme mods, no migration code.
- The 11 URL fields keep their sanitizer `esc_url_raw` and labels from the old controls ("FIRST Inspires URL", "TheBlueAlliance URL", "Facebook URL", "Instagram URL", "YouTube URL", "Twitter URL", "GitHub URL", "Snapchat URL", "TikTok URL", "Discord URL", "Add Calendar URL"); the contact field is a page picker (Meta Box `post` field, `post_type` `page`, `select_advanced`) labelled "Contact Page" with sanitizer `absint` (single value, stores an id). Relative URLs such as `/about/` stay valid (`Links::is_internal` handles them).
- No non-empty defaults. Accessors return `''` for a missing key, a non-array option or a wrongly typed value (`Settings::url( $key ): string`, `Settings::contact_page(): int`, which is strict: a positive integer or an all-digit string, everything else gives 0; the page itself still saves through `absint`).
- `plugins/hyperpress-social/hyperpress-social.php` header gets `Requires Plugins: hyperpress-utils, meta-box-aio`, and `Convention::REQUIRES_PLUGINS['hyperpress-social']` becomes `array( 'hyperpress-utils', 'meta-box-aio' )` (in `plugins/tests/src/Support/Convention.php`; `PluginHeadersTest` checks the header against it). The social `Plugin::boot()` runs after utils because every plugin boots on `plugins_loaded`.
- `Links::all()` output (keys `key, icon, svg, svg_width, title, url, target, rel`, display order, internal/external classification, contact permalink only for a published page) must not change.
- Classes loaded by unit tests use the guard `defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`.
- Tooling runs in Docker only, from `plugins/` and `themes/hyperpress-foundation/`: `docker compose run --rm php composer lint|test:unit|test:integration` (append `-- --filter <Name>`). Lint stays at 0; check its exit code explicitly. Never read `.env*` files.
- Commit with explicit paths: `git add <paths>` then `git commit -m "..." -- <paths>`. Tick each task's boxes in this file in the same commit as its code.

## Review Focus

- The option holds a non-array value or a key holds an array: every accessor returns `''`/`0`, no warnings.
- A hostile URL (`javascript:alert(1)`, a lookalike host such as `evil.example/?https://site.test`) saved by hand into the option: `Links::all()` still escapes/classifies it as before (output is escaped by the widget; `esc_url_raw` runs on save only, so the existing escaping tests on `Links`/the widget must still pass with values written straight into the option).
- The contact page is unpublished, trashed, missing or `0`/negative: no contact link.
- With `hyperpress-social` active but the option never saved, the footer social widget renders nothing without notices.
- Nothing else registers or reads a social Customizer setting after Task 2 (grep).

## File Structure

- `plugins/hyperpress-social/src/Settings.php` (new): option, field ids, ordered network list with labels, `url()`, `contact_page()`.
- `plugins/hyperpress-social/src/SettingsPage.php` (new), `src/MetaBoxes.php` (new).
- `plugins/hyperpress-social/src/Links.php` (reads switch), `src/Plugin.php` (wiring), `src/Customizer.php` deleted.

---

### Task 1: Social settings page and plugin requirements

**Files:**
- Create: `plugins/hyperpress-social/src/Settings.php`, `SettingsPage.php`, `MetaBoxes.php`
- Modify: `plugins/hyperpress-social/src/Plugin.php` (`boot()` also registers `SettingsPage` and `MetaBoxes`; keep `Customizer::register()` until Task 2), `plugins/hyperpress-social/hyperpress-social.php` (header `Requires Plugins`), `plugins/tests/src/Support/Convention.php` (REQUIRES_PLUGINS entry)
- Test: create `plugins/tests/integration/Social/SocialSettingsPageTest.php`, `SocialSettingsTest.php`

**Interfaces:**
- Produces: `Settings::OPTION = 'hyperpress_social'`; `Settings::NETWORKS` (ordered `key => label` for the 11 URL fields) and `Settings::CONTACT = 'contact'`; `Settings::field_id( string $key ): string` returns `'hyperpress_social_' . $key`; `Settings::url( string $key ): string` (the saved string for the URL network `$key`, `''` otherwise); `Settings::contact_page(): int` (the saved contact page when it is a positive integer or an all-digit string, as an int; everything else (negative, float, padded or mixed text, arrays, missing) gives 0).
- Produces: `SettingsPage::ID = 'hyperpress_social'`, `register()`, `pages( array ): array`; `MetaBoxes::register()`, `boxes( array ): array` with box `hyperpress_social_box`, `settings_pages` `SettingsPage::ID` and the 12 fields in the order from Global Constraints (URL fields `type => 'url'` with `sanitize_callback => 'esc_url_raw'`; the contact field after `discord`, then `add_calendar` last).

- [x] **Step 1: Write failing tests.** `SocialSettingsPageTest`: `pages()` entry values; `boxes()` has exactly 12 fields with the expected ids in order, types (`url`, `post` for contact), sanitizers (`esc_url_raw`, `absint`), labels as in Global Constraints, and the contact field is not multiple and targets `page`. `SocialSettingsTest`: nothing saved gives `''`/0 for every key; saved values come back; non-array option, a saved array value and an int where a string is expected give `''`; a contact page saved as `'12'` gives 12, as `'abc'`/negative/array gives 0. `PluginHeadersTest` (existing, Convention-driven) must fail first because of the Convention change.
- [x] **Step 2:** Run `composer test:unit` and `composer test:integration -- --filter Social` from `plugins/`. Expected: FAIL (header mismatch, classes missing).
- [x] **Step 3:** Implement to the interfaces, update the header and Convention entry.
- [x] **Step 4:** Run the full `composer test:unit`, `test:integration` and `lint` from `plugins/`, and `composer test:integration` and `lint` from the theme. Expected: pass.
- [x] **Step 5:** Commit `feat(social): add the Social settings page`.

### Task 2: Switch `Links` and remove the Customizer section

**Files:**
- Modify: `plugins/hyperpress-social/src/Links.php` (line ~89 `get_theme_mod( 'hyperpress_social_' . $social['key'] )` becomes `Settings::url( $social['key'] )`; `contact_url()` reads `Settings::contact_page()`), `plugins/hyperpress-social/src/Plugin.php` (drop `Customizer::register()`)
- Delete: `plugins/hyperpress-social/src/Customizer.php`
- Test: migrate `plugins/tests/integration/SocialsTest.php`, `SocialsLinksTest.php`, `SocialIconsTest.php`, `SocialsLayoutTest.php` and the theme tests `themes/hyperpress-foundation/tests/integration/FooterSocialsTest.php`, `FooterEscapingTest.php`, `NoPluginsTest.php` (its social footer test) from `set_theme_mod`/`remove_theme_mod` of `hyperpress_social_*` to `update_option( 'hyperpress_social', array( ... ) )`/`delete_option`; create `plugins/tests/integration/Social/SocialReadersTest.php`

**Interfaces:** Consumes `Settings::url()`, `Settings::contact_page()`. `Links::all()` output is unchanged.

- [x] **Step 1: Write failing tests.** Update the listed tests to write the option (keys are the prefixed field ids; keep every assertion). In `SocialsTest` delete the tests that exercised the Customizer controls (URL sanitizer through `get_setting`, the section/prefix test, the contact setting sanitizer) and move their intent to `Social/SocialSettingsPageTest`: every URL field's `sanitize_callback` drops a `javascript:` URL and keeps an https URL when called directly, the contact callback gives 0 for a non-existent id value `'abc'`. New `SocialReadersTest`: a `WP_Customize_Manager` after `do_action( 'customize_register', $manager )` has no section `hyperpress_social` and none of the 12 settings; `Links::all()` with the option never saved returns `array()`; values written straight into the option (a lookalike host, a relative URL) are classified as before (reuse the data from `SocialsTest::lookalikes`).
- [x] **Step 2:** Run `composer test:integration -- --filter 'Social|Footer'` from `plugins/` and the theme. Expected: FAIL.
- [x] **Step 3:** Switch the reads, delete `Customizer.php` and its `boot()` call, migrate the tests. Grep `plugins/` and `themes/` (exclude `vendor/`) for `hyperpress_social_` as a theme mod and for `Social\Customizer` afterwards: no hit.
- [x] **Step 4:** Run the full `composer test:unit`, `test:integration` and `lint` from `plugins/`, and `composer test:integration`, `test:unit` and `lint` from the theme. Expected: pass.
- [x] **Step 5:** Commit `refactor(social): read the social links from the Social settings page`.

### Task 3: Verify in a browser and close out

**Files:** this plan; completed plans that mention changed files.

- [ ] **Step 1 (manual):** hyperPress menu lists Social. Open it, fill several URLs, a relative one such as `/about/` and a contact page; save, reload; values stay. Clear one and save; it disappears from the footer.
- [ ] **Step 2 (manual):** the footer socials widget shows the links in the same order as before, external links open in a new tab, the contact link points at the chosen published page, and the Customizer no longer has a "Socials" section. Note whether the browser's URL validation blocks a relative URL in the field (the old Customizer control was also `type=url`).
- [ ] **Step 3 (dev data, optional):** after `cd docker/<site> && docker compose --profile local run --rm wpcli db export -`, copy the old values once: `wpcli eval '$o = array(); foreach ( array( "firstinspires", "thebluealliance", "facebook", "instagram", "youtube", "twitter", "github", "snapchat", "tiktok", "discord", "contact", "add_calendar" ) as $k ) { $v = get_theme_mod( "hyperpress_social_$k", "" ); if ( "" !== $v && 0 !== $v ) { $o[ "hyperpress_social_$k" ] = $v; } } update_option( "hyperpress_social", $o );'`.
- [ ] **Step 4:** Grep `docs/superpowers/plans/`, `specs/` and `audit/` for `hyperpress_social_`, the "Socials" Customizer section, `SocialsTest` and `Social\Customizer`; append a dated note under "Outcome / execution notes" of each completed plan whose statement became untrue; leave the rest.
- [ ] **Step 5:** Tick the boxes, add `> **STATUS: ✅ COMPLETED** (date, commit range)` at the top and commit `docs(plans): complete social settings page`.

## Outcome / execution notes
