# hyperpress-event

## Intent

The HYPER sites have events that are neither competitions nor one-off countdowns: events the team takes part in (Healthy Kids Day, KidsFest) and fundraisers the alumni host (Bar Crawl, Cookout, Bowl-a-thon). Today each is a hand-built WordPress Page under a parent `/events/` page, for example `https://hyperalumni.org/events/bar-crawl-2024/`, using the "Full Screen"/"wide" page template with `[hyperpress_banner]`-style shortcodes and hand-written Foundation markup in the content (a TimeCircles countdown with inline jQuery, a button row, banner sections for info, tickets and photos).

`hyperpress-event` replaces that for new events with a real post type that gets, for free: a countdown, a place in the front page countdown slot, the same full-screen banner-section layout, e-ticket products on the alumni site (WooCommerce), event photos (NextGEN Gallery), and stable evergreen URLs (`/event/bar-crawl/` always leads to the current Bar Crawl).

The `/events/` Page stays exactly as it is: the hub of the events section (intro text plus a list of events through a shortcode, as the Competitions page does for competitions) and the parent of ordinary subpages such as the build schedule and the competitions page. The post type uses the singular `/event/` base, like `/competition/` with a `/competitions/` hub. Old Page-based events keep working at their current URLs until the owner removes them. The owner controls the content on both sites and recreates any old event he wants by hand. No importer.

## Goals

- A public post type `hyper_event` at `/event/<slug>/`, for example `/event/bar-crawl-2026/`. No post-type archive: the Events page is the hub.
- Events belong to a **series** (Bar Crawl, Cookout). `/event/series/<series>/` is the series archive. `/event/<series>/` is an evergreen alias that redirects to the series' current event.
- A `[hyperpress_event_list]` shortcode for the hub page and anywhere else.
- Each event has its own countdown shortcode and joins the front page countdown slot through the existing `hyperpress_frontpage_countdown_candidates` filter, competing under the existing selection rules (earliest start wins), with its own lead-days setting.
- The single-event page uses the theme's full-screen layout, with breadcrumbs kept and the sidebar removed. The editor content is a stack of banner sections built from shortcodes.
- Banner colour is configurable: an Events settings default, an override on each event, and an override on each section. Banner subtitle: each event sets its own, with a fallback from the Events settings.
- Alumni site only (WooCommerce active): the event can select products (e-tickets) and show them as a compact buy block with a one-step checkout.
- NextGEN active: the event can select galleries and show them: one gallery as a short grid with a "View more" button, several as an album-style view.
- One plugin. The WooCommerce and NextGEN parts load only when those plugins are active, so the hyperonline site shows no dead fields.

## Non-goals / anti-goals

- No importer, redirects or page changes for the old Page-based events or for the `/events/` subpages. Nothing under `/events/` moves, and the plugin adds no routing that touches it.
- No changes to competitions, `hyper_countdown` or the front-page selection rules. Events are one more candidate in the same single slot (an event three weeks out can displace a competition six weeks out; that is intended).
- No season link, schedule days repeater, RSVP/ticketing outside WooCommerce, calendar feed or map in this version.
- No structured buttons/links repeater: the Facebook/Partiful/"Event Info" buttons stay hand-written in the content.
- The theme must not name the plugin. Plugins stay independent: `hyperpress-event` must not hard-require WooCommerce, NextGEN or `hyperpress-frontpage`.
- Must never print two front-page countdowns, never offer a past event on the front page, and never leave an empty banner behind a section that has nothing to show.

## Approaches

**Plugin structure.** (A) One plugin with integrations that switch on only when WooCommerce or NextGEN is active (chosen by the owner). (B) A core plugin plus `hyperpress-event-tickets` and `hyperpress-event-photos` add-ons: cleaner dependencies, but three plugins to version and package for two small features.

**Photos.** (A) Delegate to NextGEN's own shortcodes: a limited grid for one gallery, an album display for several. It reuses the theme's `nggallery/` overrides, so photos look like the rest of the site (chosen by the owner, "let's try it"). (B) Query NextGEN's data and render the plugin's own markup: full control, but it duplicates the theme's NextGEN markup and depends on NextGEN internals. B is the fallback only if the first task's spike (below) shows A cannot work.

**Evergreen URL.** (1) Year-suffixed permanent slugs plus a derived alias (chosen). (2) Start with `bar-crawl` and rename the previous event's slug to `bar-crawl-<year>` when a newer one is created: rejected, it silently rewrites published slugs on save and has many edge cases (drafts, trash, back-dating, republish, caches, shared links).

**URL base and the existing `/events/` Page.** The Page `/events/` has subpages (build schedule, competitions) that must keep working, and a post type whose single URLs are `events/<slug>` would be matched before them and return 404 for those subpages. (1) Post type at the singular `/event/`, hub Page `/events/` with a list shortcode (chosen by the owner; it is how Newsletter and Competition already pair a singular post type with a plural hub, and it needs no routing code). (2) Post type at `/events/` plus a request fallback that serves a child Page when no event matches, with a slug guard: the owner's preferred URL shape, but it adds fallback routing and leaves a ghost parent page. (3) Post type at `/events/` and the subpages moved with 301s: rejected, it changes existing URLs.

**Full-screen layout.** A generic theme filter (chosen) rather than a plugin `template_include` copy of the theme's template, which would duplicate theme markup and drift.

## Post type, taxonomy and URLs

- Namespace `HyperPress\Event`, plugin header and boot as the other plugins (`plugins/AGENTS.md`): `Requires Plugins: hyperpress-utils, hyperpress-countdown, hyperpress-shortcode, meta-box-aio` (`hyperpress-shortcode` because the tickets and photos sections render their banner through its `Banner` class); text domain `hyperpress`; registered keys `hyper_*`, other identifiers `hyperpress_event_*`.
- CPT `hyper_event`: `rewrite` slug `event`, `with_front` false, `has_archive` false, `supports` title, editor, excerpt, thumbnail, `show_in_rest` true. Capabilities, labels and admin-menu placement follow Competition's registration.
- Taxonomy `hyper_event_series`: non-hierarchical, public, `show_in_rest`, `show_admin_column`, `show_in_nav_menus` true, rewrite slug `event/series`, `with_front` false. Rewrite rules for `/event/series/<term>/` and `/event/series/<term>/page/<n>/`.
- The reserved word `series` cannot be an event slug (filter `wp_unique_post_slug_is_bad_flat_slug`), so `/event/series/` can never be an event.
- Plugin activation and deactivation flush rewrite rules. The base `event` is a constant shared by the CPT, taxonomy, alias and menu code, not a setting.
- A regression test proves a Page at `/events/<slug>/` (the hub and a child page) still resolves with the plugin active.

### Evergreen alias `/event/<series-slug>/`

- Handled on `template_redirect` at a priority before `redirect_canonical` (priority 10), only when the request is a 404 for post type `hyper_event`, with `name` equal to the slug of an existing `hyper_event_series` term. Core's own 404 guess (a prefix match on the slug) would otherwise pick an arbitrary `bar-crawl-YYYY`, so ours must run first and be deterministic. The implementation uses priority 0: NextGEN Gallery's router calls `redirect_canonical()` itself at priority 1, so any later priority is bypassed when NextGEN is active.
- Target: among published events in the series, the one with the nearest upcoming end (an event is upcoming while now is before its end), otherwise the one with the latest start. Ties: lowest post ID. No published events: no redirect, normal 404.
- Response: a temporary (302) redirect to the event permalink, because the target changes over time.
- A real event whose slug equals a series slug wins (the alias only runs on a 404). The series edit screen shows a notice when an event with the same slug exists.

### Series archive and the hub

- `/event/series/<term>/` lists the series' events: upcoming first (soonest start first), then past (newest start first) (my call). Pagination via the standard taxonomy `/page/<n>/` rule.
- Hooks follow Competition's `Listing` and `TaxonomyBreadcrumbs`: the theme's `hyperpress_posts_template` filter supplies the listing template; the banner shows the series name with the Events settings fallback subtitle; breadcrumbs read Events > Series name, where "Events" links to the hub page (see Events settings).
- The hub is an ordinary Page (`/events/`) the owner edits. It shows the list with `[hyperpress_event_list]`:
  - Attributes: `series` (a series slug; default all), `show` (`upcoming`, `past` or `all`, default `all`), `limit` (default no limit). Order is upcoming (soonest first) then past (newest first).
  - Each item: title linked to the event, start date, venue and a thumbnail when set. The exact markup is the builder's call; the series archive reuses it.
  - No output when there are no matching events.

### Menus

- Appearance > Menus shows an "Event series" box (hidden by default under Screen Options, normal WordPress behaviour). Ticking a series adds a menu item whose link is the evergreen alias `/event/<slug>/`, not the archive. This is a `wp_setup_nav_menu_item` filter that rewrites `url` only for items of type `taxonomy` and object `hyper_event_series`. It does not change `get_term_link()`, the rewrite rules, the archive, breadcrumbs, or direct visits to `/event/series/<term>/`.
- WordPress marks the current item by the menu item's object (the term), so on the series archive the item should still get its current-item class; a test must prove it against the theme's menu output, since the link and the page differ.
- A Custom Link to any URL still works.

## Event data

Meta Box fields (registered like Competition's `MetaBoxes`), all `hyperpress_event_*`:

- `start` (datetime `Y-m-d H:i`, site timezone; required for the countdown and the front page), `end` (datetime, optional), `venue_name`, `venue_address` (text), `subtitle` (text, the event's banner subtitle; empty means use the Events settings fallback), `banner_color` (hex colour picker; empty means use the default).
- Only when WooCommerce is active: `products` (multi select of published `product` posts).
- Only when NextGEN is active: `galleries` (multi select of NextGEN galleries, loaded through NextGEN's own API).
- "Event over" means now is after `end`, or after the end of the start day (23:59:59, site timezone) when `end` is empty. An event with no start is never treated as over. Time handling uses `wp_timezone()` and `DateTimeImmutable` only (no `strtotime()`).

### Events settings page

An "Events" page under the shared hyperPress AdminMenu, registered like Competition's (`ArchiveBannerSettings::page()` may be reused for the subtitle field, with the meaning "fallback subtitle"; the builder decides whether that helper fits or whether a plain field is simpler), with:

- `default_banner_color` (hex): the fallback colour for all event banners.
- `fallback_subtitle` (text): the banner subtitle for event pages whose own `subtitle` is empty, and for the series archive banner.
- `hub_page` (page select): the Events hub page. Used for the breadcrumb root "Events" on events and series. Empty means the crumb is omitted.
- `frontpage` (checkbox "Show on the front page", default on while there is no saved row; once a row is saved a missing key means off, as Competition's setting behaves) and `frontpage_lead_days` (0 to 365, default 21 (my call: shorter than competitions' 60)).
- `countdown_past_zero` (checkbox, default off).

Subtitle resolution on an event: the event's `subtitle`, then the settings `fallback_subtitle`, then none. Colour resolution is below.

## Countdown and front page

- `[hyperpress_event_countdown id?]` (id optional, defaults to the current event) renders through `HyperPress\Countdown\Renderer::render()`; there is no second rendering path. Arguments: heading = event title, location = venue name, `past_zero` from the setting between start and end (and before the start), slug `event-<ID>`, show date on. Renders nothing when the event has no start. The shortcode shows the countdown on the event's own page, held at zero (`past_zero` false whatever the setting) once the event is over, so it never counts up for months.
- Front page: a `FrontpageCountdown` class (as in Competition) appends one candidate per published `hyper_event` with a start: `id` `event-<ID>`, `start` = the event start, `show_from` = start minus `frontpage_lead_days` (site timezone), `show_until` = the event's end (hard), `fallback_after` absent, `priority` 10, `render` = the shortcode path above. Private, start-less and past (over) events are not offered. The filter does nothing when the setting is off or `hyperpress-frontpage` is inactive.
- This fulfils the follow-up named in the 2026-11-02 front-page countdown spec ("the `hyperpress-event` plugin can be added later by hooking the same filter"); update that spec's notes in the same change.

## Page layout

### Theme (hyperpress-foundation)

- `single.php` gains `$full_screen = (bool) apply_filters( 'hyperpress_single_full_screen', false );`. When true: `.main-container` gets the class `full-screen` (as in `page-templates/page-full-screen.php`) and `get_sidebar()` is skipped. Breadcrumbs, `post-navigation` and comments are unchanged. The theme names no plugin; the filter and its meaning are documented in the theme's `AGENTS.md` beside `content_template` and `hyperpress_posts_template`, and covered by a theme integration test (filter off: unchanged markup and the sidebar; filter on: class present and no sidebar).
- Breadcrumbs sit inside the `full-screen` container on events. The builder checks the result in the browser and adds minimal theme CSS only if they look wrong.

### Event single

- `hyperpress_single_full_screen` returns true for `is_singular( 'hyper_event' )`. The plugin's `content_template` returns its `templates/content.php`, which prints `the_content()` and nothing structural.
- The page's own banner (top of the page) comes from `hyperpress_banner_content` at priority 10 like other plugin banners, with the event title, the resolved subtitle, and the colour from the resolution below. `HyperPress\Utils\Banner` only reads a theme mod, so the event banner is built by filtering `hyperpress_banner_content` after it (builder confirms).
- New events open pre-filled (`default_content`, only for `hyper_event`, only when empty) with a scaffold: the countdown, an info section with placeholder rows, `[hyperpress_event_tickets]`, `[hyperpress_event_photos]`. The editor can reorder or delete sections and add `[hyperpress_banner]` sections of their own. Exact scaffold markup is the builder's call, using the theme's current grid classes (the theme is Foundation 6, not the old site's Foundation 5 `row`/`columns`).

### Banner colour

- `hyperpress-shortcode`'s `[hyperpress_banner]` gains a `color` attribute: a hex value validated with `sanitize_hex_color()` (invalid is ignored) rendered as an inline background colour in the way the theme's own banner does (the builder checks `template-parts/banner.php` for how text contrast is handled).
- When `color` is empty the shortcode applies a generic filter, `hyperpress_banner_shortcode_default_color` ( `string $color`, `array $atts` ), default `''`. `hyperpress-shortcode` knows nothing about events.
- `hyperpress-event` answers that filter on `hyper_event` posts. Resolution order for any banner in an event: shortcode `color` attribute, the event's `banner_color`, the Events settings `default_banner_color`, the theme's default banner colour. The page's top banner uses the same order minus the shortcode.

### Tickets `[hyperpress_event_tickets title? subtitle? color? anchor?]`

- Renders its own banner (default title "Tickets"; same attributes and colour resolution as `[hyperpress_banner]`) followed by one compact row per selected product: name, price (`get_price_html()`, so a sale price shows as the discount), a quantity input, and a "Buy" button that adds the product to the cart and goes straight to checkout (`woocommerce_add_to_cart_redirect` returning `wc_get_checkout_url()` for requests carrying the plugin's marker). Only simple, purchasable products; any other type links to its product page.
- A product that is out of stock stays as a row with "Sold out" and no button. An unpublished, trashed or missing product is skipped.
- The whole block, banner included, outputs nothing when WooCommerce is inactive, no product is selected, no selected product remains, or the event is over.
- The discount is whatever WooCommerce applies (sale price or its own rules). The "door" price on the old page was text in the content, not a product.

### Photos `[hyperpress_event_photos title? subtitle? color? anchor?]`

- Renders its own banner (default title "Photos") and then:
  - One gallery selected: NextGEN's gallery display limited to a handful of images (about 6), with a "View more" button that leads to the full gallery.
  - Several galleries selected: NextGEN's album-style display of those galleries, linking into each.
  - No gallery selected and the event not over: a "Check back after the event" message.
  - No gallery selected and the event over: the whole block is omitted.
  - NextGEN inactive: the whole block is omitted.
- A selected gallery shows whatever the date, so the owner publishes photos by attaching a gallery.

#### First task: spike NextGEN

Before building the photos block, confirm in a running site (hyperalumni stack with NextGEN):

1. whether `[ngg src="galleries" ids="…" display="basic_compact_album"|"basic_extended_album"]` (or NextGEN's equivalent) builds an album view from several gallery IDs, or whether an album object must exist;
2. what a "View more" link can target for a single gallery (in place via NextGEN's display, a link into the Media > Photos gallery URL, or a lightbox of all images), including how it behaves with the theme's `nggallery/` overrides and NextGEN's AJAX pagination (see the theme's `AGENTS.md` NextGEN section: keep its ids and classes).

If NextGEN's shortcodes cannot do either, fall back to approach B for that part and record the decision.

## Constraints

- PHP 8.3 floor, PHPCS WordPress standard, text domain `hyperpress`. PHP runs in Docker only; follow `plugins/AGENTS.md` and the repo root `AGENTS.md`. Shared test setup is under `plugins/tests/`.
- Site timezone everywhere; no `strtotime()` or `current_time( 'timestamp' )`.
- Integrations check for their plugin at runtime (`class_exists`/function checks), never as a hard dependency; saved meta survives deactivation harmlessly.
- Commit with explicit paths (root `AGENTS.md`). Plans: commit the plan on its own first, tick checkboxes in the same commits as the code, and update every plan or spec that mentions a file you change (at least the 2026-11-02 front-page countdown spec and plan).

## Tests the plan must cover

- Alias redirect: nearest upcoming wins over later and past events; with none upcoming the latest past wins; draft/private/trashed events ignored; no events means 404; a real event slug equal to a series slug is served directly; runs before core's guess (a request that core would have guessed to the wrong `bar-crawl-YYYY` goes to the right one); 302 status.
- Series archive: rewrite and pagination, order (upcoming first, then past), banner and breadcrumbs (hub crumb present or omitted by the setting); `series` is a rejected event slug.
- `/events/` Pages (the hub and a child page) still resolve with the plugin active.
- `[hyperpress_event_list]`: `series`, `show` and `limit`, order, empty output.
- Subtitle resolution: event, settings fallback, none.
- Menu: the series menu item's `url` is the alias; `get_term_link()` and the archive are unchanged; the current-item class on the series archive.
- Front page candidate: window opens exactly `lead_days` before start and closes at the event end (end-of-day when no end); skipped when the setting is off, no start, not published, or private; selection against a competition candidate follows the existing earliest-start rule.
- Countdown shortcode output through `Renderer`, including no output without a start.
- Banner shortcode: `color` valid and invalid, filter default, the resolution order shortcode, event, settings, none.
- Tickets: out of stock row, skipped products, no products, WooCommerce inactive, event over, buy-and-checkout redirect, non-simple product link.
- Photos: the four selection states and NextGEN inactive.
- Theme: `hyperpress_single_full_screen` on and off.
- Fields appear only when WooCommerce/NextGEN are active.

## Left to the builder

Class and file layout beyond what is named, the capabilities set, field labels and admin screen layout, scaffold markup, listing templates, the "View more" mechanics after the spike, CSS for the ticket rows and the list, AGENTS.md entries (`plugins/AGENTS.md`, theme `AGENTS.md`), and how the new plugin is added to any packaging or Nx lists.
