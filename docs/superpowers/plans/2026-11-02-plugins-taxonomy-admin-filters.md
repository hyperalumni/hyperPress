# Taxonomy Admin Filters Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add Season-style "All …" filter dropdowns to the wp-admin post lists for Program, District, Week, Competition Type and Sponsor Tier, from one shared class.

**Architecture:** A new `HyperPress\Utils\TaxonomyAdminFilter` keeps a registry of taxonomy => label callback and prints dropdowns from one `restrict_manage_posts` hook. Each plugin registers its taxonomies on `init`; Season's `AdminFilter::render()` delegates to the helper. Core filters the list through each taxonomy's `query_var`.

**Tech Stack:** PHP 8.3, WordPress 6.9, PHPUnit integration tests in Docker (`plugins/`).

**Spec:** `docs/superpowers/specs/2026-11-02-plugins-taxonomy-admin-filters-design.md`

## Global Constraints

Run everything from `plugins/` with `docker compose run --rm php composer <script>` (PHP never on the host). Test classes live in `plugins/tests/integration/`, namespace `HyperPress\Tests\Integration` (Utils tests in `tests/integration/Utils/`).

- Dropdown shown only when `is_object_in_taxonomy( $post_type, $taxonomy )`, the taxonomy has a `query_var`, and it has at least one term (`wp_count_terms`, `hide_empty => false`).
- Value is the term slug; selected term read from `$_GET[ $query_var ]` with `sanitize_title( wp_unslash() )`; `wp_dropdown_categories` with `value_field => 'slug'`, `hierarchical => true`, `orderby => name`, `hide_empty => false`.
- Labels, exact: `All seasons`, `All programs`, `All districts`, `All weeks`, `All competition types`, `All tiers` (text domain `hyperpress`). Labels must not be translated while plugins boot (WP 6.7+ flags early text domain loading), so the registry stores a callable and translates at render.
- Taxonomy keys: `hyper_program`, `hyper_district`, `hyper_week`, `hyper_competition_type`, `hyper_tier`.
- File header for new utils classes: `defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`. PHPCS WordPress standard, tabs.
- Commit from the repo root with explicit paths (`git add <paths>`; `git commit -m "type(scope): lowercase summary" -- <paths>`).

## Review Focus

- Program dropdown must not appear on a post type that is not assigned to Program in its options, and must appear on one that is.
- A taxonomy with no terms prints nothing (no empty "All …" select).
- Registering the same taxonomy twice (tests call `Plugin::register()` repeatedly) must not print the dropdown twice.
- A hostile `$_GET` value (array, markup) must not break the page or be echoed unescaped.
- Enabling Tier's query var: `?hyper_tier=<slug>` filters the sponsors list in wp-admin; on the front end the query var stays off (core), and the other registration properties of Tier stay as they were.

---

### Task 1: Shared helper and Season delegation

**Files:**

- Create: `hyperpress-utils/src/TaxonomyAdminFilter.php`
- Modify: `hyperpress-season/src/AdminFilter.php`
- Test: `tests/integration/Utils/TaxonomyAdminFilterTest.php` (new), `tests/integration/SeasonAdminFilterTest.php` (must pass unchanged)

**Interfaces:**

- Produces: `TaxonomyAdminFilter::register( string $taxonomy, callable $all_label ): void` (idempotent per taxonomy; adds one `restrict_manage_posts` handler); `TaxonomyAdminFilter::render( string $taxonomy, string $all_label, string $post_type ): void` (prints nothing when the rules in Global Constraints fail); `TaxonomyAdminFilter::on_restrict_manage_posts( $post_type = '' ): void` (the hook handler, loops the registry).

- [x] **Step 1: Write failing tests** in `Utils/TaxonomyAdminFilterTest`, registering a throwaway hierarchical taxonomy `hyper_taf_test` on `post` and a flat one on `page`: `test_prints_a_dropdown_named_after_the_query_var_with_the_all_option`; `test_value_is_the_slug_and_the_current_get_value_is_selected`; `test_prints_nothing_without_terms`; `test_prints_nothing_for_a_post_type_without_the_taxonomy`; `test_prints_nothing_for_a_taxonomy_without_a_query_var`; `test_array_or_markup_get_values_are_ignored_safely` (`$_GET[...] = array('x')` and `'"><script>'` give no warning and no raw `<script>` in output); `test_registering_twice_prints_one_dropdown_through_the_hook` (call `register` twice, then capture `do_action( 'restrict_manage_posts', 'post', 'top' )` and assert `substr_count( $html, "name='hyper_taf_test'" ) === 1`); `test_the_label_callable_is_resolved_at_render_time` (register with a callable that returns `All things`, assert it appears).
- [x] **Step 2: Run to verify failure.** `docker compose run --rm php composer test:integration -- --filter TaxonomyAdminFilterTest`. Expected: FAIL (class not found).
- [x] **Step 3: Implement `TaxonomyAdminFilter`** per the interfaces above, moving the body of Season's `AdminFilter::render` into `render()`; the registry is a private static array `taxonomy => callable`; `register()` stores the callable and adds `add_action( 'restrict_manage_posts', array( self::class, 'on_restrict_manage_posts' ) )` only if not already added (`has_action` check). Accept only string `$_GET` values.
- [x] **Step 4: Delegate Season.** `AdminFilter::render( $post_type = '' )` keeps its signature and calls `TaxonomyAdminFilter::render( Plugin::TAXONOMY, __( 'All seasons', 'hyperpress' ), $post_type )` (guard `is_string( $post_type )`); `AdminFilter::register()` and its `restrict_manage_posts` hook stay exactly as they are.
- [x] **Step 5: Run tests and lint.** `composer test:integration -- --filter 'TaxonomyAdminFilterTest|SeasonAdminFilterTest'` then `composer lint`. Expected: PASS (Season's test unchanged), exit 0. Note: `SeasonAdminFilterTest::test_nothing_is_printed_when_there_are_no_terms` fails when run with `--filter` on its own, on the original code too (the test database already holds seeded seasons); it passes in the full integration suite, so verify with the full `composer test:integration`.
- [x] **Step 6: Commit** `feat(utils): add a shared taxonomy filter for admin post lists`.

### Task 2: Register the five dropdowns, enable Tier's query var

**Files:**

- Modify: `hyperpress-program/src/Plugin.php`, `hyperpress-competition/src/Plugin.php`, `hyperpress-sponsor/src/Plugin.php` (`register()`/`tier_definition()`), `tests/integration/RegistrationParityTest.php`
- Test: new `tests/integration/TaxonomyAdminFiltersTest.php`

**Interfaces:**

- Consumes: `TaxonomyAdminFilter::register( string, callable )` (Task 1).
- Produces: after each plugin's `Plugin::register()`, `restrict_manage_posts` prints the matching dropdown.

- [x] **Step 1: Write failing tests** in `TaxonomyAdminFiltersTest` (helper `html( string $post_type )` = capture of `do_action( 'restrict_manage_posts', $post_type, 'top' )`; create one term per taxonomy with `self::factory()->term->create`): `test_competitions_get_district_week_and_type_dropdowns` (names `hyper_district`, `hyper_week`, `hyper_competition_type`; labels `All districts`, `All weeks`, `All competition types`); `test_sponsors_get_the_tier_dropdown` (`name='hyper_tier'`, `All tiers`); `test_program_appears_only_on_assigned_post_types` (set the Program post types option so it is attached to `hyper_competition` only and call `\HyperPress\Program\Plugin::register()`; `All programs` is in the competition output and absent for `hyper_sponsor`; then attach it to `hyper_sponsor` too and it appears there); `test_the_competition_dropdowns_do_not_appear_on_sponsors`; `test_filtering_competitions_by_district_uses_the_query_var` (a `WP_Query` with `hyper_district` => slug returns only the matching competition); `test_filtering_sponsors_by_tier_uses_the_query_var` (`hyper_tier` => slug returns only the matching sponsor). Use the real option name from `Settings::OPTION` of the Program plugin; read `Program\Settings` to find how `post_types()` is stored.
- [x] **Step 2: Run to verify failure.** `composer test:integration -- --filter TaxonomyAdminFiltersTest`. Expected: FAIL.
- [x] **Step 3: Register the dropdowns.** In each plugin's `Plugin::register()` (the method that runs on `init` and calls `TaxonomyRegistrar::register`) add `TaxonomyAdminFilter::register( <taxonomy const>, static fn() => __( '<label>', 'hyperpress' ) )`: Program `All programs`; Competition district/week/type with `All districts` / `All weeks` / `All competition types`; Sponsor `Plugin::TIER` with `All tiers`. Add the `use HyperPress\Utils\TaxonomyAdminFilter;` import.
- [x] **Step 4: Enable Tier's query var (admin only, by core's rules).** In `hyperpress-sponsor/src/Plugin.php::tier_definition()` remove the `'args' => array( 'query_var' => false )` line and the comment above it.
- [x] **Step 5: Parity exception: not needed.** Core keeps Tier's query var only under `is_admin()`, so the parity test (which runs outside wp-admin) still sees `false`; `RegistrationParityTest` is unchanged. The Tier dropdown tests register Sponsor as wp-admin would (`set_current_screen( 'edit-hyper_sponsor' )`, `Sponsor::register()`).
- [x] **Step 6: Run the whole suite and lint.** `composer test:unit`, `composer test:integration`, `composer lint`. Expected: all PASS, exit 0.
- [x] **Step 7: Commit** `feat(plugins): add admin filter dropdowns for program, district, week, type and tier`.

### Task 3: Check it in the admin and close out

**Files:**

- Modify: `plugins/AGENTS.md` (one line under Plugin conventions), this plan

- [ ] **Step 1: Look at it.** Start the hyperonline stack (`cd docker/hyperonline && docker compose --profile local up -d`), open `http://localhost:4080/wp-admin/edit.php?post_type=hyper_competition` in the browser tool (login needs the site's admin credentials: ask the owner if they are not already logged in) and confirm: the row shows All dates, All seasons, All programs (if assigned), All districts, All weeks, All competition types, Filter; choosing a district and pressing Filter narrows the list; the row wraps cleanly at a narrow width. Check the sponsors list shows All tiers and filters. If the admin is not reachable, say so and rely on the integration tests.
- [ ] **Step 2: Document.** Add a bullet to `plugins/AGENTS.md`: taxonomy filters in admin lists come from `TaxonomyAdminFilter::register()` on `init`, shown only on post types attached to the taxonomy.
- [ ] **Step 3: Close the plan.** Run the full suites once more, add a `STATUS: ✅ COMPLETED` banner with what passed, commit `docs(plans): complete taxonomy admin filters plan`.

## Known follow-ups

- Dropdown order is registration order (plugin load order); reorder only if the owner asks.
