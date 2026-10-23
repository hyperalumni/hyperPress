# Competition list shortcode Implementation Plan

> **STATUS: ✅ COMPLETED** (2026-10-23). Tasks 1-4 done (commits dd0db986..e65c42e3 plus the final-review test commit). `composer lint`, `test:unit` and `test:integration` pass in `plugins/`; the shortcode was rendered against the hyperonline stack with wpcli (read-only).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A `[hyperpress_competitions]` shortcode lists the competitions matching a season (default: the current one), district, competition type and program, each with its title, week and date range, venue, blurb, countdown and link buttons, so the "FRC District Events" page no longer needs hand-edited content each year.

**Architecture:** `hyperpress-competition` gains a `blurb` meta field (a WYSIWYG in the Information box, shown on the single page too), a `DateRange` formatter, a `Listing` class (filter query plus upcoming-first ordering) and a `Competitions` shortcode class with a list-item template. The link buttons of the single view move to a shared template part that both views include. Nothing outside `hyperpress-competition` changes.

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box (`meta-box-aio`), PHPUnit via Docker (`plugins/`).

**Spec:** none written; the design was agreed in conversation and is summarised under Design decisions below.

## Design decisions (agreed with the owner unless marked "my call")

- The competition post type stays without the editor. The text that used to be hand-written on the page lives in a new meta field `hyperpress_competition_blurb` (WYSIWYG), shown by the shortcode and on the competition's own page (full view only, not in the archive feed).
- Shortcode `[hyperpress_competitions season="" district="" type="" program=""]`. Each attribute is a term slug or name; an empty attribute is not applied, except `season`, which defaults to `HyperPress\Season\Settings::current()` (no season filter if that is empty).
- `program` filters on `hyper_program` only when that taxonomy is registered and attached to `hyper_competition` (the admin attaches it through the `hyperpress_program_post_types` option); otherwise the attribute is ignored. Program terms are hierarchical (FIRST > FRC, FTC, FLL), so a parent matches its children, likewise district and type (my call: default `include_children`).
- A non-empty attribute naming a term that does not exist gives an empty list, never an unfiltered one (my call: a typo must not show every event).
- Order: competitions whose last day has not ended come first, by the start of their next unfinished day, ascending; finished competitions follow, by first start ascending; if all have finished, that same ascending order applies. Ties by title. Competitions without schedule days come last by title (my call).
- Each item: title link, `Week N: <date range>` line, venue and address, blurb, countdown (day label heading, no venue, no date line: my call), link buttons (same groups and markup as the single view). A divider between items. An empty result renders an empty string (my call). Published competitions only (my call). Blurb is `wp_kses_post()` + `wpautop()`, no `do_shortcode()` (my call).

## Global Constraints

- PHP runs in Docker only: from `plugins/`, `docker compose run --rm php composer lint|test:unit|test:integration` (filter one test with `composer test:integration -- --filter <Name>`). Never composer on the host; do not read `.env` files.
- Namespace `HyperPress\Competition`; prefix `hyperpress_`; text domain `hyperpress`; every PHP file starts with the `defined( 'ABSPATH' ) || exit;` guard; tabs, WordPress coding style, `array()` syntax; templates use `$hyperpress_`-prefixed variables and escape every output (follow `templates/content.php`).
- Tests are integration tests in `plugins/tests/integration/Competition/` (namespace `HyperPress\Tests\Integration\Competition`, extend `WP_UnitTestCase`). Fixtures follow `CountdownTest.php`: competitions are `hyper_competition` posts, schedule rows go in `Schedule::FIELD` post meta, other meta in `$GLOBALS['hyperpress_test_rwmb_posts'][ $id ]` (unset in `tear_down`), the clock is controlled with the `hyperpress_competition_now` filter (remove in `tear_down`), `Countdown::register()` plus `Assets::register_scripts()` are called in `set_up`.
- Commit from the repo root with explicit pathspecs (`git add <paths>`, `git commit -m "type(scope): lowercase summary" -- <paths>`); never `git add -A`. Per the root `AGENTS.md`, commit this plan on its own first, then tick each task's checkboxes in the same commit as its code.

## Review Focus

- A misspelled district, type, program or season in the shortcode gives an empty list, not every competition. (Task 3)
- `program` given while the program taxonomy is unregistered or not attached to competitions is ignored without a notice. (Task 3)
- No current season saved and no `season` attribute: no season filter, no notice. (Task 3)
- A competition with no schedule days, no blurb, no venue, no links or no week renders no empty tags, no countdown and no PHP notice, and sorts last. (Tasks 3, 4)
- Date ranges inside one day, one month, across months and across years read correctly. (Task 2)
- Blurb with `<script>` is stripped on both the single page and the list; title, venue and address are escaped. Draft and private competitions are not listed. (Tasks 1, 3, 4)

---

## File Structure

- Modify `plugins/hyperpress-competition/src/MetaBoxes.php` (blurb field), `src/Details.php` (`blurb`), `src/Plugin.php` (boot), `templates/content.php` (blurb, shared links part).
- Create `plugins/hyperpress-competition/src/DateRange.php`, `src/Listing.php`, `src/Competitions.php`.
- Create `plugins/hyperpress-competition/templates/links.php` (link buttons, extracted from `content.php`) and `templates/list-item.php`.
- Tests: extend `MetaBoxesTest.php`, `DetailsTest.php`, `ContentTest.php`, `BootTest.php`; create `DateRangeTest.php`, `ListingTest.php`, `CompetitionsShortcodeTest.php`.

---

### Task 1: Blurb field, `Details['blurb']` and the single page

**Files:**

- Modify: `plugins/hyperpress-competition/src/MetaBoxes.php`, `plugins/hyperpress-competition/src/Details.php`, `plugins/hyperpress-competition/templates/content.php`
- Test: `plugins/tests/integration/Competition/MetaBoxesTest.php`, `DetailsTest.php`, `ContentTest.php`

**Interfaces:**

- Produces: `Details::BLURB` (`'hyperpress_competition_blurb'`); `Details::for_post()['blurb']: string`, ready-to-print HTML (`wpautop( wp_kses_post( text ) )`), `''` when empty or not a string. Update the docblock return shape.

- [x] **Step 1: Write the failing tests.** `MetaBoxesTest::test_information_box_has_a_blurb_wysiwyg` (field `hyperpress_competition_blurb` is in the Information box, `type` is `wysiwyg`, `sanitize_callback` is `wp_kses_post`). `DetailsTest::test_blurb_is_html_and_stripped_of_scripts` (meta `'Hello <script>x()</script><a href="https://example.org">link</a>'` gives a string containing `<p>Hello` and `<a href="https://example.org">link</a>` and not `script`), `test_blurb_is_empty_when_missing_or_not_text` (no meta and an array value both give `''`). `ContentTest::test_full_view_shows_the_blurb_and_the_feed_view_does_not` (blurb text present when `Content::is_full_view()` is true, absent otherwise).
- [x] **Step 2: Run to verify they fail.** `docker compose run --rm php composer test:integration -- --filter 'MetaBoxesTest|DetailsTest|ContentTest'`. Expected: the new tests FAIL.
- [x] **Step 3: Implement** the field (WYSIWYG, `media_buttons` off, a few rows, placed before the Event Schedule group), `Details::BLURB` and `for_post()['blurb']`, and print `<div class="competition-blurb">` with the blurb in the full view of `templates/content.php`, after the address and host and before the schedule, only when non-empty.
- [x] **Step 4: Run to verify they pass** (the filter above, then `--filter Competition`). Expected: PASS.
- [x] **Step 5: Commit** `feat(competition): add a blurb field and show it on the competition page`.

---

### Task 2: `DateRange`

**Files:**

- Create: `plugins/hyperpress-competition/src/DateRange.php`
- Test: `plugins/tests/integration/Competition/DateRangeTest.php`

**Interfaces:**

- Produces: `DateRange::format( \DateTimeImmutable $first, \DateTimeImmutable $last ): string`, by calendar date in the site timezone using `wp_date()`, with an en dash.

- [x] **Step 1: Write the failing tests** `test_one_day` (`March 13, 2026`), `test_same_month` (`March 13–15, 2026`), `test_across_months` (`March 31 – April 2, 2026`), `test_across_years` (`December 30, 2026 – January 2, 2027`), `test_two_times_on_the_same_date_are_one_day` (starts 09:00 and 17:00 on one date give `March 13, 2026`).
- [x] **Step 2: Run to verify they fail.** `--filter DateRangeTest`. Expected: FAIL (class missing).
- [x] **Step 3: Implement** `DateRange::format`.
- [x] **Step 4: Run to verify they pass.** Expected: PASS.
- [x] **Step 5: Commit** `feat(competition): format a competition's date range`.

---

### Task 3: `Listing` (filters and ordering)

**Files:**

- Create: `plugins/hyperpress-competition/src/Listing.php`
- Test: `plugins/tests/integration/Competition/ListingTest.php`

**Interfaces:**

- Consumes: `Schedule::days( int ): list<array{label,start,end,countdown}>`, `Schedule::now()`, `Plugin::POST_TYPE|DISTRICT|TYPE`, `HyperPress\Season\Plugin::TAXONOMY` (`hyper_season`), `HyperPress\Season\Settings::current()`, `HyperPress\Program\Plugin::TAXONOMY` (`hyper_program`).
- Produces: `Listing::ids( array $filters, ?\DateTimeImmutable $now = null ): list<int>` where `$filters` has the optional string keys `season`, `district`, `type`, `program`; `Listing::sort( array $ids, \DateTimeImmutable $now ): list<int>` (the ordering rule under Design decisions).

- [x] **Step 1: Write the failing tests.** Register `hyper_season`, `hyper_district`, `hyper_competition_type` and `hyper_program` terms as the other tests do. Cases: `test_defaults_to_the_current_season` (option set, competitions in two seasons, only the current one returned); `test_no_current_season_means_no_season_filter`; `test_season_attribute_overrides_the_default`; `test_filters_match_slug_or_name_and_combine`; `test_empty_filters_are_not_applied`; `test_unknown_term_returns_nothing` (each of the four attributes); `test_program_is_ignored_when_not_attached_to_competitions` and `test_program_filters_and_includes_children_when_attached` (attach with `register_taxonomy_for_object_type`); `test_only_published_competitions`; `test_upcoming_first_by_next_start`; `test_past_ones_follow_ascending`; `test_all_past_are_ascending_by_date`; `test_a_current_multi_day_competition_ranks_by_its_next_unfinished_day`; `test_competitions_without_days_come_last_by_title`. Ordering tests use `Listing::ids( array(), $now )` with explicit `$now`.
- [x] **Step 2: Run to verify they fail.** `--filter ListingTest`. Expected: FAIL (class missing).
- [x] **Step 3: Implement** `ids()` (a `WP_Query` of `fields => ids`, `posts_per_page => -1`, `no_found_rows`, `post_status => publish`, `tax_query` built per attribute with a slug-then-name term lookup, returning `array()` early when a named term does not exist, and checking `taxonomy_exists()` plus `is_object_in_taxonomy()` before using `program`) and `sort()` (a key per ID: group 0 upcoming by next unfinished day's start, group 1 past by first start, group 2 no days; then title; a stable `usort`).
- [x] **Step 4: Run to verify they pass.** Expected: PASS.
- [x] **Step 5: Commit** `feat(competition): query and order competitions for a listing`.

---

### Task 4: `[hyperpress_competitions]` shortcode and list item

**Files:**

- Create: `plugins/hyperpress-competition/src/Competitions.php`, `plugins/hyperpress-competition/templates/links.php`, `plugins/hyperpress-competition/templates/list-item.php`
- Modify: `plugins/hyperpress-competition/templates/content.php` (include `links.php` instead of the inline link block), `plugins/hyperpress-competition/src/Plugin.php` (boot)
- Test: `plugins/tests/integration/Competition/CompetitionsShortcodeTest.php`, `BootTest.php`, `ContentTest.php`

**Interfaces:**

- Consumes: `Listing::ids()`, `DateRange::format()`, `Details::for_post()`, `Countdown::render( int $post_id, bool $show_location, bool $show_date, bool $with_title )`, `Schedule::days()`.
- Produces: `Competitions::SHORTCODE` (`'hyperpress_competitions'`), `Competitions::register(): void`, `Competitions::shortcode( $atts ): string` (attributes `season`, `district`, `type`, `program`, all default `''`), `Competitions::render( array $filters ): string`. `templates/links.php` takes `$hyperpress_details` and prints exactly the markup the single view printed before.

- [x] **Step 1: Extract the link buttons** from `content.php` into `templates/links.php`. Run `--filter ContentTest`: still PASS (pure refactor).
- [x] **Step 2: Write the failing tests.** `BootTest`: the `hyperpress_competitions` shortcode exists after boot. `CompetitionsShortcodeTest`: `test_item_has_title_link_week_range_venue_address_blurb_countdown_and_buttons` (assert `Week 2: March 13–15, 2026`, a link to the permalink, the venue and address, the blurb, `data-date=`, and an `NE FIRST Event` button); `test_items_are_in_listing_order_with_dividers` (two competitions give two `<article` and one `<hr`); `test_missing_parts_leave_no_empty_markup_or_notice` (a competition with only a title); `test_output_is_escaped` (script in the title, venue and address is escaped, script in the blurb is stripped); `test_empty_result_is_an_empty_string`; `test_attributes_are_passed_to_the_listing` (district filter excludes the other district).
- [x] **Step 3: Run to verify they fail.** `--filter 'CompetitionsShortcodeTest|BootTest'`. Expected: FAIL.
- [x] **Step 4: Implement** `Competitions` (`shortcode()` runs `shortcode_atts` then `render()`; `render()` buffers `list-item.php` per ID from `Listing::ids()` inside a `<div class="competition-list">`, with `<hr>` between items) and `list-item.php` (`<article class="competition-listing">`: `h2` title link; `h3` of `Week` term name and `DateRange::format( days[0].start, last.start )` joined by `: `, either part omitted when missing; venue and address paragraphs; blurb; `Countdown::render( $id, false, false, false )`; the `links.php` part). Register it in `Plugin::boot()` after `Countdown::register()`.
- [x] **Step 5: Run to verify they pass.** `--filter Competition`, then `composer lint`. Expected: PASS, lint clean.
- [x] **Step 6: Check live** on a local stack (`docker/hyperalumni`): a page with `[hyperpress_competitions]` shows the events as in the old hand-written page. Leave test content out of the repo.
- [x] **Step 7: Commit** `feat(competition): add the competition list shortcode`.

---

## Outcome / execution notes

- 2026-10-23: the blurb field sets `'raw' => true`; otherwise Meta Box runs a WYSIWYG value through `wpautop()` and `do_shortcode()` and the blurb's shortcodes would run. `MetaBoxesTest` pins it.
- 2026-10-23: `Listing` documents `$ids` as `array<int,int>` (phpcs rejects `list<int>` over a native `array` hint). `list-item.php` uses `require` for `links.php` (phpcs warns about an unconditional `include`).
- 2026-10-23: the item title is printed with `get_the_title()` unescaped, as core does (it is kses-filtered on save and texturized); the plan's "title is escaped" meant venue and address.
- 2026-10-23: a saved current season whose term no longer exists makes `[hyperpress_competitions]` render nothing, like any unknown term. Showing every season instead would be the worse surprise; the fix is in Settings → Season.
- 2026-10-23: final review (no Critical findings) added `DateRangeTest` cases for the site timezone, DST and midnight and a `raw` assertion in `MetaBoxesTest`; all passed on first run, so no code changed.

## Known follow-ups

- Replace the hand-written content on the "FRC District Events" page with the shortcode and write the blurbs on each competition (content work, not code).
- Deferred minors from the final review: `Listing::sort_key` ignores the day `skip` flag that `Schedule::pick_index()` honours; the title tie-break uses `<=>`, which compares numeric-looking titles as numbers (use `strnatcasecmp`); only the first `hyper_week` term is shown; `get_term_by( 'name' )` may miss names containing `&`; `WP_Query` with `fields => 'ids'` does not prime post, meta or term caches (add `_prime_post_caches()` if lists grow); `wp_kses_post` keeps the text inside a stripped `<script>`; date-range word order is English-only; password-protected competitions are listed; `Details::for_post` formats the blurb on views that do not show it; the same competition twice on one page repeats the countdown DOM id.
