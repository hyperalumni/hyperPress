# Front Page Countdown Selection Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** The front page shows exactly one countdown, chosen from candidates offered by plugins through one filter: the selected one-off countdowns and, automatically, competitions.

**Architecture:** `hyperpress-frontpage` owns a candidate filter, a pure `pick()` and a theme-facing HTML filter. `hyperpress-countdown` exposes a `Window` helper for a countdown's dates. `hyperpress-competition` offers a candidate per competition. The theme only prints what the HTML filter returns. A later `hyperpress-event` plugin just hooks the candidate filter.

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box, PHPUnit unit and integration tests in Docker (`plugins/` and `themes/hyperpress-foundation/`).

**Spec:** `docs/superpowers/specs/2026-11-02-plugins-frontpage-countdown-selection-design.md` (read it; the contract and rules below are copied from it).

## Global Constraints

- Plugin commands run from `plugins/`: `docker compose run --rm php composer test:unit|test:integration|lint`. Theme commands from `themes/hyperpress-foundation/`, same scripts. PHP never on the host. Plugin integration tests live in `plugins/tests/integration/` (namespace `HyperPress\Tests\Integration[\Sub]`), unit tests in `plugins/tests/unit/`. Verify with the full `composer test:integration` (a few existing tests are order dependent when run alone with `--filter`).
- Candidate shape (filter `hyperpress_frontpage_countdown_candidates`): `id` string, `start` `DateTimeImmutable`, `show_from` `?DateTimeImmutable`, `show_until` `?DateTimeImmutable`, `fallback_after` optional `?DateTimeImmutable`, `priority` int (default 10), `render` callable `(): string`.
- Window open iff `show_from < now` and `now < show_until`, both strict, each optional. Candidate is a fallback iff `fallback_after` is set and `now` is past it. Pick: earliest `start`, then highest `priority`, then lowest `id` among open non-fallbacks; otherwise the open fallback with the latest `start`, then highest `priority`, then lowest `id`; otherwise none. Closed or not-yet-open candidates never displace a fallback.
- Theme filter: `hyperpress_frontpage_countdown_html` (string in, string out). The theme names no plugin, shortcode or post type.
- Times in `wp_timezone()`; no `strtotime()` / `current_time( 'timestamp' )` in new code. Text domain `hyperpress`. PHPCS WordPress standard, tabs. Plugins stay independent (guard cross-plugin classes with `class_exists`).
- Competition settings: `hyperpress_competition_frontpage` (checkbox) and `hyperpress_competition_frontpage_lead_days` (number 0 to 365, default 60, malformed gives 60). Meta Box drops an unticked checkbox's key, so once a settings row exists a missing key means off; with no saved row the default is on.
- Commit from the repo root with explicit paths; style `type(scope): lowercase summary`.

## Review Focus

- The front page must never print more than one countdown, nor a closed one, whatever a third-party filter returns (malformed candidates, a `render` that throws or returns a non-string, duplicate ids).
- When nothing qualifies, the theme prints no wrapper at all (no empty `entry-content`).
- A finished one-off countdown with no Hide After stays until the end of its day, then yields to any open candidate, and stays up (at zero) when none is open.
- The `[hyperpress_countdown]` shortcode behaves exactly as before: published and private posts, `ignoreshowhide`, hide-before and hide-after, an unparseable date.
- Each plugin works when the others are inactive (no `Window`, no competition plugin, no countdown plugin): no fatal, no output.
- End-of-day and lead-day maths in the site timezone, including a DST change and an event near midnight.

---

### Task 1: Selection logic in hyperpress-frontpage

**Files:**

- Create: `hyperpress-frontpage/src/Countdowns.php`
- Modify: `hyperpress-frontpage/src/Plugin.php` (call `Countdowns::register()` in `boot()`)
- Test: `tests/unit/Frontpage/CountdownsPickTest.php`, `tests/integration/Frontpage/CountdownsHtmlTest.php`

**Interfaces:**

- Produces: class `HyperPress\Frontpage\Countdowns` with `public const CANDIDATES_FILTER = 'hyperpress_frontpage_countdown_candidates'`, `public const HTML_FILTER = 'hyperpress_frontpage_countdown_html'`; `public static function register(): void` (adds `html` to `HTML_FILTER`); `public static function pick( array $candidates, \DateTimeImmutable $now ): ?array`; `public static function html( $html = '' ): string`; `public static function now(): \DateTimeImmutable` (site timezone, passed through the `hyperpress_frontpage_now` filter so tests can fix the clock, falling back to the real time when a filter returns something else).
- `html()` returns the chosen candidate's `render()` result when it is a string, otherwise the incoming `$html`; it catches `\Throwable` from `render`.

- [ ] **Step 1: Write failing unit tests** for `pick()` (candidates built with a small helper): `test_returns_null_for_an_empty_list`; `test_a_window_is_open_only_strictly_between_the_bounds` (before `show_from`, exactly `show_from`, inside, exactly `show_until`, after); `test_either_bound_may_be_missing`; `test_the_earliest_start_wins`; `test_priority_then_id_break_ties` (equal starts: higher priority wins; equal priority: lower id wins); `test_a_started_but_open_candidate_outranks_future_ones`; `test_a_candidate_past_its_fallback_after_loses_to_any_open_primary`; `test_a_fallback_is_shown_when_it_is_the_only_open_candidate`; `test_among_fallbacks_the_latest_start_wins`; `test_closed_or_not_yet_open_candidates_never_displace_a_fallback`; `test_a_future_fallback_after_keeps_the_candidate_primary`; `test_malformed_candidates_are_ignored` (not an array, missing `start`, string `start`, non-callable `render`, `show_from` that is a string, `fallback_after` that is an int).
- [ ] **Step 2: Run to verify failure.** `docker compose run --rm php composer test:unit`. Expected: new tests FAIL (class not found).
- [ ] **Step 3: Implement `Countdowns::pick`** as specified in Global Constraints (validate each candidate first, then filter, then partition, then sort with `usort` on `start->getTimestamp()`, priority, id).
- [ ] **Step 4: Write failing integration tests** for `html()`: `test_returns_the_chosen_candidates_html` (add a filter on `CANDIDATES_FILTER` returning one open candidate, fix the clock with `hyperpress_frontpage_now`); `test_returns_the_incoming_value_when_nothing_is_open`; `test_a_render_that_throws_or_returns_a_non_string_is_ignored` (returns the incoming value; no exception); `test_the_theme_filter_is_wired` (`apply_filters( Countdowns::HTML_FILTER, '' )` returns the candidate's HTML); `test_third_party_filter_returning_a_non_array_is_ignored`.
- [ ] **Step 5: Implement `register()`, `html()`, `now()`** and call `Countdowns::register()` from `Plugin::boot()`.
- [ ] **Step 6: Run tests and lint.** `composer test:unit`, `composer test:integration`, `composer lint`. Expected: PASS, exit 0.
- [ ] **Step 7: Commit** `feat(frontpage): pick one countdown from filtered candidates`.

### Task 2: Window helper in hyperpress-countdown

**Files:**

- Create: `hyperpress-countdown/src/Window.php`
- Modify: `hyperpress-countdown/src/Shortcode.php`
- Test: `tests/integration/Countdown/WindowTest.php` (new); the existing countdown shortcode tests (`CountdownShortcodeHardeningTest` and any under `tests/integration/Countdown/`) must pass unchanged.

**Interfaces:**

- Produces: `HyperPress\Countdown\Window` with `public static function is_renderable( $post ): bool` (what `Shortcode::is_renderable` does today: a `hyper_countdown` that is published, or private and readable by the current user); `public static function visibility( int $post_id ): array` returning `array{show_from:?\DateTimeImmutable,show_until:?\DateTimeImmutable}` from the Hide Before / Hide After fields (`hyperpress_countdown_hide_before` / `_hide_after`, read with `Meta::get`, parsed as wall-clock in `wp_timezone()`, empty or unparseable gives `null`); `public static function is_open( ?\DateTimeImmutable $from, ?\DateTimeImmutable $until, \DateTimeImmutable $now ): bool` (strict on both sides); `public static function for_post( int $post_id ): ?array` returning `array{start:\DateTimeImmutable,show_from:?\DateTimeImmutable,show_until:?\DateTimeImmutable}`, or `null` when the post is not renderable or its `hyperpress_countdown_event_date_time` has no parseable date.

- [ ] **Step 1: Write failing tests** in `WindowTest`: `for_post` returns the three values for a published countdown with all fields; `null` for a draft, a private post the user cannot read (and the array for a reader), a post of another type, a missing or garbage event date; empty Hide Before/After give `null` bounds; values are read in the site timezone (set `timezone_string` to a zone with a non-zero offset and compare `getOffset()`); `is_open` truth table for strictness and missing bounds.
- [ ] **Step 2: Run to verify failure.** `composer test:integration`. Expected: new tests FAIL.
- [ ] **Step 3: Implement `Window`** and change `Shortcode::add_countdown_shortcode` to use `Window::is_renderable()` and, for the show/hide decision, `Window::visibility()` + `Window::is_open()` with the current time from `new \DateTimeImmutable( 'now', wp_timezone() )`; `ignoreshowhide`, the event-date check, the name, location and past-zero handling stay as they are. Remove the now-unused private `is_renderable` from `Shortcode`.
- [ ] **Step 4: Run tests and lint.** Full `composer test:integration`, `composer test:unit`, `composer lint`. Expected: PASS, exit 0 (the existing shortcode tests prove parity).
- [ ] **Step 5: Commit** `feat(countdown): expose a countdown's visibility window`.

### Task 3: One-off countdown candidates

**Files:**

- Modify: `hyperpress-frontpage/src/Countdowns.php`
- Test: `tests/integration/Frontpage/CountdownCandidatesTest.php`

**Interfaces:**

- Consumes: `HyperPress\Countdown\Window::for_post( int ): ?array` (Task 2), `Settings::COUNTDOWNS` option key.
- Produces: `Countdowns::selected_countdown_candidates( $candidates ): array`, hooked on `CANDIDATES_FILTER` at priority 5 in `register()`. Each selected, valid countdown yields `id` `countdown-<ID>`, `start`, `show_from`, `show_until` = Hide After (or `null`), `fallback_after` = 23:59:59 of the start's date in `wp_timezone()` when Hide After is empty, else `null`, `priority` 10, `render` = `do_shortcode( '[hyperpress_countdown id="<ID>" /]' )`.

- [ ] **Step 1: Write failing tests**: IDs `array( '12', '7" x="y', 'abc', '0', 12 )` are sanitized with `absint`, zeros dropped, duplicates ignored (use real countdown posts with the matching IDs where needed); unpublished and non-countdown posts are skipped; fields map to the candidate (`show_until` from Hide After, `fallback_after` null when Hide After is set); with no Hide After `fallback_after` is 23:59:59 on the start's date, in a zone with DST (start on a spring-forward date) and for a start at 23:50; the option missing or not an array gives no candidates; the candidate's `render` returns the shortcode output (register a stub `hyperpress_countdown` shortcode if the real one is absent).
- [ ] **Step 2: Run to verify failure.** `composer test:integration`. Expected: FAIL.
- [ ] **Step 3: Implement** `selected_countdown_candidates` reading `get_option( Settings::OPTION )[ Settings::COUNTDOWNS ]` and hook it in `register()`.
- [ ] **Step 4: Run tests and lint.** Full integration, unit, lint. Expected: PASS, exit 0.
- [ ] **Step 5: Commit** `feat(frontpage): offer the selected countdowns as candidates`.

### Task 4: Competition candidates and settings

**Files:**

- Create: `hyperpress-competition/src/FrontpageCountdown.php`
- Modify: `hyperpress-competition/src/Settings.php`, `hyperpress-competition/src/MetaBoxes.php`, `hyperpress-competition/src/Plugin.php` (`FrontpageCountdown::register()` in `boot()`)
- Test: `tests/integration/Competition/FrontpageCountdownTest.php`, `tests/integration/Competition/FrontpageSettingTest.php`

**Interfaces:**

- Produces: `Settings::FRONTPAGE`, `Settings::FRONTPAGE_LEAD_DAYS`, `Settings::FRONTPAGE_LEAD_DAYS_DEFAULT = 60`, `Settings::frontpage_enabled(): bool`, `Settings::frontpage_lead_days(): int`, `Settings::sanitize_lead_days( $value )` (int 0 to 365 as a string, else `''` so Meta Box drops the key); `defaults()` gains `FRONTPAGE => true` and the lead days default. `FrontpageCountdown::register(): void` adds `candidates` to `hyperpress_frontpage_countdown_candidates` at priority 10; `FrontpageCountdown::candidates( $candidates ): array`.
- A candidate per published `hyper_competition` having at least one counted day: `id` `competition-<ID>`; `start` = `Schedule::current( $id )['start']`; `show_from` = first counted day's `start` minus `frontpage_lead_days()` days; `show_until` = last counted day's `end`; `priority` 10; no `fallback_after`; `render` = `static fn() => Countdown::render( $id, true, false, true )`. Private, draft and day-less competitions are skipped. Disabled setting returns the input untouched.

- [ ] **Step 1: Write failing tests** for settings (`FrontpageSettingTest`, modelled on `DayEndSettingTest`): defaults (on, 60); a saved row without the checkbox key is off; malformed lead days (`''`, `'x'`, `'-1'`, `'366'`, `'1.5'`) give 60 and sanitize to `''`; valid values (`'0'`, `'45'`, `'365'`) are kept; the two fields exist in the competition settings box with the right types. Tests for candidates (`FrontpageCountdownTest`, clock fixed with the `hyperpress_competition_now` filter): window opens exactly lead-days before the first counted day (assert `show_from` and, through `Countdowns::pick`, that it is closed a second before and open a second after); closes at the last counted day's end; a multi-day competition's `start` moves to the next counted day once a day ends; a day with `skip` is not counted; skipped when the setting is off, when there is no counted day, when draft or private; `render` output contains the competition's title; the filter leaves earlier candidates in the list and appends.
- [ ] **Step 2: Run to verify failure.** `composer test:integration`. Expected: FAIL.
- [ ] **Step 3: Implement settings and meta box fields** (checkbox "Show on the front page" with a description; number field "Front page lead time (days)" with `min` 0, `max` 365, `std` 60, `sanitize_callback` `Settings::sanitize_lead_days`), add the defaults, and `frontpage_enabled()` / `frontpage_lead_days()` following `past_zero()` / `day_end()`.
- [ ] **Step 4: Implement `FrontpageCountdown`** per the interface; query IDs with `get_posts( array( 'post_type' => 'hyper_competition', 'post_status' => 'publish', 'fields' => 'ids', 'numberposts' => -1, 'no_found_rows' => true ) )`.
- [ ] **Step 5: Run tests and lint.** Full integration, unit, lint. Expected: PASS, exit 0.
- [ ] **Step 6: Commit** `feat(competition): offer competitions to the front page countdown`.

### Task 5: Theme and documentation

**Files:**

- Modify: `themes/hyperpress-foundation/front-page.php`, `themes/hyperpress-foundation/AGENTS.md`, `plugins/AGENTS.md`
- Test: `themes/hyperpress-foundation/tests/integration/FrontPageContentTemplateTest.php`

**Interfaces:**

- Consumes: `apply_filters( 'hyperpress_frontpage_countdown_html', '' )` (Task 1).

- [ ] **Step 1: Write failing tests** in the theme's `FrontPageContentTemplateTest`, replacing `test_front_page_only_passes_integer_countdown_ids_to_the_shortcode` (its ID sanitising now lives in Task 3): `test_front_page_prints_the_countdown_html_from_the_filter_in_the_entry_content` (a filter returning `<p class="cd-test">x</p>` on `hyperpress_frontpage_countdown_html`; the HTML appears inside `.entry-content`); `test_front_page_prints_no_countdown_wrapper_when_the_filter_returns_nothing` (no `entry-content` block from that section; assert via the surrounding markup the template prints today for the countdown block); keep `test_front_page_prints_no_literal_shortcode_when_the_countdown_plugin_is_inactive` passing.
- [ ] **Step 2: Run to verify failure.** From the theme dir: `docker compose run --rm php composer test:integration -- --filter FrontPageContentTemplateTest`. Expected: FAIL.
- [ ] **Step 3: Update `front-page.php`**: replace the `hyperpress_frontpage_setting( 'hyperpress_frontpage_countdowns' ...)` block and its `foreach`/`do_shortcode` loop with `$hyperpress_countdown = apply_filters( 'hyperpress_frontpage_countdown_html', '' );` and print the existing wrapper (post loop, `post_class`, `hyperpress_page_before_entry_content`, `entry-content`, `hyperpress_page_after_entry_content`) only when the string is non-empty, echoing it as the plugin returned it (add the same kind of `phpcs:ignore` the old `echo do_shortcode` needed, if lint asks).
- [ ] **Step 4: Document.** Theme `AGENTS.md`: add `hyperpress_frontpage_countdown_html` to the filter list in "Direction". `plugins/AGENTS.md`: a bullet describing the candidate contract (keys, window rule, fallback rule, pick order, who supplies what, and that a future event plugin hooks `hyperpress_frontpage_countdown_candidates`).
- [ ] **Step 5: Run tests and lint.** Theme: `composer test:integration`, `composer test:unit`, `composer lint`. Plugins: full suites once more. Expected: PASS, exit 0.
- [ ] **Step 6: Commit** `feat(theme-foundation): print the front page countdown from a filter`.

### Task 6: Check it on the hyperonline site and close out

**Files:** this plan.

- [ ] **Step 1: Look at it.** With the hyperonline stack running (`cd docker/hyperonline && docker compose --profile local up -d`), open `http://localhost:4080/` in the browser tool (the owner is logged in). Create temporary test data with WP-CLI (`docker compose --profile local run --rm -T wpcli wp ...`): two published countdown posts with future dates and no Hide After, selected in the Front Page "Countdowns" field, and the competition front-page setting ticked. Confirm: exactly one countdown renders and it is the one with the sooner date; after moving the sooner one's date into the past (it has no Hide After) it still shows until the end of its day; once that day is over and the other countdown is open, the other one shows; with only the finished one left it still shows at zero. Competitions cannot be forced into their window without changing their dates, so check them with the integration tests and, if hyperonline has a competition within 60 days, on the page. Remove the temporary posts and restore every option afterwards, and report what was and was not observed.
- [ ] **Step 2: Close the plan.** Run every suite once more (plugins and theme), add a `STATUS: ✅ COMPLETED` banner with what passed, commit `docs(plans): complete front page countdown selection plan`.

## Known follow-ups

- The future `hyperpress-event` plugin hooks `hyperpress_frontpage_countdown_candidates` (and may add `fallback_after` or a priority); no change to the theme or `hyperpress-frontpage`.
- An admin list column or notice flagging finished countdowns without a Hide After.
