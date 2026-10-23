# Competition templates Implementation Plan

> **STATUS: ✅ COMPLETED** (2026-10-19). Tasks 1-7 done and reviewed (commits 73811fb..c8da8ff), plus a final-review fix wave. Lint, unit and integration suites pass in `plugins/` and the theme. One item is pending for the owner: save a start date through the admin UI and confirm the stored text is `YYYY-MM-DD HH:MM` (see Execution notes).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A single `hyper_competition` page shows a full-width map under the banner, a live countdown and the competition details; archive and list entries show a short summary with no map.

**Architecture:** The countdown plugin gets a public `Renderer` (instant in, markup out) that the existing shortcode now calls. The competition plugin stores its start date as site-timezone wall-clock text (like the countdown), supplies a `content_template` override (full view on its own page, summary elsewhere), a countdown shortcode, and a map hooked into one new generic action in the theme's `single.php`.

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box (`rwmb_meta`, `RWMB_OSM_Field`), TimeCircles, PHPUnit via Docker (`plugins/` and the theme).

**Spec:** `docs/superpowers/specs/2026-10-18-competition-templates-design.md`

## Global Constraints

- PHP runs in Docker only: from `plugins/` or `themes/hyperpress-foundation/`, `docker compose run --rm php composer lint|test:unit|test:integration`. Never composer on the host. Filter one test with `docker compose run --rm php composer test:integration -- --filter <Name>`.
- Prefix new code `hyperpress_`; namespaces `HyperPress\Competition` and `HyperPress\Countdown`; text domain `hyperpress`; every PHP file starts with the `defined( 'ABSPATH' ) || exit;` guard (the existing files in a namespace that unit tests load use `|| ( defined( 'HYPERPRESS_TESTING' ) || exit )`).
- Keep the theme's plugin-facing hooks unchanged: `hyperpress_banner_content`, `hyperpress_breadcrumbs_content`, `content_template`.
- Site timezone is the named zone `America/New_York`; dates are wall-clock text read with `wp_timezone()`.
- Plugin header `Requires Plugins` for competition becomes `hyperpress-utils, hyperpress-season, hyperpress-robot, hyperpress-countdown, meta-box-aio`.
- Existing `[hyperpress_countdown]` output must not change: `plugins/tests/integration/CountdownShortcodeHardeningTest.php` stays green and unedited.
- Commit from the repo root: `git add <paths>` then `git commit -m "type(scope): lowercase summary" -- <paths>`. Never `git add -A`. Per the root `AGENTS.md`, commit this plan on its own first, then tick the checkboxes in the same commit as each task's code.
- Foundation theme only; nothing in `themes/hyperpress-uikit`.

## Review Focus

- A competition with no start date (or unparseable text): no countdown, no date line, no PHP notice. (Task 2, Task 4, Task 5)
- A start date in the past: the countdown block still renders (zeros), and the settings flag is passed through. (Task 4)
- Venue/host/link text containing HTML or quotes is escaped; a link with an empty URL is skipped; external competition links get `target="_blank"` and `rel="noopener noreferrer"`; links to this site get `target="_self"` and no `rel`, except internal PDFs, which open in a new tab like external links. (Task 5)
- A competition inside another page's loop (`is_singular()` true but the queried object is a different post) gets the summary, never the full view or the map. (Task 5, Task 6)
- A map value that is garbage, out of range or empty, a missing address, or Meta Box missing: no map output and no error; archives never output a map. (Task 6)
- Dates across daylight saving (winter EST and summer EDT) land on the correct instant. (Task 2)

---

## File Structure

- Create `plugins/hyperpress-countdown/src/Renderer.php`: the countdown markup and inline init script, moved out of `Shortcode`.
- Modify `plugins/hyperpress-countdown/src/Shortcode.php`: loads its post, applies the show/hide window, calls `Renderer`.
- Create `plugins/hyperpress-competition/src/StartDate.php`: the one place that reads the start-date field as a `DateTimeImmutable`.
- Create `plugins/hyperpress-competition/src/Countdown.php`: competition countdown (function and `[hyperpress_competition_countdown]` shortcode).
- Create `plugins/hyperpress-competition/src/Details.php`: sanitized, structured view data for a competition.
- Create `plugins/hyperpress-competition/src/Content.php` and `plugins/hyperpress-competition/templates/content.php`: the `content_template` override and the full/summary markup.
- Create `plugins/hyperpress-competition/src/Map.php`: the map under the banner.
- Modify `plugins/hyperpress-competition/src/Settings.php`, `src/MetaBoxes.php`, `src/Plugin.php`, `hyperpress-competition.php`.
- Modify `themes/hyperpress-foundation/single.php`, `themes/hyperpress-foundation/AGENTS.md`, `themes/hyperpress-foundation/tests/unit/PluginHooksContractTest.php`.
- Modify `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md` (competition `Requires` row).
- Tests: `plugins/tests/integration/Countdown/RendererTest.php`, `plugins/tests/integration/Competition/{StartDateTest,PastZeroSettingTest,CountdownTest,DetailsTest,ContentTest,MapTest}.php`, `themes/hyperpress-foundation/tests/unit/SingleTemplateHookTest.php`.

Test conventions (from the existing suites): integration tests extend `WP_UnitTestCase`, namespace `HyperPress\Tests\Integration\Competition` (or `\Countdown`); Meta Box is stubbed, so set `$GLOBALS['hyperpress_test_rwmb'][ $key ]` (any post) or `$GLOBALS['hyperpress_test_rwmb_posts'][ $post_id ][ $key ]` (one post) and unset them in `tear_down()`; `RWMB_OSM_Field::$calls` records each `render_map( $location, $args )` call; the countdown script handle is `hyperpress-countdown-timecircles` and its inline JS is `implode( "\n", (array) wp_scripts()->get_data( $handle, 'after' ) )`. Plugins load in the integration bootstrap automatically.

---

### Task 1: Countdown `Renderer`

**Files:**

- Create: `plugins/hyperpress-countdown/src/Renderer.php`
- Modify: `plugins/hyperpress-countdown/src/Shortcode.php` (lines 29-44 `color`, 99-186 render body)
- Test: `plugins/tests/integration/Countdown/RendererTest.php`

**Interfaces:**

- Produces: `HyperPress\Countdown\Renderer::render( \DateTimeImmutable $when, array $args = array() ): string`. `$args` keys, all optional: `name` (string, default `''`), `location` (string, `''`), `past_zero` (bool, `false`), `slug` (string, `'countdown'`, used for the element id `slug-<wp_unique_id()>`), `show_date` (bool, `true`; the `<h4><time>` date line). It enqueues `hyperpress-countdown-timecircles` script and style and adds the inline init script, exactly like today.

- [x] **Step 1: Write the failing test** in `RendererTest` (class `RendererTest`, `set_up` resets `$GLOBALS['wp_scripts'] = null` and calls `Assets::register_scripts()`):
  - `test_marks_up_the_instant_with_offset`: `Renderer::render( new \DateTimeImmutable( '2030-07-01 10:00', new \DateTimeZone( 'America/New_York' ) ), array( 'name' => 'Big Game', 'location' => 'Field 1' ) )` contains `data-date="2030-07-01T10:00:00-04:00"`, `<h2 class="cell align-center">Big Game</h2>`, `<h4 class="cell align-center">Field 1</h4>`, `<section class="grid-x countdown align-center">`.
  - `test_winter_instant_uses_est_offset`: the same with `2030-01-01 10:00` contains `data-date="2030-01-01T10:00:00-05:00"`.
  - `test_show_date_false_omits_the_time_line`: output does not contain `<time>`; with the default it contains `<time>Monday July 01, 2030 @ 10:00AM</time>`.
  - `test_past_zero_flag_reaches_the_init_script`: with `past_zero => true` the inline JS contains `"count_past_zero": true`, with the default `"count_past_zero": false`.
  - `test_enqueues_assets_and_slug_prefixes_the_element_id`: after rendering with `slug => 'competition-7'`, `wp_script_is( 'hyperpress-countdown-timecircles', 'enqueued' )` is true and the output matches `/id="competition-7-\d+"/`.

- [x] **Step 2: Run to verify it fails.** From `plugins/`: `docker compose run --rm php composer test:integration -- --filter RendererTest`. Expected: FAIL, class `Renderer` not found.

- [x] **Step 3: Implement `Renderer::render`** in `plugins/hyperpress-countdown/src/Renderer.php` by moving the markup, the `color()` helper and the inline script from `Shortcode::add_countdown_shortcode` (lines 29-44 and 104-186) verbatim, taking `data-date` from `$when->format( 'c' )` and the date line from `$when->format( 'l F d, Y @ h:iA' )`; honour `show_date`. No behaviour change otherwise.

- [x] **Step 4: Run to verify it passes.** Same command. Expected: PASS.

- [x] **Step 5: Refactor `Shortcode::add_countdown_shortcode`** to keep everything up to and including the `strtotime( $event_date )` check (lines 46-102), then build `new \DateTimeImmutable( $event_date, wp_timezone() )` and `return Renderer::render( $when, array( 'name' => $event_name, 'location' => $event_location, 'past_zero' => $count_past_zero, 'slug' => $slug ) );`. Remove the now-unused `color()` from `Shortcode`.

- [x] **Step 6: Verify the regression suite.** `docker compose run --rm php composer test:integration -- --filter "Countdown"`. Expected: `CountdownShortcodeHardeningTest` and `RendererTest` PASS with the hardening test unedited.

- [x] **Step 7: Commit.** `git add plugins/hyperpress-countdown/src/Renderer.php plugins/hyperpress-countdown/src/Shortcode.php plugins/tests/integration/Countdown/RendererTest.php docs/superpowers/plans/2026-10-18-competition-templates.md` then `git commit -m "refactor(countdown): extract a public renderer from the shortcode" -- <same paths>`.

---

### Task 2: Start date stored as wall-clock text, and `StartDate`

**Files:**

- Modify: `plugins/hyperpress-competition/src/MetaBoxes.php:93-99`
- Create: `plugins/hyperpress-competition/src/StartDate.php`
- Test: `plugins/tests/integration/Competition/StartDateTest.php`

**Interfaces:**

- Produces: `HyperPress\Competition\StartDate::instant( int $post_id ): ?\DateTimeImmutable`: reads `hyperpress_competition_event_start_date` through `HyperPress\Utils\Meta::get`, returns the text read in `wp_timezone()`, or `null` for empty, non-string or unparseable values. Also `StartDate::FIELD` (`'hyperpress_competition_event_start_date'`).

- [x] **Step 1: Write the failing tests** in `StartDateTest` (`set_up` does `update_option( 'timezone_string', 'America/New_York' )`; `tear_down` unsets the meta globals):
  - `test_summer_text_is_an_edt_instant`: meta `2030-07-01 10:00` gives `$instant->format( 'c' ) === '2030-07-01T10:00:00-04:00'` and `$instant->getTimestamp() === ( new \DateTimeImmutable( '2030-07-01T14:00:00+00:00' ) )->getTimestamp()`.
  - `test_winter_text_is_an_est_instant`: `2030-01-01 10:00` gives `2030-01-01T10:00:00-05:00`.
  - `test_empty_or_invalid_text_gives_null` (data provider: `''`, `'not a date'`, `array()`, `0`): returns `null`.
  - `test_start_field_stores_wall_clock_text_in_an_explicit_format`: find the field with `id` `StartDate::FIELD` in `apply_filters( 'rwmb_meta_boxes', array() )` (the box id `hyperpress_competition_information`) and assert `$field['timestamp'] === false`, `$field['save_format'] === 'Y-m-d H:i'` and that no `format` key exists.

- [x] **Step 2: Run to verify it fails.** `docker compose run --rm php composer test:integration -- --filter StartDateTest`. Expected: FAIL (class missing).

- [x] **Step 3: Implement** `StartDate::instant` with `new \DateTimeImmutable( $text, wp_timezone() )` in a try/catch for `\Exception`, rejecting non-strings and empty text. Change the field in `MetaBoxes.php` to `'timestamp' => false` and add `'save_format' => 'Y-m-d H:i'` (the stored text format is explicit; the countdown field at `plugins/hyperpress-countdown/src/MetaBoxes.php:42-51` is the model for wall-clock text).

- [x] **Step 4: Run to verify it passes.** Expected: PASS.

- [x] **Step 5: Commit** `StartDate.php`, `MetaBoxes.php`, the test and the plan: `feat(competition): store the start date as site-timezone text`.

---

### Task 3: Count-past-zero setting

**Files:**

- Modify: `plugins/hyperpress-competition/src/Settings.php`, `plugins/hyperpress-competition/src/MetaBoxes.php:25-43` (settings box)
- Test: `plugins/tests/integration/Competition/PastZeroSettingTest.php`

**Interfaces:**

- Produces: `Settings::PAST_ZERO = 'hyperpress_competition_countdown_past_zero'`; `Settings::past_zero(): bool`; `Settings::defaults()` gains `PAST_ZERO => false`.
- Consumes: the `Settings::OPTION` array option and `Settings::default_option` filter already in place.

- [x] **Step 1: Write the failing tests** (follow `CompetitionSubtitleSettingTest.php` for how the option and the settings box are read):
  - `test_default_is_off`: with no option row, `Settings::past_zero() === false`.
  - `test_checked_value_is_on`: `update_option( Settings::OPTION, array( Settings::PAST_ZERO => '1' ) )` gives `true`; `array()` (key dropped by Meta Box when unchecked) gives `false`; a non-array option gives `false`.
  - `test_settings_box_has_a_checkbox_for_it`: the box `hyperpress_competition_box` from `apply_filters( 'rwmb_meta_boxes', array() )` has a field with `id` `Settings::PAST_ZERO` and `type` `checkbox`.

- [x] **Step 2: Run to verify it fails.** `docker compose run --rm php composer test:integration -- --filter PastZeroSettingTest`. Expected: FAIL.

- [x] **Step 3: Implement** the constant, `defaults()` entry and `past_zero()` (`is_array( $option ) && ! empty( $option[ self::PAST_ZERO ] )`), and add a `checkbox` field named "Count past zero" with a short description ("Keep counting after the start time instead of stopping at zero") to the settings box in `MetaBoxes::boxes`.

- [x] **Step 4: Run to verify it passes.** Expected: PASS, and the existing `CompetitionSettingsTest` and `CompetitionSubtitleSettingTest` still pass.

- [x] **Step 5: Commit** `feat(competition): add a count-past-zero setting`.

---

### Task 4: `Countdown` and its shortcode

**Files:**

- Create: `plugins/hyperpress-competition/src/Countdown.php`
- Test: `plugins/tests/integration/Competition/CountdownTest.php`

**Interfaces:**

- Consumes: `StartDate::instant`, `Settings::past_zero`, `HyperPress\Countdown\Renderer::render` (Task 1).
- Produces: `Countdown::render( int $post_id, bool $show_location = true, bool $show_date = false ): string`: empty string unless the post is a published (or readable private) `hyper_competition` with a usable start date and `Renderer` exists; otherwise `Renderer::render( $when, array( 'location' => <venue name>, 'past_zero' => Settings::past_zero(), 'slug' => 'competition-' . $post_id, 'show_date' => $show_date ) )`. `Countdown::register(): void` adds shortcode `hyperpress_competition_countdown` with attribute `id`, which calls `render( absint( $atts['id'] ) )`.

- [x] **Step 1: Write the failing tests** (create a published `hyper_competition` and set `$GLOBALS['hyperpress_test_rwmb']` for the start date and `hyperpress_competition_venue_name`):
  - `test_renders_the_countdown_from_the_start_date`: start `2030-07-01 10:00` and `timezone_string` `America/New_York`; `Countdown::render( $id )` contains `data-date="2030-07-01T10:00:00-04:00"` and the venue name, and does not contain `<time>`.
  - `test_past_start_still_renders_and_the_setting_reaches_the_script`: start `2001-01-01 10:00` renders; with `Settings::PAST_ZERO` on, the inline JS contains `"count_past_zero": true`.
  - `test_nothing_without_a_usable_date` (provider: no meta, `'junk'`): returns `''`.
  - `test_nothing_for_other_posts_or_drafts`: a `post`, and a `draft` competition, both give `''`.
  - `test_shortcode_renders_the_same_markup_for_an_id`: `do_shortcode( '[hyperpress_competition_countdown id="<id>"]' )` equals the function's output apart from the numeric element-id suffix; `[hyperpress_competition_countdown]` with no id gives `''`.

- [x] **Step 2: Run to verify it fails.** `docker compose run --rm php composer test:integration -- --filter "Competition.*CountdownTest"`. Expected: FAIL.

- [x] **Step 3: Implement** `Countdown` as specified. Guard with `class_exists( '\HyperPress\Countdown\Renderer' )`. Reading the venue name: `HyperPress\Utils\Meta::get( 'hyperpress_competition_venue_name', $post_id )`, cast to string.

- [x] **Step 4: Run to verify it passes.** Expected: PASS.

- [x] **Step 5: Commit** `feat(competition): render a countdown from the start date`.

---

### Task 5: `Details`, `Content` and the template

**Files:**

- Create: `plugins/hyperpress-competition/src/Details.php`, `plugins/hyperpress-competition/src/Content.php`, `plugins/hyperpress-competition/templates/content.php`
- Test: `plugins/tests/integration/Competition/DetailsTest.php`, `plugins/tests/integration/Competition/ContentTest.php`

**Interfaces:**

- Consumes: `StartDate::instant`, `Countdown::render`, `Plugin::POST_TYPE`.
- Produces: `Details::for_post( int $post_id ): array` returning `array{host:string,venue:string,address:string,when:?\DateTimeImmutable,links:list<array{label:string,url:string,target:string,rel:string,group:string}>}`. Each link also carries `group`: `'live'` for streams, `'info'` for every other link (including the derived Blue Alliance link). `links` come from, in order, `hyperpress_competition_ne_first_event_url` ("NE FIRST Event"), `..._ftc_ma_event_url` ("FTC MA Event"), `..._first_event_url` ("FIRST Event"), `..._frc_events_url` ("FRC Events"), a derived Blue Alliance link, `..._agenda_url` ("Public Agenda"), then the streams (`..._stream_url`). The Blue Alliance link is built from `hyperpress_competition_tba_event_key`: the value is trimmed and lowercased, and when it matches `/^\d{4}[a-z0-9]+$/` the link is `The Blue Alliance` -> `https://www.thebluealliance.com/event/<key>`; otherwise there is none. The stream field is cloneable (`clone_as_multiple`, one meta row per stream, so older single-stream data reads as one stream); each stream is one entry, labelled `Stream`, or `Stream 1`, `Stream 2`, ... when there is more than one stream and a stream has no title. Every other link is a Meta Box link array (`url`, `title`, `target`); `label` is the link's `title` when non-empty, else the default label; entries without a URL are dropped; `target` is `'_blank'` with `rel` `noopener noreferrer` for an external URL, and `'_self'` with an empty `rel` for a URL on this site unless its path ends in `.pdf`, which also opens in a new tab (`HyperPress\Utils\Link::target()` / `::rel()`, the rule the socials use); the derived Blue Alliance link is always external.
- Produces: `Content::register(): void` (hooks `content_template`), `Content::add_content_template( mixed $content_template ): mixed` (returns `dirname( __DIR__ ) . '/templates/content.php'` for the competition post type, else the argument unchanged, same shape as `HyperPress\Newsletter\Content`), `Content::is_full_view(): bool` (`is_singular( Plugin::POST_TYPE ) && get_the_ID() === get_queried_object_id()`).

- [x] **Step 1: Write the failing `DetailsTest`:** meta values come back trimmed strings; links keep their order, the link `title` overrides the default label, empty-URL links are dropped, missing start date gives `when === null`; with `timezone_string` `America/New_York`, `when` has the `-05:00` offset for January text.

- [x] **Step 2: Write the failing `ContentTest`** (create a competition with title "Granite State", venue `Arena <b>One</b>`, host `Host & Co`, address `1 Main St`, start `2030-07-01 10:00`, one link `https://example.org/e?a=1&b=2` titled `Event "page"` and one with an empty URL; render the template with `ob_start(); include Content::add_content_template( '' ); $html = ob_get_clean();` after `setup_postdata`):
  - `test_filter_supplies_the_template_only_for_competitions`: while `get_post_type()` is `hyper_competition` the filter returns a readable path inside `hyperpress-competition/templates/`; for a `post` it returns `''`.
  - `test_full_view_on_the_competitions_own_page` (`go_to( get_permalink( $id ) )`): the HTML contains the countdown section, `Arena &lt;b&gt;One&lt;/b&gt;`, `Host &amp; Co`, `1 Main St`, the formatted date from `wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), ... )`, an `<a href="https://example.org/e?a=1&#038;b=2"` (or `&amp;`) link with `target="_blank"` and `rel="noopener noreferrer"`, and exactly one link.
  - `test_summary_in_a_list`: after `go_to( home_url( '/' ) )`, the HTML contains a title link to the permalink, the date, the venue and address, and none of: `countdown`, `target="_blank"`, `hyperpress-map`.
  - `test_summary_when_listed_inside_another_page`: `go_to` a normal page whose queried object differs from the competition, with the competition set up via `setup_postdata`; `is_full_view()` is false and no countdown markup appears.
  - `test_empty_fields_are_skipped`: a competition with only a title renders no empty labels and no countdown and raises no notice.

- [x] **Step 3: Run to verify they fail.** `docker compose run --rm php composer test:integration -- --filter "Competition.*(Details|Content)Test"`. Expected: FAIL.

- [x] **Step 4: Implement `Details`, `Content` and `templates/content.php`.** Template shape follows `plugins/hyperpress-newsletter/templates/content.php`: `<header>` with the title link (`h2.entry-title`, list views only) and `do_action( 'hyperpress_labels' )`; `<div class="entry-content">` with the thumbnail (`the_post_thumbnail( 'full', array( 'class' => 'thumbnail' ) )`, as the default `content.php` does), then in the full view the countdown (`echo Countdown::render( get_the_ID() )` with a phpcs ignore for pre-escaped output), the formatted date, venue, address, host and the links list (`<ul>` with one `<li><a target="_blank" rel="noopener noreferrer">`); in the summary just the date, venue and address. (Superseded 2026-10-20: the full view now prints date and venue inside the countdown, labels address and host, and renders the links as button groups; see the last execution note.) Escape every value (`esc_html`, `esc_url`, `esc_attr`); format with `wp_date()` as in the spec; Foundation utility classes only, no new SCSS unless markup needs it.

- [x] **Step 5: Run to verify they pass.** Expected: PASS.

- [x] **Step 6: Commit** `feat(competition): full and summary views through content_template`.

---

### Task 6: Map under the banner

**Files:**

- Modify: `themes/hyperpress-foundation/single.php:12`, `themes/hyperpress-foundation/AGENTS.md` (plugin-facing hooks sentence), `themes/hyperpress-foundation/tests/unit/PluginHooksContractTest.php` (`fired_actions`)
- Create: `plugins/hyperpress-competition/src/Map.php`
- Test: `themes/hyperpress-foundation/tests/unit/SingleTemplateHookTest.php`, `plugins/tests/integration/Competition/MapTest.php`

**Interfaces:**

- Produces (theme): `do_action( 'hyperpress_after_banner' );` in `single.php` immediately after `get_template_part( 'template-parts/banner' )` and before `<div class="main-container">`. It takes no arguments.
- Produces (plugin): `Map::register(): void` (adds `hyperpress_after_banner` action calling `Map::render`), `Map::render(): void` (echoes the map or nothing), `Map::parse( mixed $value ): ?array` returning `array{lat:float,lng:float,zoom:int}` for strings like `"lat,lng"` or `"lat,lng,zoom"` with latitude in [-90, 90], longitude in [-180, 180], zoom clamped to 0-19 and default 15, else `null`.

- [x] **Step 1: Write the failing theme test** `SingleTemplateHookTest` (unit, reads `single.php` like `PluginHooksContractTest::theme_source`): the file contains `do_action( 'hyperpress_after_banner' )`, positioned after `template-parts/banner` and before `main-container`. Add `'after banner' => array( 'hyperpress_after_banner' )` to `PluginHooksContractTest::fired_actions`.

- [x] **Step 2: Write the failing `MapTest`** (create a competition, `go_to( get_permalink( $id ) )`, `RWMB_OSM_Field::$calls = array()` in `set_up`, capture `Map::render()` with `ob_start`):
  - `test_renders_the_competitions_location_full_width`: address `1 Main St` and map `42.1,-71.2,15` produce one `RWMB_OSM_Field::$calls[0]` with location `42.1,-71.2,15` and args `height` `300px`, `width` `100%`, `marker` true; the output contains `class="hyperpress-map"`.
  - `test_nothing_without_an_address_or_a_valid_location` (provider: empty address; empty map; `'garbage'`; `'91,0'`; `'0,181'`): no output, no recorded call, no notice.
  - `test_nothing_on_lists_and_other_posts`: on the home URL, on a normal post, and on a competition when `get_queried_object_id()` differs: no output.
  - `test_parse_clamps_zoom_and_defaults_it`: `Map::parse( '1,2' )['zoom'] === 15`, `Map::parse( '1,2,99' )['zoom'] === 19`.
  - `test_hook_is_registered`: after `Map::register()`, `has_action( 'hyperpress_after_banner', array( Map::class, 'render' ) )` is not false.

- [x] **Step 3: Run to verify they fail.** Theme: from `themes/hyperpress-foundation/`, `docker compose run --rm php composer test:unit`. Plugins: `docker compose run --rm php composer test:integration -- --filter MapTest`. Expected: FAIL.

- [x] **Step 4: Implement.** Add the action to `single.php`; list `hyperpress_after_banner` among the plugin-facing hooks in the theme `AGENTS.md`. `Map::render` mirrors `plugins/hyperpress-contact/src/MapShortcode.php` (same `js_options` that disable interaction, `pointer-events:none` wrapper with `role="img"` and an `aria-label` using the venue name), shows only when `is_singular( Plugin::POST_TYPE )`, the venue address is non-empty, `Map::parse` of `hyperpress_competition_venue_map` is valid, and `class_exists( 'RWMB_OSM_Field' )`. Format the location string with up to seven decimals like `MapShortcode::decimal`.

- [x] **Step 5: Run to verify they pass.** Both commands above. Expected: PASS.

- [x] **Step 6: Commit** (theme and plugin paths together): `feat(competition): full-width map under the banner on the single page`.

---

### Task 7: Wiring, dependency, and verification

**Files:**

- Modify: `plugins/hyperpress-competition/src/Plugin.php:32-47` (boot), `plugins/hyperpress-competition/hyperpress-competition.php` (`Requires Plugins`), `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md` (competition row, lines ~54-72)

- [x] **Step 1: Write the failing test** `plugins/tests/integration/Competition/BootTest.php`: after plugins load, `shortcode_exists( 'hyperpress_competition_countdown' )`, `has_filter( 'content_template', array( Content::class, 'add_content_template' ) )` and `has_action( 'hyperpress_after_banner', array( Map::class, 'render' ) )` are all truthy; and the main file's header (read with `get_file_data`) lists `hyperpress-countdown` under `Requires Plugins`.

- [x] **Step 2: Run to verify it fails.** `docker compose run --rm php composer test:integration -- --filter "Competition.*BootTest"`. Expected: FAIL.

- [x] **Step 3: Wire it.** In `Plugin::boot()` call `Countdown::register();`, `Content::register();` and `Map::register();`. Update the header to `Requires Plugins:    hyperpress-utils, hyperpress-season, hyperpress-robot, hyperpress-countdown, meta-box-aio` and the competition row of the convention table to match. If any existing convention test encodes the `Requires Plugins` list (grep `Requires Plugins` in `plugins/tests`), update its expectation for competition only.

- [x] **Step 4: Full verification.** From `plugins/`: `docker compose run --rm php composer lint` (0 violations), `composer test:unit`, `composer test:integration`. From `themes/hyperpress-foundation/`: the same three. Expected: all green.

- [x] **Step 5: Manual check in the local stack** (`cd docker/hyperonline && docker compose --profile local up -d`, WordPress on http://localhost:4080, phpMyAdmin 4090; hyperalumni does not use the competition plugin; activate the countdown and competition plugins there if needed; run wpcli from `docker/hyperonline`). Re-enter the start date and an address on one competition, then: `docker compose --profile local run --rm wpcli wp post meta get <id> hyperpress_competition_event_start_date` should print wall-clock text like `2030-07-01 10:00`; the single page shows the map under the banner and the countdown matching Eastern time; `/competitions/` and the blog list show the summary and no map. Fix and re-test anything that differs; if the stored text format differs from `yyyy-mm-dd hh:mm`, adjust `StartDate` and its test.

- [x] **Step 6: Close out.** Add `> **STATUS: ✅ COMPLETED** (YYYY-MM-DD). <what passed / commit range>` at the top of this plan, resolve the spec's "Open items" entry with the result of the manual check, and tick any remaining boxes. Commit: `chore(competition): wire the competition templates and note the dependency`.

---

## Known follow-ups

- `Map::parse` duplicates `HyperPress\Contact\Settings::parse_map`; move both to `hyperpress-utils` if a third caller appears.
- A raw-date countdown shortcode for editors can sit on top of `Renderer` later.
- Filtering or sorting "upcoming competitions" by the start date text is possible with a date-typed meta query and is not built here.

## Execution notes

- 2026-10-18: Task 2 first used `'format' => 'yy-mm-dd'` (copied from the countdown field), which Meta Box does not document; replaced with `'save_format' => 'Y-m-d H:i'`. Task 7's manual check must confirm the stored text is `YYYY-MM-DD HH:MM`.
- 2026-10-19: Task 7's live check on the hyperonline stack (temporary competition via wpcli, deleted afterwards) found that `rwmb_meta()` returns rendered HTML for the `osm` map field, so `Map` now reads the map with `get_post_meta()`.
- 2026-10-19: final-review fixes (commit c8da8ff): `StartDate` reads the stored text with `get_post_meta()` and parses it strictly (`Y-m-d H:i`, then `Y-m-d H:i:s`); links whose URL `esc_url_raw()` rejects are dropped; `Countdown::render( $post_id, $show_location = true )` and the full view passes `false`, so the venue is printed once.
- PENDING (owner): save a competition's start date in the admin UI, then run `wp post meta get <id> hyperpress_competition_event_start_date` from `docker/hyperonline`; expect `YYYY-MM-DD HH:MM`. Also confirm a link saved through the UI renders (the final reviewer flagged that the link fields are read through `rwmb_meta()`), and check whether the labels block shows twice on the single page.
- Deferred minors: no test for the Meta Box-missing case of `Map` (no seam), private-post path of the competition countdown shortcode, real archive URL test for `Map`, `(int)` zoom cast on huge floats, `js_options` duplicated from `MapShortcode`.
- Follow-up: the countdown field still carries a no-op `'format' => 'yy-mm-dd'`; once the stored text is confirmed, give it an explicit `save_format` and drop the dead key.
- 2026-10-19: Meta Box fills a link's title with its URL when none is typed, so `Details` treats a title equal to the URL as empty and shows the default label (commit after c8da8ff).
- 2026-10-19: the single competition banner now shows the competition's own title; `SingularBanner::register()` gained an optional `$season_prefix` argument (default true; the competition passes false).
- 2026-10-19: `hyperpress_after_banner` now fires from `template-parts/banner.php` (every template), not `single.php`.

- 2026-10-20: added the FRC Events and Public Agenda link fields, a derived The Blue Alliance link (from the `tba_event_key` field, now sanitized with `sanitize_key`) and made the stream field cloneable (multiple streams, `clone_as_multiple`, no migration of existing single-stream data). `Details::for_post` link order is now NE FIRST, FTC MA, FIRST, FRC Events, The Blue Alliance, Public Agenda, streams. Meta Box runs the field's `sanitize_callback` once per clone and drops clones that sanitize to empty, so `MetaBoxes::event_link` is unchanged.
- 2026-10-20: competition links now open in a new tab only when the URL is external. The socials' private `Links::is_internal()` moved to `HyperPress\Utils\Link` (`is_internal`, `target`, `rel`) and both `hyperpress-social` and `Details` use it; `Details` links gained a `rel` key and the template prints `rel` only when non-empty. Test fixtures that used `https://example.org/` as an external URL now use `https://external.example/`, because `example.org` is the integration-test site host.
- 2026-10-20: internal links whose URL path ends in `.pdf` (query string and fragment ignored, case-insensitive) also open in a new tab: `Link::target()` returns `_blank` for them, so `Details` gives them `rel` `noopener noreferrer`. `Link::is_internal()` is unchanged; the socials pick this up through the same helper.
- 2026-10-20: the last two hardcoded `target="_blank" rel="noopener"` links also use `Link::target()` / `::rel()`: the sponsor "Website" link (`hyperpress-sponsor/templates/content.php`) and the newsletter "View on Canva" link (`hyperpress-newsletter/templates/content.php`). Their `rel` is now `noopener noreferrer`, printed only when non-empty. Canva URLs are always external (`MetaBoxes::is_canva_url`), so that link is always `_blank`. Covered by `SponsorContentTemplateTest` and `NewsletterContentTemplateTest`.
- 2026-10-20: on the competition edit screen the Meta Box `osm` address autocomplete menu rendered behind the Leaflet map: jQuery UI adds `ui-front`, and `.ui-front { z-index: 100 }` (WordPress and Meta Box core CSS) ties with and loads after Meta Box's `.ui-autocomplete { z-index: 999999 }`, while Leaflet panes and controls use 200-1000. `AdminAssets` (hooked from `Plugin::boot` on `admin_enqueue_scripts` priority 20, after Meta Box enqueues `rwmb-osm` at priority 10) attaches `.ui-autocomplete.ui-front{z-index:999999}` to the `rwmb-osm` style on `post.php`/`post-new.php` for `hyper_competition` only; the two-class selector wins whatever the load order.
- 2026-10-20: single competition page redesign (owner-approved mockup, option A). The full view calls `Countdown::render( $id, true, true )`, so the date line (`l F d, Y @ h:iA`, the homepage format) and the venue name render under the circles inside the countdown block; the `competition-date` / `competition-venue` paragraphs are only printed in the summary view, or in the full view when no countdown renders (no start date, or the countdown plugin is inactive), so a competition without a date still shows its venue. The shortcode `[hyperpress_competition_countdown]` and `Countdown::render( $id )` are unchanged (venue, no date). Address and host paragraphs carry a bold `Address:` / `Hosted by:` label in the full view (translatable, text domain `hyperpress`; the summary address stays unlabelled). The links `<ul>` is replaced by `<div class="competition-links">` with up to two groups, each a `<h6 class="subheader">` (`Event Info`, `Watch Live`) and a `<div class="competition-links-group">` of `<a class="button">` (info) or `<a class="button alert">` (streams, label followed by a `fa-solid fa-circle-play` icon); an empty group renders nothing, including its label. `Details::for_post` links gained `group` (`info` or `live`). No theme SCSS was added (plugin content is not styled by the theme): the group labels are Foundation `h6.subheader`, the buttons are whitespace-separated inline-block `.button`s that wrap naturally and get their spacing from `.button`'s own margin, and `competition-links`, `competition-links-group`, `competition-links-info` / `-live` are unstyled hooks.
- 2026-10-21: the link buttons also carry Foundation's `small-down-expanded` class (the theme already sets `$button-responsive-expanded: true`, and its breakpoints are xsmall 0, small 320px, medium 640px), so on phones (under 640px) each button is full width on its own line, an easier tap target, while tablets and desktops keep the grouped inline buttons. No theme change was needed.

---

## Follow-up: multi-day event schedule (2026-10-20)

> **STATUS: ✅ COMPLETED** (2026-10-21). Tasks F1-F7 were committed together as `feat(competition): replace the start date with a repeatable event schedule`; the generic `.faded` theme utility is its own commit, `feat(theme): add a faded utility class`; the shortcode heading is `feat(competition): put the title in the countdown shortcode heading`; F8 (day end times: a "Days end at" setting plus an optional per-day "Ends at") is `feat(competition): end each schedule day at a time of day`. `composer lint`, `test:unit` and `test:integration` pass (1406 integration tests, 6 skipped). The owner checked the admin fields, the page and the end-time switch (with temporary times) in the browser. Tasks F1-F8 use the same conventions, constraints and test conventions as Tasks 1-7; the Global Constraints and Review Focus above still apply.

**Goal:** A competition can have several days (for example Load In, Day 1, Day 2), each with its own start and a "Don't count down to this day" checkbox (inverted from the original "Count down to this day", see the execution notes). The countdown targets the next counted day and shows that day's label as its heading; the full view lists every day when there are two or more.

**Architecture:** The single `hyperpress_competition_event_start_date` field is replaced by a cloneable Meta Box `group` field `hyperpress_competition_event_days` (one serialized array of rows in one meta row). A new `Schedule` class normalizes and sorts the rows and picks the current day. `StartDate` shrinks to the shared text parser. `Countdown` and `Details` read from `Schedule`; nothing changes in `hyperpress-countdown` (`Renderer` already prints its `name` arg as an `<h2>` and nothing when it is empty).

### Decisions (owner-approved)

- The old key is dropped entirely: no migration and no fallback read of `hyperpress_competition_event_start_date` (its values are mostly empty and will be re-entered).
- A finished event keeps counting its last counted day (past-zero behaviour then follows the existing setting).
- The schedule list in the full view is this plan's own call, which the owner can flip: it prints only when there are 2 or more days, lists all days regardless of the countdown checkbox, and single-day events show only the countdown (which already carries the date).
- (F8) A day ends at a time of day: the optional per-day `end` sub-field, else the competition setting "Days end at" (default 20:00). The countdown stays on a day until that day ends, then switches to the next counted day. This replaces the "first counted day starting at or after now" pick and the calendar-date card-state rule of F7.

### Non-goals

- Switching the page to the next day without a reload when a day reaches zero (a reload shows the next day).
- Per-day streams or per-day venues. End DATES: a day has an end TIME OF DAY only (Task F8), always on the same date as its start, so a day cannot run past midnight.
- Migrating old start-date data.
- Any change to `plugins/hyperpress-countdown`.

### Interfaces

- `HyperPress\Competition\StartDate::parse( mixed $text ): ?\DateTimeImmutable`: the parsing `StartDate::instant` has today, extracted unchanged (non-string or blank gives `null`; trimmed; `!Y-m-d H:i` then `!Y-m-d H:i:s`, strict, read in `wp_timezone()`; any warning or error gives `null`). `StartDate::instant()` and `StartDate::FIELD` are removed in F5 (they exist until then so each task stays green).
- `HyperPress\Competition\Schedule` (new, `src/Schedule.php`):
  - `Schedule::FIELD = 'hyperpress_competition_event_days'`.
  - `Schedule::days( int $post_id ): list<array{label:string,start:\DateTimeImmutable,countdown:bool}>`: reads the group with `get_post_meta( $post_id, self::FIELD, true )` (not `rwmb_meta()`, which may reformat, as `StartDate` does) and passes it to `normalize()`.
  - `Schedule::normalize( mixed $rows ): list<...>`: non-array input gives `array()`; each row must be an array whose `start` parses through `StartDate::parse` (otherwise the row is dropped); `label` is `trim` of a scalar string (else `''`); the normalized `countdown` bool is `empty( $row['skip'] )`: a day is counted unless its stored row has a non-empty `skip` (missing key, `0`, `'0'`, `''`, `false` are counted; `1`, `'1'`, `true` are skipped), because Meta Box omits an unticked checkbox from the row; the result is sorted ascending by `start->getTimestamp()` with a stable tie-break on the original row order, and re-indexed.
  - `Schedule::pick( array $days, \DateTimeImmutable $now ): ?array`: among days with `countdown` true, the first whose `start` is later than or equal to `$now`; else the last counted day; `null` when no day is counted.
  - `Schedule::current( int $post_id, ?\DateTimeImmutable $now = null ): ?array`: `pick( days( $post_id ), $now ?? self::now() )`.
  - `Schedule::now(): \DateTimeImmutable`: `apply_filters( 'hyperpress_competition_now', new \DateTimeImmutable( 'now', wp_timezone() ) )`, and the filter result is used only when it is a `\DateTimeImmutable`. This is the test seam: unit-style tests call `pick()`/`current()` with an injected `$now`; template and `Countdown` tests control the clock with `add_filter( 'hyperpress_competition_now', fn() => new \DateTimeImmutable( '2030-07-01 09:00', wp_timezone() ) )` and remove it in `tear_down()`.
- `Countdown::render( int $post_id, bool $show_location = true, bool $show_date = false, bool $with_title = false ): string` (the fourth parameter was added 2026-10-21, see the execution notes; the first three keep their meaning). It targets `Schedule::current( $post_id )['start']`, adds `'name' => <day label>` to the `Renderer` args, and keeps `location`, `past_zero`, `slug`, `show_date`. No counted day gives `''`.
- `Details::for_post()['when']` is now `Schedule::current( $post_id )['start'] ?? null` and a new `days` key holds `list<array{label:string,start:\DateTimeImmutable}>` of ALL days (counted or not) in chronological order. Updated shape: `array{host:string,venue:string,address:string,when:?\DateTimeImmutable,days:list<array{label:string,start:\DateTimeImmutable}>,links:list<...>}`.
- MetaBoxes: in the `hyperpress_competition_information` box the `hyperpress_competition_event_start_date` field is replaced by a field with `id` `hyperpress_competition_event_days`, `type` `group`, `clone` `true`, `add_button` (translatable "Add another day"), `sort_clone` `true`, and sub-`fields`: `label` (`type` `text`, `sanitize_callback` `sanitize_text_field`, placeholder e.g. "Day 1"), `start` (`type` `datetime`, `inline` `false`, `timestamp` `false`, `save_format` `Y-m-d H:i`, same as the old field), `skip` (`type` `checkbox`, name "Don't count down to this day", no `std`). Sub-field ids are exactly `label`, `start`, `skip`. Sub-field `sanitize_callback`s need no custom function.

### Task F1: `StartDate::parse` and `Schedule`

**Files:**

- Modify: `plugins/hyperpress-competition/src/StartDate.php`
- Create: `plugins/hyperpress-competition/src/Schedule.php`
- Test: `plugins/tests/integration/Competition/StartDateTest.php` (add parse tests, keep the existing ones), `plugins/tests/integration/Competition/ScheduleTest.php`

**Interfaces:** as above (`StartDate::parse`, `Schedule::*`).

- [x] **Step 1: Write the failing tests.** In `StartDateTest` add `test_parse_reads_summer_and_winter_text_in_the_site_zone` (`'2030-07-01 10:00'` gives `2030-07-01T10:00:00-04:00`, `'2030-01-01 10:00'` gives `-05:00`) and `test_parse_rejects_blank_or_invalid_input` (provider: `''`, `'  '`, `'not a date'`, `'2030-02-31 10:00'`, `array()`, `0`, `null` all give `null`). In `ScheduleTest` (`set_up` sets `timezone_string` `America/New_York`; `tear_down` removes the `hyperpress_competition_now` filter; rows are stored with `update_post_meta( $id, Schedule::FIELD, array( ... ) )`):
  - `test_rows_are_normalised_and_sorted_by_start`: rows given out of order, with whitespace in labels, come back as a list sorted ascending with trimmed `label`, a `DateTimeImmutable` `start` in `America/New_York`, and a boolean `countdown`.
  - `test_invalid_or_empty_rows_are_dropped`: rows with `start` `''`, missing, `'junk'`, a non-array row, and a non-array stored value (`'x'`, `''`, no meta) leave only the valid rows (or `array()`).
  - `test_a_missing_skip_flag_is_counted_and_ticked_values_skip` (replaces `test_missing_countdown_flag_counts_explicit_false_does_not`): a row with no `skip` key has `countdown` `true`; `skip` of `0`, `'0'`, `''`, `false` is `true`; `1`, `'1'`, `true` is `false`. `test_the_stored_shape_meta_box_produces_counts_unticked_days`: a `skip => '1'` row is skipped and a row with no `skip` key is counted.
  - `test_equal_starts_keep_row_order`: two rows with the same `start` keep their original order.
  - `test_start_keeps_the_correct_instant_across_dst`: `2030-03-10 12:00` (just after spring-forward) is `-04:00` and `2030-03-09 12:00` is `-05:00`; the row's `getTimestamp()` equals the matching UTC instant.
  - `test_pick_before_the_first_day_gives_the_first_counted_day`, `test_pick_between_days_gives_the_next_counted_day`, `test_pick_after_the_last_day_gives_the_last_counted_day` (call `Schedule::pick( $days, $now )` with an injected `$now`; a `$now` exactly equal to a day's start still selects that day).
  - `test_pick_skips_days_with_the_countdown_unticked`: with Load In unticked, `$now` before Load In selects Day 1; with the last day unticked and `$now` after everything, the last counted day is chosen.
  - `test_pick_gives_null_when_no_day_is_counted` (all unticked, and an empty list).
  - `test_current_uses_the_now_filter`: with the `hyperpress_competition_now` filter returning a fixed `DateTimeImmutable`, `Schedule::current( $id )` follows it; a filter returning a non-`DateTimeImmutable` falls back to the real clock.
  - `test_old_start_date_key_is_not_read`: with only `hyperpress_competition_event_start_date` set via `update_post_meta` (literal key), `Schedule::days( $id )` is `array()`.
- [x] **Step 2: Run to verify it fails.** From `plugins/`: `docker compose run --rm php composer test:integration -- --filter "(StartDateTest|ScheduleTest)"`. Expected: FAIL (`StartDate::parse` and `Schedule` missing).
- [x] **Step 3: Implement.** Extract the body of `StartDate::instant` after the `get_post_meta` call into `public static function parse( mixed $text ): ?\DateTimeImmutable` (same trim, formats, `wp_timezone()` and `getLastErrors` rules), and make `instant()` call `self::parse( get_post_meta( $post_id, self::FIELD, true ) )`. Create `Schedule` per the interfaces (`defined( 'ABSPATH' ) || exit;` guard, `final class`, namespace `HyperPress\Competition`, docblocks with the array shapes as in `Details`).
- [x] **Step 4: Run to verify it passes.** Same command. Expected: PASS, with the existing `StartDateTest` cases still green.
- [x] **Step 5: Commit** `StartDate.php`, `Schedule.php`, the two tests and the plan: `feat(competition): add a schedule model for multi-day events`.

### Task F2: `Countdown` targets the current day

**Files:**

- Modify: `plugins/hyperpress-competition/src/Countdown.php` (lines 40-53)
- Test: `plugins/tests/integration/Competition/CountdownTest.php`

**Interfaces:** consumes `Schedule::current`, `Schedule::FIELD`; produces the behaviour in the Interfaces list.

- [x] **Step 1: Update and add failing tests.** Replace every fixture that sets the old field id (`hyperpress_competition_event_start_date`, set through the meta globals or `update_post_meta`) with `update_post_meta( $id, Schedule::FIELD, array( array( 'label' => '', 'start' => '2030-07-01 10:00' ) ) )` (a counted day has no `skip` key; a skipped one has `'skip' => '1'`). Keep the existing assertions (EDT offset, past start still renders and passes `"count_past_zero": true`, nothing without a usable date, nothing for other posts or drafts, shortcode parity). Add:
  - `test_label_is_the_heading`: a day labelled `Day 1` renders `<h2 class="cell align-center">Day 1</h2>`.
  - `test_no_label_means_no_heading`: an empty label renders no `<h2`.
  - `test_label_is_escaped`: label `A <b>&</b>` renders `A &lt;b&gt;&amp;&lt;/b&gt;`.
  - `test_targets_the_next_counted_day`: rows Load In (`2030-06-30 16:00`, unticked) and Day 1 (`2030-07-01 09:00`), `now` filter at `2030-06-30 08:00`: `data-date="2030-07-01T09:00:00-04:00"` and heading `Day 1`.
  - `test_between_days_targets_the_next_day_and_after_the_last_keeps_the_last_day`: with two counted days, `now` between them targets the second; `now` after both still targets the second (zeros) and keeps its heading.
  - `test_no_counted_day_gives_nothing`: all rows unticked returns `''`.
  - `test_show_date_and_venue_are_unchanged`: `render( $id, true, true )` still contains `<time>` for the current day and the venue.
  - `test_old_start_date_key_is_ignored`: only the old key set gives `''`.
- [x] **Step 2: Run to verify it fails.** `docker compose run --rm php composer test:integration -- --filter "Competition.*CountdownTest"`. Expected: FAIL.
- [x] **Step 3: Implement.** In `Countdown::render` replace `StartDate::instant( $post_id )` with `$day = Schedule::current( $post_id )`; return `''` when `null`; pass `$day['start']` to `Renderer::render` and add `'name' => $day['label']` to the args array. Update the class docblock ("from a competition's schedule").
- [x] **Step 4: Run to verify it passes.** Expected: PASS.
- [x] **Step 5: Commit** `feat(competition): count down to the current schedule day`.

### Task F3: `Details` follows the schedule

**Files:**

- Modify: `plugins/hyperpress-competition/src/Details.php` (docblock at line 29, return array at lines 61-67)
- Test: `plugins/tests/integration/Competition/DetailsTest.php`

**Interfaces:** consumes `Schedule::days`, `Schedule::current`; produces `when` and `days` as above.

- [x] **Step 1: Update and add failing tests.** Move every fixture that sets the old field id to `Schedule::FIELD` rows (as in F2). Keep the January `-05:00` offset assertion on `when`, and the missing-start assertion (`when === null`, now also `days === array()`). Add:
  - `test_when_is_the_next_counted_day`: rows Load In (unticked, early), Day 1 and Day 2; `now` filter before Load In gives `when` equal to Day 1's start; between Day 1 and Day 2 gives Day 2; after everything gives Day 2 (last counted day).
  - `test_when_is_null_when_no_day_is_counted`: all unticked gives `null`.
  - `test_days_lists_every_day_in_order`: `days` holds all rows (including the unticked one) as `array( 'label' => ..., 'start' => DateTimeImmutable )`, sorted chronologically, with no `countdown` key.
  - `test_days_has_one_entry_for_a_single_day`: one row gives `count( days ) === 1`.
- [x] **Step 2: Run to verify it fails.** `docker compose run --rm php composer test:integration -- --filter "Competition.*DetailsTest"`. Expected: FAIL.
- [x] **Step 3: Implement.** Compute `$days = Schedule::days( $post_id )` once; `when` is `Schedule::pick( $days, Schedule::now() )['start'] ?? null`; `days` maps each day to `array( 'label' => $day['label'], 'start' => $day['start'] )`. Update the `@return` shape.
- [x] **Step 4: Run to verify it passes.** Expected: PASS, plus a quick `--filter "Competition"` to confirm `ContentTest` has not regressed (its old-field fixtures are fixed in F4).
- [x] **Step 5: Commit** `feat(competition): derive the details date from the schedule`.

### Task F4: Schedule list in the full view

**Files:**

- Modify: `plugins/hyperpress-competition/templates/content.php` (after the host paragraph, lines 50-52)
- Test: `plugins/tests/integration/Competition/ContentTest.php`

**Interfaces:** consumes `Details::for_post()['days']`.

- [x] **Step 1: Update and add failing tests.** Replace the old-field fixtures in `ContentTest` with `Schedule::FIELD` rows (the existing "Granite State" competition gets one row `2030-07-01 10:00`) and keep all current assertions (the formatted date in the summary still comes from `wp_date( date_format . ' ' . time_format )` of `when`; the full view still prints the date under the circles). Add:
  - `test_single_day_has_no_schedule_list`: one row on the competition's own page: the HTML contains no `competition-schedule` and no `Schedule`.
  - `test_multi_day_lists_every_day_in_order`: rows Load In (`2030-06-30 16:00`, countdown unticked), Day 1 (`2030-07-01 09:00`), Day 2 (`2030-07-02 09:00`): the HTML contains `<ul class="competition-schedule">` with three `<li>` in chronological order, the first reading `Load In: Sunday June 30 @ 4:00 PM` (the format is `Label: Weekday Month D @ time`, built with `wp_date( 'l F j @ g:i A', $ts, wp_timezone() )`; assert against the same `wp_date` call rather than a hard-coded string for the other rows), including the unticked day.
  - `test_schedule_entries_are_escaped`: a label `A <b>&"x"` appears as `A &lt;b&gt;&amp;&quot;x&quot;` and never raw.
  - `test_schedule_entry_without_a_label_prints_only_the_date`: an empty label gives `<li>Monday July 01 @ 10:00 AM</li>`-style text (computed with `wp_date`) with no leading colon.
  - `test_schedule_is_full_view_only`: the same competition in a list (`go_to( home_url( '/' ) )`) and inside another page's loop shows no schedule list.
  - `test_summary_date_follows_the_current_day`: in the list view the date line is the current counted day's date, not the first day's (use the `hyperpress_competition_now` filter).
  - `test_countdown_heading_shows_the_day_label_in_the_full_view`: the full view contains `<h2 class="cell align-center">Day 1</h2>` for the current day.
- [x] **Step 2: Run to verify it fails.** `docker compose run --rm php composer test:integration -- --filter "Competition.*ContentTest"`. Expected: FAIL.
- [x] **Step 3: Implement the template block.** Inside `if ( $hyperpress_full )`, after the host paragraph and before the links groups, add: `if ( count( $hyperpress_details['days'] ) >= 2 )` print `<div class="competition-schedule-block"><p class="competition-schedule-label"><strong><?php esc_html_e( 'Schedule', 'hyperpress' ); ?>:</strong></p><ul class="competition-schedule">` with one `<li>` per day: `esc_html( label )` followed by `: ` only when the label is non-empty, then `esc_html( wp_date( 'l F j @ g:i A', $day['start']->getTimestamp(), wp_timezone() ) )`. Foundation utility classes only; `competition-schedule*` are unstyled hooks, no theme SCSS. Keep the phpcs conventions already used in the file (`hyperpress_` variable prefix, escaping on every value). _(Superseded by F7: the `<ul class="competition-schedule">` became day cards; the block conditions and the label stay.)_
- [x] **Step 4: Run to verify it passes.** Expected: PASS.
- [x] **Step 5: Commit** `feat(competition): list every day of a multi-day event on the full view`.

### Task F5: Event Schedule field, drop the old field

**Files:**

- Modify: `plugins/hyperpress-competition/src/MetaBoxes.php` (the field at lines 99-106), `plugins/hyperpress-competition/src/StartDate.php` (remove `FIELD` and `instant`)
- Test: `plugins/tests/integration/Competition/StartDateTest.php` (move/replace the field-definition test around line 75), `plugins/tests/integration/Competition/MetaBoxesTest.php` (create if no suitable file exists; otherwise add to the existing metabox test)

- [x] **Step 1: Write the failing tests.** In the metabox test, find the field `hyperpress_competition_event_days` in the `hyperpress_competition_information` box from `apply_filters( 'rwmb_meta_boxes', array() )` and assert:
  - `test_event_days_is_a_cloneable_group`: `type` `group`, `clone` `true`, and `clone_as_multiple` is not set (the group is one serialized array).
  - `test_event_days_sub_fields`: the sub-field ids are exactly `label`, `start`, `skip` (in that order); `label` is `text`; `start` is `datetime` with `timestamp === false`, `save_format === 'Y-m-d H:i'` and no `format` key; `skip` is `checkbox` with no `std`.
  - `test_old_start_date_field_is_gone`: no field with id `hyperpress_competition_event_start_date` exists in the box.
  - In `StartDateTest`, delete `test_start_field_stores_wall_clock_text_in_an_explicit_format` (its assertions now live on the `start` sub-field above) and the `StartDate::instant` cases (parse cases from F1 stay).
- [x] **Step 2: Run to verify it fails.** `docker compose run --rm php composer test:integration -- --filter "(StartDateTest|MetaBox)"`. Expected: FAIL.
- [x] **Step 3: Implement.** Replace the old field array in `MetaBoxes::register_information_meta_box` with the `group` field per the Interfaces; remove `StartDate::FIELD` and `StartDate::instant`; update the `StartDate` class docblock ("Parses wall-clock date text in the site timezone"). Then `grep -rn "event_start_date\|StartDate::instant\|StartDate::FIELD" plugins themes docs` and fix every remaining code or test hit (docs under `specs/`/`plans/` that describe current behaviour get a one-line note; the historical Tasks 2-5 text above stays as written).
- [x] **Step 4: Run to verify it passes.** Same command, then `--filter Competition` for the whole competition group. Expected: PASS.
- [x] **Step 5: Commit** `feat(competition): replace the start date with a repeatable event schedule`.

### Task F6: Verification and docs

- [x] **Step 1: Full verification.** From `plugins/`: `docker compose run --rm php composer lint` (0 violations), `docker compose run --rm php composer test:unit`, `docker compose run --rm php composer test:integration`. Expected: all green, `CountdownShortcodeHardeningTest` and `RendererTest` unedited.
- [x] **Step 2: Manual check in the local stack** (`cd docker/hyperonline && docker compose --profile local up -d`, WordPress on http://localhost:4080). In wp-admin edit a competition: the Information box shows "Event Schedule" rows (label, date/time, "Don't count down to this day" unticked on a new row, including rows added with the button). Add Load In (tick the skip box), Day 1 and Day 2, then save and check: `docker compose --profile local run --rm wpcli wp post meta get <id> hyperpress_competition_event_days` prints one serialized array of rows whose `start` values look like `2030-07-01 10:00`; the single page's countdown shows the Day 1 label as its heading and counts to Day 1 (Load In is skipped because its skip box is ticked); the "Schedule" list appears under the host line with all three days in order; with only one row there is no Schedule list; with all rows unticked there is no countdown; a competition with the old field only shows no countdown until a day is entered; `/competitions/` and the blog list show the summary date for the current counted day and no schedule list. Check the group sub-field date picker saves `Y-m-d H:i` (if Meta Box stores a different text format inside a group, adjust `Schedule`/`StartDate::parse` and note it here).
- [x] **Step 3: Docs.** Update the `StartDate`/`Schedule` mention in `plugins/hyperpress-competition/` docs or readme if any exist (grep `Event Start Date`), add the decisions above to the Execution notes with the final commit range, and add the COMPLETED banner to this section. Commit: `docs(competition): note the multi-day schedule`.

### Task F7: Schedule day cards

Owner-approved replacement of F4's `<ul>` (the 2+ days, full-view-only rule and the translatable "Schedule" label stay). Foundation classes plus one generic theme utility (`.faded`); no plugin-specific styling.

**Files:**

- Modify: `plugins/hyperpress-competition/src/Schedule.php` (`pick_index`, `columns`; `pick` uses `pick_index`), `plugins/hyperpress-competition/src/Details.php` (`days[]['state']`), `plugins/hyperpress-competition/templates/content.php` (cards)
- Test: `plugins/tests/integration/Competition/ScheduleTest.php`, `DetailsTest.php`, `ContentTest.php`

**Interfaces:**

- `Schedule::pick_index( array $days, \DateTimeImmutable $now ): ?int`: the index of the day `pick()` returns (null when no day is counted); `pick()` is `$days[ pick_index() ] ?? null`.
- `Schedule::columns( int $count ): array{small:int,medium:int,large:int}`: small 1 when `n <= 3` else 2; medium `min( n, 3 )`, except `n == 4` gives 2; large `n` when `n <= 5` else 3.
- `Details::for_post()['days']` entries are `array( 'label' => string, 'start' => \DateTimeImmutable, 'state' => 'past'|'current'|'upcoming' )`: (superseded by F8: states now follow each day's end time) states compare CALENDAR DATES in `wp_timezone()` with `Schedule::now()` as the clock: `past` is a day whose date is before today's date (skipped days included); `current` is the day the countdown targets (`Schedule::pick_index`) when its date is today or later; everything else is `upcoming` (a day that is today and already started but is not the target, and all future days). A finished event therefore has no `current` day, and the last day stays `current` from midnight starting it until midnight after it. With no counted day nothing is `current`.
- Template markup (2+ days): `<div class="competition-schedule grid-x grid-margin-x align-center small-up-S medium-up-M large-up-L">` holding one `<div class="cell"><div class="callout text-center ...">` per day with `<h6>label</h6>` (omitted when empty), `<div><strong>date</strong></div>` and `<div>time</div>`. `current` adds `primary` and `aria-current="true"`; `past` adds the class `faded` (a generic utility `.faded { opacity: 0.5; }` in the Foundation theme, `src/assets/scss/global/_utilities.scss`). Date `wp_date( 'l, F j' )` for 2-3 days, `wp_date( 'D, M j' )` for 4 or more; time `wp_date( 'g:i A' )`; all in `wp_timezone()` and escaped.

- [x] **Step 1: Write the failing tests.** `ScheduleTest`: `pick_index` before, between, after and at an exact start, with skipped days and with none counted, agreeing with `pick()`; the `columns` rules for 1-7 days. `DetailsTest`: `days` entries are `label`, `start`, `state`; states before the first counted day, between days (a skipped Load In is `upcoming` on its own date and `past` after it; a started non-target day is `upcoming`), on the last day after its start (still `current`), the day after the last day and long after (all `past`, none `current`), midnight boundaries at 00:00 and 23:59 including a DST date, a `now` filter in another zone, and with every day skipped. `ContentTest`: the column classes for 2, 3, 4, 5 and 7 days, `grid-x grid-margin-x align-center` on every row, full dates for 2-3 days and short dates from 4, time format, `<h6>` label (none when empty), escaping, the card states (`faded` class on past cards, no inline style; a finished event has no `aria-current`), no `current` card when all days are skipped; single day, summary and another page's loop show no schedule block.
- [x] **Step 2: Run to verify it fails.** `docker compose run --rm php composer test:integration -- --filter "Competition.*(ScheduleTest|DetailsTest|ContentTest)"`. Expected: FAIL (`pick_index`/`columns` missing, no `state`, old `<ul>`).
- [x] **Step 3: Implement** `Schedule::pick_index`/`columns`, `Details` states and the template cards, and the theme's `.faded` utility (imported from `app.scss` under Global).
- [x] **Step 4: Run to verify it passes.** Same command, then `--filter Competition`, then lint, unit and integration. Expected: PASS.
- [x] **Step 5: Commit** `feat(competition): show the schedule as day cards`.

### Task F8: Day end times

Owner-approved. The countdown should stay on a day until that day ENDS (most days end at 8 PM), then switch to the next counted day; some days run longer (Load In until 9 PM). A day gets an end TIME OF DAY (same date as its start), from a competition-wide setting with an optional per-day override. This supersedes F7's calendar-date card-state rule and the start-based pick of F1.

**Files:**

- Modify: `plugins/hyperpress-competition/src/Settings.php` (`DAY_END`, `DAY_END_DEFAULT`, `defaults()`, `day_end()`, `sanitize_day_end()`), `plugins/hyperpress-competition/src/MetaBoxes.php` (the settings box `hyperpress_competition_box` next to `Settings::PAST_ZERO`; the `end` sub-field after `start` in the `hyperpress_competition_event_days` group), `plugins/hyperpress-competition/src/Schedule.php` (`end` in the normalized day, the end-based `pick_index`), `plugins/hyperpress-competition/src/Details.php` (`day_states`)
- Unchanged by design: `plugins/hyperpress-competition/src/Countdown.php` (it still targets the picked day's start and label), `plugins/hyperpress-competition/templates/content.php` (it already renders `state`), everything in `plugins/hyperpress-countdown`
- Test: `plugins/tests/integration/Competition/DayEndSettingTest.php` (new, modelled on `PastZeroSettingTest`), `ScheduleTest.php`, `DetailsTest.php`, `ContentTest.php`, `CountdownTest.php`, `MetaBoxesTest.php`

**Findings from planning (Meta Box 5.15.2 / MB Group 1.4.7 in the hyperonline container, a temporary post saved through `RW_Meta_Box::save_post`, then deleted):** a `time` field normalizes with `timestamp` false and `save_format` `''` (a standalone time field stores the picker text, 24-hour `HH:mm`, for example `20:00`); setting `save_format` to `H:i` is accepted. Inside the cloneable group a `time` sub-field is stored as the submitted text (`'end' => '21:00'`) and, like every empty group value, an EMPTY one is omitted from the row (`array( 'start' => '2030-07-02 09:00' )`), as the datetime and checkbox sub-fields behave; group sub-field `sanitize_callback`s do not run. So `Schedule` must validate the stored `end` itself, and an absent key means "use the setting". The picker's default `stepMinute` is 5.

**Interfaces:**

- `Settings::DAY_END = 'hyperpress_competition_day_end'`, `Settings::DAY_END_DEFAULT = '20:00'`; `Settings::defaults()` gains `DAY_END => DAY_END_DEFAULT`.
- `Settings::day_end(): string`: the stored value in the option array `Settings::OPTION` when it is a string matching `^([01]\d|2[0-3]):[0-5]\d$`, else `20:00` (option missing, not an array, key missing because Meta Box drops an empty field, wrong type or malformed text all give the default).
- `Settings::sanitize_day_end( mixed $value ): string`: the trimmed value when it matches the same pattern, else `''` (Meta Box then drops the key and `day_end()` returns the default).
- Settings page: in the `hyperpress_competition_box` fields, after `Settings::PAST_ZERO`, a field with `id` `Settings::DAY_END`, name "Days end at" (translatable like its neighbours), `type` `time`, `timestamp` `false`, `save_format` `H:i`, `std` `Settings::DAY_END_DEFAULT` (a cleared field drops its key and `day_end()` falls back to the same default the field shows), a short translatable `desc` ("The countdown stays on a day until this time, then moves to the next day. A day can override it.") and `sanitize_callback` `array( Settings::class, 'sanitize_day_end' )`.
- MetaBoxes: in the `hyperpress_competition_event_days` group a new optional sub-field after `start`: `id` `end`, name "Ends at", `type` `time`, `timestamp` `false`, `save_format` `H:i`, `inline` `false`, NO `std`. Sub-field ids are now exactly `label`, `start`, `end`, `skip`.
- Normalized day: `array{label:string,start:\DateTimeImmutable,end:\DateTimeImmutable,countdown:bool}`. `end` is the row's `end` text of day when it matches `^([01]\d|2[0-3]):[0-5]\d$`, otherwise `Settings::day_end()`, applied to the SAME DATE as `start` in `wp_timezone()` with `$start->setTime( $hour, $minute )` (so DST is right: the instant comes from the local wall-clock time). If that end is not after `start` (for example a 9 PM start with the 8 PM default) the end is `$start->setTime( 23, 59, 59 )`. `normalize` still sorts by `start`; `Details['days']` entries stay `{label,start,state}`.
- `Schedule::pick_index( array $days, \DateTimeImmutable $now ): ?int` (and `pick()`, which uses it): the first COUNTED day whose `end` is strictly later than `$now`; if there is none, the last counted day; `null` when no day is counted. The countdown still targets the picked day's START and shows its label (during the day it shows zeros, or counts up with the existing past-zero setting), and `Details['when']` stays the picked day's start.
- `Details::day_states`: `past` = the day's `end` is not after now (`end <= now`); `current` = the picked day AND not `past`; `upcoming` = everything else (this includes a skipped day that is in progress and a counted day whose start has passed but whose end has not, when it is not the picked one). So Day 1 stays `current` until its end, then it is `past` (faded) and Day 2 is `current`; after the last day ends every card is `past` and none is `current`; a skipped Load In is faded once its own end has passed.

- [x] **Step 1: Verify the Meta Box storage again in the container** (a temporary post saved through `RW_Meta_Box::save_post` with a group holding a `datetime` `start` and a `time` `end` sub-field with `save_format` `H:i`, as for F1; delete the post afterwards). Expected as in the findings above: `'end' => '21:00'` stored as typed, an empty `end` omitted from the row. If the stored text differs (for example `9:00 PM` or seconds), adjust the accepted pattern in `Schedule`/`Settings` and note it in the execution notes.
- [x] **Step 2: Write the failing tests.**
  - `DayEndSettingTest`: `Settings::day_end()` is `20:00` with no option row, with a non-array option, with the key missing, and with malformed values (`''`, `'8pm'`, `'24:00'`, `'20:60'`, `'2:00'`, an array); a valid value (`'21:30'`, `'00:00'`, `'23:59'`) is returned; `Settings::defaults()` contains `DAY_END` `20:00`; `sanitize_day_end` keeps a valid value and returns `''` for the malformed ones; the settings box has a `time` field with id `Settings::DAY_END`, `timestamp` false, `save_format` `H:i` and `std` `20:00`, placed after `Settings::PAST_ZERO`.
  - `MetaBoxesTest`: sub-field ids are exactly `label`, `start`, `end`, `skip`; `end` is `time` with `timestamp` false, `save_format` `H:i` and NO `std` key (update `test_event_days_sub_fields`).
  - `ScheduleTest` (normalize): the default end is 20:00 on the start's date and follows `hyperpress_competition` option changes; a per-row `end` of `21:00` overrides it; an invalid or empty `end` (`''`, `'junk'`, `'25:00'`, `'9pm'`, an array, `null`) falls back to the setting; an end equal to or before the start (9 PM start with the default, or an `end` of `09:00` on a 09:00 start) becomes 23:59:59 of the start's date; DST dates keep the right offset (`2030-03-10 12:00` starts `-04:00` and ends `20:00-04:00`; `2030-03-09` ends `20:00-05:00`; `2030-11-03` ends `-04:00` or `-05:00` as the wall-clock time requires); the normalized day shape is `label`, `start`, `end`, `countdown`.
  - `ScheduleTest` (pick): with Day 1 and Day 2 on consecutive dates starting at 09:00 and the default end, `pick_index` at exactly the start still gives that day, between start and end still gives that day, one second before the end still gives that day, exactly AT the end gives the next counted day, and after the last day's end gives the last counted day; skipped days are never picked (a skipped Load In ending at 21:00 does not hold the countdown); no counted day gives `null`; `pick()` agrees with `pick_index()`; update the existing pick tests, whose fixtures only carry `start` (add `end` to the helper days) and which assumed "first counted day starting at or after now".
  - `DetailsTest` (states): replace the calendar-date state tests with end-based ones: at each of the moments above Day 1 is `current` until its end and `past` from its end; Day 2 is `upcoming` then `current` from Day 1's end; after the last end every card is `past` and none is `current`; a skipped Load In with a 21:00 end is `upcoming` while in progress (20:30) and `past` from 21:00; the same boundaries on a DST date; all-skipped schedule: no `current`, `past`/`upcoming` by end; `Details['when']` stays the picked day's start (Day 1's start until Day 1 ends).
  - `ContentTest` (cards): the card tags follow the same moments (`faded`, `primary` + `aria-current`, plain), including the second just before and exactly at a day's end, and a finished event with no `aria-current`; update the F7 card-state test that asserted the calendar rule ("Day 1 is today but the countdown moved on").
  - `CountdownTest`: the countdown keeps Day 1's `data-date` and `>Day 1</h2>` heading during Day 1 (at its start, midday, one second before its end), switches to Day 2's date and heading at exactly Day 1's end, and after the last day's end keeps the last counted day; a skipped day never holds the countdown; the shortcode (`Title: Day 1`) follows the same switch; update `test_between_days_targets_the_next_day_and_after_the_last_keeps_the_last_day` and the other pick-dependent cases whose `now` falls between a day's start and its end.
- [x] **Step 3: Run to verify it fails.** From `plugins/`: `docker compose run --rm php composer test:integration -- --filter "Competition"`. Expected: FAIL (no `Settings::day_end`, no `end` sub-field or normalized `end`, start-based pick and calendar-date states).
- [x] **Step 4: Implement** `Settings` (constants, defaults, `day_end`, `sanitize_day_end`), the settings-page field and the `end` sub-field in `MetaBoxes`, `Schedule::normalize` (`end` per the rules above, using `Settings::day_end()`; read it once per call, not per row), the end-based `pick_index`, and `Details::day_states`. Update the docblocks and array shapes (`Schedule`, `Details`, `Countdown`). Do not touch `plugins/hyperpress-countdown`.
- [x] **Step 5: Run to verify it passes.** Same command, then from `plugins/`: `docker compose run --rm php composer lint` (clean), `composer test:unit`, `composer test:integration` (the whole suite; `CountdownShortcodeHardeningTest` and `RendererTest` unedited). Expected: all green.
- [x] **Step 6: Docs.** In this section mark the F7 notes and Interfaces that describe the calendar-date state rule and the "Day 1 in the afternoon is `upcoming`" behaviour as superseded by F8 (already flagged in the text above), add execution notes for F8 (the end rules, the 23:59:59 fallback, why the end applies to the start's date only, the Meta Box storage findings, and any spring-forward end time that does not exist, which PHP resolves to the next valid instant), and update the spec note in `docs/superpowers/specs/2026-10-18-competition-templates-design.md` only if it describes the pick or the card states.
- [x] **Step 7: Manual check in the local stack (owner).** In wp-admin open the Competition settings page: "Days end at" shows `20:00`, saves, and a cleared value returns to `20:00`. On a competition set Day 1 and Day 2 on consecutive dates and give Load In an "Ends at" of 9 PM; use the `hyperpress_competition_now` filter (or temporary times) to check around the end time: the countdown and the "Schedule" cards stay on Day 1 until 8 PM, switch to Day 2 at 8 PM (Day 1 faded), show no highlighted card after Day 2 ends, and the skipped Load In fades at its own 9 PM; a day with an empty "Ends at" uses the setting; the sub-field's time picker saves `HH:mm` (check with `wp post meta get <id> hyperpress_competition_event_days`).
- [x] **Step 8: Commit** `feat(competition): end each schedule day at a time of day`.

### Execution notes (follow-up)

- 2026-10-20: verified against the live Meta Box 5.15.2 / MB Group 1.4.7 in the hyperonline container (a temporary post saved through `RW_Meta_Box::save_post`, then deleted): a cloneable `group` without `clone_as_multiple` is stored as ONE meta row holding a serialized list of row arrays keyed by sub-field id (`array( 0 => array( 0 => array( 'label' => ..., 'start' => '2030-07-01 09:00', 'countdown' => '1' ), ... ) )`, i.e. `get_post_meta( $id, FIELD )` has one entry and `get_post_meta( $id, FIELD, true )` is the row list), so `Schedule::normalize` needed no shape change. A `datetime` sub-field with `timestamp => false` is stored as the submitted text unchanged inside a group (the group stores the raw POST values; `save_format` is not applied there, and the sub-fields' `sanitize_callback`s are not run either, so labels are trimmed in `Schedule::normalize` and escaped on output). Empty strings and empty arrays are dropped from a stored row, so a blank label has no `label` key.
- 2026-10-20 (superseded by the next note): an UNCHECKED checkbox sub-field is ABSENT from the stored row (a checked one is `'1'`), so the plan's "missing `countdown` key counts as ticked" rule would have re-ticked every unticked day; the first implementation used `! empty( $row['countdown'] )` with `std => 1`.
- 2026-10-20 DEVIATION (checkbox inverted): rendering the Information box in the hyperonline container showed that once a competition has saved rows, Meta Box renders the hidden "Add another day" template row from saved data, so with `std => 1` an unticked first row (for example Load In) made every newly added day start unticked and silently excluded from the countdown. The field is therefore a `skip` checkbox ("Don't count down to this day", no `std`): Meta Box omits an unticked checkbox from the stored row, so absent means counted, `skip => '1'` means skipped, and the default of new or template rows no longer matters. `Schedule::normalize` sets the normalized `countdown` bool to `empty( $row['skip'] )`; the normalized day shape `{label,start,countdown}`, `pick()`, `Countdown` and `Details` are unchanged. Tests write `'skip' => '1'` for skipped days and no key for counted ones. Verified by rendering the box for rows `[Load In skip=1, Day 1]`: template row unchecked, Load In checked, Day 1 unchecked.
- 2026-10-20: running `phpcbf` over `tests/integration/Competition` (outside the lint scope) reformatted the existing compact one-line arrays in `ContentTest`, `CountdownTest`, `DetailsTest` and `StartDateTest` to the multi-line WordPress style, so their diffs are larger than the behavioural change.
- 2026-10-20: the field carries a short plain-string `label_description` (not `desc`, which Meta Box prints on the same line as the "Add another day" button; not wrapped in `__()`, because `EmptyLabelTest` rejects translated label descriptions) ("One row per day. The countdown targets the next day that is not marked \"Don't count down to this day\".") that the plan did not list. `StartDateTest` now only holds the `parse` cases (the `instant` cases and the field-definition test were removed, as planned); the field-definition assertions live in the new `Competition/MetaBoxesTest.php`. Fixtures in `CountdownTest`, `DetailsTest` and `ContentTest` use `Schedule::FIELD` rows (`CountdownTest` and `ContentTest` keep a `START` shorthand constant in their meta arrays that the helper turns into a one-row, counted schedule). 2026-10-20 (later): the group has no description at all (Meta Box prints a `label_description` inline against the label, and `desc` beside the "Add another day" button); and the `skip` checkbox has none either: its label, "Don't count down to this day", is self-explanatory.
- 2026-10-21 (F7): schedule day cards replace the `<ul>`. Column rules: phones get one column up to three days then two; tablets get `min( n, 3 )` except exactly four days (2, so it is not a lopsided 3 + 1); desktops get one column per day up to five, then three; `align-center` on every row centres an incomplete last row. Dates are the long form (`Friday, March 27`) for 2-3 days and the short form (`Fri, Mar 27`) from 4, so narrow cards do not wrap.
- 2026-10-21 (F7): the current card is the Foundation `primary` callout with `aria-current="true"` so the state is not conveyed by colour alone. Upcoming cards carry neither.
- _(Superseded by F8 for the pick and the card states: they now follow each day's end time, so the "Day 1 in the afternoon is `upcoming`" behaviour below no longer exists.)_
- 2026-10-21 (F7 follow-up, supersedes the earlier "past = started before now" and "finished event keeps its last day current" behaviour for the cards only; the countdown, `Schedule::pick` and `Details['when']` are unchanged): card states compare calendar dates in the site timezone. `past` is a day dated before today; `current` is the countdown's day if it is dated today or later; the rest are `upcoming`. After the whole event is over every card is `past` and none is `current`, instead of highlighting the last day forever; on the last day it stays `current` until local midnight. A skipped day is `past` once its date has gone by. A day that is today and already started but is not the countdown's target (for example Day 1 in the afternoon, with Day 2 next) is `upcoming`.
- 2026-10-21 (F7 follow-up): past cards use the class `faded` instead of an inline `style="opacity: .5;"`. The theme gains a generic `.faded { opacity: 0.5; }` in a new `global/_utilities.scss`, imported from `app.scss` after `global/colors`. The name is `faded`, not `fade`, because Bootstrap's `.fade` sets opacity 0 and other plugins may load it. This refines the earlier "no theme CSS" decision: a generic utility in the theme is fine, plugin-specific styling in the theme is not (plugin content stays unstyled otherwise). The compiled `app.css` contains `.faded{opacity:.5}`.
- 2026-10-21 (F7): `Schedule::pick_index` exists so `Details` can mark the current day by position: labels can be empty or repeated, so comparing labels (or start times) to the picked day would be ambiguous. `pick()` now calls it, so the two cannot disagree. `Schedule::columns` is a pure function so the grid rules are unit-testable without rendering. `Details` gets its clock from `Schedule::now()`, so `hyperpress_competition_now` controls the states in tests.
- 2026-10-21: `Countdown::render()` gained an optional fourth parameter `$with_title` (default false). When true the heading (`name` arg, an `<h2>`) is `<title>: <day label>`, or just whichever of the two is not empty; the title is `get_the_title()` stripped of tags with `wp_strip_all_tags` and decoded with `wp_specialchars_decode`, so the Renderer's `esc_html()` escapes it exactly once. The `[hyperpress_competition_countdown]` shortcode passes `true` (it appears outside the competition's page); the full-page template call `render( $id, true, true )` and other callers keep the label-only heading because the title is already in the page banner.
- 2026-10-21 (F8): days end at a time of day. `Settings::day_end()` (option `hyperpress_competition_day_end`, `H:i`, default `20:00`, anything not matching `^([01]\d|2[0-3]):[0-5]\d$` falls back to the default; the settings-page `time` field uses `save_format` `H:i`, `std` `20:00` and `Settings::sanitize_day_end`) is the default; a group row's optional `end` sub-field overrides it. `Schedule::normalize` applies the end to the SAME DATE as the start with `setTime()` in `wp_timezone()` (so the offset is right on DST dates) and falls back to 23:59:59 of that date when the end is not after the start (a 9 PM start with the 8 PM default, or an `end` equal to or before the start). A day cannot run past midnight. Re-verified in the hyperonline container (temporary post, deleted): a `time` field with `save_format` `H:i` stores `21:30` as typed; inside the group `'end' => '21:00'` is stored as typed and an empty `end` is omitted from the row; a cleared standalone time field drops its meta row. So `Schedule` validates the stored text itself and an absent key means "use the setting". A spring-forward end time that does not exist resolves to the next valid instant (PHP `setTime`); not tested.
- 2026-10-21 (F8): `Schedule::pick_index`/`pick` now choose the first COUNTED day whose `end` is strictly later than now (else the last counted day, else null), replacing "first counted day starting at or after now". The countdown still targets that day's START and label, so during the day it shows zeros or counts up per the past-zero setting; at exactly a day's end the next counted day takes over. `Details::day_states` is end-based: `past` = `end <= now`, `current` = the picked day unless it is past, otherwise `upcoming` (a skipped day in progress is `upcoming`; after the last day ends every card is `past` and none is `current`). This supersedes F7's calendar-date rule and the "Day 1 in the afternoon is `upcoming`" behaviour. `CompetitionSettingsPageTest::test_the_box_has_the_subtitle_field` now expects three fields.

### Follow-up: known follow-ups

- Reloading the page is needed to move the countdown to the next day when one reaches zero.
- Per-day venues or streams would extend the group's sub-fields and `Schedule::normalize`.
- The schedule-list rule (only with 2+ days, all days shown) is the plan's own call; flip it in `templates/content.php` if the owner prefers otherwise.
- 2026-10-23: the competition list shortcode (`docs/superpowers/plans/2026-10-23-competition-list-shortcode.md`) added a `blurb` field and `Details::blurb()`, printed in the full view of `templates/content.php`, and moved the link-button block of `content.php` into `templates/links.php`, shared with the list item. The markup the single page prints is unchanged.
