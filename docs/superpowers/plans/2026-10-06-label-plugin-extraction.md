# hyperpress-label extraction Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Move the theme's label renderer and post labels into `hyperpress-label`, let `hyperpress-season`, `-newsletter` and `-sponsor` register their own labels, and add per-post-type Customizer settings that choose which labels show (or turn labels off).

**Architecture:** `hyperpress-label` owns a `Registry` of label definitions (filled lazily through the `hyperpress_label_register` action), a `Resolver` that reads two `theme_mod`s per post type, a `Renderer` on the `hyperpress_labels` action, and a `Customizer` section. Templates call `do_action( 'hyperpress_labels' )`. `hyperpress_labels_content` stays as the final filter.

**Tech Stack:** PHP 8.3, WordPress 6.9, PHPUnit via `wp-phpunit` (integration suite), phpcs. Everything runs in Docker from `plugins/`.

**Spec:** `plugins/docs/superpowers/specs/2026-10-06-label-plugin-extraction-design.md`

## Global Constraints

- Namespace `HyperPress\Label`, PSR-4 under `src/`; main file `hyperpress-label/hyperpress-label.php` keeps the existing header fields, with a real description. Text domain `hyperpress`. Version `2026.0.0`, Requires at least `6.9`, Requires PHP `8.3`.
- `Requires Plugins` for `hyperpress-label` stays empty (`Convention::REQUIRES_PLUGINS`). The label plugin must not depend on `hyperpress-season`; season, newsletter and sponsor must not require the label plugin.
- Identifiers: `hyperpress_label_*` for actions, filters and theme mods. Label IDs: `post.date` (10), `post.author` (20), `post.category` (40), `post.comments` (50), `season.posted_during` (30), `sponsor.last_sponsored` (10), `sponsor.tier` (20), `sponsor.season_count` (30).
- Theme mods per post type `T`: `hyperpress_label_{T}_enabled` (bool) and `hyperpress_label_{T}_labels` (string[] of label IDs). Customizer section `hyperpress_label`, title "Labels", priority 140.
- Label array shape: `label`, `title`, `classes`, `url`, `rel`. Markup is unchanged from `theme/template-parts/labels.php`.
- Season links use `get_term_link()`; a `WP_Error` link becomes `''`.
- Tests are integration tests (`plugins/tests/integration/`, namespace `HyperPress\Tests\Integration`, `WP_UnitTestCase`), because the resolver and Customizer need WordPress. Run from `plugins/`: `docker compose run --rm php composer test:integration -- --filter <Name>`; lint with `docker compose run --rm php composer lint` (0 violations, gating).
- Git: never a bare `git commit`. From `plugins/` or `theme/`: `git add <paths>` then `git commit -m "..." -- <paths>`. Terse imperative messages. The plan checkboxes for a task are ticked in the same commit as its code.

## Review Focus

- **Outside the loop:** `do_action( 'hyperpress_labels' )` when `get_post_type()` is false (no current post) prints nothing and raises no notice. Tested in Task 3.
- **Sloppy providers:** a callback returning a non-array, or label arrays missing `classes`/`url`/`rel`/`title`, must not warn or fatal in the template. Tested in Task 3.
- **Garbage Customizer input:** non-array, duplicated or unknown label IDs submitted for `_labels` are cleaned to a list of registered IDs. Tested in Task 5.
- **Stale saved settings:** saved IDs for a label that is no longer registered (plugin deactivated), or a saved post type that no longer exists, are ignored with no error. Tested in Tasks 2 and 5.
- **Empty data:** a post with no seasons, a sponsor with no tier, a post with no categories yield no label and no warning. Tested in Tasks 4, 6 and 7.

---

## Preflight

- [ ] **Step 1: Commit the spec and this plan on their own**

From `plugins/`: `git add docs/superpowers/specs/2026-10-06-label-plugin-extraction-design.md docs/superpowers/plans/2026-10-06-label-plugin-extraction.md` then `git commit -m "docs(plans): label plugin extraction" -- docs/superpowers/specs/2026-10-06-label-plugin-extraction-design.md docs/superpowers/plans/2026-10-06-label-plugin-extraction.md`.

### Task 1: Plugin scaffold and Registry

**Files:**
- Modify: `hyperpress-label/hyperpress-label.php`
- Create: `hyperpress-label/src/Plugin.php`, `hyperpress-label/src/Registry.php`
- Test: `tests/integration/LabelRegistryTest.php`

**Interfaces:**
- Produces: `HyperPress\Label\Plugin::boot(): void`. `HyperPress\Label\Registry` with `static instance(): self`, `static reset( bool $skip_collect = false ): void`, `add( string $id, array $args ): void`, `all(): array` (id => definition, sorted by `priority` ascending, insertion order for ties; first call fires `do_action( 'hyperpress_label_register', $this )` once), `default_post_types( string $id ): array` (applies filter `hyperpress_label_default_post_types( array $types, string $label_id )`), `ids(): array`. A definition is `array{group:string,title:string,callback:callable,default_post_types:string[],priority:int}`; `add()` fills defaults `group => ''`, `priority => 10`, `default_post_types => []`, and ignores entries whose `callback` is not callable.
- `reset( true )` leaves an empty registry marked as collected (tests register their own labels); `reset()` leaves one that will collect again on next use.

- [x] **Step 1: Write failing tests** in `LabelRegistryTest`: `test_add_fills_defaults` (priority 10, group `''`, empty default types), `test_all_sorts_by_priority_then_insertion`, `test_action_fires_once_and_passes_the_registry` (count callbacks on `hyperpress_label_register` across two `all()` calls = 1; argument is the `Registry`), `test_default_post_types_applies_the_filter`, `test_a_non_callable_callback_is_ignored`, `test_reset_recollects` . Each test starts with `Registry::reset( true )` except the action and reset tests, which use `Registry::reset()`.
- [x] **Step 2: Run** `--filter LabelRegistryTest`; expected FAIL (class not found).
- [x] **Step 3: Implement** `Registry`, `Plugin::boot()` (empty for now), and replace the placeholder in `hyperpress-label.php`: real description ("Configurable labels for post headers, per post type"), the same spl_autoload_register block as `hyperpress-season.php` with prefix `HyperPress\\Label\\`, and `add_action( 'plugins_loaded', array( \HyperPress\Label\Plugin::class, 'boot' ) );`.
- [x] **Step 4: Run** `--filter LabelRegistryTest` (PASS), then `--testsuite unit` via `docker compose run --rm php composer test:unit` (header lint still passes), then `composer lint`.
- [x] **Step 5: Commit** `feat(label): add the plugin scaffold and label registry` with the paths above.

### Task 2: Resolver

**Files:**
- Create: `hyperpress-label/src/Resolver.php`
- Test: `tests/integration/LabelResolverTest.php`

**Interfaces:**
- Consumes: `Registry::instance()->all()`, `Registry::default_post_types()`.
- Produces: `Resolver::enabled_key( string $post_type ): string`, `labels_key( string $post_type ): string`, `default_enabled( string $post_type ): bool` (true when any registered label lists the type in its default post types), `default_labels( string $post_type ): array` (IDs), `is_enabled( string $post_type, string $label_id ): bool`.

- [x] **Step 1: Write failing tests** (registry seeded with `Registry::reset( true )` plus `add()` of labels `a` defaulting to `['post']` and `b` defaulting to `['page']`): `test_defaults_apply_with_nothing_saved` (`a` on post true, `b` on post false, `default_enabled('post')` true, `default_enabled('attachment')` false); `test_saved_list_overrides_defaults` (`set_theme_mod( labels_key('post'), ['b'] )` → `b` true, `a` false); `test_disabled_post_type_shows_nothing` (`_enabled` false, labels still saved → both false; re-enabling restores the saved list); `test_stale_ids_are_ignored` (saved list `['gone','a']` → `a` true, no error, `is_enabled('post','gone')` false); `test_empty_saved_list_shows_nothing`; `test_default_types_filter_is_honoured` (filter adds `page` to `a`).
- [x] **Step 2: Run** `--filter LabelResolverTest`; expected FAIL.
- [x] **Step 3: Implement** `Resolver` with `get_theme_mod( key, default )`; cast `_enabled` to bool and `_labels` to an array of strings. Tear down with `remove_theme_mod` for touched keys.
- [x] **Step 4: Run** the filter, then `composer lint`.
- [x] **Step 5: Commit** `feat(label): resolve enabled labels per post type`.

### Task 3: Renderer and template

**Files:**
- Create: `hyperpress-label/src/Renderer.php`, `hyperpress-label/templates/labels.php`
- Modify: `hyperpress-label/src/Plugin.php` (call `Renderer::register()`)
- Test: `tests/integration/LabelRendererTest.php`

**Interfaces:**
- Consumes: `Registry`, `Resolver::is_enabled`, `Resolver::default_enabled` not needed here.
- Produces: `Renderer::register(): void` (adds `hyperpress_labels` action), `Renderer::labels(): array` (the guarded, filtered, normalized list), `Renderer::render(): void`.
- Template: markup copied verbatim from `theme/template-parts/labels.php` (the `$labels` variable is in scope). `render()` loads `locate_template( 'hyperpress-label/labels.php' )` when found, else the plugin's own.

Behavior of `labels()`: `$type = get_post_type()` (when false and `is_singular()`, fall back to the queried object's post type); empty when no type. Guard `is_singular( $type ) || is_post_type_archive( $type ) || ( is_main_query() && get_post_type() === $type )`, else empty. For each registry label in order where `Resolver::is_enabled( $type, $id )`, call the callback, skip non-array results, skip non-array entries, and normalize each entry with defaults `label ''`, `title ''`, `classes array()`, `url ''`, `rel ''`. Then `apply_filters( 'hyperpress_labels_content', $labels )`.

- [x] **Step 1: Write failing tests** (registry seeded with test labels on a `post`): `test_labels_follow_registry_order_and_are_filtered` (two labels at priorities 20/10 come out 10 then 20; a `hyperpress_labels_content` filter can append); `test_disabled_post_type_runs_no_callback` (callback sets a flag, `set_theme_mod( _enabled, false )`, flag unset); `test_unselected_label_is_skipped`; `test_no_output_outside_the_loop` (no `go_to`, `do_action( 'hyperpress_labels' )` under `ob_start` is `''`); `test_works_after_go_to_without_the_post` (`go_to( get_permalink( $id ) )` only, labels non-empty); `test_non_array_callback_results_and_entries_are_ignored` and `test_missing_keys_are_filled` (entry `array( 'label' => 'X' )` renders without warnings and `labels()[0]['classes'] === array()`); `test_markup_matches_the_old_template` (one label with url renders `<div class="cell shrink label primary">` containing an `<a href=... rel=...>`); `test_theme_template_override_wins` (write a temp file at `get_stylesheet_directory() . '/hyperpress-label/labels.php'` that prints a marker, assert the marker in the output, delete the file and directory in `tear_down`).
- [x] **Step 2: Run** `--filter LabelRendererTest`; expected FAIL.
- [x] **Step 3: Implement** `Renderer` and the template as described; wire into `Plugin::boot()`.
- [x] **Step 4: Run** the filter, then `composer lint`.
- [x] **Step 5: Commit** `feat(label): render labels on the hyperpress_labels action`.

### Task 4: Post labels

**Files:**
- Create: `hyperpress-label/src/Labels/PostLabels.php`
- Modify: `hyperpress-label/src/Plugin.php` (call `PostLabels::register()`)
- Test: `tests/integration/PostLabelsTest.php`

**Interfaces:**
- Produces: `PostLabels::register(): void` (adds `hyperpress_label_register` → `register_labels( Registry $registry ): void`), and static callbacks `date(): array`, `author(): array`, `category(): array`, `comments(): array`, each returning a list of label arrays. Registered with group "Post", defaults `['post']`, IDs and priorities from Global Constraints.
- Callback bodies are ports of `theme/library/labels/post.php` without the post-type condition: date `'%1$s @ %2$s'` with title `Posted At`, classes `dark`; author title `Posted By`, classes `primary byline author`, `rel => 'author'`, url `get_author_posts_url`; category = first category, title `Posted In`, class `info`, url `get_category_link`, and an empty list when the post has none; comments `N comment(s)`, class `neutral`, url `get_comments_link()`, empty title.

- [x] **Step 1: Write failing tests**, ported from `theme/tests/integration/LabelsBreadcrumbsGuardTest.php` (first two tests) and `StrictComparisonsTest.php::test_comment_count_label`, but reading `Renderer::labels()`: `test_post_without_categories_has_no_category_label` (titles contain `Posted At`, not `Posted In`; no label is `''`), `test_post_with_a_category_gets_it` (`Posted In`, `Guard Cat`), `test_comment_count_label` with the `0 comments` / `1 comment` / `2 comments` provider, `test_order_is_date_author_category_comments`, `test_labels_do_not_appear_on_a_page` (type `page`, default off).
- [x] **Step 2: Run** `--filter PostLabelsTest`; expected FAIL.
- [x] **Step 3: Implement** `PostLabels` and wire it.
- [x] **Step 4: Run** the filter and `--filter 'Label'`, then `composer lint`.
- [x] **Step 5: Commit** `feat(label): add the post labels`.

### Task 5: Customizer and control

**Files:**
- Create: `hyperpress-label/src/Customizer.php`, `hyperpress-label/src/Controls/LabelDropdownControl.php`
- Modify: `hyperpress-label/src/Plugin.php` (call `Customizer::register()`)
- Test: `tests/integration/LabelCustomizerTest.php` (style of `NewsletterCustomizerTest`)

**Interfaces:**
- Consumes: `Registry`, `Resolver` keys and defaults.
- Produces: `Customizer::register(): void` (hooks `customize_register` → `customize( WP_Customize_Manager $manager ): void`), `Customizer::post_types(): array` (name => plural label; public types except `attachment`, plus any type in a label's default types), `Customizer::sanitize_labels( $value ): array`. `LabelDropdownControl extends WP_Customize_Control`, `$type = 'dropdown-labels'`, multi-select of registry labels with option text `"{group}: {title}"`, modelled on `HyperPress\Utils\Controls\PostTypeDropdownControl` but without depending on Utils.
- Per post type: setting `hyperpress_label_{T}_enabled` (`theme_mod`, `transport refresh`, default `Resolver::default_enabled`, sanitize `rest_sanitize_boolean`) with a core `checkbox` control labelled `Show labels on {plural}`; setting `hyperpress_label_{T}_labels` (default `Resolver::default_labels`, sanitize `Customizer::sanitize_labels`) with the dropdown control. Section `hyperpress_label`, title `Labels`, priority 140.

- [x] **Step 1: Write failing tests**: `test_section_exists_with_priority_140`; `test_each_public_post_type_has_both_settings_and_controls_in_the_section` (`post`, `page`, no `attachment`); `test_defaults_match_the_resolver` (`post` enabled true with the post label IDs; `page` enabled false); `test_sanitize_labels_keeps_only_registered_ids` (`array( 'post.date', 'nope', 'post.date', 5 )` → `array( 'post.date' )`); `test_sanitize_labels_rejects_non_arrays` (`'x'`, `null` → `array()`); `test_stale_saved_ids_do_not_break_registration` (`set_theme_mod( labels_key('post'), array( 'gone' ) )` then building the manager raises nothing); `test_a_post_type_named_only_in_label_defaults_gets_controls` (register a label defaulting to a non-public type `hyper_x`).
- [x] **Step 2: Run** `--filter LabelCustomizerTest`; expected FAIL.
- [x] **Step 3: Implement** both classes and wire `Customizer::register()`.
- [x] **Step 4: Run** the filter, then `composer lint`.
- [x] **Step 5: Commit** `feat(label): add the Labels Customizer section`.

### Task 6: Season label (`hyperpress-season`)

**Files:**
- Create: `hyperpress-season/src/Labels.php`
- Modify: `hyperpress-season/src/Plugin.php` (`Labels::register();` in `boot()`)
- Test: `tests/integration/SeasonLabelTest.php`

**Interfaces:**
- Consumes: `HyperPress\Label\Registry::add` (received through the action argument; no class_exists guard needed because the callback only runs when the label plugin fires it), `SortedSeasons::get()`.
- Produces: `HyperPress\Season\Labels::register(): void`, `register_labels( \HyperPress\Label\Registry $registry ): void` adding `season.posted_during` (group "Season", title "Posted During", priority 30, defaults `['post']`), and `season_label(): array` returning zero or one label: name of the newest season, title `Posted During`, classes `warning`, url `get_term_link()` or `''` on `WP_Error`, `rel ''`.

- [x] **Step 1: Write failing tests**: `test_season_label_links_to_the_term` (ported from `NewsletterLabelsTest::test_season_label_url_is_the_term_link`, on a `post`, read through `Renderer::labels()`); `test_no_seasons_means_no_label`; `test_newest_season_wins` (`2025` and `2026` → `2026`); `test_a_term_link_error_gives_an_empty_url` (filter `term_link` to return a `WP_Error`).
- [x] **Step 2: Run** `--filter SeasonLabelTest`; expected FAIL.
- [x] **Step 3: Implement** `Labels` and the boot call.
- [x] **Step 4: Run** the filter and `--filter Season`, then `composer lint`.
- [x] **Step 5: Commit** `feat(season): register the Posted During label`.

### Task 7: Sponsor labels (`hyperpress-sponsor`)

**Files:**
- Modify: `hyperpress-sponsor/src/Labels.php`, `hyperpress-sponsor/templates/content.php:17`
- Test: `tests/integration/SponsorLabelsTest.php`

**Interfaces:**
- Produces: `Labels::register(): void` now adds the `hyperpress_label_register` action instead of the raw filter; `register_labels( \HyperPress\Label\Registry $registry ): void` adds `sponsor.last_sponsored` (10), `sponsor.tier` (20), `sponsor.season_count` (30), group "Sponsor", defaults `['hyper_sponsor']`; callbacks `last_sponsored()`, `tier()`, `season_count()` each return zero or one label (bodies are ports of the current `labels_sponsor`, minus the post-type condition). The template line becomes `<?php do_action( 'hyperpress_labels' ); ?>`.

- [x] **Step 1: Write failing tests** on a `hyper_sponsor` post via `Renderer::labels()`: `test_three_labels_in_order` (season name titled `Last Sponsored`, tier, `N Seasons`), `test_season_count_singular` (`1 Season`), `test_no_seasons_and_no_tier_means_no_labels`, `test_tier_title_includes_the_description`, `test_sponsor_labels_do_not_show_on_posts`.
- [x] **Step 2: Run** `--filter SponsorLabelsTest`; expected FAIL.
- [x] **Step 3: Implement** the changes; remove `labels_sponsor` and its `add_filter`.
- [x] **Step 4: Run** the filter and `--filter Sponsor`, then `composer lint`.
- [x] **Step 5: Commit** `feat(sponsor): register sponsor labels and render through the label action`.

### Task 8: Newsletter defaults (`hyperpress-newsletter`)

**Files:**
- Modify: `hyperpress-newsletter/src/Labels.php`, `hyperpress-newsletter/templates/content.php:34`, `tests/integration/NewsletterLabelsTest.php`
- Review: `tests/integration/NewsletterSeasonErrorTest.php`, `NewsletterSeasonEscapingTest.php` (run `grep -n 'hyperpress_labels_content\|labels'` first; adjust only if they read labels)

**Interfaces:**
- Produces: `Labels::register(): void` adds filter `hyperpress_label_default_post_types` → `default_post_types( array $types, string $label_id ): array`, which appends `Plugin::POST_TYPE` when `$label_id` is one of `post.date`, `post.author`, `post.comments`, `season.posted_during`. The raw `labels_newsletter` filter and its body are deleted. Template line 34 becomes `<?php do_action( 'hyperpress_labels' ); ?>`.

- [x] **Step 1: Update `NewsletterLabelsTest`** to read `\HyperPress\Label\Renderer::labels()` instead of `apply_filters`, and add `test_newsletter_gets_date_author_season_comments_in_order` (titles `Posted At`, `Posted By`, `Posted During`, `''`) and `test_newsletter_has_no_category_label`. Run `--filter NewsletterLabelsTest`; expected FAIL before the implementation.
- [x] **Step 2: Implement** `Labels`, delete the old provider, switch the template.
- [x] **Step 3: Run** `--filter Newsletter`, then `composer lint`.
- [x] **Step 4: Commit** `refactor(newsletter): take labels from hyperpress-label`.

### Task 9: Theme switch and removal (`theme/` repo)

**Files:**
- Modify: `theme/template-parts/content.php:24`, `theme/functions.php:41` (remove the require), `theme/tests/unit/PluginHooksContractTest.php` (remove the `'labels'` row), `theme/tests/integration/LabelsBreadcrumbsGuardTest.php` (delete the two label tests, keep the breadcrumb tests), `theme/tests/integration/StrictComparisonsTest.php` (delete the comment-count label test and its provider if unused elsewhere)
- Delete: `theme/template-parts/labels.php`, `theme/library/labels.php`, `theme/library/labels/post.php`

- [x] **Step 1:** Replace `get_template_part( 'template-parts/labels' )` in `content.php` with `do_action( 'hyperpress_labels' )`; run `grep -rn 'template-parts/labels' theme plugins --include=*.php` and confirm nothing is left.
- [x] **Step 2:** Delete the three files and the `functions.php` require; apply the test edits above.
- [x] **Step 3: Run** from `theme/`: `docker compose run --rm php composer test:unit`, `composer test:integration`, `composer lint`; expected all PASS. Rerun the plugins integration suite from `plugins/`.
- [x] **Step 4: Commit** (in `theme/`) `refactor: move labels to hyperpress-label`.

### Task 10: Docs, AGENTS and Docker mounts

**Files:**
- Modify: `theme/docs/superpowers/specs/2026-10-02-theme-cleanup-and-extraction-design.md` (labels are now extracted to `hyperpress-label`; `hyperpress_labels_content` is produced by the plugin), `theme/AGENTS.md:31` (drop `hyperpress_labels_content` from the hooks the theme fires; add that templates call `do_action( 'hyperpress_labels' )`), `plugins/AGENTS.md` (`hyperpress-label` is no longer a placeholder; only `hyperpress-student` is), `plugins/docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md` (label row no longer "placeholder")
- Modify (untracked stacks): `hyperalumni/compose.main.yaml` (add `- ../plugins/hyperpress-label/:/var/www/html/wp-content/plugins/hyperpress-label` after the countdown line, keeping the list alphabetical). `hyperonline/compose.main.yaml:21` already mounts it.

- [ ] **Step 1:** Make the doc edits; add an "Outcome / execution notes" line to this plan.
- [ ] **Step 2:** `docker compose --profile local config` in `hyperalumni/` succeeds.
- [ ] **Step 3:** Manual check in a running stack (`hyperalumni`): a single post shows date, author, category and comments labels (season appears only with `hyperpress-season` mounted and a season assigned); in Customizer > Labels, unchecking "Show labels on Posts" removes them in the preview; reselecting a subset shows only that subset.
- [ ] **Step 4: Commit** in each repo: `docs: label plugin extraction notes` (theme) and `docs: mark hyperpress-label as implemented` (plugins), with this plan's checkboxes ticked and a `> **STATUS: ✅ COMPLETED**` banner added.
