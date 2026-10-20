# Sponsor archive grouping Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** The paginated sponsor archive shows a season heading (all-time archive) and a tier heading per group, repeating " (continued)" headings when a page starts mid-group.

**Architecture:** The sponsor plugin supplies the archive loop through the theme's existing `hyperpress_posts_template` filter. A `Grouping` class derives each sponsor's season and tier with the same rules as `Ordering` (PHP comparers mirroring the SQL) and decides which headings a sponsor needs. `Ordering` gains an opt-in so a one-row secondary query can find the last sponsor of the previous page. No theme change.

**Tech Stack:** PHP 8.3, WordPress 6.9, PHPUnit via Docker (`plugins/`).

**Spec:** `docs/superpowers/specs/2026-10-19-sponsor-archive-grouping-design.md`

## Global Constraints

- PHP runs in Docker only: from `plugins/`, `docker compose run --rm php composer lint|test:unit|test:integration` (filter one test with `composer test:integration -- --filter <Name>`). Never composer on the host; do not read `.env` files.
- Namespace `HyperPress\Sponsor`; prefix `hyperpress_`; text domain `hyperpress`; every PHP file starts with the `defined( 'ABSPATH' ) || exit;` guard; tabs, WordPress coding style, `array()` syntax.
- Use only the theme's existing `hyperpress_posts_template` filter (returns a template path or `''`); the plugin returns its loop template only for the main query of the sponsor archive (not feeds, not admin) and returns the argument unchanged otherwise. The theme keeps header, banner, breadcrumbs, pagination, sidebar and footer. No theme change.
- Headings: season `h2`, tier `h3`; sponsor entry titles inside the grouped loop are `h4`, everywhere else `h2` (unchanged). Heading text = the term name ("Season 31", "Level 4"); sponsors without a season: "Other sponsors" (season level); in a season but without a tier: "Other sponsors" (tier level, `h3`); both texts filterable. Repeated headings append " (continued)" (translatable).
- Page size 24 on the sponsor archive main query, filterable through `hyperpress_sponsor_archive_per_page`.
- Grouping keys follow `Ordering`'s rules: newest season = highest trailing number of the sponsor's season names (unnumbered after numbered); tier = highest ranking per `Ordering::tier_compare()`; no season / no tier = last. Do not invent another rule.
- Commit from the repo root with explicit pathspecs (`git add <paths>`, `git commit -m "type(scope): lowercase summary" -- <paths>`); never `git add -A`. Per the root `AGENTS.md`, commit this plan on its own first, then tick each task's checkboxes in the same commit as its code.

## Review Focus

- A page that starts mid-group repeats the season and tier headings with " (continued)"; a page that starts exactly at a group boundary does not; page 1 never says continued. (Task 2, Task 3)
- A sponsor with no season (all-time archive) and a sponsor with a season but no tier land last under "Other sponsors" headings, with no PHP notice. (Task 2, Task 3)
- A multi-season sponsor appears once, under its newest season; the headings never disagree with the SQL order (consistency test over the same fixtures). (Task 2)
- Season-scoped archive: tier headings only, no season heading, no " (continued)" season line. (Task 2, Task 3)
- Term names with HTML or quotes are escaped in headings; an empty archive shows the theme's "nothing found" part, not bare headings. (Task 3)
- Sponsor titles are `h4` only inside the grouped loop; elsewhere (widget lists, front page, singles) the template still prints `h2`. (Task 3)

---

## File Structure

- Modify `plugins/hyperpress-sponsor/src/Ordering.php`: season comparer, opt-in query var (and `is_season_scoped` made public).
- Create `plugins/hyperpress-sponsor/src/Grouping.php`: group keys and heading decisions.
- Create `plugins/hyperpress-sponsor/src/ArchiveLoop.php`: `hyperpress_posts_template` filter, page size, previous-page sponsor lookup.
- Create `plugins/hyperpress-sponsor/templates/loop.php`: the grouped loop.
- Modify `plugins/hyperpress-sponsor/templates/content.php` (title tag), `plugins/hyperpress-sponsor/src/Plugin.php` (boot).
- Tests (namespace `HyperPress\Tests\Integration\Sponsor`, in `plugins/tests/integration/Sponsor/`): extend `OrderingTest.php`; create `GroupingTest.php`, `ArchiveLoopTest.php`, `GroupedLoopTest.php`.

Test conventions: integration tests extend `WP_UnitTestCase`; look at the existing `OrderingTest.php` for the fixture helpers that create sponsors with `hyper_season` and `hyper_tier` terms, menu order and titles, register the season taxonomy for the sponsor post type, and flush rewrites for `/seasons/<slug>/sponsors/`.

---

### Task 1: `Ordering` season comparer and opt-in

**Files:**

- Modify: `plugins/hyperpress-sponsor/src/Ordering.php`
- Test: `plugins/tests/integration/Sponsor/OrderingTest.php`

**Interfaces:**

- Produces: `Ordering::season_compare( \WP_Term|string $a, \WP_Term|string $b ): int` (highest first, same rule as the SQL season key: trailing number from names matching `^(.* )?[0-9]+$`, unnumbered after numbered, ties by name); `Ordering::ORDER_VAR` (`'hyperpress_sponsor_order'`); `Ordering::is_season_scoped( WP_Query $query ): bool` becomes `public`.
- Behaviour: `order_clauses()` also applies to a non-main query that has `ORDER_VAR` truthy and post type `hyper_sponsor`, still skipping admin, feeds and an explicit `orderby`; main-query behaviour is unchanged.

- [x] **Step 1: Write the failing tests** in `OrderingTest`: `test_season_compare_ranks_by_trailing_number` (`Season 31` before `Season 4`, numbered before `Alpha`, equal numbers by name, accepts terms and plain names); `test_opt_in_secondary_query_is_ordered` (a `new WP_Query( array( 'post_type' => 'hyper_sponsor', Ordering::ORDER_VAR => true, 'fields' => 'ids', 'posts_per_page' => -1 ) )` returns the same ID order as the main archive for the fixtures); the existing `test_a_secondary_query_keeps_the_default_order` still passes unchanged.
- [x] **Step 2: Run to verify they fail.** `docker compose run --rm php composer test:integration -- --filter OrderingTest`. Expected: FAIL (method and constant missing).
- [x] **Step 3: Implement** `season_compare` (mirror the SQL: rank = trailing number or -1, descending, then name) and the opt-in guard change; make `is_season_scoped` public.
- [x] **Step 4: Run to verify they pass** (`OrderingTest`, then `--filter Sponsor`). Expected: PASS.
- [x] **Step 5: Commit** `feat(sponsor): add a season comparer and an opt-in to the sponsor ordering`.

---

### Task 2: `Grouping`

**Files:**

- Create: `plugins/hyperpress-sponsor/src/Grouping.php`
- Test: `plugins/tests/integration/Sponsor/GroupingTest.php`

**Interfaces:**

- Consumes: `Ordering::season_compare`, `Ordering::tier_compare`, `Ordering::is_season_scoped`.
- Produces:
  - `Grouping::keys( int $post_id ): array{season:?\WP_Term,tier:?\WP_Term}` (newest season by `season_compare`, highest tier by `tier_compare`; `null` when the sponsor has none).
  - `Grouping::headings( array $current, ?array $previous, bool $season_scoped, bool $page_start ): array` returning a list of `array{tag:string,text:string}` in print order (`h2` season first, then `h3` tier). `$current`/`$previous` are `keys()` results (`$previous` is `null` for the very first sponsor). Rules: a level is printed when it differs from `$previous` (a season change also prints the tier heading); when `$page_start` is true and a level equals `$previous`'s, it is printed with " (continued)" appended (season heading omitted entirely when `$season_scoped`); `null` season/tier use the "Other sponsors" text; texts pass through the filters `hyperpress_sponsor_archive_season_label` and `hyperpress_sponsor_archive_tier_label` (arguments: text, term or null).

- [ ] **Step 1: Write the failing tests** in `GroupingTest`:
  - `test_keys_pick_newest_season_and_highest_tier` (sponsor in seasons 30 and 32, tiers level-2 and level-10 → season 32, level-10; sponsor without terms → both `null`).
  - `test_headings_on_group_changes` (table-driven: first sponsor prints season + tier; same group prints none; new tier prints tier only; new season prints season + tier; scoped archive never prints a season heading).
  - `test_page_start_repeats_unchanged_headings_with_continued` (same group → `Season 31 (continued)` and `Level 3 (continued)`; same season but new tier → season `(continued)` and the tier normally; new season → both normal; page 1 start with `$previous === null` → none continued; scoped → tier only).
  - `test_missing_season_and_tier_use_other_sponsors` (no season → season heading `Other sponsors`; season but no tier → tier heading `Other sponsors`) and `test_labels_are_filterable`.
  - `test_group_keys_follow_the_sql_order`: create a fixture set (several seasons incl. a multi-season sponsor, tiers incl. `level-10`, `level-4`, an unnumbered tier, a sponsor without season, one without tier), load the all-time archive with `go_to( '/?post_type=hyper_sponsor' )`, and assert that walking `$GLOBALS['wp_query']->posts` the (season, tier) keys never increase under `season_compare` / `tier_compare` (no sponsor is preceded by one that ranks lower) and a multi-season sponsor appears once.
- [ ] **Step 2: Run to verify they fail.** `docker compose run --rm php composer test:integration -- --filter GroupingTest`. Expected: FAIL (class missing).
- [ ] **Step 3: Implement** `Grouping`; escape nothing here (texts are returned raw; the template escapes). Make the translatable " (continued)" suffix and the "Other sponsors" text use `__( ..., 'hyperpress' )`.
- [ ] **Step 4: Run to verify they pass** (`GroupingTest`, then `--filter Sponsor`). Expected: PASS.
- [ ] **Step 5: Commit** `feat(sponsor): derive archive groups and headings`.

---

### Task 3: `ArchiveLoop` and the grouped loop template

**Files:**

- Create: `plugins/hyperpress-sponsor/src/ArchiveLoop.php`, `plugins/hyperpress-sponsor/templates/loop.php`
- Modify: `plugins/hyperpress-sponsor/templates/content.php` (title tag), `plugins/hyperpress-sponsor/src/Plugin.php` (boot)
- Test: `plugins/tests/integration/Sponsor/ArchiveLoopTest.php`, `plugins/tests/integration/Sponsor/GroupedLoopTest.php`

**Interfaces:**

- Consumes: `Grouping::keys`, `Grouping::headings`, `Ordering::ORDER_VAR`, `Ordering::is_season_scoped`, `Content::add_content_template` (sponsor entry markup).
- Produces:
  - `ArchiveLoop::register(): void` (called from `Plugin::boot()`): filter `hyperpress_posts_template` → `ArchiveLoop::posts_template( mixed $template ): mixed` (returns `dirname( __DIR__ ) . '/templates/loop.php'` when `is_post_type_archive( Plugin::POST_TYPE )`, main query, not feed, not admin; otherwise the argument unchanged); action `pre_get_posts` → `ArchiveLoop::set_page_size( WP_Query $query ): void` (main sponsor archive only, `posts_per_page` = `max( 1, (int) apply_filters( 'hyperpress_sponsor_archive_per_page', 24 ) )`).
  - `ArchiveLoop::previous_sponsor_id( WP_Query $main ): int`: `0` on page 1 or when none; otherwise the ID of the sponsor immediately before the first sponsor of the current page, found with a secondary query copying `$main->query`, with `ORDER_VAR` true, `fields` `ids`, `posts_per_page` 1, `offset` `( paged - 1 ) * posts_per_page - 1`, no `paged`, `no_found_rows` true.
  - `templates/loop.php`: for the main query; no posts → `get_template_part( 'template-parts/content', 'none' )`; otherwise loops with `the_post()`: gets `Grouping::keys( get_the_ID() )`, previous keys (the previous post in this page, or `previous_sponsor_id()` for the first post on a page > 1, or `null` on page 1), prints each heading from `Grouping::headings( ..., Ordering::is_season_scoped( $wp_query ), $is_page_start )` as `<{tag} class="sponsor-group-heading">` + `esc_html( text )`, sets `$hyperpress_title_tag = 'h4'`, includes the sponsor content template (`Content::add_content_template( '' )`), then prints `<div class="section-divider"><hr/></div>`; calls `wp_reset_postdata()` at the end.
  - `content.php` prints the entry title with the tag from `$hyperpress_title_tag` when set to one of `h2`..`h6`, else `h2` (current markup otherwise unchanged).

- [ ] **Step 1: Write the failing tests.**
  - `ArchiveLoopTest`: `test_filter_returns_the_loop_only_for_the_sponsor_archive_main_query` (path ends `templates/loop.php` and is readable on `go_to( '/?post_type=hyper_sponsor' )`; unchanged on another post type archive, on the sponsor feed `/?post_type=hyper_sponsor&feed=rss2`, and in admin); `test_page_size_defaults_to_24_and_is_filterable` (assert `$wp_query->get( 'posts_per_page' )` is 24; with the filter returning 2 it is 2; a secondary sponsor query and the sponsor feed are untouched); `test_previous_sponsor_id` (page 1 → 0; with 5 sponsors and page size 2, page 2 → the ID of the 2nd sponsor in order, page 3 → the 4th; season-scoped archive page 2 uses the scoped order).
  - `GroupedLoopTest` (render the loop: `ob_start(); include ArchiveLoop::posts_template( '' ); $html = ob_get_clean();` after `go_to` the archive URL and `ArchiveLoop::register()`/`Content::register()` in `set_up`): headings in the right order and levels for the all-time archive (`Season 32` h2, `Level 4` h3, entries h4, "Other sponsors" last); the season-scoped archive prints no `h2` season heading; page 2 starting mid-group prints `Season 31 (continued)` and `Level 3 (continued)`; page 2 starting at a boundary prints no `(continued)`; page 1 never prints it; a term named `A <b>"B"</b>` is escaped in the heading; an archive with no sponsors prints the theme "nothing found" content and no heading; the sponsor content template still prints `<h2` titles when included outside the loop (no `$hyperpress_title_tag` set).
- [ ] **Step 2: Run to verify they fail.** `docker compose run --rm php composer test:integration -- --filter "ArchiveLoopTest|GroupedLoopTest"`. Expected: FAIL.
- [ ] **Step 3: Implement** `ArchiveLoop`, `templates/loop.php`, the `content.php` title tag, and call `ArchiveLoop::register();` from `Plugin::boot()` (after `Ordering::register();`). Keep `loop.php` thin: every decision lives in `Grouping`/`ArchiveLoop`.
- [ ] **Step 4: Run to verify they pass** (`--filter "ArchiveLoopTest|GroupedLoopTest"`, then `--filter Sponsor`). Expected: PASS, and the existing sponsor content-template, widget, archive and ordering tests unchanged.
- [ ] **Step 5: Commit** `feat(sponsor): group the sponsor archive by season and tier`.

---

### Task 4: Verification and close-out

**Files:**

- Modify: `docs/superpowers/plans/2026-10-02-plugins-04-remaining-plugins.md` (one dated bullet), this plan (banner, notes)

- [ ] **Step 1: Full verification.** From `plugins/`: `docker compose run --rm php composer lint` (0 violations), `composer test:unit`, `composer test:integration`. Expected: all green.
- [ ] **Step 2: Live check (non-destructive) on the hyperonline stack** (`cd docker/hyperonline && docker compose --profile local up -d`; WordPress on http://localhost:4080; wpcli: `docker compose --profile local run --rm wpcli wp ...`). Create 30 temporary published sponsors `zz-group-NN` spread over seasons 30-32 (create a missing season term only if absent and remove it afterwards), tiers Level 1-4 and some without; `curl -s http://localhost:4080/sponsors/` and `/sponsors/page/2/`, and `/seasons/season-31/sponsors/`; report the heading sequence (`grep -o '<h[234][^>]*>[^<]*'`), that page 2 repeats headings with "(continued)" when it starts mid-group, that the scoped archive has no season `h2`, that the page has pagination, and that titles are `h4`. Then delete every temporary post (and any temporary term) and confirm none remain. Do not change existing content.
- [ ] **Step 3: Close out.** Append to the plugins-04 plan's Task 3 sponsor bullet area one dated bullet ("2026-10-19: the sponsor archive is grouped by season and tier with continued headings across pages (`HyperPress\Sponsor\ArchiveLoop`, `Grouping`)"); add `> **STATUS: ✅ COMPLETED** (YYYY-MM-DD). <what passed / commit range>` at the top of this plan; `pnpm exec oxfmt --check` the edited docs (load Node first: `source ~/.nvm/nvm.sh && nvm use && export PATH="$HOME/.local/share/pnpm/bin:$PATH"`). Commit: `chore(sponsor): note the grouped sponsor archive`.

---

## Known follow-ups

- A stored tier rank (term meta) would replace the slug-number rule shared by `Ordering` (SQL) and `tier_compare()` (PHP).
- Multi-season sponsors appear only under their newest season; listing a sponsor under every season it supported would need a different query.
