# hyperpress-event Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the `hyperpress-event` plugin: an Event post type at `/event/<slug>/` with series, evergreen alias, countdowns on the front page, full-screen banner-section pages, WooCommerce e-tickets and NextGEN photos.

**Architecture:** One plugin in `plugins/hyperpress-event` (namespace `HyperPress\Event`) modelled on `hyperpress-competition`. Two small generic extension points elsewhere: a theme filter for the full-screen single layout and a banner-shortcode `color` attribute with a default-color filter. WooCommerce and NextGEN parts load only when those plugins are active.

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box (`meta-box-aio`), PHPUnit integration tests in Docker, PHPCS (WordPress standard).

**Spec:** `docs/superpowers/specs/2026-11-13-plugins-event-design.md` (read it with this plan).

## Global Constraints

- PHP 8.3 floor, PHPCS WordPress standard, text domain `hyperpress`. PHP runs in Docker only.
- Time: site timezone only (`wp_timezone()`, `DateTimeImmutable`); no `strtotime()` or `current_time( 'timestamp' )`.
- Keys: post type `hyper_event`, taxonomy `hyper_event_series`; other identifiers `hyperpress_event_*` (must match `/^hyperpress_[a-z0-9_]+$/`).
- URLs: CPT base `event` (`has_archive` false), series `event/series/<term>/`, alias `/event/<series-slug>/` (302). `series` is a reserved event slug. Nothing under `/events/` is touched.
- The theme names no plugin. `hyperpress-event` must not hard-require WooCommerce, NextGEN or `hyperpress-frontpage`; integrations check at runtime. Header `Requires Plugins: hyperpress-utils, hyperpress-countdown, hyperpress-shortcode, meta-box-aio`.
- The front page shows exactly one countdown; never a past event's. No section banner is printed when its section has nothing to show.
- Commit with explicit paths: `git add <paths> docs/superpowers/plans/2026-11-13-plugins-event.md` then `git commit -m "type(scope): lowercase summary" -- <paths>`. Never `git add -A`. Tick the task's boxes in the same commit.
- Commands (from the repo root): plugin tests `cd plugins && docker compose run --rm php composer test:integration -- --filter <Name>`, lint `composer lint` the same way (run `composer install` first on a new checkout); theme tests from `themes/hyperpress-foundation` the same way. Time control in tests: filter `hyperpress_event_now`.

## Review Focus

Inputs the spec implies but does not spell out; each has a test in the owning task.

1. An event whose `end` is not after its `start` (Task 3): the end is ignored and the end of the start day is used.
2. Ticket quantity: zero, negative, or beyond a limited stock (Task 13): the input is `min="1"`, defaults to 1, and carries `max` for limited stock.
3. Event shortcodes used outside an event (a Page or widget) (Tasks 4, 13, 15): they print nothing and raise no PHP notice.
4. Alias requests that are not real: wrong case, unknown slug, a series with only drafts (Task 10): the case is normalised, the others stay a 404.
5. Stale saved references: a deleted or unpublished product, a deleted NextGEN gallery (Tasks 13, 15): skipped, and the section falls back to its empty state.

---

## Task 0: Commit the spec and the plan

- [x] Run `git add docs/superpowers/specs/2026-11-13-plugins-event-design.md && git commit -m "docs(specs): hyperpress-event design" -- docs/superpowers/specs/2026-11-13-plugins-event-design.md`
- [x] Run `git add docs/superpowers/plans/2026-11-13-plugins-event.md && git commit -m "docs(plans): hyperpress-event" -- docs/superpowers/plans/2026-11-13-plugins-event.md`

## Task 1: Plugin skeleton, post type, series taxonomy, reserved slug, rewrite flush

**Files:**
- Create: `plugins/hyperpress-event/hyperpress-event.php`, `src/Plugin.php`, `src/Slugs.php`, `src/Rewrite.php`
- Modify: `plugins/tests/src/Support/Convention.php` (add `hyperpress-event` to `PLUGINS` and `LOAD_ORDER` after `hyperpress-frontpage`, its `REQUIRES_PLUGINS` entry, `hyper_event` in `POST_TYPES`, `hyper_event_series` in `TAXONOMIES`)
- Test: `plugins/tests/integration/Event/RegistrationTest.php`

**Interfaces:**
- Produces: `Plugin::POST_TYPE = 'hyper_event'`, `Plugin::SERIES = 'hyper_event_series'`, `Plugin::BASE = 'event'`, `Plugin::boot(): void`, `Plugin::register(): void`; `Slugs::RESERVED = 'series'`, `Slugs::register(): void`; `Rewrite::register(): void`.

- [x] **Step 1: Write the failing tests** in `RegistrationTest` (set `/%postname%/` permalinks and flush in `set_up`):
  - `test_post_type_uses_singular_base_and_has_no_archive`: rewrite slug `event`, `with_front` false, `has_archive` false, `show_in_rest` true, supports title/editor/excerpt/thumbnail.
  - `test_series_taxonomy_routes_under_event_series`: attached to `hyper_event`, rewrite slug `event/series`, `show_in_nav_menus`, `show_admin_column`, not hierarchical; `get_term_link` of term `bar-crawl` ends `/event/series/bar-crawl/`.
  - `test_event_permalink`: `get_permalink` of slug `bar-crawl-2026` ends `/event/bar-crawl-2026/`.
  - `test_series_is_a_reserved_event_slug`: inserting an event with `post_name` `series` gives a different slug.
  - `test_pages_under_events_still_resolve`: a Page `events` with a child `build-schedule`; after `go_to` each permalink, `is_page()` is true and `is_404()` false.
  - The existing convention tests (`PluginHeadersTest`, `KeyConventionTest`, `RegistrationParityTest`, `TaxonomyAttachmentTest`, `CapabilitiesTest`, `IdentifierConventionTest`) pass with the new plugin.
- [x] **Step 2:** Run the filter `RegistrationTest`; expect failures (plugin missing).
- [x] **Step 3: Implement.** Main file and autoloader copy `hyperpress-competition.php` with prefix `HyperPress\Event\`, the header's `Requires Plugins` above and `Plugin::boot` on `plugins_loaded`. `Plugin::register()` calls `PostTypeRegistrar::register()` with `slug` `event`, `archive` `false`, plus `TaxonomyRegistrar::register()` for the series (`slug` `event/series`, `object_types` `hyper_event`, `show_admin_column` true) and `TaxonomyAdminFilter::register`; call `Capabilities::ensure` on `init` 20 like Competition. `Slugs` filters `wp_unique_post_slug_is_bad_flat_slug` to reject `series` for `hyper_event`. `Rewrite` flushes once per plugin version using an option (copy `hyperpress-competition/src/Rewrite.php::maybe_flush`).
- [x] **Step 4:** Run `RegistrationTest` and the convention tests above; expect PASS. Run `composer lint`; expect 0 violations.
- [x] **Step 5: Commit** `feat(event): add event post type and series taxonomy`.

## Task 2: Events settings

**Files:**
- Create: `src/Settings.php`, `src/SettingsPage.php`
- Test: `plugins/tests/integration/Event/SettingsTest.php`

**Interfaces:**
- Produces: `Settings::OPTION = 'hyperpress_event'`; constants `DEFAULT_BANNER_COLOR`, `FALLBACK_SUBTITLE`, `HUB_PAGE`, `FRONTPAGE`, `FRONTPAGE_LEAD_DAYS`, `COUNTDOWN_PAST_ZERO` (values `hyperpress_event_default_banner_color`, `hyperpress_event_fallback_subtitle`, `hyperpress_event_hub_page`, `hyperpress_event_frontpage`, `hyperpress_event_frontpage_lead_days`, `hyperpress_event_countdown_past_zero`); `Settings::default_banner_color(): string` (hex or ''), `fallback_subtitle(): string`, `hub_page_id(): int` (0 unless a published Page), `frontpage_enabled(): bool`, `frontpage_lead_days(): int` (0 to 365, default 21), `past_zero(): bool`, `Settings::default_option( mixed $value, string $option, mixed $default ): mixed`; `SettingsPage::ID = 'hyperpress_event'`, `SettingsPage::register(): void`.

- [x] **Step 1: Write the failing tests** (`SettingsTest`):
  - `test_defaults_without_saved_row`: frontpage on, lead days 21, past zero off, colour '' , subtitle '', hub 0.
  - `test_saved_row_without_checkbox_key_means_off`: `update_option( OPTION, array() )` makes `frontpage_enabled()` false.
  - `test_lead_days_range`: `'30'` gives 30, `'0'` gives 0, `'366'`, `'-1'`, `'abc'` give 21.
  - `test_color_accepts_hex_only`: `#F15623` kept, `red` gives ''.
  - `test_hub_page_must_be_a_published_page`: draft page, trashed page, a post ID and a missing ID all give 0.
  - `test_settings_page_is_under_the_hyperpress_menu`: `apply_filters( 'mb_settings_pages', array() )` contains id `hyperpress_event` with `parent` `AdminMenu::SLUG`.
- [x] **Step 2:** Run `--filter SettingsTest`; expect FAIL.
- [x] **Step 3: Implement** following `hyperpress-competition/src/Settings.php` and `SettingsPage.php` (default-option filter on `default_option_hyperpress_event`, accessor sanitising). `SettingsPage::pages()` adds the page with `ArchiveBannerSettings::page( ID, OPTION, __( 'Events', 'hyperpress' ) )`. The meta box with the six fields on that page is registered in Task 3's `Fields` class (id `hyperpress_event_settings_box`, `settings_pages` `SettingsPage::ID`; a colour field, text field, `post` field restricted to `page`, two checkboxes, one number field 0 to 365).
- [x] **Step 4:** Run `SettingsTest` and `IdentifierConventionTest`; expect PASS.
- [x] **Step 5: Commit** `feat(event): add events settings`.

## Task 3: Event fields, integrations switch and dates

**Files:**
- Create: `src/Fields.php`, `src/Integrations.php`, `src/Dates.php`
- Test: `plugins/tests/integration/Event/DatesTest.php`, `FieldsTest.php`

**Interfaces:**
- Produces: `Integrations::woocommerce(): bool` and `Integrations::nextgen(): bool` (`class_exists( 'WooCommerce' )`, `class_exists( 'Imagely\NGG\DataMappers\Album' )`, each overridable by filters `hyperpress_event_woocommerce_active` and `hyperpress_event_nextgen_active`); `Fields::START`, `END`, `VENUE_NAME`, `VENUE_ADDRESS`, `SUBTITLE`, `BANNER_COLOR`, `PRODUCTS`, `GALLERIES` (all `hyperpress_event_<name>`), `Fields::register(): void` (hooks `rwmb_meta_boxes`; box id `hyperpress_event_box`, plus the settings box from Task 2); `Dates::now(): DateTimeImmutable` (filter `hyperpress_event_now`), `Dates::start( int $post_id ): ?DateTimeImmutable`, `Dates::end( int $post_id ): ?DateTimeImmutable` (null without a start), `Dates::is_over( int $post_id ): bool`.

- [x] **Step 1: Write the failing tests.**
  - `DatesTest`: `test_start_is_parsed_in_the_site_timezone` (timezone `America/New_York`, `2026-11-28 14:00`); `test_end_defaults_to_the_end_of_the_start_day` (`2026-11-28 23:59:59`); `test_end_not_after_start_is_ignored` (end earlier than, and equal to, start both give the end of day; Review Focus 1); `test_no_start_gives_null_end_and_never_over`; `test_malformed_start_is_null` (`garbage`, `2026-02-30 10:00`); `test_is_over_is_strict` (at the end instant false, one second later true); `test_now_filter`.
  - `FieldsTest`: the box has the six common fields; `products` absent and `galleries` absent by default; present when the `hyperpress_event_woocommerce_active` / `hyperpress_event_nextgen_active` filters return true; every field id passes `IdentifierConventionTest`'s pattern.
- [x] **Step 2:** Run both filters; expect FAIL.
- [x] **Step 3: Implement.** `Fields` mirrors `hyperpress-competition/src/MetaBoxes.php` (a `datetime` field with `save_format` `Y-m-d H:i`, a `color` field for the override, `post` field with `post_type` `product` multiple `select_advanced` for products, a `select_advanced` with `multiple` for galleries whose options come from `Galleries::options()` (Task 15; until then an empty array via `class_exists` guard)). `Dates` parses with `DateTimeImmutable::createFromFormat( '!Y-m-d H:i', …, wp_timezone() )` and rejects values with parse errors.
- [x] **Step 4:** Run the filters and `composer lint`; expect PASS.
- [x] **Step 5: Commit** `feat(event): add event fields and date helpers`.

## Task 4: Countdown shortcode and front page candidate

**Files:**
- Create: `src/Countdown.php`, `src/FrontpageCountdown.php`
- Test: `plugins/tests/integration/Event/CountdownTest.php`, `FrontpageCountdownTest.php`

**Interfaces:**
- Consumes: `Dates`, `Settings`, `Fields`; `HyperPress\Countdown\Renderer::render( DateTimeImmutable $when, array $args ): string` (args `name`, `location`, `past_zero`, `slug`, `show_date`).
- Produces: `Countdown::SHORTCODE = 'hyperpress_event_countdown'`, `Countdown::register(): void`, `Countdown::shortcode( $atts ): string`, `Countdown::render( int $id ): string`; `FrontpageCountdown::register(): void`, `FrontpageCountdown::candidates( array $candidates ): array` (filter `hyperpress_frontpage_countdown_candidates`).

- [x] **Step 1: Write the failing tests.**
  - `CountdownTest`: `test_explicit_id_renders_through_the_shared_renderer` (output has the event title, venue name and a `data-date` attribute; the element id starts `event-<id>`); `test_no_id_uses_the_current_event`; `test_past_zero_follows_the_setting`; `test_empty_without_a_start`; `test_empty_for_a_draft_or_other_post_type`; `test_empty_outside_an_event_without_notices` (a Page as the current post, Review Focus 3).
  - `FrontpageCountdownTest`: `test_candidate_shape_and_window` (id `event-<ID>`, `start` the event start, `show_from` = start minus 21 days, `show_until` = the event end, no `fallback_after`, priority 10, `render` returns the countdown HTML); `test_lead_days_keep_wall_clock_across_dst` (start `2026-11-02 09:00` New York, lead 3: `show_from` is `2026-10-30 09:00`); `test_skipped_when_setting_off_no_start_draft_or_private`; `test_end_of_day_window_when_no_end`; `test_earliest_start_wins_against_a_competition_candidate` (pass an event candidate and a synthetic one to `HyperPress\Frontpage\Countdowns::pick`).
- [x] **Step 2:** Run both filters; expect FAIL.
- [x] **Step 3: Implement** after `hyperpress-competition/src/Countdown.php` and `FrontpageCountdown.php` (read both). Use `Settings::frontpage_lead_days()` with a `DateInterval` subtraction on the tz-aware start; query published `hyper_event` posts only.
- [x] **Step 4:** Run the filters and lint; expect PASS.
- [x] **Step 5: Commit** `feat(event): add event countdown and front page candidates`.

## Task 5: Theme full-screen filter

**Files:**
- Modify: `themes/hyperpress-foundation/single.php`, `themes/hyperpress-foundation/AGENTS.md` (filter list)
- Test: `themes/hyperpress-foundation/tests/integration/SingleFullScreenTest.php` (pattern: `FrontPageContentTemplateTest`)

**Interfaces:**
- Produces: filter `hyperpress_single_full_screen` (`bool`, default `false`) applied in `single.php`.

- [x] **Step 1: Write the failing tests** that render `single.php` for a post into an output buffer: `test_default_single_is_unchanged` (container class is exactly `main-container`, the sidebar from `get_sidebar()` present); `test_filter_true_adds_full_screen_and_drops_the_sidebar`; `test_filter_true_keeps_breadcrumbs_and_hooks` (breadcrumb markup present; `hyperpress_before_main` and `hyperpress_after_main` fire).
- [x] **Step 2:** Run `--filter SingleFullScreenTest` in the theme; expect FAIL.
- [x] **Step 3: Implement** `$full_screen = (bool) apply_filters( 'hyperpress_single_full_screen', false );`, add the class `full-screen` to `.main-container` when true and call `get_sidebar()` only when false. Document the filter beside `content_template` in the theme `AGENTS.md`.
- [x] **Step 4:** Run the theme integration suite and `composer lint`; expect PASS.
- [x] **Step 5: Commit** `feat(theme): add hyperpress_single_full_screen filter`.

## Task 6: Banner shortcode colour

**Files:**
- Modify: `plugins/hyperpress-shortcode/src/Shortcodes/Banner.php`
- Test: `plugins/tests/integration/Shortcodes/BannerColorTest.php` (place beside the plugin's other tests)

**Interfaces:**
- Produces: attribute `color` on `[hyperpress_banner]`; filter `hyperpress_banner_shortcode_default_color` ( `string $color`, `array $atts` ): string.

- [x] **Step 1: Write the failing tests:** `test_color_attribute_sets_the_inline_background` (`color="#F15623"` gives `style="background-color: #F15623"` on the `<header>`); `test_invalid_colors_are_dropped` (`red`, `#fff;background:url(x)`, `"><script>` produce no `style=` at all); `test_filter_supplies_the_default_when_the_attribute_is_empty`; `test_attribute_wins_over_the_filter`; `test_non_hex_filter_result_is_ignored`; `test_markup_unchanged_without_a_color` (exact previous string) and `test_empty_without_title_and_subtitle`.
- [x] **Step 2:** Run `--filter BannerColorTest`; expect FAIL.
- [x] **Step 3: Implement** the attribute with `sanitize_hex_color()`; when empty, run the filter and sanitise its result; add `style="background-color: <hex>"` to the `<header>` using the same format as `themes/hyperpress-foundation/template-parts/banner.php`.
- [x] **Step 4:** Run the filter and lint; expect PASS.
- [x] **Step 5: Commit** `feat(shortcode): add color attribute to the banner shortcode`.

## Task 7: Banner colour and subtitle resolution

**Files:**
- Create: `src/Appearance.php`, `src/Sections.php`
- Test: `plugins/tests/integration/Event/AppearanceTest.php`

**Interfaces:**
- Consumes: Task 2 `Settings`, Task 3 `Fields`, Task 6 filter, `HyperPress\Utils\Banner::register( string $post_type, array $args )` (args `type`, `subtitle` callable, `color_mod`, `archive_title`) and its `hyperpress_banner_args` filter; `HyperPress\Shortcode\Shortcodes\Banner::render( array $atts, ?string $content = null ): string`.
- Produces: `Appearance::color( int $post_id, string $attr = '' ): string` (attribute, then the event's `banner_color`, then `Settings::default_banner_color()`, each valid hex only, else ''); `Appearance::subtitle( int $post_id ): string` (event `subtitle`, then `Settings::fallback_subtitle()`, else ''); `Appearance::register(): void`; `Sections::atts( $atts, string $default_title ): array` (keys `title`, `subtitle`, `color`, `anchor`); `Sections::banner( int $post_id, array $atts ): string` ('' when title and subtitle are both empty).

- [x] **Step 1: Write the failing tests:** `test_color_resolution_order` (attribute over event over settings over ''; an invalid stored colour is skipped to the next level); `test_subtitle_resolution_order`; `test_default_color_filter_answers_only_for_the_current_event` (in an event context `[hyperpress_banner title="x"]` shows the event colour, on a Page it is unchanged); `test_page_banner_uses_event_title_subtitle_and_colour` (`apply_filters( 'hyperpress_banner_content', … )` on a singular event); `test_section_banner_passes_anchor_and_colour_and_is_empty_without_text`.
- [x] **Step 2:** Run `--filter AppearanceTest`; expect FAIL.
- [x] **Step 3: Implement.** Register the event's page banner with `Banner::register( 'hyper_event', … )` and adjust per post through `hyperpress_banner_args` (pattern: `hyperpress-season/src/SingularBanner.php::adjust`; read it first). `Sections::banner()` calls the shortcode plugin's `Banner::render()` with the resolved colour.
- [x] **Step 4:** Run the filter and lint; expect PASS.
- [x] **Step 5: Commit** `feat(event): resolve banner colour and subtitle`.

## Task 8: Single event page

**Files:**
- Create: `src/Single.php`, `templates/content.php`
- Test: `plugins/tests/integration/Event/SingleTest.php`

**Interfaces:**
- Consumes: Task 5 filter. Produces: `Single::register(): void`.

- [x] **Step 1: Write the failing tests:** `test_full_screen_only_on_a_singular_event` (true for a singular event, false for a post, a Page and the series archive); `test_content_template_only_for_events` (returns the plugin's `templates/content.php`, passes other values through); `test_template_prints_the_content_with_shortcodes_run`; `test_new_event_gets_the_scaffold` (`default_content` for `hyper_event` with empty content contains `[hyperpress_event_countdown]`, `[hyperpress_event_tickets]` and `[hyperpress_event_photos]`; unchanged for other post types and for non-empty content); `test_theme_renders_the_event_full_screen` (rendering the theme's `single.php` gives `main-container full-screen`, breadcrumbs, no sidebar).
- [x] **Step 2:** Run `--filter SingleTest`; expect FAIL.
- [x] **Step 3: Implement** the three hooks (`hyperpress_single_full_screen`, `content_template`, `default_content`). The scaffold (countdown, info section with placeholder rows, tickets, photos) uses the theme's Foundation 6 grid classes.
- [x] **Step 4:** Run the filter, the theme suite and lint; expect PASS.
- [x] **Step 5: Commit** `feat(event): add the full-screen single event page`.

## Task 9: Listing, `[hyperpress_event_list]` and the series archive

**Files:**
- Create: `src/Listing.php`, `src/ListShortcode.php`, `src/SeriesArchive.php`, `templates/series.php`
- Test: `plugins/tests/integration/Event/ListingTest.php`

**Interfaces:**
- Produces: `Listing::ids( string $series = '', string $show = 'all', int $limit = 0, ?DateTimeImmutable $now = null ): array<int>` (upcoming means not yet over, soonest start first, then past, newest start first; events without a start last); `Listing::html( array $ids ): string`; `ListShortcode::SHORTCODE = 'hyperpress_event_list'` (attributes `series`, `show`, `limit`); `SeriesArchive::register(): void`.

- [x] **Step 1: Write the failing tests:** `test_order_is_upcoming_then_past`; `test_show_upcoming_past_all_and_unknown_means_all`; `test_series_filter_and_unknown_series_is_empty`; `test_limit_applies_after_ordering_and_negative_means_none`; `test_drafts_private_and_trashed_are_excluded`; `test_shortcode_markup_has_title_link_date_venue_and_thumbnail_when_present`; `test_shortcode_is_empty_without_events`; `test_series_archive_uses_the_plugin_template_and_order` (main query on `is_tax( 'hyper_event_series' )` is ordered by `post__in` from `Listing::ids`, and page 2 of a paginated archive holds the next ids); `test_series_banner_uses_the_term_name_and_fallback_subtitle` (the theme's priority 80 and 90 fallbacks do not replace it).
- [x] **Step 2:** Run `--filter ListingTest`; expect FAIL.
- [x] **Step 3: Implement.** `SeriesArchive` hooks `pre_get_posts` (series main query: `post__in` = `Listing::ids( slug )`, or `array( 0 )` when empty, `orderby` `post__in`), `hyperpress_posts_template` and `hyperpress_banner_content` at priority 10. Pattern: `hyperpress-competition/src/Listing.php` and `Competitions.php`.
- [x] **Step 4:** Run the filter and lint; expect PASS.
- [x] **Step 5: Commit** `feat(event): add event list shortcode and series archive`.

## Task 10: Evergreen alias redirect

**Files:**
- Create: `src/Alias.php`
- Test: `plugins/tests/integration/Event/AliasTest.php`

**Interfaces:**
- Produces: `Alias::target( WP_Term $series, ?DateTimeImmutable $now = null ): ?int` (post ID), `Alias::register(): void`, `Alias::maybe_redirect(): void` (on `template_redirect` priority 5).

- [x] **Step 1: Write the failing tests** (capture redirects with the `wp_redirect` filter returning false, which makes `wp_safe_redirect()` return false so no `exit`): `test_nearest_upcoming_wins`; `test_latest_past_when_none_upcoming`; `test_tie_goes_to_the_lowest_id`; `test_unpublished_events_are_ignored`; `test_series_without_published_events_stays_a_404`; `test_runs_before_the_core_guess` (events `bar-crawl-2025` past with the lower ID and `bar-crawl-2026` upcoming: the redirect goes to 2026); `test_a_real_event_with_the_series_slug_is_served_directly`; `test_unrelated_404_is_untouched` (`/event/nothing/`) and `test_mixed_case_alias_redirects` (`/event/Bar-Crawl/`, Review Focus 4); `test_status_is_302`.
- [x] **Step 2:** Run `--filter AliasTest`; expect FAIL.
- [x] **Step 3: Implement.** Act only when `is_404()`, `get_query_var( 'post_type' )` is `hyper_event` and `get_term_by( 'slug', sanitize_title( get_query_var( 'name' ) ), SERIES )` exists; choose with `Dates::end()` against `Dates::now()`; redirect with `wp_safe_redirect( get_permalink( $id ), 302 )` and `exit` only when it returns true.
- [x] **Step 4:** Run the filter and lint; expect PASS.
- [x] **Step 5: Commit** `feat(event): redirect the series alias to the current event`.

## Task 11: Breadcrumbs

**Files:**
- Create: `src/Breadcrumbs.php`
- Test: `plugins/tests/integration/Event/BreadcrumbsTest.php`

**Interfaces:**
- Produces: `Breadcrumbs::register(): void`, `Breadcrumbs::filter( array $items ): array` (filter `hyperpress_breadcrumbs_content`, priority 10; crumb shape in the theme `AGENTS.md`).

- [x] **Step 1: Write the failing tests:** `test_single_event_crumbs` (Events linking to the hub page, then the series linking to its archive, then the title with no url); `test_hub_crumb_omitted_without_a_hub_page`; `test_event_without_a_series_skips_that_crumb`; `test_series_archive_crumbs` (Events, then the series name); `test_other_contexts_untouched`.
- [x] **Step 2:** Run `--filter BreadcrumbsTest`; expect FAIL.
- [x] **Step 3: Implement** after `hyperpress-competition/src/TaxonomyBreadcrumbs.php::filter` (read it for the context detection and crumb keys). Do not use `Utils\Breadcrumbs::register`: its first crumb links to `get_post_type_archive_link()` (events have no archive, so "Events" would have no link and the hub page from the settings cannot be injected), and it only handles `is_singular` and `is_post_type_archive`, not the series (taxonomy) archive. It skips the season crumb when a post has no season, so that is not the reason.
- [x] **Step 4:** Run the filter and lint; expect PASS.
- [x] **Step 5: Commit** `feat(event): add event breadcrumbs`.

## Task 12: Series menu items link to the alias

**Files:**
- Create: `src/Menus.php`
- Test: `plugins/tests/integration/Event/MenusTest.php`

**Interfaces:**
- Produces: `Menus::register(): void`, `Menus::url( object $item ): object` (filter `wp_setup_nav_menu_item`).

- [x] **Step 1: Write the failing tests:** `test_series_item_links_to_the_alias` (`type` `taxonomy`, `object` `hyper_event_series` gives `home_url( '/event/<slug>/' )`); `test_other_items_and_invalid_items_are_untouched`; `test_term_link_and_archive_are_unchanged` (`get_term_link` still `/event/series/<slug>/`); `test_series_item_is_current_on_the_archive` (render `wp_nav_menu( array( 'menu' => $id, 'echo' => false ) )` after `go_to` the series archive: the item has `current-menu-item` while its `href` is the alias).
- [x] **Step 2:** Run `--filter MenusTest`; expect FAIL.
- [x] **Step 3: Implement** the filter; keep `$item->object_id` untouched so WordPress still marks the current item.
- [x] **Step 4:** Run the filter; expect PASS. Re-check the current-item test against the theme's walker output with a menu in a registered location if the default walker passes but the theme's does not.
- [x] **Step 5: Commit** `feat(event): link series menu items to the alias`.

## Task 13: Tickets (WooCommerce)

**Files:**
- Create: `src/Ticket.php`, `src/WooTickets.php`, `src/TicketsBlock.php`, `src/Tickets.php`, `plugins/tests/src/Support/FakeProduct.php` (a stand-in for `WC_Product` and stubs for `wc_get_product()` and `wc_get_checkout_url()`, defined only when absent)
- Test: `plugins/tests/integration/Event/TicketsTest.php`

**Interfaces:**
- Produces: `Ticket` (final, readonly): `int $id`, `string $name`, `string $price_html`, `bool $in_stock`, `bool $simple`, `string $url`, `int $max` (0 means unlimited); `WooTickets::load( array $ids ): array<Ticket>`; `TicketsBlock::html( array $tickets, int $event_id ): string`; `Tickets::SHORTCODE = 'hyperpress_event_tickets'`, `Tickets::register(): void`, `Tickets::shortcode( $atts ): string`, `Tickets::checkout_redirect( string $url ): string` (filter `woocommerce_add_to_cart_redirect`, marker request key `hyperpress_event_buy`).

- [x] **Step 1: Write the failing tests:**
  - `load`: `test_published_simple_in_stock_product_becomes_a_ticket`; `test_out_of_stock_product_stays_with_in_stock_false`; `test_missing_unpublished_trashed_and_non_product_ids_are_skipped` (Review Focus 5); `test_non_simple_product_is_flagged_and_links_to_its_page`; `test_selection_order_kept_and_duplicates_dropped`.
  - `html`: `test_row_has_name_price_quantity_and_buy_form` (quantity `name="quantity" type="number" min="1" value="1"`, hidden `add-to-cart` and `hyperpress_event_buy`, form action the event permalink, method get); `test_limited_stock_sets_max` and `test_unlimited_has_no_max` (Review Focus 2); `test_sold_out_row_has_text_and_no_button`; `test_non_simple_row_is_a_link_without_a_form`.
  - `shortcode`: `test_empty_when_woocommerce_inactive`, `test_empty_without_products`, `test_empty_when_no_product_remains`, `test_empty_when_the_event_is_over`, `test_empty_outside_an_event_without_notices` (Review Focus 3); `test_prints_its_own_banner_with_default_title_tickets_and_custom_attributes`.
  - `checkout_redirect`: with the marker returns the checkout URL, without it returns the input.
- [x] **Step 2:** Run `--filter TicketsTest`; expect FAIL.
- [x] **Step 3: Implement.** The shortcode uses `Integrations::woocommerce()` (so tests switch it with the filter), `Fields::PRODUCTS`, `Dates::is_over()` and `Sections::banner()`.
- [x] **Step 4:** Run the filter and lint; expect PASS.
- [x] **Step 5: Commit** `feat(event): add the e-ticket block`.

## Task 14: NextGEN spike (findings only)

**Files:**
- Modify: this plan (a "Spike findings" section appended below Task 16)

- [ ] **Step 1:** On a local stack with NextGEN (install it in the stack with `docker compose --profile local run --rm wpcli wp plugin install nextgen-gallery --activate` if missing, then create two galleries with images), try: `[ngg src="galleries" ids="1,2" display="basic_compact_album"]` and `basic_extended_album`; a single `[ngg src="galleries" ids="1" display="basic_thumbnail" images_per_page="6"]`.
- [ ] **Step 2:** Record in the plan: (a) whether several gallery IDs make an album view without an album object, and the exact working shortcode; (b) what "View more" can target for one gallery (in place, a gallery URL, or a lightbox) and the exact markup or link; (c) behaviour with the theme's `nggallery/` overrides and AJAX pagination (keep NextGEN's ids and classes); (d) the NextGEN detection class and the gallery table columns used for options (`ngg_gallery.gid`, `title`).
- [ ] **Step 3:** If (a) fails, record the fallback (the plugin renders its own album markup from the theme's `template-parts/nextgen/`), and adjust Task 15's interfaces accordingly.
- [ ] **Step 4: Commit** `docs(plans): record the nextgen spike findings`.

## Task 15: Photos (NextGEN)

**Files:**
- Create: `src/Galleries.php`, `src/NextGenShortcodes.php`, `src/PhotosBlock.php`, `src/Photos.php`
- Test: `plugins/tests/integration/Event/PhotosTest.php`

**Interfaces:**
- Produces (adjust names and shortcode strings to the Task 14 findings): `Galleries::options(): array<int,string>` (id to title from `{$wpdb->prefix}ngg_gallery`), `Galleries::existing( array $ids ): array<int>`; `NextGenShortcodes::single( int $id, int $limit = 6 ): string`, `NextGenShortcodes::album( array $ids ): string`; `PhotosBlock::html( array $gallery_ids, bool $over, string $banner ): string`; `Photos::SHORTCODE = 'hyperpress_event_photos'`, `Photos::register(): void`, `Photos::shortcode( $atts ): string`.

- [ ] **Step 1: Write the failing tests** (a temporary `ngg_gallery` table is created in the test; `pre_do_shortcode_tag` fakes the `ngg` shortcode):
  - `test_options_and_existing`: options from the table, `existing` drops IDs that are not in it.
  - `test_one_gallery_uses_the_single_display_with_a_limit_and_a_view_more_link`; `test_several_galleries_use_the_album_display`.
  - `test_no_gallery_and_event_not_over_prints_check_back_after_the_event`; `test_no_gallery_and_event_over_prints_nothing`; `test_selected_gallery_shows_whatever_the_date`.
  - `test_deleted_gallery_ids_fall_back_to_the_no_gallery_state` (Review Focus 5).
  - `test_empty_when_nextgen_inactive` and `test_empty_outside_an_event_without_notices` (Review Focus 3); `test_prints_its_own_banner_with_default_title_photos`.
- [ ] **Step 2:** Run `--filter PhotosTest`; expect FAIL.
- [ ] **Step 3: Implement** per the spike. Keep NextGEN's wrapper ids and classes untouched (theme `AGENTS.md`). Replace the `Galleries::options()` guard in `Fields` with the real call.
- [ ] **Step 4:** Run the filter and lint; expect PASS.
- [ ] **Step 5: Commit** `feat(event): add the event photos block`.

## Task 16: Docs, full verification and manual check

**Files:**
- Modify: `plugins/AGENTS.md` (new plugin entry: keys, URLs, shortcodes, filters, Woo/NextGEN switches), `docs/superpowers/specs/2026-11-02-plugins-frontpage-countdown-selection-design.md` and its plan `docs/superpowers/plans/2026-11-02-plugins-frontpage-countdown-selection.md` (note that `hyperpress-event` now hooks the filter), this plan (banner and status)

- [ ] **Step 1:** Update the files above. Grep `docs/superpowers/plans/` for `frontpage_countdown_candidates`, `hyperpress_banner` and `content_template` and update any other plan that mentions a changed file.
- [ ] **Step 2:** Run the whole plugin suites (`composer lint`, `test:unit`, `test:integration`) and the theme suites; all green. Run `pnpm fmt:check` (load Node first: `source ~/.nvm/nvm.sh && nvm use && export PATH="$HOME/.local/share/pnpm/bin:$PATH"`); fix with `pnpm fmt`.
- [ ] **Step 3: Manual check** on the hyperalumni stack (WooCommerce and NextGEN) and the hyperonline stack (neither): activate `hyperpress-event`; create series "Bar Crawl" and two events; check `/event/bar-crawl-2026/` full-screen layout with breadcrumbs and no sidebar (add minimal theme CSS only if the breadcrumbs look wrong), `/event/bar-crawl/` redirect, `/event/series/bar-crawl/`, the Events page with `[hyperpress_event_list]`, `/events/` subpages unchanged, the series menu box, the front page countdown, a ticket purchase to checkout, a sold-out product, photos with one and several galleries; confirm hyperonline shows no ticket or gallery fields.
- [ ] **Step 4:** Add the `> **STATUS: ✅ COMPLETED**` banner to this plan.
- [ ] **Step 5: Commit** `docs(event): document the event plugin` with the changed docs paths.

## Known follow-ups

- A per-event "hide from the front page" switch, a buttons/links repeater, a season link, and an importer for old Page-based events were left out on purpose (spec non-goals).
