# Event Venue Map Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Show a non-interactive OpenStreetMap under the banner of a single event when it has a venue address and a pin; then drop the hard-coded default pin from the competition map field.

**Architecture:** Copy competition's pattern into `hyperpress-event`: an `osm` Meta Box field linked to the venue address, a `Map` class on the theme's `hyperpress_after_banner` action that renders through `RWMB_OSM_Field::render_map`, and an `AdminAssets` z-index fix. Competition's `std` default pin is then removed in a separate task.

**Tech Stack:** PHP 8.3 (Docker only), WordPress, Meta Box (`meta-box-aio`, `RWMB_OSM_Field`), PHPUnit integration suite (`WP_UnitTestCase`), PHPCS.

**Spec:** `docs/superpowers/specs/2026-11-14-event-venue-map-design.md`

## Global Constraints

- Text domain `hyperpress`; escape all output; PHPCS (WordPress standard, PHPCompatibilityWP `testVersion 8.3-`) must pass.
- Namespace `HyperPress\Event`, one class per concern in `plugins/hyperpress-event/src/`; identifiers `hyperpress_event_{field}`.
- PHP runs in Docker only: from `plugins/`, `docker compose run --rm php composer lint|test:integration|test:unit`. Run the targeted test with `docker compose run --rm php vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap-integration.php --filter <Name>`.
- Read the stored pin with `get_post_meta` (not `rwmb_meta`: for an osm field Meta Box returns rendered HTML).
- Pin value format `lat,lng[,zoom]`; zoom default 15, max 19; lat in [-90,90], lng in [-180,180].
- No default (`std`) pin on the event field.
- Commit from the repo root with explicit pathspecs (`git add <paths>` then `git commit -m "..." -- <paths>`); `type(scope): lowercase summary`. Commit only when the owner asks.

## Review Focus

- Address set but pin empty: nothing printed (pinned by the "unusable" data provider).
- Pin set but address empty: nothing printed.
- Out-of-range or garbage pin saved by hand/import: nothing printed, no fatal.
- Non-event page or list page where `hyperpress_after_banner` also fires: nothing printed.
- Whitespace-only address or venue name: treated as empty; label falls back to "Map of the venue".

---

### Task 1: Event venue map

**Files:**
- Modify: `plugins/hyperpress-event/src/Fields.php` (add `VENUE_MAP` const and field after the venue address field, `Fields.php:66-71`; add `map_location` sanitizer)
- Create: `plugins/hyperpress-event/src/Map.php`
- Create: `plugins/hyperpress-event/src/AdminAssets.php`
- Modify: `plugins/hyperpress-event/src/Plugin.php` (`boot()`: add `Map::register();` and `AdminAssets::register();`)
- Modify: `plugins/AGENTS.md` (Events paragraph)
- Test: `plugins/tests/integration/Event/MapTest.php` (create), `plugins/tests/integration/Event/FieldsTest.php` (add field and sanitizer tests)

**Interfaces:**
- Produces: `Fields::VENUE_MAP = 'hyperpress_event_venue_map'`; `Fields::map_location( mixed $value ): string`; `Map::register(): void`; `Map::render(): void`; `Map::parse( mixed $value ): ?array{lat:float,lng:float,zoom:int}`; `AdminAssets::register(): void`; `AdminAssets::enqueue( string $hook_suffix ): void`.
- Consumes: `Plugin::POST_TYPE` (`hyper_event`), `Fields::VENUE_NAME`, `Fields::VENUE_ADDRESS`, `\HyperPress\Utils\Meta::get( string $key, int $post_id )`, `RWMB_OSM_Field::render_map( string $location, array $args )`.

- [x] **Step 1: Write the failing tests.** Create `tests/integration/Event/MapTest.php` (namespace `HyperPress\Tests\Integration\Event`, `final class MapTest extends WP_UnitTestCase`) following the structure of `tests/integration/Competition/MapTest.php` with `Fields::VENUE_*` keys and `Plugin::POST_TYPE` from `HyperPress\Event`. Create the event post with `self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_status' => 'publish' ) )`, set `$GLOBALS['hyperpress_test_rwmb_posts'][ $id ]` for address/name, and `update_post_meta( $id, Fields::VENUE_MAP, ... )` for the pin; clear `RWMB_OSM_Field::$calls` in `set_up` and remove the `hyperpress_after_banner` action in `tear_down`. Tests:
  - `test_renders_the_events_location_full_width`: address `1 Main St`, name `Big Arena`, pin `42.1,-71.2,15` → one `RWMB_OSM_Field::$calls` entry with location `42.1,-71.2,15`, `height` `300px`, `width` `100%`, `marker` true, `js_options.dragging` false; output contains `class="hyperpress-map"`, `role="img"`, `Big Arena`, `pointer-events:none;`.
  - `test_label_falls_back_without_a_venue_name`: no name → output contains `aria-label="Map of the venue"`.
  - `test_nothing_without_an_address_or_a_valid_location` with `@dataProvider unusable_locations`: cases `empty address` (`''`, `42.1,-71.2,15`), `whitespace address` (`'   '`, `42.1,-71.2,15`), `empty map` (`1 Main St`, `''`), `garbage map`, `latitude high` (`91,0`), `longitude high` (`0,181`) → output `''` and `RWMB_OSM_Field::$calls === array()`.
  - `test_nothing_on_lists_and_other_posts`: home URL and an ordinary post carrying the same meta → output `''`, no calls.
  - `test_parse_clamps_zoom_and_defaults_it`: `parse('1,2')['zoom'] === 15`, `'1,2,99'` → 19, `'1,2,-4'` → 0, `'garbage'` and `array()` → null.
  - `test_hook_is_registered`: after `Map::register()`, `has_action( 'hyperpress_after_banner', array( Map::class, 'render' ) )` is not false.
  - `test_boot_registers_the_hook_and_admin_assets`: `Plugin::boot()` has already run in the integration bootstrap; assert `has_action( 'hyperpress_after_banner', array( Map::class, 'render' ) )` and `has_action( 'admin_enqueue_scripts', array( AdminAssets::class, 'enqueue' ) )` are both not false (if the suite's bootstrap does not boot the plugin, call `Plugin::boot()` once in this test, mirroring `tests/integration/Competition/BootTest.php`).
  - In `FieldsTest.php`, add `test_venue_map_field_is_an_osm_field_linked_to_the_address`: the field `hyperpress_event_venue_map` in `rwmb_meta_boxes` has type `osm`, `address_field` `hyperpress_event_venue_address`, no `api_key` key, **no `std` key**, and sits directly after the address field. Add `test_map_location_keeps_only_lat_lng_zoom`: `Fields::map_location` returns the input for `42.1,-71.2,15` and `42.25,-71.0`; `''` for `<script>alert(1)</script>`, `42.25;-71.0`, `42.25,-71.0,15,extra`, and `array( '42.25,-71.0' )`.

- [x] **Step 2: Run to verify failure.** Run `docker compose run --rm php vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap-integration.php --filter 'Event\\\\(MapTest|FieldsTest)'` from `plugins/`. Expected: failures/errors (class `HyperPress\Event\Map` not found; field missing).

- [x] **Step 3: Add the field and sanitizer.** In `Fields.php`: add `public const VENUE_MAP = 'hyperpress_event_venue_map';` after `VENUE_ADDRESS`; in `event_box()` add, directly after the venue-address entry, an array with `id` `self::VENUE_MAP`, `name` `__( 'Location', 'hyperpress' )`, `desc` `__( 'Click the map to place the pin; typing the venue address above also finds it. The map shows on the event page when the address is set.', 'hyperpress' )`, `type` `osm`, `address_field` `self::VENUE_ADDRESS`, `sanitize_callback` `array( self::class, 'map_location' )`. Add `public static function map_location( mixed $value ): string` with the same behavior as `HyperPress\Competition\MetaBoxes::map_location` (non-string → `''`; trim; keep only `/^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?(,\s*\d+)?$/`, else `''`).

- [x] **Step 4: Create `Map.php`.** `final class Map` in `HyperPress\Event`, same header guard as `Fields.php` (`defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`), `use RWMB_OSM_Field;`. `register()` adds `hyperpress_after_banner` → `render`. `render()` and `parse()` are the competition implementations (`plugins/hyperpress-competition/src/Map.php:23-111`) with `Plugin::POST_TYPE`, `Fields::VENUE_ADDRESS`, `Fields::VENUE_NAME`, `Fields::VENUE_MAP` substituted, the same class doc/translators comments, and the same `RWMB_OSM_Field::render_map` arguments and printed wrapper. Copy `decimal()` as a private helper too.

- [x] **Step 5: Create `AdminAssets.php`.** Same as `plugins/hyperpress-competition/src/AdminAssets.php` in namespace `HyperPress\Event`, comparing `get_current_screen()->post_type` with `Plugin::POST_TYPE`.

- [x] **Step 6: Register.** In `Plugin::boot()` add `Map::register();` and `AdminAssets::register();` after `Info::register();`.

- [x] **Step 7: Run the targeted tests and the PHP suites.** Targeted filter as in Step 2: all pass. Then from `plugins/`: `docker compose run --rm php composer lint` (no PHPCS errors), `composer test:unit` and `composer test:integration` (all pass; `IdentifierConventionTest`, `KeyConventionTest` and `MetaBoxSanitizeCallbacksTest` must stay green with the new field).

- [x] **Step 8: Document.** In `plugins/AGENTS.md`, in the Events paragraph, add one sentence: the event box has an `osm` field `hyperpress_event_venue_map` (no default pin) linked to the venue address, and `Map` prints a full-width non-interactive OpenStreetMap on `hyperpress_after_banner` on a single event only when the address is set and the pin parses; `AdminAssets` lifts the address autocomplete above the Leaflet map.

- [ ] **Step 9: Manual check.** Start the stack the owner uses, edit an event, search the address in the Location field, save, open the event: map appears under the banner; clear the address, save: no map. Report what was and wasn't checked.

- [x] **Step 10: Commit (only when the owner asks).**

```bash
git add plugins/hyperpress-event/src/Fields.php plugins/hyperpress-event/src/Map.php plugins/hyperpress-event/src/AdminAssets.php plugins/hyperpress-event/src/Plugin.php plugins/AGENTS.md plugins/tests/integration/Event/MapTest.php plugins/tests/integration/Event/FieldsTest.php docs/superpowers/plans/2026-11-14-event-venue-map.md docs/superpowers/specs/2026-11-14-event-venue-map-design.md
git commit -m "feat(event): show a venue map on the single event page" -- <same paths>
```

### Task 2: Remove the competition default pin

**Files:**
- Modify: `plugins/hyperpress-competition/src/MetaBoxes.php:120` (delete the `'std' => '42.25228605221164, -71.00051344845554,15',` line)
- Test: `plugins/tests/integration/CompetitionMapFieldTest.php`, `plugins/tests/integration/CompetitionSettingsTest.php:164-174`

**Interfaces:**
- Consumes: the `hyperpress_competition_venue_map` field array. Saved values are untouched; new competitions start with no pin.

- [ ] **Step 1: Write the failing test.** In `CompetitionMapFieldTest::test_venue_map_field_is_an_osm_field_without_an_api_key` add `$this->assertArrayNotHasKey( 'std', $field );` (rename the test to `test_venue_map_field_is_an_osm_field_without_an_api_key_or_default_pin`).

- [ ] **Step 2: Run to verify it fails.** `docker compose run --rm php vendor/bin/phpunit --testsuite integration --bootstrap tests/bootstrap-integration.php --filter CompetitionMapFieldTest`. Expected: FAIL on the `std` assertion.

- [ ] **Step 3: Remove the `std` line** from the field in `MetaBoxes.php`. In `CompetitionSettingsTest::test_map_field_keeps_only_lat_lng_zoom`, delete the assertion at line 169 (`'the registered default has a space'`); the space-after-comma shape is still accepted by the sanitizer regex, so keep an equivalent assertion without the "registered default" wording only if the owner wants the space case pinned.

- [ ] **Step 4: Verify.** Targeted tests pass; then `composer lint`, `composer test:unit`, `composer test:integration` from `plugins/`. Grep `docs/` and `plugins/` for `42.2522` and update any prose that still describes the default pin (the earlier search found only the three code/test lines above).

- [ ] **Step 5: Commit (only when the owner asks).** `git commit -m "fix(competition): drop the default venue pin" -- plugins/hyperpress-competition/src/MetaBoxes.php plugins/tests/integration/CompetitionMapFieldTest.php plugins/tests/integration/CompetitionSettingsTest.php`

## Execution notes

- 2026-11-14: Task 1's `--filter 'Event\\\\(MapTest|FieldsTest)'` matched nothing; the targeted run used `--filter '/Tests.Integration.Event.(MapTest|FieldsTest)/'`. `Map` and `AdminAssets` copy competition's with only the namespace, the field constants and the `ABSPATH` guard (`|| ( defined( 'HYPERPRESS_TESTING' ) || exit )`, as in `Fields.php`) changed. `test_boot_registers_the_hook_and_admin_assets` calls `Plugin::boot()` itself.
- The theme's `single.php` includes `template-parts/banner.php`, which fires `hyperpress_after_banner`, so the hook runs on single events.
- Task 2 dropped the "registered default has a space" assertion in `CompetitionSettingsTest` without a replacement.
- Suites after both tasks: lint clean, plugin unit 238, integration 1937 (6 skipped).
- Not verified: Task 1 Step 9, the browser check of the map on a live event.

## Known follow-ups

- Share one map class between competition and event in `hyperpress-utils` (not done here to avoid refactoring competition).
