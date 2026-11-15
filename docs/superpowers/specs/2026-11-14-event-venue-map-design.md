# Event venue map (design)

Status: approved by the owner in conversation, 2026-11-14.

## Intent

Show a map on the single event page when the event has a venue address, the way `hyperpress-competition` does. Editors set the pin in the admin; existing events show a map once someone re-saves them with a pin.

## Goals

- A full-width, non-interactive OpenStreetMap (300px, marker) on a single `hyper_event` page, directly after the "Event Info" banner (inside `[hyperpress_event_info]`, before the Date/Time/Where row), only when the event has a venue address and a valid pin. (Revised 2026-11-14: first built under the page banner on `hyperpress_after_banner`, moved into the Event Info section at the owner's request.)
- Same look and behaviour as the competition map.

## Non-goals

- No server-side geocoding of existing addresses.
- No change to the venue name/address fields or the `[hyperpress_event_info]` shortcode.
- No shared map class in `hyperpress-utils` (possible follow-up; it would mean refactoring competition).
- No new settings.

## Approaches

1. **Copy competition's pattern into `hyperpress-event` (chosen).** Smallest change, no cross-plugin refactor, consistent with how the other plugins are built.
2. Extract a shared map class into `hyperpress-utils` and use it from both plugins. Less duplication, but it rewrites working competition code, which was not asked for.
3. Geocode the address server-side. Covers existing events without editing, but needs a geocoding service, caching and failure handling. Declined.

## Design

- `Fields::VENUE_MAP` (`hyperpress_event_venue_map`): a Meta Box `osm` field in the event box after the venue address, `address_field` set to `Fields::VENUE_ADDRESS`, with a sanitizer that keeps only the `lat,lng[,zoom]` shape and returns `''` otherwise (mirrors `HyperPress\Competition\MetaBoxes::map_location`; like competition, range checking is left to `Map::parse` at render time). **No default (`std`) pin**: a default would put a wrong marker on any event that has an address but no real pin. Builder to confirm how the field behaves without `std`.
- `HyperPress\Event\Map` (`src/Map.php`): no hook of its own: `Map::html( int $post_id ): string` returns the markup, and `Info::shortcode` places it between its banner and its columns. It returns `''` when `RWMB_OSM_Field` is missing, the address is empty or the stored pin does not parse. The map therefore shows only where the Event Info section shows (an event with an address always has a Where column), and not when an author leaves `[hyperpress_event_info]` out of the content. Reads the stored text with `get_post_meta` (not `rwmb_meta`, which returns rendered HTML for osm fields). `aria-label` is "Map of {venue name}" or "Map of the venue".
- `HyperPress\Event\AdminAssets`: on the event edit screen, raise `.ui-autocomplete.ui-front` above the Leaflet map (as competition's `AdminAssets` does).
- Register the new classes in `Plugin.php`; add a line to the Events paragraph of `plugins/AGENTS.md`.
- Tests: unit tests for pin parsing and the sanitizer; integration test for rendering (valid, no address, bad pin, non-event page).

## Constraints

- Plugin conventions in `plugins/AGENTS.md`: text domain `hyperpress`, escaping, PHPCS, Docker-only PHP, one class per concern.
- `hyperpress-event` already requires `meta-box-aio`.

## Left to the builder

- Where the tests go and which existing test pattern to follow.
- Confirming `hyperpress_after_banner` fires on event singles (the banner partial is used by the page banner path).
- Exact field behaviour without `std`.

## Separate follow-up (second task, after the event map)

Remove the hard-coded default pin (`'std' => '42.25228605221164, -71.00051344845554,15'`) from the competition `venue_map` field (`plugins/hyperpress-competition/src/MetaBoxes.php`). Already saved values are unaffected; new competitions start with no pin until the editor sets one. Check competition tests and docs that mention the default.
