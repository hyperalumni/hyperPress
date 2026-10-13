# Settings pages Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the Customizer options that are data or configuration into Meta Box settings pages under the hyperPress wp-admin menu, starting with the shared pieces (menu, General page, Google Maps key) and the new `hyperpress-frontpage` plugin; the remaining plugins follow in their own small plans.

**Architecture:** `hyperpress-utils` owns the top-level menu and a "General" page that is the first menu item. Each plugin owns one Meta Box settings page registered through the `mb_settings_pages` and `rwmb_meta_boxes` filters (the `hyperpress-contact` pattern); values live in one array option per plugin. The theme only reads those options through a small helper, with defaults, so it works with every plugin inactive.

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box (`meta-box-aio`, stubbed in tests), PHPUnit through `docker compose run --rm php composer ...`.

**Spec:** `docs/superpowers/specs/2026-10-12-settings-pages-design.md`

## Global Constraints

- Plugin header values: Version `2026.0.0`, Requires at least `6.9`, Requires PHP `8.3`, text domain `hyperpress`, `Requires Plugins: hyperpress-utils, meta-box-aio` for settings-page plugins; `hyperpress-utils` now needs `meta-box-aio`.
- Namespace `HyperPress\<Name>`, one class per concern in `src/`, PSR-4 autoloader in the main file, `Plugin::boot` on `plugins_loaded`. Identifiers are `hyperpress_{plugin}_{field}`; classes loaded by unit tests use the guard `defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`.
- Meta Box field ids, and so the keys in each settings option array, follow `IdentifierConventionTest`: they match `^hyperpress_[a-z0-9_]+$` and are named `hyperpress_{plugin}_{field}` (for example `hyperpress_contact_map` in option `hyperpress_contact`).
- Settings pages attach with `parent => AdminMenu::SLUG`; Meta Box stores one array option per page via `update_option`. Read with `get_option`, never `rwmb_meta` (not installed in tests; stubbed).
- No fallback reads of the old theme mods, no runtime migration code, no admin notice, no redirect from `admin.php?page=hyperpress`.
- The theme stays presentation-only and must still work with every plugin inactive (`NoPluginsTest`).
- Tooling runs in Docker only, from `plugins/` or `themes/hyperpress-foundation/`: `docker compose run --rm php composer lint|test:unit|test:integration` (append `-- --filter <Class>`). Lint stays at 0 violations. Never read `.env*` files.
- Commit with explicit paths: `git add <paths>` then `git commit -m "..." -- <paths>`; never a bare commit or `git add -A`. Tick each task's boxes in this file in the same commit as its code.
- Before committing, grep `docs/superpowers/plans/` for files you changed and note any hit in Task 9.

## Review Focus

- `hyperpress_frontpage` (or `hyperpress_general`) holds a non-array value (hand-edited): the theme helper and `GoogleMapsKey::get()` return their defaults, no warnings.
- The saved post types include an unregistered or non-public type: the front page query drops it (Task 7).
- The saved categories or countdowns include junk (`'7" onload="x'`, `'abc'`, `0`, ids of deleted terms): only positive integer ids survive and nothing unescaped reaches the shortcode.
- The subtitle contains hostile markup: `template-parts/banner.php` still strips it (existing `wp_kses`), now fed from the option (Task 7).
- General is registered after other pages, or not at all (Meta Box inactive): `AdminMenu::reorder()` puts it first when present and does nothing when absent (Task 3).

## File Structure

- `plugins/hyperpress-utils/src/GeneralPage.php` (new): the General page and its one field.
- `plugins/hyperpress-utils/src/AdminMenu.php`, `GoogleMapsKey.php`: slimmed menu, option-array key reader.
- `plugins/hyperpress-frontpage/` (new): `hyperpress-frontpage.php`, `src/{Plugin,Settings,Sanitizers,SettingsPage,MetaBoxes}.php`.
- `themes/hyperpress-foundation/library/frontpage-settings.php` (new): the read helper.
- Deleted from the theme: `library/customize/homepage.php`, `library/metaboxes/homepage-customize.php` (and its loader if nothing else is in it).

---

### Task 1: `hyperpress-utils` requires `meta-box-aio`

**Files:**
- Modify: `plugins/tests/src/Support/Convention.php` (REQUIRES_PLUGINS entry for utils, line ~65), `plugins/hyperpress-utils/hyperpress-utils.php` (header)
- Test: `plugins/tests/unit/PluginHeadersTest.php` (Convention-driven, no new test)

**Interfaces:** Produces: `Convention::REQUIRES_PLUGINS['hyperpress-utils'] === array( 'meta-box-aio' )`.

- [x] **Step 1:** Change the utils entry in `Convention::REQUIRES_PLUGINS` to `array( 'meta-box-aio' )`.
- [x] **Step 2:** Run `docker compose run --rm php composer test:unit` from `plugins/`. Expected: `PluginHeadersTest` FAILS for `hyperpress-utils` (header has no `Requires Plugins`).
- [x] **Step 3:** Add `Requires Plugins:    meta-box-aio` to the header of `hyperpress-utils.php`, formatted like the contact plugin's header.
- [x] **Step 4:** Run `composer test:unit`, `composer test:integration` and `composer lint`. Expected: all pass.
- [x] **Step 5:** Commit `feat(utils): require meta-box-aio`, including this plan file.

### Task 2: General page and the Google Maps key

**Files:**
- Create: `plugins/hyperpress-utils/src/GeneralPage.php`
- Modify: `plugins/hyperpress-utils/hyperpress-utils.php` (boot), `plugins/hyperpress-utils/src/GoogleMapsKey.php`
- Test: create `plugins/tests/integration/Utils/GeneralPageTest.php`; modify `Utils/GoogleMapsKeyTest.php`, `Contact/ContactSettingsPageTest.php:81`, `Contact/MapShortcodeTest.php:22,24`

**Interfaces:**
- Produces: `GeneralPage::ID = 'hyperpress_general'` (page id and slug), `GeneralPage::OPTION = 'hyperpress_general'`, `GeneralPage::FIELD_KEY = 'hyperpress_general_google_maps_api_key'`; `GeneralPage::register(): void` (adds the two filters), `pages( array $pages ): array`, `boxes( array $boxes ): array`.
- Produces: `GoogleMapsKey::get(): string` now reads `get_option( GeneralPage::OPTION )['hyperpress_general_google_maps_api_key']`; the `OPTION` constant, the flat option and the theme-mod fallback are removed.

- [x] **Step 1: Write failing tests.** `GeneralPageTest`: `test_pages_registers_general_under_the_hyperpress_menu` (entry with `id` and `option_name` `hyperpress_general`, `parent` `AdminMenu::SLUG`, `menu_title` `General`, `capability` `manage_options`); `test_boxes_registers_one_text_field_for_the_key` (box with `settings_pages` `hyperpress_general`, one field id `hyperpress_general_google_maps_api_key`, type `text`, `sanitize_callback` `sanitize_text_field`). `GoogleMapsKeyTest`: returns `''` with nothing saved; returns the trimmed value from `hyperpress_general`; returns `''` when the option is a string; ignores the old flat option and the old theme mod. Update the three contact tests to write `update_option( 'hyperpress_general', array( 'hyperpress_general_google_maps_api_key' => ... ) )`.
- [x] **Step 2:** Run `composer test:integration -- --filter 'GeneralPage|GoogleMapsKey|MapShortcode|ContactSettingsPage'`. Expected: FAIL.
- [x] **Step 3:** Implement `GeneralPage` (final class, filters `mb_settings_pages` and `rwmb_meta_boxes`, `style` `no-boxes` like contact, field label "Google Maps API key" with a description to restrict the key and enable the Geocoding API), hook `GeneralPage::register` on `plugins_loaded` in the utils main file, and rewrite `GoogleMapsKey::get()` to read the array defensively.
- [x] **Step 4:** Run the filter above, then the full `composer test:integration`, `test:unit` and `lint`. Expected: pass.
- [x] **Step 5:** Commit `feat(utils): add the General settings page and move the Google Maps key into it`.

### Task 3: Menu without Overview, General first

**Files:**
- Modify: `plugins/hyperpress-utils/src/AdminMenu.php`
- Test: rewrite `plugins/tests/integration/Utils/AdminMenuTest.php`

**Interfaces:**
- Consumes: `GeneralPage::ID`.
- Produces: `AdminMenu::SLUG = 'hyperpress'` (unchanged); `boot(): void` hooks `register` at `admin_menu` priority 5 and `reorder` at priority 999; `register(): void` adds only the top-level menu (empty callback, `manage_options`, same title, icon and position 80); `reorder(): void` moves the submenu item whose slug is `GeneralPage::ID` to the front of `$submenu['hyperpress']`. `render()` and `children()` are removed (confirm nothing else calls them).

- [x] **Step 1: Write failing tests** (`AdminMenuTest`): `test_registers_the_top_level_menu`; `test_has_no_overview_submenu` (no submenu item with slug `hyperpress`); `test_reorder_puts_general_first_when_it_registered_after_other_pages`; `test_reorder_keeps_the_other_items_in_their_order`; `test_reorder_does_nothing_without_a_general_page` (no error, `$submenu` unchanged). Use `add_submenu_page( AdminMenu::SLUG, ... )` and an administrator user as the old test did.
- [x] **Step 2:** Run `composer test:integration -- --filter AdminMenuTest`. Expected: FAIL.
- [x] **Step 3:** Rework `AdminMenu` to the interface above.
- [x] **Step 4:** Run `composer test:integration`, `test:unit` and `lint`. Expected: pass.
- [x] **Step 5:** Commit `feat(utils): make General the first hyperPress menu page and drop Overview`.

### Task 4: Competition reads the shared key

**Files:**
- Modify: `plugins/hyperpress-competition/src/MetaBoxes.php:61`, `plugins/hyperpress-competition/src/Customizer.php`
- Test: rewrite `plugins/tests/integration/CompetitionMapApiKeyTest.php`

**Interfaces:** Consumes `GoogleMapsKey::get()`. `Customizer` keeps only `SUBTITLE_SETTING` and its section (it moves in a later plan); `API_KEY_SETTING`, `google_maps_api_key()` and the key setting and control are removed.

- [x] **Step 1: Write failing tests:** the venue map field's `api_key` equals the key saved in `hyperpress_general`; it is `''` when none is saved; `hyperpress_competition_google_maps_api_key` is no longer a registered Customizer setting (the banner subtitle still is).
- [x] **Step 2:** Run `composer test:integration -- --filter 'CompetitionMapApiKey|CompetitionSettings'`. Expected: FAIL.
- [x] **Step 3:** Switch `MetaBoxes.php` to `GoogleMapsKey::get()` and delete the key pieces of `Customizer`.
- [x] **Step 4:** Run `composer test:integration` and `lint`. Expected: pass.
- [x] **Step 5:** Commit `refactor(competition): read the Google Maps key from the shared General setting`.

### Task 5: `hyperpress-frontpage` skeleton

**Files:**
- Create: `plugins/hyperpress-frontpage/hyperpress-frontpage.php`, `plugins/hyperpress-frontpage/src/Plugin.php`
- Modify: `plugins/tests/src/Support/Convention.php` (PLUGINS and LOAD_ORDER next to `hyperpress-countdown`; REQUIRES_PLUGINS `array( 'hyperpress-utils', 'meta-box-aio' )`), `plugins/phpcs.xml.dist`, `plugins/tests/bootstrap-unit.php` (a `HyperPress\Frontpage\` autoloader beside the contact one), `docker/compose.main.yaml` (one bind-mount line beside contact), `AGENTS.md:26` and `plugins/AGENTS.md:10` (15 becomes 16)
- Test: Convention-driven suites

**Interfaces:** Produces: namespace `HyperPress\Frontpage`; `Plugin::boot(): void` (empty until Task 6), header Plugin Name `hyperPress Front Page`, same header values as the contact plugin.

- [ ] **Step 1:** Add the three `Convention.php` entries. Run `composer test:integration` and `composer test:unit` from `plugins/`. Expected: the Convention-driven tests FAIL because the plugin file does not exist.
- [ ] **Step 2:** Create the main file and `Plugin` by copying the contact plugin's pattern; update the other listings.
- [ ] **Step 3:** Run `composer test:integration`, `test:unit` and `lint`. Expected: pass.
- [ ] **Step 4:** Commit `feat(frontpage): add hyperpress-frontpage plugin skeleton`.

### Task 6: Front Page settings page

**Files:**
- Create: `plugins/hyperpress-frontpage/src/Settings.php`, `Sanitizers.php`, `SettingsPage.php`, `MetaBoxes.php`
- Modify: `plugins/hyperpress-frontpage/src/Plugin.php`
- Test: create `plugins/tests/integration/Frontpage/FrontpageSettingsPageTest.php`, `FrontpageSanitizersTest.php`, `FrontpageSettingsTest.php`

**Interfaces:**
- `Settings::OPTION = 'hyperpress_frontpage'`; field id constants `CATEGORIES = 'hyperpress_frontpage_categories'`, `POST_TYPES = 'hyperpress_frontpage_post_types'`, `BANNER_TITLE = 'hyperpress_frontpage_banner_title'`, `BANNER_SUBTITLE = 'hyperpress_frontpage_banner_subtitle'`, `BANNER_BACKGROUND_COLOR = 'hyperpress_frontpage_banner_background_color'`, `BANNER_BUTTON_TEXT = 'hyperpress_frontpage_banner_button_text'`, `BANNER_BUTTON_PAGE = 'hyperpress_frontpage_banner_button_page'`, `COUNTDOWNS = 'hyperpress_frontpage_countdowns'`; `Settings::defaults(): array` (`hyperpress_frontpage_post_types` `array( 'post' )`, `hyperpress_frontpage_banner_subtitle` the text from the spec); `Settings::default_option( $default, $option, $passed_default )` hooked on `default_option_hyperpress_frontpage` returns `defaults()` when the caller passed no explicit default (my call, so an unsaved page still shows the default subtitle).
- `Sanitizers::categories( mixed $value ): array` (existing category term ids as ints, from `hyperpress_sanitize_home_categories`, the single-id case dropped); `Sanitizers::post_types( mixed $value ): array` (registered, public post types, unique, from `hyperpress_sanitize_home_post_types`).
- `SettingsPage::ID = 'hyperpress_frontpage'`, `register()`, `pages( array ): array` (menu and page title `Front Page`, `parent` `AdminMenu::SLUG`, `option_name` `Settings::OPTION`); `MetaBoxes::register()`, `boxes( array ): array` (one box, `settings_pages` `SettingsPage::ID`, fields below).
- Fields (titles and limits copied from `themes/.../library/metaboxes/homepage-customize.php`): `hyperpress_frontpage_categories` (`taxonomy`, `taxonomy` `category`, `select_advanced`, multiple, `sanitize_callback` `Sanitizers::categories`); `hyperpress_frontpage_post_types` (`select_advanced`, multiple, options built from `get_post_types( array( 'public' => true ), 'objects' )` at filter time, `std` `array( 'post' )`, `sanitize_callback` `Sanitizers::post_types`); `hyperpress_frontpage_banner_title` (`text`, 4-word limit); `hyperpress_frontpage_banner_subtitle` (`textarea`, `std` the default text, 100-word limit, `raw` true); `hyperpress_frontpage_banner_background_color` (`color`); `hyperpress_frontpage_banner_button_text` (`text`, 4-word limit); `hyperpress_frontpage_banner_button_page` (`post`, `page`, `select_advanced`, published pages); `hyperpress_frontpage_countdowns` (`post`, `hyper_countdown`, multiple, `select_advanced`).

- [ ] **Step 1: Write failing tests.** `FrontpageSanitizersTest` (ported from `HomepageSettingsTest` lines 50-77): non-public and unknown post types dropped, non-array becomes `array()`, duplicates removed, nonexistent category ids dropped, existing ids kept as ints. `FrontpageSettingsPageTest`: `pages()` entry values; `boxes()` has exactly the eight field ids above in that order with the expected types, multiple flags and `sanitize_callback`s; the post types options contain `post` and exclude a non-public type. `FrontpageSettingsTest`: with no saved option `get_option( 'hyperpress_frontpage' )['hyperpress_frontpage_post_types']` is `array( 'post' )` and the subtitle default is present; an explicit default passed to `get_option` wins.
- [ ] **Step 2:** Run `composer test:integration -- --filter Frontpage`. Expected: FAIL.
- [ ] **Step 3:** Implement the four classes to the interfaces and wire them in `Plugin::boot()`.
- [ ] **Step 4:** Run `composer test:integration`, `test:unit` and `lint`. Expected: pass.
- [ ] **Step 5:** Commit `feat(frontpage): add the Front Page settings page`.

### Task 7: Theme reads the Front Page option

**Files:**
- Create: `themes/hyperpress-foundation/library/frontpage-settings.php` (require it from `functions.php` beside the other `library/` requires)
- Modify: `themes/hyperpress-foundation/front-page.php` (lines 35, 61, 67), `library/banner/front.php`, `template-parts/banner.php` (the `get_theme_mod( 'hyperpress_homepage_customize_banner_subtitle' )` at line 24)
- Test: update `themes/hyperpress-foundation/tests/integration/FrontPageQueryTest.php`, `BannerSubtitleTest.php`, `FrontPageContentTemplateTest.php`, `NoPluginsTest.php`; create `FrontpageSettingTest.php`

**Interfaces:** Produces `hyperpress_frontpage_setting( string $field, $default = '' )`: returns the value of `$field` in `get_option( 'hyperpress_frontpage' )`, or `$default` when the option is not an array or the key is missing. Callers pass the full field id (for example `hyperpress_frontpage_banner_title`), because the theme cannot use plugin constants. `front-page.php` keeps its current normalization (categories through `absint` and filter, countdowns through `absint` and skip 0) and additionally drops post types that are not registered and public.

- [ ] **Step 1: Write failing tests.** `FrontpageSettingTest`: helper returns the stored value; returns the default for a missing key, for a non-array option, and for no option. `FrontPageQueryTest`: set `update_option( 'hyperpress_frontpage', array( 'hyperpress_frontpage_categories' => ... ) )` in place of `set_theme_mod`, keeping the two existing cases, plus `test_non_public_post_types_in_the_option_are_not_queried`. `FrontPageContentTemplateTest`: option holds `array( 'hyperpress_frontpage_post_types' => array( 'hyper_award' ), 'hyperpress_frontpage_countdowns' => array( '12', '7" x="y', 'abc' ) )`, same assertions as today. `BannerSubtitleTest`: subtitle comes from the option; hostile markup is still removed. A banner test: title, background color, button text and button link come from the option on the front page. `NoPluginsTest`: with no plugins and no option the front page renders with no warnings, an empty banner and no countdowns.
- [ ] **Step 2:** Run `docker compose run --rm php composer test:integration` from `themes/hyperpress-foundation/`. Expected: the changed tests FAIL.
- [ ] **Step 3:** Implement the helper and switch the five reads (`front-page.php` categories, post types, countdowns; `library/banner/front.php` title, subtitle, background color, button text, button page; `template-parts/banner.php:24`).
- [ ] **Step 4:** Run the theme `composer test:integration`, `composer lint`, and `pnpm phpcs` if the theme gates on it. Expected: pass. Also run `HYPERPRESS_TEST_WITHOUT_EXTRACTED=1` once.
- [ ] **Step 5:** Commit `refactor(theme): read the front page settings from the hyperpress_frontpage option`.

### Task 8: Remove the front-page Customizer sections

**Files:**
- Delete: `themes/hyperpress-foundation/library/customize/homepage.php` (and its require in `library/customize.php`), `library/metaboxes/homepage-customize.php` (and `library/metaboxes.php` with its require at `functions.php:79`, if it contains nothing else)
- Test: modify `HomepageSettingsTest.php` (delete the tests ported in Task 6 and the metabox test), `CustomizerTest.php` (the `hyperpress_home_blog_post_types` assertion), `NoPluginsTest.php:103-104`, `EmptyMetaBoxSectionsTest.php` (docblock and method name no longer mention the homepage exception)

**Interfaces:** Consumes the Task 7 reads. The two dropdown control classes stay (program and season still use them).

- [ ] **Step 1: Write failing tests:** the Customizer has no section `hyperpress_front_page_posts` and no panel or section created for `hyperpress_front_page`; no setting `hyperpress_home_blog_categories` or `hyperpress_home_blog_post_types` is registered; `apply_filters( 'rwmb_meta_boxes', array() )` contains no box with id `hyperpress_front_page`.
- [ ] **Step 2:** Run the theme `composer test:integration`. Expected: FAIL.
- [ ] **Step 3:** Delete the files and requires and update the tests listed above.
- [ ] **Step 4:** Run the theme `composer test:integration` and `composer lint`, then `composer test:integration` from `plugins/`. Expected: pass.
- [ ] **Step 5:** Commit `refactor(theme): remove the front page Customizer sections`.

### Task 9: Verify in a browser and close out

**Files:** this plan; completed plans that mention changed files.

- [ ] **Step 1 (manual):** start `docker/hyperalumni`, activate `meta-box-aio`, `hyperpress-utils`, `hyperpress-frontpage`, `hyperpress-competition`, `hyperpress-contact`. Confirm "hyperPress" in the sidebar opens General (first item), there is no Overview, and Contact and Front Page are listed below it.
- [ ] **Step 2 (manual):** save the Google Maps key on General; confirm the competition venue map and the contact map pick it up, and that the old Customizer key field is gone.
- [ ] **Step 3 (manual):** fill in and save every Front Page field; confirm the front page shows the banner (title, subtitle, color, button), the countdowns, and the selected categories and post types; confirm the categories and post types selections survive a reload (the taxonomy field stores ids in the option).
- [ ] **Step 4 (manual):** deactivate `hyperpress-frontpage` and then all plugins; confirm the front page still renders. Open the Customizer and confirm the "Front Page" and "Front Page Posts" sections are gone.
- [ ] **Step 5 (dev data, optional):** after `cd docker/hyperalumni && docker compose --profile local run --rm wpcli db export -` (backup), copy the old theme mods once with `wpcli eval`, for example `update_option( 'hyperpress_general', array( 'hyperpress_general_google_maps_api_key' => get_theme_mod( 'hyperpress_competition_google_maps_api_key', '' ) ) );` and a similar call for `hyperpress_frontpage` mapping the old `hyperpress_home_blog_*` and `hyperpress_homepage_customize_*` mods to the field ids above. Re-entering the values by hand is equally fine.
- [ ] **Step 6:** Grep `docs/superpowers/plans/` for `homepage.php`, `homepage-customize`, `CategoryDropdownControl`, `Front Page Posts`, `AdminMenu` and `Overview`. For each completed plan that mentions a changed file, append a dated note under its "Outcome / execution notes" (known: `2026-10-02-theme-01-tooling-and-baseline.md:568`, `2026-10-02-theme-02-renames-and-breadcrumbs.md`, `2026-10-12-hyperpress-contact.md` for the removed Overview page). Do not edit plans whose references still hold (the control classes stay until the later plans).
- [ ] **Step 7:** Tick the boxes, add `> **STATUS: ✅ COMPLETED** (date, commit range)` at the top and commit `docs(plans): complete settings pages`.

---

## Later plans (not part of this plan)

One small plan each, in this order, following the Front Page recipe (settings page class, `MetaBoxes`, `Sanitizers`, reader switch, remove the `add_setting` controls and the empty `MetaBoxes::customize_metabox` section holder, tests, browser check, dev-data `wpcli` copy, plan notes). Re-verify each plugin's settings with a grep for `customize_register`, `get_theme_mod` and `'panel' =>` before writing its plan.

1. `hyperpress-season`: `hyperpress_season_current`, `_banner_subtitle`, `_post_types` (read in `SeasonBanner.php`, `DisplaySponsorsWidget.php`).
2. `hyperpress-program`: `hyperpress_program_post_types`. After this and season, delete `CategoryDropdownControl`, `PostTypeDropdownControl` and their three test files; check whether `LabelDropdownControl` can go once label has moved.
3. `hyperpress-newsletter`, `hyperpress-sponsor`, `hyperpress-competition`: their banner subtitles (competition also deletes its `Customizer` class; the banner filters read `subtitle_mod` today).
4. `hyperpress-social`: the 12 `hyperpress_social_*` settings (`Links.php`).
5. `hyperpress-label`: the per-post-type labels (`Resolver.php`).
6. `hyperpress-media`: `hyperpress_nggallery_photos_page` (`NextGen/Breadcrumbs.php`).
