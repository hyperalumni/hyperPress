# Label settings page Implementation Plan

> **STATUS: ✅ CODE COMPLETE, browser checks pending** (2026-10-13). Tasks 1-2 and Task 3 step 4 are done on `feat/settings-pages`; Task 3 steps 1-3 (Labels page, front-end labels and Customizer section gone, optional dev-data copy) need a person in a browser.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the per-post-type label settings of `hyperpress-label` (a "show labels" switch and a list of label ids for every public post type) from the Customizer to a "Labels" Meta Box settings page under the hyperPress menu, switch `Resolver` to the new option, and delete the Customizer section and the `LabelDropdownControl` Customizer control.

**Architecture:** Same recipe as the earlier pages (`Settings`, `SettingsPage`, `MetaBoxes`), with two differences: the fields are generated per post type and per registered label at `rwmb_meta_boxes` time (the `Registry` collects labels on the first call after `init`, which Meta Box's box registration satisfies), and a save-time normalizer makes cleared values explicit, because Meta Box drops the key of an empty field and "never configured" must not be confused with "cleared".

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box (stubbed in tests), PHPUnit through `docker compose run --rm php composer ...` from `plugins/` (and `themes/hyperpress-foundation/` for the theme tests).

**Spec:** `docs/superpowers/specs/2026-10-12-settings-pages-design.md` (row "Label"). Label design: `docs/superpowers/specs/2026-10-06-label-plugin-extraction-design.md`. Models: the earlier `docs/superpowers/plans/2026-10-13-settings-pages-*.md`; their "Outcome / execution notes" list the rulings that still apply.

## Global Constraints

- Option and page id `hyperpress_label`; box `hyperpress_label_box`; menu and page title `Labels`; `parent => AdminMenu::SLUG`, `manage_options`, `style => no-boxes`. Read with `get_option` (Meta Box is not installed in tests). No fallback reads of the old theme mods, no migration code.
- Field ids and option keys are the existing theme mod ids: `hyperpress_label_<post_type>_enabled` (checkbox, sanitizer `rest_sanitize_boolean`, `std` `Resolver::default_enabled( $post_type )`) and `hyperpress_label_<post_type>_labels` (`select_advanced`, `multiple`, options `label id => "{group}: {title}"` in registry order, `std` `Resolver::default_labels( $post_type )`, sanitizer = the old `Customizer::sanitize_labels`: registered ids only, strings, no duplicates, non-array gives `array()`). Labels: "Show labels on %s" and "Labels on %s" (`%s` = plural post type name), description "Labels selected here appear on this post type. Select none to show no labels." — all copied from the old `Customizer.php`. Both are valid for `IdentifierConventionTest` (`^hyperpress_[a-z0-9_]+$`).
- Post types listed: public types except `attachment`, plus any type named in a label's default types (the old `Customizer::post_types()` logic, moved to `Settings::post_types()`).
- Read rules in `Resolver::is_enabled()` (behaviour otherwise unchanged): the `_enabled` key present in the option array gives its boolean value, missing gives `default_enabled()`; the `_labels` key present as an array gives that list (non-scalars dropped), missing or not an array gives `default_labels()`; the option missing or not an array gives the defaults; a label id no longer registered is ignored.
- Cleared values must become explicit (Meta Box drops the key of an empty field and keeps the row): the page has a hidden marker field `hyperpress_label_saved` (`std` `1`). A `pre_update_option_hyperpress_label` filter `Settings::normalize( $value )` runs only when the incoming array contains the marker: it keeps the marker stored (`Resolver` ignores it) and, for every post type the form listed (`Settings::listed_post_types()`, remembered by `MetaBoxes::boxes()`; the current `Settings::post_types()` when none was captured), adds `_enabled => false` and `_labels => array()` when those keys are missing. Writes without the marker (tests, `wpcli`) are stored as given. A post type registered after the last save has no keys and so keeps the registry defaults.
- `hyperpress-label.php` header gets `Requires Plugins: hyperpress-utils, meta-box-aio`; `Convention::REQUIRES_PLUGINS['hyperpress-label']` becomes `array( 'hyperpress-utils', 'meta-box-aio' )` (`plugins/tests/src/Support/Convention.php`; `PluginHeadersTest` checks it).
- The theme must keep working with the label plugin inactive (templates call `do_action( 'hyperpress_labels' )`; nothing renders). `Renderer`, `Registry`, the `hyperpress_label_register` action, the `hyperpress_label_default_post_types` and `hyperpress_labels_content` filters keep their behaviour.
- `HyperPress\Label\Controls\LabelDropdownControl` and its tests go with the Customizer in Task 2; `hyperpress-utils` is not touched.
- Classes loaded by unit tests use the guard `defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`.
- Tooling runs in Docker only, from `plugins/` and `themes/hyperpress-foundation/`: `docker compose run --rm php composer lint|test:unit|test:integration` (append `-- --filter <Name>`). Lint stays at 0; check its exit code explicitly. Never read `.env*` files.
- Commit with explicit paths: `git add <paths>` then `git commit -m "..." -- <paths>`. Tick each task's boxes in this file in the same commit as its code.

## Review Focus

- The option holds a non-array value, or a key holds the wrong type: `Resolver` falls back to the registry defaults, no warnings.
- Cleared values: a saved page with a post type's labels all deselected and "Show labels" unchecked shows no labels for it (explicit `false`/`array()` via the normalizer), while a post type the page has never seen keeps the registry defaults.
- A saved label id that is no longer registered is ignored; the sanitizer drops unregistered ids on save.
- Building the fields when no label is registered, or no extra post type exists: the page still renders (empty options) without notices; building them before `init` finished (the `Registry` warns via `_doing_it_wrong`) must not happen on the normal path.
- Newsletter opts into the shared post labels through the `hyperpress_label_default_post_types` filter: its defaults still apply on a fresh install.
- With the label plugin inactive the theme renders without labels and without errors (`NoPluginsTest` mode).

## File Structure

- `plugins/hyperpress-label/src/Settings.php` (new): option, marker, `post_types()`, `saved( string $key )`, `sanitize_labels()`, `normalize()`.
- `plugins/hyperpress-label/src/SettingsPage.php`, `src/MetaBoxes.php` (new).
- `plugins/hyperpress-label/src/Resolver.php` (reads switch), `src/Plugin.php` (wiring); deleted: `src/Customizer.php`, `src/Controls/LabelDropdownControl.php`.

---

### Task 1: Labels settings page

**Files:**
- Create: `plugins/hyperpress-label/src/Settings.php`, `SettingsPage.php`, `MetaBoxes.php`
- Modify: `plugins/hyperpress-label/src/Plugin.php` (`boot()` also registers `SettingsPage`, `MetaBoxes` and the `pre_update_option_hyperpress_label` filter; keep `Customizer::register()` until Task 2), `plugins/hyperpress-label/hyperpress-label.php` (header `Requires Plugins`), `plugins/tests/src/Support/Convention.php` (REQUIRES_PLUGINS)
- Test: create `plugins/tests/integration/Label/LabelSettingsPageTest.php`, `LabelSettingsTest.php`

**Interfaces:**
- Produces: `Settings::OPTION = 'hyperpress_label'`, `Settings::MARKER = 'hyperpress_label_saved'`; `Settings::post_types(): array<string,string>` (name => plural label); `Settings::sanitize_labels( $value ): array`; `Settings::normalize( $value )` (as in Global Constraints; returns non-arrays untouched); `Settings::saved(): array` returns the option when it is an array, else `array()`.
- Produces: `SettingsPage::ID = 'hyperpress_label'`, `register()`, `pages( array ): array`; `MetaBoxes::register()`, `boxes( array ): array` with box `hyperpress_label_box` whose fields are: for each post type from `Settings::post_types()` the `_enabled` then the `_labels` field, then the hidden marker field.

- [x] **Step 1: Write failing tests.** `LabelSettingsPageTest`: `pages()` values; `boxes()` has, per registered post type (use a registered test label and `post`/`page` plus a custom public type), the two fields with the expected ids, types (`checkbox`, `select_advanced` multiple), labels, `std` equal to `Resolver::default_enabled/default_labels`, options `id => "{group}: {title}"` in registry order, sanitizers; the hidden marker field with `std` `1`; a type named only in a label's default types appears; `attachment` does not; with no labels registered the label fields still build with empty options. `LabelSettingsTest`: `sanitize_labels` ported cases from `LabelCustomizerTest::test_sanitize_labels_keeps_only_registered_ids` (and the non-array/duplicate cases); `normalize()` with the marker fills missing `_enabled`/`_labels` for every listed type with `false`/`array()`, keeps present values, removes the marker, does nothing without the marker and returns a non-array untouched; `PluginHeadersTest` must fail first because of the Convention change.
- [x] **Step 2:** Run `composer test:unit` and `composer test:integration -- --filter Label` from `plugins/`. Expected: FAIL.
- [x] **Step 3:** Implement to the interfaces; update the header and the Convention entry. Use `Resolver::enabled_key()`/`labels_key()` for the field ids.
- [x] **Step 4:** Run the full `composer test:unit`, `test:integration` and `lint` from `plugins/`, and `composer test:integration` and `lint` from the theme. Expected: pass.
- [x] **Step 5:** Commit `feat(label): add the Labels settings page`.

### Task 2: Switch `Resolver`, remove the Customizer and its control

**Files:**
- Modify: `plugins/hyperpress-label/src/Resolver.php` (`is_enabled()` reads `Settings::saved()` per the read rules; update the class docblock), `src/Plugin.php` (drop `Customizer::register()`)
- Delete: `plugins/hyperpress-label/src/Customizer.php`, `src/Controls/LabelDropdownControl.php` (and the directory), `plugins/tests/integration/LabelCustomizerTest.php` after porting
- Test: migrate every test that sets or removes a `hyperpress_label_*` theme mod (grep `plugins/tests` and `themes/*/tests` for `hyperpress_label_`; about 13 files incl. `LabelResolverTest`, `LabelRendererTest`, `PostLabelsTest`, `SeasonLabelTest`, `SponsorLabelsTest`, `ProgramLabelTest`, `DistrictLabelTest`, `WeekLabelTest`, `NewsletterLabelsTest`, `EmptyLabelTest`, and theme tests such as `LabelsBreadcrumbsGuardTest`) to `update_option( 'hyperpress_label', array( ... ) )`/`delete_option`; create `plugins/tests/integration/Label/LabelReadersTest.php`

**Interfaces:** Consumes `Settings::saved()`. `Resolver::enabled_key()`, `labels_key()`, `default_enabled()`, `default_labels()` and `is_enabled()` keep their signatures.

- [x] **Step 1: Write failing tests.** Convert the listed tests to the option (keys are the same ids); in `LabelResolverTest` add: the option missing gives the defaults; `_enabled` false hides, `_enabled` missing with the row present gives the default; `_labels` `array()` hides everything for that type, `_labels` missing gives the defaults; a non-array option and a non-array `_labels` value give the defaults; stale ids are ignored. `LabelReadersTest`: a `WP_Customize_Manager` after `do_action( 'customize_register', $manager )` has no section `hyperpress_label` and no `hyperpress_label_*` setting; `class_exists( 'HyperPress\Label\Controls\LabelDropdownControl' )` is false and the file `src/Controls/LabelDropdownControl.php` does not exist (assert the file path). Port from `LabelCustomizerTest` into `LabelSettingsPageTest`/`LabelSettingsTest` anything not yet covered: defaults equal the resolver (already in Task 1), stale saved ids do not break page building, a post type named only in label defaults gets fields; the control's description-escaping test and render test are deleted with the control.
- [x] **Step 2:** Run `composer test:integration -- --filter 'Label|Season|Sponsor|Program|Competition|Newsletter'` and the theme's `composer test:integration`. Expected: FAIL.
- [x] **Step 3:** Switch `Resolver`, delete the files and the `Customizer::register()` call, migrate the tests. Afterwards grep `plugins/` and `themes/` (exclude `vendor/`, `node_modules/`, `docs/`, `.superpowers/`) for `get_theme_mod`/`set_theme_mod`/`remove_theme_mod` with `hyperpress_label_`, `Label\Customizer`, `LabelDropdownControl`: no hit except the absence assertions.
- [x] **Step 4:** Run the full `composer test:unit`, `test:integration` and `lint` from `plugins/`, `composer test:integration`, `test:unit` and `lint` from the theme, and the theme integration suite once with `docker compose run --rm -e HYPERPRESS_TEST_WITHOUT_PLUGINS=1 php composer test:integration` (known baseline there: exactly 2 failures, both `LabelsBreadcrumbsGuardTest` season-crumb cases). Expected: pass, same two failures.
- [x] **Step 5:** Commit `refactor(label): read the label settings from the Labels settings page`.

### Task 3: Verify in a browser and close out

**Files:** this plan; completed plans that mention changed files.

- [ ] **Step 1 (manual):** hyperPress menu lists Labels. The page shows, per post type, a "Show labels" checkbox and a label list with the defaults selected on first load (posts: date, author, category, comments; sponsors, seasons, programs per the registry). Change a few, save, reload; values stay. For one type uncheck "Show labels" and clear another type's list, save, and confirm the front end shows no labels for them while other types are unchanged. Save twice in a row; on the second save clear one type's labels and uncheck another's checkbox; after saving and reloading the cleared values stick.
- [ ] **Step 2 (manual):** confirm the hidden marker does not appear in the UI, that saving twice does not reset anything, and the Customizer no longer has a "Labels" section. Activate a plugin that adds a label default for a type that did not exist at save time (or temporarily register one) and confirm it still shows its default.
- [ ] **Step 3 (dev data, optional):** after `cd docker/<site> && docker compose --profile local run --rm wpcli db export -`, copy the old values once: `wpcli eval '$o = array(); foreach ( get_theme_mods() as $k => $v ) { if ( 0 === strpos( $k, "hyperpress_label_" ) ) { $o[ $k ] = $v; } } update_option( "hyperpress_label", $o );'`.
- [x] **Step 4:** Grep `docs/superpowers/plans/`, `specs/` and `audit/` for `hyperpress_label_`, `LabelDropdownControl`, the "Labels" Customizer section, `LabelCustomizerTest` and `Label\Customizer`; append a dated note under "Outcome / execution notes" of each completed plan whose statement became untrue (known: the label extraction plan and design); leave the rest.
- [ ] **Step 5:** Tick the boxes, add `> **STATUS: ✅ COMPLETED** (date, commit range)` at the top and commit `docs(plans): complete label settings page`.

## Outcome / execution notes

- (2026-10-13) `hyperpress-label` now requires `hyperpress-utils` and `meta-box-aio`: the `Requires Plugins` header, the `Convention::REQUIRES_PLUGINS` table and `PluginHeadersTest` (and the plugins convention spec's table) agree.
- (2026-10-13) The hidden marker `hyperpress_label_saved` plus the `pre_update_option_hyperpress_label` normalizer (`Settings::normalize()`) exist because Meta Box drops the key of an empty field while keeping the row. Without it a cleared "Show labels" box or label list would read as never configured and snap back to the registry defaults. A post type registered after the last save has no keys and keeps the defaults; writes without the marker (tests, wpcli) are stored as given.
- (2026-10-13) Final review fixes: the normalizer keeps the marker stored. Meta Box applies a field's `std` only until the page has first been saved, so a removed marker would render empty, be posted empty and dropped, and the normalizer would never run again (a cleared "Show labels" or label list would snap back to the defaults from the second save on). `normalize()` still acts only when the marker key is present, and `Resolver` ignores the key. It also now touches only the post types the form listed (`Settings::listed_post_types()`, captured by `MetaBoxes::boxes()` in the same request), so a post type registered after Meta Box built the fields is not switched off.
- (2026-10-13) Timing contract: `Registry::all()` collects the `hyperpress_label_register` listeners on its first call after init and freezes them; `MetaBoxes::boxes()` makes that call when Meta Box registers its boxes (init, priority 20 as far as we know). Register `hyperpress_label_register` listeners before init priority 20, normally on `plugins_loaded`. Documented in the `Registry::all()` and `MetaBoxes::boxes()` docblocks.
- (2026-10-13) The fields are generated per post type at `rwmb_meta_boxes` time from the `Registry` (which collects on the first call after `init`).
- (2026-10-13) `Resolver` reads: a key present gives its value, a key missing gives the default (same for a non-array option or a non-array `_labels`).
- (2026-10-13) Deleted: the `Customizer` class, `LabelDropdownControl` and its render and description-escaping tests, and `LabelCustomizerTest`. Their intents moved to `Label/LabelSettingsPageTest`, `LabelSettingsTest`, `LabelResolverTest` and `LabelReadersTest`.
- (2026-10-13) No migration: until the optional wpcli copy (Task 3 step 3) runs, existing sites show the registry defaults. Editing now needs `manage_options` instead of `edit_theme_options`, and there is no live preview.
- (2026-10-13) Browser items still to check: the hidden marker is saved and stripped; an empty multi-select really drops its key (the normalizer's premise); the `desc` key works on `select_advanced`; no early `Registry::all()` warning; the page is usable with about 20 fields.
- (2026-10-13) Deferred minors from the reviews: the inline test cleanups were moved into `tear_down()` (done in the test commit); the `desc`-or-`description` loose assertion in `LabelSettingsPageTest` is to be tightened after the browser check; the new `LabelResolverTest` cases for the false and non-array option also pass against a theme-mod `Resolver`, so only the override tests prove the switch.
