# Plugins: Competition Type Breadcrumbs

> **STATUS: ✅ COMPLETED, except the manual browser checks** (2026-10-13). Commits 2d5f9cb..HEAD; `composer test:unit` (171 tests), `composer test:integration` (953 tests, 6 skipped) and `composer lint` pass. Task 4 Step 5 and Task 5 Step 5 (manual check on hyperalumni) are open: the competition, season and newsletter plugins are inactive on the local site.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show the competition type as a breadcrumb between the post type and the season (`Competitions > District > Season 31`), give the type-only archive `/competition-types/district/` its own trail (`Competitions > District`), give the competition type, district and week archives a real banner instead of the theme's "Archive" (`District Competitions` plus the competition subtitle), add the queried terms to the banner subtitle on competition archives (`<subtitle> in District, Season 31`), and make it impossible for a second taxonomy in the request to change which season a breadcrumb or banner shows.

**Architecture:** A new `HyperPress\Utils\RequestTerm::get( $taxonomy )` is the only way plugin code reads "the term of taxonomy X for this archive request". It reads the taxonomy's own query var, not `get_queried_object()`, which can return any queried taxonomy's term. The season consumers (`Utils\Breadcrumbs`, `Utils\Banner`, `Season\Breadcrumbs`, `Season\SeasonBanner`) move to it. `Utils\Breadcrumbs::register()` then takes an optional list of extra taxonomies, and `hyperpress-competition` passes `hyper_competition_type` and adds a small `TypeBreadcrumbs` filter for the type-only archive. A source-scan test keeps `get_queried_object()` out of plugin code so the bug can't return. For banners, `Utils\Banner::register()` takes an optional `taxonomies` list; the pure `apply()` turns the queried terms of those taxonomies into the archive title (taxonomy-only pages) and into the subtitle suffix (post-type archives). The competition plugin passes its three taxonomies. Setting a banner `type` also stops the theme's `library/banner/archive.php` from writing "Archive".

**Tech Stack:** PHP 8.3, WordPress 6.9, PHPUnit (unit + `WP_UnitTestCase` integration), phpcs. All run through Docker.

**Spec:** none. The design was agreed in conversation (2026-10-13). Background: `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md` and `docs/superpowers/plans/2026-10-02-plugins-02-shared-utils.md` (where `Breadcrumbs` and `Banner` were built).

**Conventions:** run from `plugins/` with `docker compose run --rm php <cmd>` (never composer on the host). Commit from the repo root with `git add <paths>` then `git commit -m "..." -- <paths>`. Never a bare `git commit`, never `git add -A`. Commit messages use `type(scope): lowercase summary` as in `git log` (for example `fix(sponsor): ...`, `docs(plans): ...`); use the exact messages given in each task.

## Global Constraints

- The trail with no extra taxonomies must be byte-identical to today's. `Breadcrumbs::register( array( $post_type ) )` is also called by newsletter, sponsor, robot and award, and those callers must not change.
- Registered keys stay `hyper_*`. The competition type taxonomy is `hyper_competition_type` (`hyperpress-competition/src/Plugin.php:19`, `Plugin::TYPE`). The season taxonomy is `hyper_season` (`Breadcrumbs::SEASON_TAXONOMY`).
- Text domain `hyperpress`. Tabs for indentation, WordPress coding standard (`composer lint` must pass).
- The type crumb shows the term's own name only, with no ancestors. `District` is a child of `Qualifying`, and the trail is `Competitions > District`.
- The type crumb links to the term's own archive (`get_term_link`), e.g. `/competition-types/district/`.
- The season-scoped post-type archive is `/seasons/<season>/competitions/` (`hyperpress-season/src/Rewrite.php`, query `hyper_season=<slug>&post_type=hyper_competition`). Adding `?hyper_competition_type=district` to it is the combined view.
- Banner text for competition taxonomy archives (`hyper_competition_type`, `hyper_district`, `hyper_week`): title is the queried term names plus the archive title, e.g. `District Competitions`; subtitle is the competition subtitle setting, unchanged. The banner `type` is `competition` and the color is the competition color, as on the competition archive.
- Banner subtitle on a competition post-type archive: `<subtitle> <joiner> <extra terms>, <season>`, e.g. `<subtitle> in District, Season 31`. Extra terms come first, in the order of the `taxonomies` list, then the season. With extra terms but no season it is `<subtitle> in District`. With neither it is unchanged. Joiners stay `in` for competition and `from` for newsletter.
- Newsletter, sponsor and every other `Banner::register()` caller omit `taxonomies` and must behave exactly as today.
- Never use `is_tax( $taxonomy )` to ask whether a taxonomy is part of the request. Core compares it with `get_queried_object()->taxonomy`, so with two taxonomies queried it can be false for the one asked about (found in Task 2). Use `RequestTerm::get( $taxonomy ) instanceof \WP_Term` instead. `is_tax()` with no argument and `is_post_type_archive()` are fine.

## Review Focus

1. A request carrying both `hyper_season` and `hyper_competition_type` must show the season for the season and the type for the type, in the breadcrumbs, the banner and the season-only page trail, whichever term `get_queried_object()` would have returned.
2. A hierarchical query var can hold a path (`qualifying/district`). `RequestTerm` must resolve it to the last segment's term, not return `null` or the parent.
3. A single competition with no type term assigned produces no type crumb, no empty crumb and no PHP notice.
4. `?hyper_competition_type=does-not-exist` produces no type crumb and no fatal (`get_term_by` returns `false`).
5. `get_term_link()` returning a `WP_Error` gives an empty `url`, as the season crumb already does.
6. A taxonomy-only competition page that also carries `?hyper_season=...` still gets the competition title and `type`, even though `SeasonBanner` also fires (the Season plugin's filter runs first and sets a title). The competition banner overwrites the title unconditionally on taxonomy pages.
7. An unknown term slug (`?hyper_competition_type=nope`) is not a taxonomy page for the banner: no `type` is set and the theme's behavior is unchanged.

---

## File Structure

| Path | Responsibility |
|---|---|
| `plugins/hyperpress-utils/src/RequestTerm.php` (create) | `RequestTerm::get()`: the archive request's term for a taxonomy, from its query var |
| `plugins/hyperpress-utils/src/Breadcrumbs.php` (modify) | Uses `RequestTerm` for the season; optional extra taxonomies; `build()` emits term crumbs |
| `plugins/hyperpress-utils/src/Banner.php` (modify, lines 29-30 and 38; Task 5 also `register()` and `apply()`) | Season term name from `RequestTerm`; optional `taxonomies` arg, taxonomy-page title, subtitle suffix |
| `plugins/tests/unit/Utils/BannerTest.php` (modify) | `apply()` with `extra_terms` |
| `plugins/tests/integration/Competition/BannerTest.php` (create) | Banner on the type, district and week archives and the combined archive |
| `plugins/hyperpress-season/src/Breadcrumbs.php` (modify, line 13) | Season term from `RequestTerm` |
| `plugins/hyperpress-season/src/SeasonBanner.php` (modify, line 21) | Season term from `RequestTerm` |
| `plugins/hyperpress-competition/src/TypeBreadcrumbs.php` (create) | Trail for the type-only archive |
| `plugins/hyperpress-competition/src/Plugin.php` (modify, `boot()` at line 23) | Pass `array( self::TYPE )` to `Breadcrumbs::register`; call `TypeBreadcrumbs::register()` |
| `plugins/tests/unit/Utils/BreadcrumbsTest.php` (modify) | `build()` with `terms` |
| `plugins/tests/integration/Utils/RequestTermTest.php` (create) | `RequestTerm::get()` behavior |
| `plugins/tests/integration/Utils/BreadcrumbsRequestTest.php` (modify) | Request-level trails |
| existing Banner and Season tests (modify, or create where none exists) | Banner and season-page behavior with both taxonomies queried. Find them with `grep -rl "Banner\|SeasonBanner\|Season.*Breadcrumbs" plugins/tests`. |
| `plugins/tests/integration/Competition/TypeBreadcrumbsTest.php` (create) | Type-only archive trail. Check `ls plugins/tests/integration` and match the existing per-plugin folder naming if it differs. |
| `plugins/tests/unit/NoQueriedObjectTest.php` (create) | Source scan: no `get_queried_object(` in plugin code outside `RequestTerm.php` |

### Task 0: Commit this plan

- [ ] **Step 1:** `git add docs/superpowers/plans/2026-10-13-plugins-competition-type-breadcrumbs.md` then `git commit -m "docs(plans): competition type breadcrumbs" -- docs/superpowers/plans/2026-10-13-plugins-competition-type-breadcrumbs.md`. Only after the user approves the plan.

### Task 1: `Breadcrumbs::build()` emits term crumbs

**Files:**
- Modify: `plugins/hyperpress-utils/src/Breadcrumbs.php` (`build()`, lines 27-72)
- Test: `plugins/tests/unit/Utils/BreadcrumbsTest.php`

**Interfaces:**
- Produces: `build()` accepts an optional `$ctx['terms']`, a `list<array{name: string, slug: string, url: string}>`. Each entry becomes a crumb `array( 'title' => name, 'url' => url, 'classes' => array( 'li' => array( 'item-' . slug . '-' . $class ), 'bread' => array( 'bread-' . slug . '-' . $class ) ) )`. Terms come after the post-type crumb, in order, and before the season crumb. A missing or empty `terms` changes nothing.

- [x] **Step 1: Write failing tests** in `BreadcrumbsTest.php`, using the existing `ctx()` and `season()` helpers and adding a `term( string $name, string $slug )` helper that returns `array( 'name', 'slug', 'url' => "/competition-types/$slug/" )`:
  - `test_build_puts_term_between_post_type_and_season`: titles equal `array( 'Gizmos', 'District', 'Season 31' )` for `terms = array( term('District','district') )` with a season.
  - `test_build_term_crumb_has_url_and_classes`: the `District` crumb has `url` `/competition-types/district/` and `li` class `item-district-gizmos`.
  - `test_build_without_terms_is_unchanged`: the result for a ctx with no `terms` key equals the result for `terms => array()`, and neither contains a term crumb.
  - `test_build_term_without_season`: titles are `array( 'Gizmos', 'District' )`.
- [x] **Step 2:** Run `docker compose run --rm php composer test:unit -- --filter BreadcrumbsTest`. Expected: the new tests FAIL (the term crumb is missing).
- [x] **Step 3:** In `build()`, loop over `$ctx['terms'] ?? array()` and append one crumb per term after the post-type crumb block and before the season block. Extend the docblock's ctx description with `terms`.
- [x] **Step 4:** Rerun the Step 2 command. Expected: PASS, including all existing `BreadcrumbsTest` tests.
- [x] **Step 5:** Tick this task's boxes in this file. Commit: `git add plugins/hyperpress-utils/src/Breadcrumbs.php plugins/tests/unit/Utils/BreadcrumbsTest.php docs/superpowers/plans/2026-10-13-plugins-competition-type-breadcrumbs.md` then `git commit -m "feat(utils): emit extra taxonomy term crumbs in Breadcrumbs::build" -- <same paths>`.

### Task 2: `RequestTerm`, and move the season consumers to it

**Files:**
- Create: `plugins/hyperpress-utils/src/RequestTerm.php`
- Modify: `plugins/hyperpress-utils/src/Breadcrumbs.php` (`context()`, lines 106-109), `plugins/hyperpress-utils/src/Banner.php` (lines 29-30), `plugins/hyperpress-season/src/Breadcrumbs.php:13`, `plugins/hyperpress-season/src/SeasonBanner.php:21`
- Test: `plugins/tests/integration/Utils/RequestTermTest.php` (create), `plugins/tests/integration/Utils/BreadcrumbsRequestTest.php`, the existing Banner and Season tests, `plugins/tests/unit/NoQueriedObjectTest.php` (create)

**Interfaces:**
- Produces: `final class HyperPress\Utils\RequestTerm` with `public static function get( string $taxonomy ): ?\WP_Term`.
  - Reads `get_query_var( $taxonomy )`. For a hierarchical taxonomy it takes the last path segment (`wp_basename`). Returns `get_term_by( 'slug', $slug, $taxonomy )` when that is a `WP_Term`, else `null` (empty var, unknown slug, unregistered taxonomy, `WP_Error`, `false`).
  - It does not check `is_tax()`. Callers keep their existing `is_tax()` guard.

- [x] **Step 1: Write failing tests**
  - `RequestTermTest`, with `hyper_season` and a hierarchical `hyper_competition_type` (`Qualifying` with child `District`) registered:
    - `test_returns_term_for_query_var`: after `go_to( '/?hyper_season=season-31' )`, `RequestTerm::get( 'hyper_season' )` is the `Season 31` term.
    - `test_each_taxonomy_gets_its_own_term`: after `go_to( '/?hyper_season=season-31&hyper_competition_type=district' )`, `get( 'hyper_season' )->slug` is `season-31` and `get( 'hyper_competition_type' )->slug` is `district`. Repeat with the terms created in the opposite order (Review Focus 1).
    - `test_hierarchical_path_resolves_to_last_segment`: query var `qualifying/district` returns `District` (Review Focus 2).
    - `test_unknown_slug_returns_null`, `test_empty_query_var_returns_null` and `test_unregistered_taxonomy_returns_null` (Review Focus 4).
  - `BreadcrumbsRequestTest`: `test_season_crumb_is_season_when_type_also_queried`. On `go_to( '/?post_type=hyper_gizmo&hyper_season=season-31&hyper_competition_type=district' )` with `Breadcrumbs::register( array( 'hyper_gizmo' ) )`, the last title is `Season 31`.
  - Banner and Season tests: on a request with both taxonomies queried, `Banner`'s `term_name` and `SeasonBanner`'s title are `Season 31`, and `Season\Breadcrumbs::filter` yields `Seasons`, `Season 31` for a season-only page request.
  - `NoQueriedObjectTest`: scan every `plugins/hyperpress-*/src/**/*.php` file. Fail if any contains `get_queried_object(` other than `hyperpress-utils/src/RequestTerm.php`. Before writing the allowlist, run `grep -rn "get_queried_object" plugins/hyperpress-*/src` and convert each remaining hit that reads a taxonomy term to `RequestTerm`. A hit that does not read a term (for example an author or a post) goes in an explicit per-file allowlist inside the test with a one-line reason.
- [x] **Step 2:** Run `docker compose run --rm php composer test:integration -- --filter "RequestTerm|BreadcrumbsRequestTest|Banner|Season"` and `composer test:unit -- --filter NoQueriedObjectTest`. Expected: FAIL (`RequestTerm` does not exist; the scan finds the four current callers).
- [x] **Step 3:** Create `RequestTerm::get()` as specified. Replace the four `get_queried_object()` reads: `Utils\Breadcrumbs::context()` (the `elseif ( is_tax( self::SEASON_TAXONOMY ) )` branch) and `Utils\Banner` take `RequestTerm::get( self::SEASON_TAXONOMY )`, and the two Season classes take `RequestTerm::get( 'hyper_season' )`. Keep each class's existing `is_tax()` and `! is_post_type_archive()` guards.
- [x] **Step 4:** Rerun the Step 2 commands, then `composer test:unit` and `composer test:integration`. Expected: PASS, including every existing breadcrumb, banner and season test.
- [x] **Step 5:** `docker compose run --rm php composer lint`. Expected: no errors.
- [x] **Step 6:** Tick this task's boxes. Commit the changed and created PHP files, the tests and this plan with `git commit -m "refactor(utils): resolve archive terms from their own query var" -- <paths>`.

### Task 3: Breadcrumbs for extra taxonomies

**Files:**
- Modify: `plugins/hyperpress-utils/src/Breadcrumbs.php` (`register()`, `context()`, lines 15-25 and 80-130)
- Test: `plugins/tests/integration/Utils/BreadcrumbsRequestTest.php`

**Interfaces:**
- Consumes: `build()` with `ctx['terms']` from Task 1; `RequestTerm::get()` from Task 2.
- Produces:
  - `public static function register( array $post_types, array $taxonomies = array() ): void`. `$taxonomies` is a list of taxonomy keys, and the default keeps current behavior.
  - `context()` adds `'terms' => list<array{name, slug, url}>`, one per extra taxonomy that is registered and has a term for the request. Singular: the first term of `wp_get_object_terms( get_the_ID(), $taxonomy )` ordered by name. Archive: `RequestTerm::get( $taxonomy )`. The URL is `get_term_link()`, or `''` for a `WP_Error` (Review Focus 5).

- [x] **Step 1: Write failing tests** in `BreadcrumbsRequestTest.php`. Register `hyper_competition_type` (hierarchical, `rewrite` slug `competition-types`, public) beside the existing `hyper_season` and `hyper_gizmo` setup, create `Qualifying` with child `District`, and season `Season 31`:
  - `test_archive_trail_includes_type_between_post_type_and_season`: the combined request with `register( array( 'hyper_gizmo' ), array( 'hyper_competition_type' ) )` gives titles `array( 'Gizmos', 'District', 'Season 31' )`, and the District `url` equals `get_term_link( $district )`.
  - `test_singular_trail_uses_assigned_type`: a gizmo with `District` and `Season 31` assigned gives `Gizmos, District, Season 31, <title>`.
  - `test_singular_without_type_has_no_type_crumb`: Review Focus 3.
  - `test_unknown_type_slug_has_no_type_crumb`: Review Focus 4.
  - `test_child_term_yields_single_crumb`: no `Qualifying` title appears.
  - `test_register_without_taxonomies_is_unchanged`: the trail for the existing season-only request equals today's.
- [x] **Step 2:** Run `docker compose run --rm php composer test:integration -- --filter BreadcrumbsRequestTest`. Expected: the new tests FAIL (`register()` takes one argument).
- [x] **Step 3:** Implement `register()` and the `context()` additions as specified. `register()` passes `$taxonomies` into the per-post-type closure.
- [x] **Step 4:** Rerun the Step 2 command, then `composer test:unit` and `composer lint`. Expected: PASS.
- [x] **Step 5:** Tick this task's boxes. Commit `plugins/hyperpress-utils/src/Breadcrumbs.php`, the test and this plan with `git commit -m "feat(utils): show extra taxonomy terms in breadcrumbs" -- <paths>`.

### Task 4: Wire `hyperpress-competition`, add the type-only trail

**Files:**
- Create: `plugins/hyperpress-competition/src/TypeBreadcrumbs.php`
- Modify: `plugins/hyperpress-competition/src/Plugin.php:27` (and add the `register()` call in `boot()`)
- Test: `plugins/tests/integration/Competition/TypeBreadcrumbsTest.php`

**Interfaces:**
- Consumes: `Breadcrumbs::register( array $post_types, array $taxonomies )` from Task 3; `RequestTerm::get()` from Task 2.
- Produces: `final class HyperPress\Competition\TypeBreadcrumbs` with `public static function register(): void` (adds the `hyperpress_breadcrumbs_content` filter) and `public static function filter( $breadcrumbs )`. The class is named `TypeBreadcrumbs` because `Plugin.php` already imports `HyperPress\Utils\Breadcrumbs`.
  - When `is_tax()` (no argument), `! is_post_type_archive()` and `RequestTerm::get( Plugin::TYPE )` is a term, append the post-type crumb (title `get_post_type_object( Plugin::POST_TYPE )->labels->name`, `url` `get_post_type_archive_link( Plugin::POST_TYPE )`, classes `item-archive-<class>` / `bread-archive-<class>` as in `Utils\Breadcrumbs::build()`), then a crumb with the term's name and no `url`. Otherwise return `$breadcrumbs` unchanged.

- [x] **Step 1: Write failing tests** in `TypeBreadcrumbsTest.php`, loading the competition plugin the way the other plugin integration tests do:
  - `test_type_archive_trail`: `go_to( get_term_link( $district ) )` then `apply_filters( 'hyperpress_breadcrumbs_content', array() )` gives titles `array( 'Competitions', 'District' )`, the first with the competitions archive URL.
  - `test_other_requests_unchanged`: on a plain page request the filter returns the input array unchanged.
  - `test_competition_archive_includes_type`: `go_to( '/?post_type=hyper_competition&hyper_season=<slug>&hyper_competition_type=district' )` gives titles `Competitions, District, <Season>`, with each crumb appearing once (the type-only filter does not also fire).
- [x] **Step 2:** Run `docker compose run --rm php composer test:integration -- --filter TypeBreadcrumbsTest`. Expected: FAIL (class not found).
- [x] **Step 3:** Create `TypeBreadcrumbs` as specified. In `Plugin::boot()`, change line 27 to `Breadcrumbs::register( array( self::POST_TYPE ), array( self::TYPE ) );` and add `TypeBreadcrumbs::register();` after it. The PSR-4 autoloader in `hyperpress-competition.php` needs no change.
- [x] **Step 4:** Run `docker compose run --rm php composer test:unit`, `composer test:integration` and `composer lint`. Expected: all pass, with no regressions in the newsletter, sponsor, robot, award or season tests.
- [ ] **Step 5:** Manual check against the running site (`cd docker/hyperalumni && docker compose --profile local up -d`, WordPress on port 3080). Load `/seasons/season-31/competitions/?hyper_competition_type=district` and `/competition-types/district/`. Confirm the trails and that the crumb links resolve. If the combined request is not treated as a post-type archive, record that and fix `context()`. (deferred to Task 5 Step 5) **Still open:** not confirmed, see Task 5 Step 5.
- [x] **Step 6:** Tick this task's boxes and commit the competition plugin files, the test and this plan with `git commit -m "feat(competition): breadcrumb trails for competition types" -- <paths>`.

### Task 5: Banner text for the competition taxonomies

**Files:**
- Modify: `plugins/hyperpress-utils/src/Banner.php` (`register()` closure, `apply()`, docblocks), `plugins/hyperpress-competition/src/Plugin.php` (`register_banner()`, line 35)
- Test: `plugins/tests/unit/Utils/BannerTest.php`, `plugins/tests/integration/Competition/BannerTest.php` (create)

**Interfaces:**
- Consumes: `RequestTerm::get()` from Task 2.
- Produces:
  - `Banner::register()` accepts an optional `$args['taxonomies']`, a `list<string>` of taxonomy keys. The closure sets `ctx['extra_terms']` to a `list<string>` of term names: for each listed taxonomy, in list order, when `RequestTerm::get( $taxonomy )` returns a term, that term's name (no `is_tax( $taxonomy )` check, see Global Constraints). It stays empty on singular requests. The closure's early return changes to: return `$banner` unless `is_singular`, `is_archive` or `extra_terms` is non-empty.
  - `Banner::apply()` reads `$ctx['extra_terms'] ?? array()`.
    - Taxonomy page (not singular, not a post-type archive, `extra_terms` non-empty): set `type`, `subtitle` and `backgroundColor` as for archives, and set `title` unconditionally to `implode( ' ', extra_terms ) . ' ' . $args['archive_title']`.
    - Post-type archive: in the existing archive-title block, build the names list as `extra_terms` followed by `term_name` when `is_tax_season`. If the list is non-empty and the subtitle is non-empty, the subtitle becomes `subtitle . ' ' . joiner . ' ' . implode( ', ', names )`. Without `extra_terms` the result must equal today's.
  - `Plugin::register_banner()` adds `'taxonomies' => array( self::DISTRICT, self::WEEK, self::TYPE )`.

- [x] **Step 1: Write failing tests**
  - `BannerTest` (pure, using its `args()` and `ctx()` helpers):
    - `test_taxonomy_page_title_is_term_names_plus_archive_title`: `extra_terms = array( 'District' )`, `archive_title` `Competitions`, `is_archive` false: `title` is `District Competitions`, `type` and `subtitle` come from args and ctx.
    - `test_taxonomy_page_joins_several_terms`: `array( 'District', 'Week 1' )` gives `District Week 1 Competitions`.
    - `test_taxonomy_page_overwrites_an_existing_title`: incoming `title` `Season 31` becomes `District Competitions` (Review Focus 6).
    - `test_archive_subtitle_lists_extra_terms_then_season`: `is_archive`, `is_tax_season`, `term_name` `Season 31`, `extra_terms = array( 'District' )`, subtitle `Subtitle`: `Subtitle in District, Season 31`.
    - `test_archive_subtitle_with_extra_terms_and_no_season`: `Subtitle in District`.
    - `test_apply_without_extra_terms_is_unchanged`: the existing season and `from` joiner tests still pass untouched, and a ctx with no `extra_terms` key behaves like `array()`.
    - `test_page_with_no_terms_is_unchanged`: not singular, not archive, no `extra_terms`: returns the incoming banner.
  - `Competition/BannerTest` (integration, with the three taxonomies and the post type registered, the competition banner registered via `Plugin::register_banner()`):
    - `test_type_archive_banner`: `go_to( get_term_link( $district_type ) )`, then `apply_filters( 'hyperpress_banner_content', array() )` has `title` `District Competitions`, `type` `competition` and the competition subtitle.
    - `test_district_and_week_archive_banners`: the same for a `hyper_district` term and a `hyper_week` term.
    - `test_combined_archive_banner`: `go_to( '/?post_type=hyper_competition&hyper_season=season-31&hyper_competition_type=district' )` gives `title` `Competitions` and a subtitle ending `in District, Season 31`.
    - `test_type_page_with_season_query_keeps_competition_banner`: Review Focus 6.
    - `test_unknown_type_slug_sets_no_banner_type`: Review Focus 7.
    - `test_other_post_type_banner_unchanged`: a newsletter-style registration without `taxonomies` produces the same banner as before for its archive.
- [x] **Step 2:** Run `docker compose run --rm php composer test:unit -- --filter BannerTest` and `composer test:integration -- --filter Competition`. Expected: FAIL (unknown `extra_terms` behavior, no taxonomy-page banner).
- [x] **Step 3:** Implement `register()` and `apply()` as specified, and update the `register()` and `apply()` docblocks (`taxonomies` arg, `extra_terms` ctx key). Add `'taxonomies'` to `Plugin::register_banner()`.
- [x] **Step 4:** Run `composer test:unit`, `composer test:integration` and `composer lint`. Expected: PASS, with no regressions in the newsletter, sponsor, season or theme-facing banner tests.
- [ ] **Step 5:** Manual check on `hyperalumni` (port 3080): load `/competition-types/district/`, a district archive, a week archive and `/seasons/season-31/competitions/?hyper_competition_type=district`. Confirm the titles and subtitles, that "Archive" is gone, and that `/seasons/season-31/newsletters/` is unchanged. Note the real district and week URL slugs from `hyperpress-competition/src/Plugin.php` (`district_definition()` and the week definition). **Not done (2026-10-13):** the local hyperalumni stack starts, but `hyperpress-season`, `hyperpress-competition` and `hyperpress-newsletter` are inactive there (taxonomies do not exist, `/competition-types/district/` is a 404), and activating them would change site data. Verify after activating them on the local site.
- [x] **Step 6:** Tick this task's boxes, set the `STATUS: COMPLETED` banner on this plan with the date and commit range, and commit the changed files with `git commit -m "feat(competition): banner text for competition taxonomy archives" -- <paths>`.

---

## Self-review notes

- Coverage: type crumb on the combined archive (Tasks 3-4), on single competitions (Task 3), the type-only page (Task 4), the wrong-term bug in breadcrumbs, banner and season page (Task 2 plus the scan guard), and no behavior change for other plugins (Task 3 `test_register_without_taxonomies_is_unchanged`).
- Banner coverage: type, district and week archives (Task 5), the combined archive subtitle (Task 5), and no change for newsletter, sponsor or other callers (Task 5 `test_other_post_type_banner_unchanged`).
- The banner title word order (`District Competitions`) is built by concatenation and the joiners are not translated, which matches the existing code. Revisit if the site is ever translated.
- A type-only page with `?hyper_season=...` shows the Season plugin's trail (`Seasons > Season 31`) and no type crumb: `TypeBreadcrumbs` defers to the Season plugin there (Task 4 ruling), because otherwise both trails would be appended. The banner on that URL is the competition banner (Task 5, Review Focus 6), so the two disagree on that one unlinked URL shape. Align them later if wanted.
- The type-only trail uses the term's own name. If the term is named "District" the crumb reads "District"; rename the term in wp-admin if you want "Districts".
