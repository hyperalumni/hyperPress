# Program settings page Implementation Plan

> **STATUS: ✅ COMPLETED** (2026-10-13, commits 46a85fa..79b8709). All tasks are done on `feat/settings-pages`; browser checks confirmed by the owner on 2026-10-13: the Program page saves, clearing every post type and saving persists the empty list (after the fix `ccecd9c`). Not confirmed in the browser: the Program taxonomy on the selected post types' edit screens and the Customizer "Programs" section being gone. Optional dev-data copy not run.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the one `hyperpress-program` Customizer setting (the post types the Program taxonomy attaches to) to a "Program" Meta Box settings page under the hyperPress menu, switch its reader, delete program's Customizer section, and then delete the two now-unused dropdown control classes in `hyperpress-utils`.

**Architecture:** Same recipe as the Season page: `Settings` owns the option `hyperpress_program` and its defaults, `Sanitizers` holds the save-side check, `SettingsPage` and `MetaBoxes` register the page and field. The taxonomy definition passes `Settings::post_types()` (a list) to the shared `TaxonomyRegistrar`, which already accepts a list. Program is the last user of `PostTypeDropdownControl` and `CategoryDropdownControl`, so a final task removes them and their tests.

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box (stubbed in tests), PHPUnit through `docker compose run --rm php composer ...` from `plugins/`.

**Spec:** `docs/superpowers/specs/2026-10-12-settings-pages-design.md` (rows "Program" and "Control deletion"). Reference plans: `docs/superpowers/plans/2026-10-12-settings-pages-season.md` (closest model) and `docs/superpowers/plans/2026-10-12-settings-pages.md`; their "Outcome / execution notes" list the rulings that still apply.

## Global Constraints

- Meta Box field ids, and so the keys in the option array, are `hyperpress_<plugin>_<field>` (`IdentifierConventionTest`): option `hyperpress_program`; field `hyperpress_program_post_types`; box id `hyperpress_program_box`; page id `hyperpress_program`.
- Settings pages attach with `parent => AdminMenu::SLUG`; read with `get_option` (Meta Box is not installed in tests; `rwmb_meta` is stubbed). No fallback reads of the old theme mod, no runtime migration code, no admin notice.
- `Settings::default_option` (filter `default_option_hyperpress_program`) only fires when the option row is absent, so accessors supply their own defaults; its callback parameter is named `$fallback` (phpcs reserves `$default`).
- Program keeps its old rule: only PUBLIC post types are valid (season used `show_ui`). The post types default is `array( 'post' )`, as the old Customizer setting displayed.
- Classes loaded by unit tests use the guard `defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`.
- `LabelDropdownControl` belongs to `hyperpress-label` (`HyperPress\Label\Controls`) and stays; its own plan handles it.
- Tooling runs in Docker only, from `plugins/` of the worktree: `docker compose run --rm php composer lint|test:unit|test:integration` (append `-- --filter <Name>`); the theme's tests run from `themes/hyperpress-foundation/` the same way. Lint stays at 0; check its exit code explicitly. Never read `.env*` files.
- Commit with explicit paths: `git add <paths>` then `git commit -m "..." -- <paths>`. Tick each task's boxes in this file in the same commit as its code.

## Review Focus

- The option holds a non-array value (hand-edited): the accessor returns the default, no warnings.
- Saved post types include an unregistered type or a non-public one: the sanitizer drops them on save; the accessor returns only non-empty strings so the taxonomy still registers.
- Behaviour change to flag: on a fresh install Program now attaches to `post` (the old code attached it to nothing until the Customizer was saved, although the Customizer displayed `post`). Same ruling as Season.
- Deleting the controls leaves no reference in plugins or themes (code, tests, `NoPluginsTest`, `NoStaleReferencesTest` wording) and the autoloader still works with an empty `src/Controls/` removed.
- The page is opened when no program term exists or the taxonomy is not registered yet: the page has no term-based options, so it must still render (it only lists post types).

## File Structure

- `plugins/hyperpress-program/src/Settings.php` (new), `Sanitizers.php` (new), `SettingsPage.php` (new), `MetaBoxes.php` (reworked: settings box replaces the empty Customizer section holder).
- Deleted: `plugins/hyperpress-program/src/Customizer.php`, `plugins/hyperpress-utils/src/Controls/CategoryDropdownControl.php`, `plugins/hyperpress-utils/src/Controls/PostTypeDropdownControl.php`, `plugins/tests/integration/SeasonProgramCustomizerTest.php` and the three `plugins/tests/integration/Utils/Controls*Test.php` / `ControlDescriptionEscapingTest.php` files.

---

### Task 1: Program settings page

**Files:**
- Create: `plugins/hyperpress-program/src/Settings.php`, `Sanitizers.php`, `SettingsPage.php`
- Modify: `plugins/hyperpress-program/src/MetaBoxes.php` (add `boxes()`, keep `customize_metabox` until Task 2; `register()` adds both filters), `plugins/hyperpress-program/src/Plugin.php` (`boot()` registers the page and the `default_option_hyperpress_program` filter)
- Test: create `plugins/tests/integration/Program/ProgramSettingsPageTest.php`, `ProgramSanitizersTest.php`, `ProgramSettingsTest.php`

**Interfaces:**
- Produces: `Settings::OPTION = 'hyperpress_program'`, `Settings::POST_TYPES = 'hyperpress_program_post_types'`; `Settings::defaults(): array` (`POST_TYPES => array( 'post' )`); `Settings::default_option( $fallback, $option, $passed_default )`; `Settings::post_types(): array` (non-empty strings; `array( 'post' )` when the key is missing or the option is not an array; a saved empty list stays empty).
- Produces: `Sanitizers::post_types( mixed $value ): array` (registered post types whose object is `public`, strings only, unique, `array_values`; non-array gives `array()`; port the logic of `PostTypeDropdownControl::sanitize_post_types` and its program cases, do not call the control class).
- Produces: `SettingsPage::ID = 'hyperpress_program'`, `register()`, `pages( array ): array` (menu and page title `Program`, `parent` `AdminMenu::SLUG`, `option_name` `Settings::OPTION`, `manage_options`, `style` `no-boxes`); `MetaBoxes::boxes( array ): array` hooked on `rwmb_meta_boxes`: one box `hyperpress_program_box` with `settings_pages` `SettingsPage::ID` and one field `Settings::POST_TYPES`: `select_advanced`, `multiple` true, options from `get_post_types( array( 'public' => true ), 'objects' )` as key => label, `std` `array( 'post' )`, `sanitize_callback` `Sanitizers::post_types`; label "Programs Post Types" and description "Select the Post Types that will use the Program Taxonomy" copied from `Customizer.php`.

- [x] **Step 1: Write failing tests.** `ProgramSanitizersTest`: ported cases from `SeasonProgramCustomizerTest` (unknown and markup types dropped: `array( 'post', 'nope', '<script>' )` gives `array( 'post' )`; non-public types dropped: `array( 'nav_menu_item', 'page', 'attachment_x' )` gives `array( 'page' )`; non-array input gives `array()`; `array( 'post', array( 'x' ), 5 )` gives `array( 'post' )`) plus duplicates collapse. `ProgramSettingsPageTest`: `pages()` entry values; `boxes()` has the one field with type, multiple flag and callback; the options include `post` and a registered public type and exclude a non-public type. `ProgramSettingsTest`: no saved option gives `array( 'post' )`; a saved list comes back; a saved empty list stays empty; a non-array option gives `array( 'post' )`; an explicit default passed to `get_option` wins. Clean up registered test post types in `tear_down()`.
- [x] **Step 2:** Run `composer test:integration -- --filter 'ProgramSanitizers|ProgramSettings'` from `plugins/`. Expected: FAIL (classes missing).
- [x] **Step 3:** Implement the classes to the interfaces and wire the page and the default-option filter in `Plugin::boot()`; keep `Customizer::register()` and `customize_metabox` working for now.
- [x] **Step 4:** Run the full `composer test:integration`, `test:unit` and `lint`. Expected: pass.
- [x] **Step 5:** Commit `feat(program): add the Program settings page`.

### Task 2: Switch the reader and remove the Customizer section

**Files:**
- Modify: `plugins/hyperpress-program/src/Plugin.php` (line ~62 `'object_types' => 'hyperpress_program_post_types'` becomes `Settings::post_types()`; `boot()` drops `Customizer::register()`), `plugins/hyperpress-program/src/MetaBoxes.php` (delete `customize_metabox`, the empty box `hyperpress_program` with `'panel' => ''`, and its registration), `plugins/tests/src/Support/Convention.php` (`THEME_DRIVEN_TAXONOMIES`)
- Delete: `plugins/hyperpress-program/src/Customizer.php`, `plugins/tests/integration/SeasonProgramCustomizerTest.php` (all its cases are program-only after the season work and were ported in Task 1)
- Test: modify `plugins/tests/integration/TaxonomyAttachmentTest.php`, and any test found by grepping `plugins/tests` and `themes/*/tests` for `hyperpress_program_post_types` or a program Customizer expectation; create `plugins/tests/integration/Program/ProgramReadersTest.php`

**Interfaces:** Consumes `Settings::post_types()`. The shared `TaxonomyRegistrar` is not changed.

- [x] **Step 1: Write failing tests.** `ProgramReadersTest`: the `hyper_program` taxonomy's object types follow the saved `hyperpress_program_post_types` after re-registration via `Plugin::register()` (and are `array( 'post' )` with nothing saved); a `WP_Customize_Manager` after `do_action( 'customize_register', $manager )` has no setting `hyperpress_program_post_types` and no section `hyperpress_program`; `apply_filters( 'rwmb_meta_boxes', array() )` has no box with id `hyperpress_program` but has `hyperpress_program_box`. `TaxonomyAttachmentTest` / `Convention`: Program is no longer theme-driven (it attaches to `post` by default); if `THEME_DRIVEN_TAXONOMIES` becomes empty, drop the constant and the loop that used it, keeping the new default-attachment assertion. Update every other test that set the theme mod to write `update_option( 'hyperpress_program', array( 'hyperpress_program_post_types' => ... ) )`.
- [x] **Step 2:** Run `composer test:integration -- --filter 'Program|TaxonomyAttachment'`. Expected: FAIL.
- [x] **Step 3:** Switch the reader, delete `Customizer.php`, its `boot()` call and `customize_metabox`, delete `SeasonProgramCustomizerTest.php`, and adjust the tests as above. Check with the grep tool that no caller of `Plugin::definition()` runs before `init`.
- [x] **Step 4:** Run the full `composer test:integration`, `test:unit` and `lint` from `plugins/`, and `composer test:integration` and `lint` from `themes/hyperpress-foundation/`. Expected: pass.
- [x] **Step 5:** Commit `refactor(program): read the program settings from the Program settings page`.

### Task 3: Delete the dropdown control classes

**Files:**
- Delete: `plugins/hyperpress-utils/src/Controls/CategoryDropdownControl.php`, `plugins/hyperpress-utils/src/Controls/PostTypeDropdownControl.php` (and the directory), `plugins/tests/integration/Utils/ControlsTest.php`, `Utils/ControlDescriptionEscapingTest.php`, `Utils/ControlsRenderTest.php`
- Modify: `themes/hyperpress-foundation/tests/integration/NoPluginsTest.php` (line ~51, the assertion on `PostTypeDropdownControl`), `themes/hyperpress-foundation/tests/unit/NoStaleReferencesTest.php` (line ~37: keep the stale-name pattern, but reword the message that says "Controls are HyperPress\Utils\Controls\*")
- Test: the whole suites

**Interfaces:** None produced. `HyperPress\Label\Controls\LabelDropdownControl` and `LabelCustomizerTest` are not touched.

- [x] **Step 1: Write failing tests.** Add one test to the existing utils integration tests (or a small new file `plugins/tests/integration/Utils/NoControlsTest.php`): `class_exists( 'HyperPress\Utils\Controls\PostTypeDropdownControl' )` and `...CategoryDropdownControl` are both false.
- [x] **Step 2:** Run it. Expected: FAIL (the classes still exist).
- [x] **Step 3:** Confirm with the grep tool (exclude `vendor/`, `node_modules/`, `docs/`) that nothing else references `HyperPress\Utils\Controls`, `PostTypeDropdownControl` or `CategoryDropdownControl` in `plugins/` or `themes/`; delete the files and the three test files; update `NoPluginsTest` and `NoStaleReferencesTest` as above.
- [x] **Step 4:** Run the full `composer test:integration`, `test:unit` and `lint` from `plugins/`, and `composer test:integration`, `test:unit` and `lint` from `themes/hyperpress-foundation/`. Expected: pass (the theme `test:unit` includes `NoStaleReferencesTest`).
- [x] **Step 5:** Commit `refactor(utils): remove the unused dropdown Customizer controls`.

### Task 4: Verify in a browser and close out

**Files:** this plan; completed plans that mention changed files.

- [x] **Step 1 (manual):** open hyperPress > Program. Check the post types select lists public post types, `post` is selected on first load, and choosing, clearing every choice, saving and reloading keeps the state.
- [ ] **Step 2 (manual):** (not confirmed in the browser) confirm the Program taxonomy appears on the selected post types' edit screens, and that the Customizer no longer has a "Programs" section.
- [ ] **Step 3 (dev data, optional):** (not run) after `cd docker/<site> && docker compose --profile local run --rm wpcli db export -`, copy the old value once: `wpcli eval 'update_option( "hyperpress_program", array( "hyperpress_program_post_types" => get_theme_mod( "hyperpress_program_post_types", array( "post" ) ) ) );'`.
- [x] **Step 4:** Grep `docs/superpowers/plans/`, `specs/` and `audit/` for `hyperpress_program_post_types`, `Programs` with Customizer, `PostTypeDropdownControl`, `CategoryDropdownControl`, `HyperPress\Utils\Controls`, `SeasonProgramCustomizerTest`; append a dated note under "Outcome / execution notes" of each completed plan whose statement became untrue; leave the rest. The controls no longer exist, so older plans that describe them get a note.
- [x] **Step 5:** Tick the boxes, add `> **STATUS: ✅ COMPLETED** (date, commit range)` at the top and commit `docs(plans): complete program settings page`.

## Outcome / execution notes

- **Default behaviour change (2026-10-12).** Program now attaches to `post` on a fresh install, because `Settings::post_types()` defaults to `array( 'post' )`. The old code attached it to nothing until the Customizer was saved, even though the Customizer displayed `post`. `Convention::THEME_DRIVEN_TAXONOMIES` and its test were removed. `ProgramLabelTest::tear_down` now re-registers the Program and Season taxonomies, and `RegistrationParityTest` expects `array( 'post' )` for both.
- **No migration code (2026-10-12).** On an existing site the old theme-mod value is ignored until the optional `wpcli` copy (Task 4 step 3) runs; otherwise Program falls back to `post`.
- **`hyperpress-utils` (2026-10-12).** Unchanged apart from deleting the two control classes. The shared `TaxonomyRegistrar` accepts a list.
- **Control deletion (2026-10-12).** `CategoryDropdownControl`, `PostTypeDropdownControl` and their three test files (`ControlsTest`, `ControlDescriptionEscapingTest`, `ControlsRenderTest`) were deleted. `LabelDropdownControl` stays and its own plan covers it; a replacement escaping test for it is added in the final fix wave.
- **Browser items still to check (2026-10-12).** The multiple `select_advanced` saves as an array; clearing every choice persists (Season's equivalent was confirmed by the owner); the `sanitize_callback` is honoured.
- **Deferred minors from reviews (2026-10-12).** `Settings::post_types()` returns saved strings without a registered/public check (the save-side sanitizer and `TaxonomyRegistrar` cover it). `TaxonomyAttachmentTest`'s default-attachment tests depend on global taxonomy state, so running `--filter 'Program|TaxonomyAttachment'` alone fails; fixed in the final fix wave. `NoControlsTest` uses `class_exists`; a sturdier file-existence assertion is planned in the final fix wave.
- Note (2026-10-13): `LabelDropdownControl` is now deleted too, together with the label Customizer section; see `docs/superpowers/plans/2026-10-13-settings-pages-label.md`.
- **2026-10-13, clearing the post types showed "Posts" again (browser test):** found in the owner's browser test (clearing Program showed "Posts" again). Meta Box redisplays `std` for an empty or missing multi-select value, so a field whose default is delivered by `default_option_<option>` must not also set `std`, and a saved row without the key means none. The `std` is removed from the post types field and `post_types()` now gives `array()` for a saved row without the key (default `array( 'post' )` only for a missing option or a non-array value). This reverses the earlier ruling that dismissed the final-review concern.
- **Browser checks (2026-10-13, owner).** Confirmed: clearing every post type and saving works and the empty list persists after a reload (after the fix `ccecd9c`, see the "Posts" bullet above). Not confirmed: that the Program taxonomy shows on the selected post types' edit screens and that the Customizer has no "Programs" section (Task 4 step 2). The optional dev-data copy (step 3) was not run.
