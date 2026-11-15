# Shared venue map (design)

Status: approved by the owner in conversation, 2026-11-14.

## Intent

The competition, event and contact plugins each carry their own copy of the same OpenStreetMap code (location parsing, the `RWMB_OSM_Field::render_map` call and its non-interactive options, the wrapper markup, the coordinate formatter, the pin sanitizer, and the autocomplete z-index fix). Put it in one class in `hyperpress-utils` so a fix is made once.

## Goals

- One class, `HyperPress\Utils\VenueMap`, that all three plugins use.
- No visible change: every plugin prints the markup it prints today (see Non-goals for the one edge case).
- Each plugin keeps only its own data access and wiring.

## Non-goals

- No change to the field definitions (labels and descriptions differ per plugin), settings, or markup.
- Contact's default pin (`std` `42.2518999,-71.0007911,17`) stays (owner's call); competition's and event's stay removed.
- The contact settings page does not get the autocomplete z-index fix (it is a settings page, not a post screen; that would be new behaviour).
- One edge case changes: contact's zoom ceiling moves from 21 to the shared 19 (OpenStreetMap's standard tiles stop at 19, so 20 and 21 only show blank tiles).

## Approaches

1. **One static class in `hyperpress-utils`, parameterised by meta keys (chosen).** Same style as `Meta` and `Banner`; both plugins already require `hyperpress-utils` and `meta-box-aio`, so no header changes.
2. A shared abstract base class with a subclass per plugin. More ceremony for three call sites.
3. A filter-based map service in utils. Over-built.

## Design

`HyperPress\Utils\VenueMap` (`plugins/hyperpress-utils/src/VenueMap.php`, final, static, same `ABSPATH` guard as `Meta.php`):

- `DEFAULT_ZOOM` 15, `MAX_ZOOM` 19.
- `parse( mixed $value ): ?array` returns `{lat:float,lng:float,zoom:int}` for `lat,lng` or `lat,lng,zoom` (non-string, wrong part count, non-numeric, or lat outside -90..90 / lng outside -180..180 gives null; zoom defaults to 15 and is clamped to 0..19). Pure: no WordPress functions.
- `sanitize( mixed $value ): string` is the Meta Box sanitize callback: the trimmed value when it matches `/^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?(,\s*\d+)?$/`, else `''`; non-string gives `''`. Range checking stays in `parse` at render time.
- `render( array $location, string $label, string $height = '300px', string $class = '', string $style = '' ): string` is the shared core: the `RWMB_OSM_Field::render_map` call (width `100%`, the given height, `marker` true, the seven `js_options` set false) inside `<div class="hyperpress-map{ class}" role="img" aria-label="{label}" style="pointer-events:none;{style}">…</div>`. `$class` is appended after a space when non-empty, `$style` is appended directly after `pointer-events:none;`, both and the label through `esc_attr`. The location string is `lat,lng,zoom` with coordinates as plain decimals (up to 7 places, trailing zeros removed, `-0` and empty as `0`).
- `for_post( int $post_id, string $name_key, string $address_key, string $map_key, string $class = '', string $style = '' ): string` is `''` when `RWMB_OSM_Field` is missing, the ID is not positive, the address (read with `\HyperPress\Utils\Meta::get`) is empty or whitespace, or the pin (read with `get_post_meta`, not `rwmb_meta`, which returns rendered HTML for osm fields) does not parse. The label is "Map of {venue name}" or "Map of the venue" (venue name read with `Meta::get`). Otherwise `render( $location, $label, '300px', $class, $style )`.
- `register_admin_fix( string $post_type ): void` hooks `admin_enqueue_scripts` at priority 20; `enqueue_admin_fix( string $hook_suffix, string $post_type ): void` adds the inline CSS `.ui-autocomplete.ui-front{z-index:999999}` to the `rwmb-osm` style on `post.php` / `post-new.php` for that post type only (competition's current behaviour).

Per plugin:

- **Competition:** a thin `Map` keeps `register()` (the `hyperpress_after_banner` hook) and `render()` (singular competition only, echoes `VenueMap::for_post( $id, 'hyperpress_competition_venue_name', 'hyperpress_competition_venue_address', 'hyperpress_competition_venue_map' )`). `AdminAssets`, `Map::parse` and `MetaBoxes::map_location` go; the field's `sanitize_callback` is `array( VenueMap::class, 'sanitize' )`; `Plugin::boot` calls `VenueMap::register_admin_fix( Plugin::POST_TYPE )`.
- **Event:** `Info::shortcode` calls `VenueMap::for_post( $post->ID, Fields::VENUE_NAME, Fields::VENUE_ADDRESS, Fields::VENUE_MAP, 'alignfull', 'padding-top:0;margin-bottom:1.5rem;' )` between its banner and its columns. `Map`, `AdminAssets` and `Fields::map_location` go; the field's `sanitize_callback` is `array( VenueMap::class, 'sanitize' )`; `Plugin::boot` calls `VenueMap::register_admin_fix( Plugin::POST_TYPE )`.
- **Contact:** `MapShortcode` keeps its `height` attribute (default 250, clamped 100..1000), its admin-only HTML-comment notices, its "Map of our location" label and its option read; it calls `VenueMap::render( $map, $label, $height . 'px' )`; its private `location()` and `decimal()` go. `Settings::parse_map( string )` delegates to `VenueMap::parse`, keeping its name and signature.

## Constraints

- Plugin conventions in `plugins/AGENTS.md` (text domain `hyperpress`, escaping, PHPCS, Docker-only PHP, PHP 8.3 floor).
- `Settings::parse_map` is documented as pure; if the unit-test bootstrap cannot load the utils class, move those unit tests next to `VenueMap`'s rather than loading WordPress.
- Test stubs: `RWMB_OSM_Field` is stubbed in `tests/bootstrap-integration.php`; the Meta Box post stub is `$GLOBALS['hyperpress_test_rwmb_posts']`.

## Left to the builder

- Where `VenueMapTest` goes (unit for pure `parse`/`sanitize`, integration for the rest) and which existing tests become wiring tests.
- The exact name of the closure-free way to hook `register_admin_fix` (a static callback with the post type bound is fine).
