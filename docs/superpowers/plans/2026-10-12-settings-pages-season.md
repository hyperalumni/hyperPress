# Season settings page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the three `hyperpress-season` Customizer settings (current season, banner subtitle, post types) to a "Season" Meta Box settings page under the hyperPress menu, switch every reader to the new option, and delete season's Customizer section.

**Architecture:** Same recipe as the Front Page page: a `Settings` class owns the option `hyperpress_season` and its defaults, a `Sanitizers` class holds the save-side checks, `SettingsPage` and `MetaBoxes` register the page and fields through the `mb_settings_pages` and `rwmb_meta_boxes` filters. Readers (`SeasonBanner`, the sponsor widget, the taxonomy definition) call small accessors on `Settings`.

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box (stubbed in tests), PHPUnit through `docker compose run --rm php composer ...` from `plugins/`.

**Spec:** `docs/superpowers/specs/2026-10-12-settings-pages-design.md` (row "Season"). First plan for reference: `docs/superpowers/plans/2026-10-12-settings-pages.md` (its "Outcome / execution notes" list the rulings that still apply).

## Global Constraints

- Meta Box field ids, and so the keys in the option array, are `hyperpress_<plugin>_<field>` (`IdentifierConventionTest`): option `hyperpress_season`; fields `hyperpress_season_current`, `hyperpress_season_banner_subtitle`, `hyperpress_season_post_types`; box id `hyperpress_season_box`; page id `hyperpress_season`.
- Settings pages attach with `parent => AdminMenu::SLUG`; read with `get_option` (Meta Box is not installed in tests; `rwmb_meta` is stubbed). No fallback reads of the old theme mods, no runtime migration code, no admin notice.
- `Settings::default_option` (filter `default_option_hyperpress_season`) only fires when the option row is absent, so accessors supply their own defaults. Its callback parameter is named `$fallback` (phpcs reserves `$default`).
- Classes loaded by unit tests use the guard `defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`; Plugin header unchanged (`Requires Plugins: hyperpress-utils, meta-box-aio`).
- `CategoryDropdownControl` and `PostTypeDropdownControl` and their tests stay: program still uses them (its plan deletes them).
- Tooling runs in Docker only, from `plugins/` of the worktree: `docker compose run --rm php composer lint|test:unit|test:integration` (append `-- --filter <Name>`). Lint stays at 0; check its exit code explicitly. Never read `.env*` files.
- Commit with explicit paths: `git add <paths>` then `git commit -m "..." -- <paths>`. Tick each task's boxes in this file in the same commit as its code.

## Review Focus

- The option holds a non-array value (hand-edited): every accessor returns its default, no warnings.
- Saved post types include an unregistered type or one without `show_ui`: the sanitizer drops them on save, and the taxonomy still registers (accessor returns only non-empty strings).
- The saved current season slug no longer exists as a `hyper_season` term (term deleted): the sponsor widget falls back to its no-season behavior instead of erroring.
- The banner subtitle holds hostile markup or shortcodes: stored through `wp_kses_post`, and `template-parts/banner.php` still strips and runs `do_shortcode` on output.
- The page is opened before any season term is seeded (first load): the current-season select shows only the empty choice and saving still works.

## File Structure

- `plugins/hyperpress-season/src/Settings.php` (new): option name, field-id constants, `defaults()`, `default_option()`, accessors `current()`, `banner_subtitle()`, `post_types()`.
- `plugins/hyperpress-season/src/Sanitizers.php` (new): `current()`, `banner_subtitle()`, `post_types()`.
- `plugins/hyperpress-season/src/SettingsPage.php` (new), `src/MetaBoxes.php` (reworked: settings box replaces the empty Customizer section holder).
- Deleted: `plugins/hyperpress-season/src/Customizer.php`.

---

### Task 1: Season settings page

**Files:**
- Create: `plugins/hyperpress-season/src/Settings.php`, `Sanitizers.php`, `SettingsPage.php`
- Modify: `plugins/hyperpress-season/src/MetaBoxes.php` (add `boxes()`; keep `customize_metabox` until Task 2), `plugins/hyperpress-season/src/Plugin.php` (`boot()` registers the page and the `default_option_hyperpress_season` filter)
- Test: create `plugins/tests/integration/Season/SeasonSettingsPageTest.php`, `SeasonSanitizersTest.php`, `SeasonSettingsTest.php`

**Interfaces:**
- Produces: `Settings::OPTION = 'hyperpress_season'`; `Settings::CURRENT = 'hyperpress_season_current'`, `BANNER_SUBTITLE = 'hyperpress_season_banner_subtitle'`, `POST_TYPES = 'hyperpress_season_post_types'`; `Settings::defaults(): array` (`POST_TYPES => array( 'post' )`, others `''`); `Settings::default_option( $fallback, $option, $passed_default )`; `Settings::current(): string` (slug, `''` when unset or not a string); `Settings::banner_subtitle(): string`; `Settings::post_types(): array` (non-empty strings; `array( 'post' )` when the key is missing).
- Produces: `Sanitizers::current( mixed $value ): string` (slug of an existing `hyper_season` term, else `''`, as the old `Customizer::sanitize_current`); `Sanitizers::banner_subtitle( mixed $value ): string` (`wp_kses_post( (string) $value )`); `Sanitizers::post_types( mixed $value ): array` (registered post types with `show_ui` true, unique, strings only; non-array gives `array()`; port the logic of `PostTypeDropdownControl::sanitize_show_ui_post_types` and its tests in `SeasonProgramCustomizerTest`, do not call the control class).
- Produces: `SettingsPage::ID = 'hyperpress_season'`, `register()`, `pages( array ): array` (menu and page title `Season`, `parent` `AdminMenu::SLUG`, `option_name` `Settings::OPTION`, `manage_options`, `style` `no-boxes`); `MetaBoxes::boxes( array ): array` hooked on `rwmb_meta_boxes`: one box `hyperpress_season_box` with `settings_pages` `SettingsPage::ID` and three fields in this order: `current` (`select`, options `'' => 'None'` plus every `hyper_season` term as slug => name via `get_terms( array( 'taxonomy' => 'hyper_season', 'hide_empty' => false ) )`, `sanitize_callback` `Sanitizers::current`), `banner_subtitle` (`textarea`, `raw` true, `sanitize_callback` `Sanitizers::banner_subtitle`), `post_types` (`select_advanced`, multiple, options built from `get_post_types( array( 'show_ui' => true ), 'objects' )` as key => label, `std` `array( 'post' )`, `sanitize_callback` `Sanitizers::post_types`). Labels and descriptions are copied from `Customizer.php`.

- [ ] **Step 1: Write failing tests.** `SeasonSanitizersTest`: ported cases from `SeasonProgramCustomizerTest` lines 25-50 and 62-74 (unknown and markup post types dropped, non-public with `show_ui` kept, e.g. `hyper_countdown`, non-array becomes `array()`, existing season slug kept, unknown slug becomes `''`) plus the subtitle test at line 53 (scripts removed). `SeasonSettingsPageTest`: `pages()` entry values; `boxes()` has the three field ids in order with types, multiple flag and `sanitize_callback`s; the current-season options contain `''` and a created season term as slug => name; the post type options include a private `show_ui` type and exclude a type without `show_ui`. `SeasonSettingsTest`: no saved option gives `post_types()` equal to `array( 'post' )`, `current()` `''`, `banner_subtitle()` `''`; saved values come back; a non-array option and a non-string current value give the defaults; an explicit default passed to `get_option` wins. Clean up registered test post types in `tear_down()`.
- [ ] **Step 2:** Run `composer test:integration -- --filter 'SeasonSanitizers|SeasonSettings'` from `plugins/`. Expected: FAIL (classes missing).
- [ ] **Step 3:** Implement the classes to the interfaces and wire `SettingsPage::register()` and the default-option filter in `Plugin::boot()`; keep `Customizer::register()` and `customize_metabox` for now.
- [ ] **Step 4:** Run the full `composer test:integration`, `test:unit` and `lint`. Expected: pass.
- [ ] **Step 5:** Commit `feat(season): add the Season settings page`.

### Task 2: Switch readers and remove the Customizer section

**Files:**
- Modify: `plugins/hyperpress-season/src/SeasonBanner.php:28`, `plugins/hyperpress-season/src/Plugin.php` (line 48 `object_types`, `boot()` drops `Customizer::register()`), `plugins/hyperpress-season/src/MetaBoxes.php` (delete `customize_metabox`), `plugins/hyperpress-sponsor/src/Widgets/DisplaySponsorsWidget.php:33`
- Delete: `plugins/hyperpress-season/src/Customizer.php`
- Test: modify `plugins/tests/integration/SponsorWidgetTest.php` (the `set_theme_mod`/`remove_theme_mod` calls on `hyperpress_season_current`, lines ~14-181), `SeasonNewestFirstTest.php` (~54, 60), `SeasonAdminFilterTest.php` (~18, 23, 85), `SeasonProgramCustomizerTest.php` (remove the season-only cases ported in Task 1 and keep the program ones), `IdentifierConventionTest.php` only if it names season

**Interfaces:**
- Consumes: `Settings::current()`, `banner_subtitle()`, `post_types()`.
- `SeasonBanner` sets `$banner['subtitle'] = Settings::banner_subtitle()`; `Plugin::definition()['object_types']` becomes the array `Settings::post_types()` (the shared `TaxonomyRegistrar` already accepts a list, so `hyperpress-utils` does not change); the sponsor widget reads `\HyperPress\Season\Settings::current()` (the sponsor plugin already requires season; keep any existing season-not-active guard).

- [ ] **Step 1: Write failing tests.** Update the listed tests to write `update_option( 'hyperpress_season', array( 'hyperpress_season_current' => ... ) )` (and `hyperpress_season_post_types`) in place of theme mods, keeping their assertions; add: `SeasonBanner` shows the saved subtitle on a season archive; the sponsor widget with a saved slug for a deleted term behaves as with no season; the taxonomy's object types follow the saved option after re-registration; the Customizer has no `hyperpress_season` section and none of the three season settings (build a `WP_Customize_Manager` and fire `customize_register` as `CustomizerTest`-style tests do); `apply_filters( 'rwmb_meta_boxes', array() )` has no box with id `hyperpress_season`.
- [ ] **Step 2:** Run `composer test:integration -- --filter 'Sponsor|Season'`. Expected: FAIL.
- [ ] **Step 3:** Switch the three readers, delete `Customizer.php`, its `Plugin::boot()` call and `MetaBoxes::customize_metabox`; update the tests as above.
- [ ] **Step 4:** Run the full `composer test:integration`, `test:unit` and `lint` from `plugins/`, and `composer test:integration` and `lint` from `themes/hyperpress-foundation/`. Expected: pass.
- [ ] **Step 5:** Commit `refactor(season): read the season settings from the Season settings page`.

### Task 3: Verify in a browser and close out

**Files:** this plan; completed plans that mention changed files.

- [ ] **Step 1 (manual):** with `hyperpress-season`, `hyperpress-sponsor` and `hyperpress-utils` active, open hyperPress > Season. Check the current season select lists the seeded seasons, the post types select lists post types with an admin UI (including Countdown), and the defaults show on first load. Save, reload, and confirm all three values stay.
- [ ] **Step 2 (manual):** confirm the season archive banner shows the saved subtitle (shortcodes use the `hyperpress_*` tags), the Display Sponsors widget filters by the saved current season, and the Season taxonomy appears on the selected post types' edit screens.
- [ ] **Step 3 (manual):** open the Customizer and confirm the "Seasons" section is gone.
- [ ] **Step 4 (dev data, optional):** after `cd docker/<site> && docker compose --profile local run --rm wpcli db export -`, copy the old values once: `wpcli eval 'update_option( "hyperpress_season", array( "hyperpress_season_current" => get_theme_mod( "hyperpress_season_current", "" ), "hyperpress_season_banner_subtitle" => get_theme_mod( "hyperpress_season_banner_subtitle", "" ), "hyperpress_season_post_types" => get_theme_mod( "hyperpress_season_post_types", array( "post" ) ) ) );'`. Old subtitle text may still use the removed `[hyper_*]` shortcode tags; update them to `hyperpress_*`.
- [ ] **Step 5:** Grep `docs/superpowers/plans/` for `hyperpress_season_`, `Customizer` with season, `SeasonProgramCustomizerTest` and `2026-10-02-plugins-03-core-plugins.md` (known: lines 179-292, 243); append a dated note under "Outcome / execution notes" of each completed plan whose statement became untrue; leave the rest.
- [ ] **Step 6:** Tick the boxes, add `> **STATUS: ✅ COMPLETED** (date, commit range)` at the top and commit `docs(plans): complete season settings page`.

## Outcome / execution notes
