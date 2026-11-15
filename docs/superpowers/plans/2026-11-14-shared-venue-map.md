# Shared Venue Map Implementation Plan

> **STATUS: ✅ COMPLETED** (2026-11-14). Tasks 1 to 4 done: `HyperPress\Utils\VenueMap` replaces the map code in competition, event and contact (contact zoom ceiling 21 to 19). Lint clean, unit 256, integration 1949 (6 skipped). Owner checked the competition, event and contact pages and the pin search in a browser. Commits f36d936c..9f4e3d46.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the three copies of the OpenStreetMap code in competition, event and contact with one `HyperPress\Utils\VenueMap` class, with no change in what visitors or editors see (except contact's zoom ceiling, 21 to 19).

**Architecture:** A static class in `hyperpress-utils` holds `parse`, `sanitize`, `render` (the shared markup and `render_map` call), `for_post` (post-meta data access for competition and event) and the admin autocomplete fix. Each plugin keeps its own wiring and data access and calls it.

**Tech Stack:** PHP 8.3 (Docker only), WordPress, Meta Box (`RWMB_OSM_Field`), PHPUnit unit and integration suites, PHPCS.

**Spec:** `docs/superpowers/specs/2026-11-14-shared-venue-map-design.md`

## Global Constraints

- Text domain `hyperpress`; escape all output; PHPCS must pass (`composer lint`).
- `VenueMap` is `final`, static, namespace `HyperPress\Utils`, guard `defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`, file `plugins/hyperpress-utils/src/VenueMap.php`.
- `MAX_ZOOM` 19, `DEFAULT_ZOOM` 15; pin format `lat,lng[,zoom]`; lat in [-90,90], lng in [-180,180]; the sanitizer regex is `/^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?(,\s*\d+)?$/`.
- Markup is unchanged per plugin: competition `<div class="hyperpress-map" role="img" aria-label="…" style="pointer-events:none;">`, event `<div class="hyperpress-map alignfull" role="img" aria-label="…" style="pointer-events:none;padding-top:0;margin-bottom:1.5rem;">`, contact the competition shape with the label "Map of our location".
- Contact's default pin (`std`) is kept; no default pin on competition or event.
- PHP runs in Docker only, from `plugins/`: `docker compose run --rm php composer lint|test:unit|test:integration`; targeted: `docker compose run --rm php vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap-integration.php --filter <regex>`. Never two compose runs at once.
- Commits (owner's standing rule): this plan and the spec are committed on their own first; each task's code is committed with that task's plan checkboxes ticked in the same commit; older plans that mention a changed file get a dated note in the same commit. Explicit pathspecs, `type(scope): lowercase summary`.

## Review Focus

- A pin with zoom above 19 (contact used to allow 21): clamps to 19.
- Whitespace-only address or a pin outside the lat/lng ranges: no map, no fatal (competition and event).
- Contact with no valid location, or Meta Box inactive: visitors get `''`, administrators get the HTML comment (unchanged).
- Text in the venue name or label containing quotes or markup: escaped in the `aria-label`.
- A non-competition or non-event post, and the competition/event list pages: still no map.

---

### Task 1: The shared `VenueMap` class

**Files:**
- Create: `plugins/hyperpress-utils/src/VenueMap.php`
- Test: `plugins/tests/integration/Utils/VenueMapTest.php` (create; the `Utils` test dir may need creating), plus `plugins/tests/unit/Utils/VenueMapTest.php` for the pure `parse`/`sanitize` tests if the unit bootstrap can load `HyperPress\Utils\*` (the builder checks; otherwise keep them in the integration test)
- Modify: `plugins/AGENTS.md` (one bullet describing `VenueMap` and who uses it)

**Interfaces:**
- Produces (exact signatures): `VenueMap::parse( mixed $value ): ?array` (`array{lat:float,lng:float,zoom:int}|null`); `VenueMap::sanitize( mixed $value ): string`; `VenueMap::render( array $location, string $label, string $height = '300px', string $class = '', string $style = '' ): string`; `VenueMap::for_post( int $post_id, string $name_key, string $address_key, string $map_key, string $class = '', string $style = '' ): string`; `VenueMap::register_admin_fix( string $post_type ): void`; `VenueMap::enqueue_admin_fix( string $hook_suffix, string $post_type ): void`; constants `DEFAULT_ZOOM = 15`, `MAX_ZOOM = 19`.

- [x] **Step 1: Write the failing tests** (`VenueMapTest`), derived from the existing tests `tests/integration/Competition/MapTest.php`, `tests/integration/Event/MapTest.php` and `tests/integration/Competition/AdminAssetsTest.php`:
  - `parse`: `'1,2'` zoom 15; `'1,2,99'` zoom 19; `'1,2,-4'` zoom 0; `'1, 2 , 3'` accepted; `'garbage'`, `'91,0'`, `'0,181'`, `'1'`, `'1,2,3,4'`, `''`, `array()` and `null` give null.
  - `sanitize`: keeps `42.1,-71.2,15`, `42.25,-71.0`, `42.25228605221164, -71.00051344845554,15` (space after the comma); returns `''` for `<script>alert(1)</script>`, `42.25;-71.0`, `42.25,-71.0,15,extra`, `array( '42.25,-71.0' )`; trims surrounding whitespace.
  - `render`: with `RWMB_OSM_Field::$calls` cleared, `render( array( 'lat' => 42.1, 'lng' => -71.2, 'zoom' => 15 ), 'L', '250px' )` makes one call with location `42.1,-71.2,15`, `width` `100%`, `height` `250px`, `marker` true and all seven `js_options` false, and returns `<div class="hyperpress-map" role="img" aria-label="L" style="pointer-events:none;"><div class="rwmb-map-canvas-stub"></div></div>`; with `$class` `alignfull` and `$style` `padding-top:0;margin-bottom:1.5rem;` the wrapper opens with `<div class="hyperpress-map alignfull" role="img" aria-label="L" style="pointer-events:none;padding-top:0;margin-bottom:1.5rem;">`; a label containing `"` and `<` is escaped; a coordinate such as `1.00000001` or `1.0E-5` is formatted as a plain decimal (`1`, `0.00001`) and `-0.0` as `0`.
  - `for_post`: event-shaped meta (`$GLOBALS['hyperpress_test_rwmb_posts'][ $id ]` for name and address, `update_post_meta` for the pin; use any post type): renders at `300px` with label "Map of Big Arena"; no venue name gives `aria-label="Map of the venue"`; data provider of unusable inputs (`empty address`, `whitespace address`, `empty map`, `garbage map`, `latitude high` `91,0`, `longitude high` `0,181`) gives `''` with no `render_map` calls; `$post_id` 0 gives `''`; extra class and style are passed through.
  - Admin fix: port competition's `AdminAssetsTest` to `enqueue_admin_fix( $hook, $post_type )` (attaches `.ui-autocomplete.ui-front{z-index:999999}` to `rwmb-osm` on `post.php` and `post-new.php` for the given post type only; nothing on other hooks, other post types or without a screen); `register_admin_fix( 'x' )` leaves `has_action( 'admin_enqueue_scripts' )` registered at priority 20.

- [x] **Step 2: Run to verify failure.** Targeted filter `/Tests.Integration.Utils.VenueMapTest/` (and the unit file). Expected: errors, `Class HyperPress\Utils\VenueMap not found`.

- [x] **Step 3: Implement `VenueMap`** per the spec's Design section and the signatures above, copying `parse`/`decimal`/the `render_map` arguments from `plugins/hyperpress-competition/src/Map.php` and the admin CSS from `plugins/hyperpress-competition/src/AdminAssets.php`. `render()` composes the location string from `$location` with the shared `decimal()` helper (private).

- [x] **Step 4: Run the new tests green, then** `composer lint`, `composer test:unit`, `composer test:integration` (nothing else changes, so all other tests stay green).

- [x] **Step 5: Document.** Add a bullet to `plugins/AGENTS.md`: `HyperPress\Utils\VenueMap` is the one place for venue-map code (`parse`, `sanitize`, `render`, `for_post`, `register_admin_fix`); competition, event and contact use it.

- [x] **Step 6: Commit.** `feat(utils): add a shared venue map class`.

### Task 2: Competition uses `VenueMap`

**Files:**
- Modify: `plugins/hyperpress-competition/src/Map.php` (keep `register()`/`render()`, drop `parse()` and `decimal()`), `src/MetaBoxes.php` (sanitize callback; delete `map_location`), `src/Plugin.php` (replace `AdminAssets::register();` with `\HyperPress\Utils\VenueMap::register_admin_fix( self::POST_TYPE );`)
- Delete: `plugins/hyperpress-competition/src/AdminAssets.php`, `plugins/tests/integration/Competition/AdminAssetsTest.php`
- Test: `plugins/tests/integration/Competition/MapTest.php`, `Competition/BootTest.php`, `plugins/tests/integration/CompetitionSettingsTest.php`

**Interfaces:**
- Consumes: `VenueMap::for_post`, `VenueMap::sanitize`, `VenueMap::register_admin_fix` from Task 1.
- Produces: `Map::register(): void` and `Map::render(): void` keep their signatures and output.

- [x] **Step 1: Adjust the tests first.** `Competition/MapTest`: drop `test_parse_clamps_zoom_and_defaults_it` (covered by `VenueMapTest`) and keep every other test unchanged (render, unusable locations, lists and other posts, hook registered). `Competition/BootTest`: add an assertion that `has_action( 'admin_enqueue_scripts' )` has a callback at priority 20 after boot (or assert via `VenueMap`'s behaviour on a competition edit screen if simpler). `CompetitionSettingsTest::test_map_field_keeps_only_lat_lng_zoom` stays as it is (the field's callback is now `VenueMap::sanitize`); add `$this->assertSame( array( VenueMap::class, 'sanitize' ), $field['sanitize_callback'] )` in a field test if one reads the field arrays. Run: the unchanged render tests must still pass, the changed ones fail until Step 2.

- [x] **Step 2: Implement.** `Map::render()` keeps its guards (`is_singular( Plugin::POST_TYPE )`, queried object ID) and echoes `VenueMap::for_post( $post_id, 'hyperpress_competition_venue_name', 'hyperpress_competition_venue_address', 'hyperpress_competition_venue_map' )` (markup is escaped inside `VenueMap`; keep a `phpcs:ignore` for the echo). Delete `AdminAssets.php` and its test; replace the sanitize callback with `array( \HyperPress\Utils\VenueMap::class, 'sanitize' )` and delete `MetaBoxes::map_location`.

- [x] **Step 3: Verify.** Targeted `/Competition|CompetitionMapField|CompetitionSettings/`, then `composer lint`, `composer test:unit`, `composer test:integration`. The competition output must be byte-identical (the existing render assertions prove it).

- [x] **Step 4: Document.** Dated note in `docs/superpowers/plans/2026-10-18-competition-templates.md` (the map now delegates to `VenueMap`; `AdminAssets` and `map_location` moved to utils).

- [x] **Step 5: Commit.** `refactor(competition): use the shared venue map`.

### Task 3: Event uses `VenueMap`

**Files:**
- Modify: `plugins/hyperpress-event/src/Info.php` (call `VenueMap::for_post( $post->ID, Fields::VENUE_NAME, Fields::VENUE_ADDRESS, Fields::VENUE_MAP, 'alignfull', 'padding-top:0;margin-bottom:1.5rem;' )` where it currently calls `Map::html( $post->ID )`), `src/Fields.php` (sanitize callback; delete `map_location`), `src/Plugin.php` (replace `AdminAssets::register();` with `\HyperPress\Utils\VenueMap::register_admin_fix( self::POST_TYPE );`)
- Delete: `plugins/hyperpress-event/src/Map.php`, `plugins/hyperpress-event/src/AdminAssets.php`
- Test: `plugins/tests/integration/Event/MapTest.php` (delete; its cases now live in `VenueMapTest`), `Event/InfoTest.php`, `Event/FieldsTest.php`

**Interfaces:**
- Consumes: `VenueMap::for_post`, `VenueMap::sanitize`, `VenueMap::register_admin_fix`.

- [x] **Step 1: Adjust the tests first.** Delete `Event/MapTest.php` after moving anything not yet covered by `VenueMapTest` (label fallback and the unusable-locations cases already are); in `InfoTest` keep `test_map_follows_the_banner_and_precedes_the_columns`, `test_no_map_without_a_pin` and `test_no_map_without_an_address` unchanged, and add `test_map_wrapper_is_full_width` asserting the exact opening tag `<div class="hyperpress-map alignfull" role="img" aria-label="Map of Big Arena" style="pointer-events:none;padding-top:0;margin-bottom:1.5rem;">`; in `FieldsTest` replace `test_map_location_keeps_only_lat_lng_zoom` with an assertion that the venue-map field's `sanitize_callback` is `array( VenueMap::class, 'sanitize' )`, and add `test_boot_registers_the_admin_fix` (`has_action( 'admin_enqueue_scripts' )` at priority 20 after `Plugin::boot()`). Run: new assertions fail until Step 2.

- [x] **Step 2: Implement** as listed under Files, then delete the two classes and `Event/MapTest.php`.

- [x] **Step 3: Verify.** Targeted `/Tests.Integration.Event/`, then `composer lint`, `composer test:unit`, `composer test:integration`. The Event Info output must be byte-identical to before this task.

- [x] **Step 4: Document.** Update the map sentence in the Events paragraph of `plugins/AGENTS.md` (the map comes from `VenueMap::for_post`, there is no event `Map` or `AdminAssets` class); dated notes in `docs/superpowers/plans/2026-11-13-plugins-event.md` and `docs/superpowers/plans/2026-11-14-event-venue-map.md` (its "Known follow-ups" shared-class item is now done; point to this plan).

- [x] **Step 5: Commit.** `refactor(event): use the shared venue map`.

### Task 4: Contact uses `VenueMap`

**Files:**
- Modify: `plugins/hyperpress-contact/src/MapShortcode.php` (use `Settings::map()`, `VenueMap::render( $map, __( 'Map of our location', 'hyperpress' ), $height . 'px' )`; delete the private `location()` and `decimal()` and the `use RWMB_OSM_Field;`; keep the `class_exists( 'RWMB_OSM_Field' )` notice branch), `src/Settings.php` (`parse_map( string $value ): ?array` returns `\HyperPress\Utils\VenueMap::parse( $value )`; delete the now-unused `DEFAULT_ZOOM`/`MAX_ZOOM` constants if nothing else references them)
- Test: `plugins/tests/integration/Contact/MapShortcodeTest.php`, and the unit test(s) of `Settings::parse_map` (find with `grep -rln parse_map plugins/tests`)

**Interfaces:**
- Consumes: `VenueMap::parse`, `VenueMap::render`.
- Produces: `Settings::parse_map( string ): ?array` and `Settings::map(): ?array` keep their signatures; the zoom ceiling is now 19.

- [x] **Step 1: Adjust the tests first.** Wherever a test asserts that zoom 20 or 21 is kept, change it to expect 19 (`'1,2,21'` gives zoom 19); add `'1,2,20'` gives 19. All other `MapShortcodeTest` and `parse_map` assertions stay unchanged (height clamp, default 250, notices for administrators and visitors, markup with "Map of our location"). If the unit suite cannot load `HyperPress\Utils\VenueMap`, move the pure `parse_map` unit tests to `VenueMapTest` and keep one integration test that `Settings::parse_map` delegates. Run: the zoom assertions fail until Step 2.

- [x] **Step 2: Implement** as listed under Files.

- [x] **Step 3: Verify.** Targeted `/Contact/`, then `composer lint`, `composer test:unit`, `composer test:integration`. The shortcode output must be byte-identical except zoom clamping.

- [x] **Step 4: Document.** Update the contact line in `plugins/AGENTS.md` if it mentions the map (otherwise none); dated note in `docs/superpowers/plans/2026-10-12-hyperpress-contact.md` (map code now in `VenueMap`; zoom ceiling 19; default pin kept).

- [x] **Step 5: Final checks.** `grep -rn "render_map\|RWMB_OSM_Field" plugins --include=*.php` outside `tests/` shows only `VenueMap.php` and the `class_exists` guard in contact; `grep -rn "map_location\|AdminAssets" plugins/hyperpress-* --include=*.php` shows nothing stale. Full `composer lint`, `composer test:unit`, `composer test:integration` green (report counts and skipped).

- [x] **Step 6: Manual check (owner, browser).** Competition single page, event single page and a page with `[hyperpress_map]` look as before, and the pin search still works in the competition and event editors.

- [x] **Step 7: Commit.** `refactor(contact): use the shared venue map`.

## Execution notes

- Task 1: the `$class` parameter of `render` and `for_post` is named `$css_class` (PHPCS flags `$class` as a reserved word); callers pass it positionally, so the signatures above are otherwise exact. `parse` and `sanitize` are tested as unit tests (`tests/unit/Utils/VenueMapTest.php`; the unit bootstrap loads `HyperPress\Utils\VenueMap`), the rest as integration tests (`tests/integration/Utils/VenueMapTest.php`). `register_admin_fix` registers a closure, so its test counts the priority-20 callbacks on `admin_enqueue_scripts` before and after. Suites after Task 1: lint clean, unit 256, integration 1961 (6 skipped).
- Task 4: the contact unit bootstrap autoloads `HyperPress\Utils\VenueMap` (the existing `SettingsMapTest` ran against it unchanged), so the `parse_map` unit tests stayed in `tests/unit/Contact/SettingsMapTest.php` and no move was needed. The zoom assertions now expect 19 (`99`, `21` and `20` all clamp to 19); the old `'0,0,21'` data-set became `'0,0,19'` plus two clamp cases. `Settings`' `DEFAULT_ZOOM` and `MAX_ZOOM` were unreferenced elsewhere and are gone (the other constants were re-aligned for PHPCS). Suites after Task 4: lint clean, unit 256, integration 1949 (6 skipped).

## Known follow-ups

- The contact settings page's autocomplete z-index (not in scope; it is a settings page).
