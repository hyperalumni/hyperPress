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
- Venue/host/link text containing HTML or quotes is escaped; a link with an empty URL is skipped; competition links get `target="_blank"` and `rel="noopener noreferrer"`. (Task 5)
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
- Produces: `Countdown::render( int $post_id ): string`: empty string unless the post is a published (or readable private) `hyper_competition` with a usable start date and `Renderer` exists; otherwise `Renderer::render( $when, array( 'location' => <venue name>, 'past_zero' => Settings::past_zero(), 'slug' => 'competition-' . $post_id, 'show_date' => false ) )`. `Countdown::register(): void` adds shortcode `hyperpress_competition_countdown` with attribute `id`, which calls `render( absint( $atts['id'] ) )`.

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
- Produces: `Details::for_post( int $post_id ): array` returning `array{host:string,venue:string,address:string,when:?\DateTimeImmutable,links:list<array{label:string,url:string,target:string}>}`. `links` come from, in order, `hyperpress_competition_ne_first_event_url` ("NE FIRST event"), `..._ftc_ma_event_url` ("FTC MA event"), `..._first_event_url` ("FIRST event"), `..._stream_url` ("Stream"); each is a Meta Box link array (`url`, `title`, `target`); `label` is the link's `title` when non-empty, else the default label; entries without a URL are dropped; `target` is `'_blank'` always for these (competition links open in a new tab).
- Produces: `Content::register(): void` (hooks `content_template`), `Content::add_content_template( mixed $content_template ): mixed` (returns `dirname( __DIR__ ) . '/templates/content.php'` for the competition post type, else the argument unchanged, same shape as `HyperPress\Newsletter\Content`), `Content::is_full_view(): bool` (`is_singular( Plugin::POST_TYPE ) && get_the_ID() === get_queried_object_id()`).

- [x] **Step 1: Write the failing `DetailsTest`:** meta values come back trimmed strings; links keep their order, the link `title` overrides the default label, empty-URL links are dropped, missing start date gives `when === null`; with `timezone_string` `America/New_York`, `when` has the `-05:00` offset for January text.

- [x] **Step 2: Write the failing `ContentTest`** (create a competition with title "Granite State", venue `Arena <b>One</b>`, host `Host & Co`, address `1 Main St`, start `2030-07-01 10:00`, one link `https://example.org/e?a=1&b=2` titled `Event "page"` and one with an empty URL; render the template with `ob_start(); include Content::add_content_template( '' ); $html = ob_get_clean();` after `setup_postdata`):
  - `test_filter_supplies_the_template_only_for_competitions`: while `get_post_type()` is `hyper_competition` the filter returns a readable path inside `hyperpress-competition/templates/`; for a `post` it returns `''`.
  - `test_full_view_on_the_competitions_own_page` (`go_to( get_permalink( $id ) )`): the HTML contains the countdown section, `Arena &lt;b&gt;One&lt;/b&gt;`, `Host &amp; Co`, `1 Main St`, the formatted date from `wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), ... )`, an `<a href="https://example.org/e?a=1&#038;b=2"` (or `&amp;`) link with `target="_blank"` and `rel="noopener noreferrer"`, and exactly one link.
  - `test_summary_in_a_list`: after `go_to( home_url( '/' ) )`, the HTML contains a title link to the permalink, the date, the venue and address, and none of: `countdown`, `target="_blank"`, `hyperpress-map`.
  - `test_summary_when_listed_inside_another_page`: `go_to` a normal page whose queried object differs from the competition, with the competition set up via `setup_postdata`; `is_full_view()` is false and no countdown markup appears.
  - `test_empty_fields_are_skipped`: a competition with only a title renders no empty labels and no countdown and raises no notice.

- [x] **Step 3: Run to verify they fail.** `docker compose run --rm php composer test:integration -- --filter "Competition.*(Details|Content)Test"`. Expected: FAIL.

- [x] **Step 4: Implement `Details`, `Content` and `templates/content.php`.** Template shape follows `plugins/hyperpress-newsletter/templates/content.php`: `<header>` with the title link (`h2.entry-title`, list views only) and `do_action( 'hyperpress_labels' )`; `<div class="entry-content">` with the thumbnail (`the_post_thumbnail( 'full', array( 'class' => 'thumbnail' ) )`, as the default `content.php` does), then in the full view the countdown (`echo Countdown::render( get_the_ID() )` with a phpcs ignore for pre-escaped output), the formatted date, venue, address, host and the links list (`<ul>` with one `<li><a target="_blank" rel="noopener noreferrer">`); in the summary just the date, venue and address. Escape every value (`esc_html`, `esc_url`, `esc_attr`); format with `wp_date()` as in the spec; Foundation utility classes only, no new SCSS unless markup needs it.

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
