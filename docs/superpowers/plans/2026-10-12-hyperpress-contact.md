# hyperpress-contact Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** A `hyperpress-contact` plugin with a "Contact" settings page (address list + map location) under the hyperPress menu, an address-card shortcode, a map shortcode, and a theme "Contact" page template.

**Architecture:** One option, `hyperpress_contact`, written by a Meta Box settings page (MB Settings Page, `parent` = `AdminMenu::SLUG`) and read directly with `get_option` (Meta Box is not installed in the test environment, and the stored format is a documented array keyed by field id). Two shortcodes render from it. The page template lives in the theme and calls the map shortcode only if it exists.

**Tech Stack:** PHP 8.3, WordPress 6.9, Meta Box AIO (settings page, `group`, `map` fields), Google Maps JavaScript API, Foundation 6 card, PHPUnit via the Docker toolchain.

**Spec:** `docs/superpowers/specs/2026-10-12-hyperpress-contact-design.md`

## Global Constraints

- Plugin dir `plugins/hyperpress-contact/`, main file `hyperpress-contact.php`, namespace `HyperPress\Contact`, one class per concern in `src/`, text domain `hyperpress`, Requires at least `6.9`, Requires PHP `8.3`, Version `2026.0.0`, `Requires Plugins: hyperpress-utils, meta-box-aio`.
- Registered identifiers follow `hyperpress_{plugin}_{field}`.
- PHP tooling runs in Docker only, from `plugins/` (or `themes/hyperpress-foundation/` for the theme): `docker compose run --rm php composer lint|test:unit|test:integration`. Lint must stay at 0 violations. To run one test class append `-- --filter <ClassName>`; if the script does not forward arguments, run the whole suite.
- All output escaped (`esc_html`, `esc_url`, `esc_attr`). The `tel:` href is built from digits and a leading `+`; the display text stays as typed.
- The theme compiles only Foundation's XY grid and card; never use `row`/`columns`.
- The theme must work with the plugin inactive: no raw shortcode text, no fatals.
- Shortcodes: `[hyperpress_address id="meeting"]` and `[hyperpress_map]` (optional `height`). Option name `hyperpress_contact`. Unknown address id or missing key/location renders nothing; logged-in administrators see an HTML comment.
- Commits: explicit paths (`git add <paths>` then `git commit -m "..." -- <paths>`), terse imperative style like `git log`.
- **Branch:** execute on `feat/hyperpress-contact`, which is `master` (including `adcd0c1`, the `.vcard` SCSS rule: `font-style: normal`, bold `.fn`) plus `e118615` (AdminMenu), `aff865a` (GoogleMapsKey) and `4bcc1ce` (spec). The card styling depends on `adcd0c1`; do not re-add the rule.

## Review Focus

- Heading, name or any field containing HTML, quotes or `&`: output is escaped (Task 4).
- Duplicate, empty, uppercase or punctuated slugs on save: unique, non-empty, lowercase (Task 2).
- Phone numbers with an extension, a country code, or no digits: sensible `tel:` href or no link (Task 4).
- Missing zoom, out-of-range or garbage map coordinates, or a missing API key: renders nothing, no JS error (Tasks 2 and 5).
- Two `[hyperpress_map]` on one page: both initialised, Google script enqueued once (Task 5); Contact template with the plugin inactive (Task 6).

---

## File Structure

- Create `plugins/hyperpress-contact/hyperpress-contact.php`: header, autoloader, `plugins_loaded` boot.
- Create `plugins/hyperpress-contact/src/Plugin.php`: `boot()` wires the other classes.
- Create `src/Settings.php`: reads the option; map parsing.
- Create `src/Addresses.php`: save-time sanitization of the address list.
- Create `src/SettingsPage.php`: `mb_settings_pages` entry.
- Create `src/MetaBoxes.php`: the two field groups on that page.
- Create `src/AddressShortcode.php`, `src/MapShortcode.php`, `assets/map.js`.
- Create `themes/hyperpress-foundation/page-templates/page-contact.php`.
- Modify `plugins/tests/src/Support/Convention.php`, `plugins/tests/bootstrap-unit.php`, `docker/compose.main.yaml`, `plugins/AGENTS.md`, root `AGENTS.md` (plugin count 14 to 15).

---

### Task 1: Plugin skeleton and registrations

**Files:**
- Create: `plugins/hyperpress-contact/hyperpress-contact.php`, `plugins/hyperpress-contact/src/Plugin.php`
- Modify: `plugins/tests/src/Support/Convention.php` (add `'hyperpress-contact'` to `PLUGINS`, to `LOAD_ORDER` next to `hyperpress-countdown`, and `=> array( 'hyperpress-utils', 'meta-box-aio' )` to `REQUIRES_PLUGINS`), `plugins/tests/bootstrap-unit.php` (add a `HyperPress\Contact\` autoloader beside the existing ones), `docker/compose.main.yaml` (one bind-mount line next to `hyperpress-countdown`, same pattern), `plugins/AGENTS.md` and root `AGENTS.md` (14 plugins becomes 15)

**Interfaces:**
- Produces: `HyperPress\Contact\Plugin::boot(): void`, hooked on `plugins_loaded` (later tasks add one line each to it).

- [ ] **Step 1:** Add the three `Convention.php` entries first. Run `composer test:integration` and `composer test:unit` from `plugins/`. Expected: the Convention-driven tests FAIL because `hyperpress-contact.php` does not exist.
- [ ] **Step 2:** Create the main file by copying the header, ABSPATH guard, PSR-4 autoloader (prefix `HyperPress\Contact\`) and the `plugins_loaded` hook from `plugins/hyperpress-countdown/hyperpress-countdown.php`; Plugin Name `hyperPress Contact`, Description `Contact addresses and map for hyperPress`. No activation hook. Create `Plugin::boot()` as an empty `final class` method.
- [ ] **Step 3:** Add the bootstrap-unit autoloader, the compose mount line and the two AGENTS.md count changes.
- [ ] **Step 4:** Run `composer lint`, `composer test:unit`, `composer test:integration`. Expected: all pass.
- [ ] **Step 5:** Commit: `feat(contact): add hyperpress-contact plugin skeleton`. Include the plan file with this task's boxes ticked.

---

### Task 2: Settings reader and address sanitizer

**Files:**
- Create: `plugins/hyperpress-contact/src/Settings.php`, `plugins/hyperpress-contact/src/Addresses.php`
- Test: `plugins/tests/unit/Contact/SettingsMapTest.php`, `plugins/tests/integration/Contact/ContactSettingsTest.php`

**Interfaces:**
- Produces:
  - `Settings::OPTION = 'hyperpress_contact'`, `Settings::ADDRESSES = 'hyperpress_contact_addresses'`, `Settings::MAP_ADDRESS = 'hyperpress_contact_map_address'`, `Settings::MAP = 'hyperpress_contact_map'`
  - `Settings::addresses(): array`: list of arrays with exactly the keys `slug, heading, name, extra, street, city, state, zip, phone` (all strings, `''` when missing); `[]` when the option is absent or not an array
  - `Settings::address( string $slug ): ?array`
  - `Settings::map(): ?array`: `array{lat:float,lng:float,zoom:int}` or `null`
  - `Settings::parse_map( string $value ): ?array`: pure, same return shape
  - `Addresses::sanitize( $groups ): array`

- [ ] **Step 1: Failing unit test** `SettingsMapTest`, using `Settings::parse_map`:
  - `'42.2518999,-71.0007911,17'` returns `array( 'lat' => 42.2518999, 'lng' => -71.0007911, 'zoom' => 17 )`
  - `'42.25,-71.0'` returns zoom `15`
  - `' 42.25 , -71.0 , 17 '` parses (whitespace tolerated)
  - zoom `99` is clamped to `21`; zoom `-3` to `0`
  - `''`, `'abc'`, `'91,0,10'`, `'0,181,10'`, `'1,2,3,4'` return `null`
- [ ] **Step 2: Failing integration test** `ContactSettingsTest`:
  - `update_option( 'hyperpress_contact', array( Settings::ADDRESSES => array( array( 'slug' => 'meeting', 'heading' => 'Team Meeting Address', 'name' => 'HYPER @ Long Robotics Center' ) ) ) )`; `addresses()` returns that entry with the other seven keys as `''`; `address( 'meeting' )` returns it, `address( 'nope' )` returns `null`
  - option absent, or a string, gives `addresses() === array()`
  - `Settings::map()` returns the parsed value stored under `Settings::MAP`
  - `Addresses::sanitize` cases: `'Team Meeting Address'` with empty slug gets slug `team-meeting-address`; slug `'Ship To!'` becomes `ship-to`; three entries with slug `meeting` become `meeting`, `meeting-2`, `meeting-3`; an entry whose fields are all empty is dropped; `<b>x</b>` in a field becomes `x`; the phone `'+1 (617) 376-1100 x2'` is kept as typed; order is preserved; a non-array argument returns `array()`; an entry with fields but empty heading and slug gets slug `address`
- [ ] **Step 3: Run both; Expected: FAIL (classes missing).**
- [ ] **Step 4: Implement** `Settings` (reads `get_option( self::OPTION )`; normalizes entries to the nine keys; `parse_map` splits on commas, requires 2 or 3 numeric parts, lat in -90..90, lng in -180..180, zoom default 15 clamped 0..21) and `Addresses::sanitize` (text fields through `sanitize_text_field`, slug through `sanitize_title`, uniqueness by numeric suffix).
- [ ] **Step 5:** Run lint and both test suites. Expected: pass.
- [ ] **Step 6:** Commit: `feat(contact): read contact settings and sanitize the address list` (with the plan file ticked).

---

### Task 3: Settings page and fields

**Files:**
- Create: `plugins/hyperpress-contact/src/SettingsPage.php`, `plugins/hyperpress-contact/src/MetaBoxes.php`
- Modify: `plugins/hyperpress-contact/src/Plugin.php` (call both `register()`), `docs/superpowers/specs/2026-10-12-hyperpress-contact-design.md` (add to "Calls made": Meta Box's map field requires `address_field`, so the settings page has a "Map address" text field that feeds it; the Geocoding API must be enabled for the key)
- Test: `plugins/tests/integration/Contact/ContactSettingsPageTest.php`

**Interfaces:**
- Consumes: `Settings` constants, `Addresses::sanitize`, `HyperPress\Utils\AdminMenu::SLUG`, `HyperPress\Utils\GoogleMapsKey::get(): string`.
- Produces: `SettingsPage::ID = 'hyperpress_contact'`, `SettingsPage::register(): void`, `SettingsPage::pages( array $pages ): array`; `MetaBoxes::register(): void`, `MetaBoxes::boxes( array $boxes ): array`.

- [ ] **Step 1: Failing tests** (filters applied with `apply_filters`; Meta Box itself is not installed):
  - `mb_settings_pages` contains one entry with `id` `hyperpress_contact`, `option_name` `hyperpress_contact`, `menu_title` `Contact`, `parent` `hyperpress`, `capability` `manage_options`
  - `rwmb_meta_boxes` contains two boxes whose ids start `hyperpress_contact`; both have `settings_pages` equal to `hyperpress_contact` and non-empty `fields`
  - the group field has id `hyperpress_contact_addresses`, `type` `group`, `clone` and `sort_clone` true, `sanitize_callback` equal to `array( Addresses::class, 'sanitize' )`, and sub-field ids exactly `heading, slug, name, extra, street, city, state, zip, phone`
  - the map field has `type` `map`, `address_field` `hyperpress_contact_map_address`, `std` `42.2518999,-71.0007911,17`, and `api_key` equal to the value of the `hyperpress_google_maps_api_key` option when it is set to `abc`
  - a text field `hyperpress_contact_map_address` exists in the same box
- [ ] **Step 2: Run; Expected: FAIL.**
- [ ] **Step 3: Implement** both classes. Group config: `add_button` `Add address`, `collapsible` true, `group_title` `{heading}`; `heading` is `required`; the `slug` field has a description saying it is the id used in `[hyperpress_address id="..."]` and is generated from the heading when left empty.
- [ ] **Step 4:** Run lint and integration suite. Expected: pass.
- [ ] **Step 5:** Commit: `feat(contact): add Contact settings page with address list and map fields`.

---

### Task 4: Address shortcode

**Files:**
- Create: `plugins/hyperpress-contact/src/AddressShortcode.php`
- Modify: `Plugin.php` (call `AddressShortcode::register()`)
- Test: `plugins/tests/integration/Contact/AddressShortcodeTest.php`

**Interfaces:**
- Consumes: `Settings::address( string ): ?array`.
- Produces: `AddressShortcode::register(): void` (tag `hyperpress_address`), `render( $atts ): string`, `card( array $address ): string`, `tel_href( string $phone ): string`.

- [ ] **Step 1: Failing tests** (store the three addresses from the current page in the option; `do_shortcode( '[hyperpress_address id="meeting"]' )`):
  - output contains `<div class="card">`, `<div class="card-divider"><h5>Team Meeting Address</h5></div>`, `<address class="vcard">`, `<span class="fn">HYPER @ Long Robotics Center</span>`, `<span class="street-address">18 Spear Street</span>`, `<span class="locality">Quincy</span>, <span class="state">MA</span> <span class="zip">02169</span>`, `<a href="tel:+16173761100">(617) 376-1100</a>`
  - the shipping address (no phone) contains no `class="phone"`, and its extra line `ATTN: Michael Gendron` appears after the `fn` span and before the street span
  - unknown id, and missing `id`, return `''` for a visitor; for an administrator they return `<!-- hyperpress_address: no address with id "nope" -->`; an id of `x--><script>` produces no `<script` in the output
  - heading `<script>alert(1)</script>` and name `A & B` come out as `&lt;script&gt;` and `A &amp; B`
  - `tel_href`: `'(617) 376-1100'` gives `'+16173761100'`; `'+44 20 7946 0958'` gives `'+442079460958'`; `'617.376.1100 x23'` gives `'+16173761100'`; `'1-617-376-1100'` gives `'+16173761100'`; `'call us'` gives `''` and the phone is then shown as plain text without a link
- [ ] **Step 2: Run; Expected: FAIL.**
- [ ] **Step 3: Implement.** `card()` skips empty parts and builds the locality line from whichever of city/state/zip are present. `tel_href` rules: strip a trailing extension matching `x`/`ext` plus digits; keep a leading `+` and the digits; 10 digits get `+1`; 11 digits starting with `1` get `+`; no digits gives `''`.
- [ ] **Step 4:** Run lint and integration suite. Expected: pass.
- [ ] **Step 5:** Commit: `feat(contact): add [hyperpress_address] shortcode`.

---

### Task 5: Map shortcode and script

**Files:**
- Create: `plugins/hyperpress-contact/src/MapShortcode.php`, `plugins/hyperpress-contact/assets/map.js`
- Modify: `Plugin.php` (call `MapShortcode::register()`)
- Test: `plugins/tests/integration/Contact/MapShortcodeTest.php`

**Interfaces:**
- Consumes: `Settings::map()`, `GoogleMapsKey::get()`, `HyperPress\Utils\Version::of( string $file )`.
- Produces: `MapShortcode::register(): void` (tag `hyperpress_map`), `render( $atts ): string`; script handles `hyperpress-contact-map` (our `assets/map.js`, footer, defines `window.hyperpressContactInitMaps`) and `hyperpress-contact-google-maps` (depends on the first).

- [ ] **Step 1: Failing tests** (store `Settings::MAP` as `42.2518999,-71.0007911,17` and the `hyperpress_google_maps_api_key` option as `test-key`):
  - output contains `class="hyperpress-map"`, `data-lat="42.2518999"`, `data-lng="-71.0007911"`, `data-zoom="17"`, `role="img"` and `style="height:250px;width:100%;pointer-events:none;"`
  - `height="400"` gives 400px; `height="5"` gives 100px; `height="9999"` gives 1000px; `height="abc"` gives 250px
  - after rendering twice, `wp_script_is( 'hyperpress-contact-google-maps', 'enqueued' )` is true, its registered src contains `key=test-key` and `callback=hyperpressContactInitMaps`, it depends on `hyperpress-contact-map`, and the output contains two map elements
  - key `a&b` appears as `key=a%26b`
  - no key, no location, or an invalid location returns `''` for a visitor and an `<!-- hyperpress_map: ... -->` comment for an administrator
- [ ] **Step 2: Run; Expected: FAIL.**
- [ ] **Step 3: Implement** `render` and the enqueue. `map.js`: `window.hyperpressContactInitMaps` finds every `.hyperpress-map` not yet initialised and creates a `google.maps.Map` from its data attributes with `disableDefaultUI: true`, `gestureHandling: 'none'`, `keyboardShortcuts: false`, `clickableIcons: false`, plus a marker at the centre. The Google script URL is built with `loading=async` and `callback=hyperpressContactInitMaps`.
- [ ] **Step 4:** Run lint and integration suite. Expected: pass. (No automated JS test; Task 7 covers it in a browser.)
- [ ] **Step 5:** Commit: `feat(contact): add [hyperpress_map] shortcode and script`.

---

### Task 6: Contact page template (theme)

**Files:**
- Create: `themes/hyperpress-foundation/page-templates/page-contact.php`
- Test: `themes/hyperpress-foundation/tests/integration/ContactTemplateTest.php`

**Interfaces:**
- Consumes: shortcode `hyperpress_map` if it exists.
- Produces: page template `Template Name: Contact`.

- [ ] **Step 1: Failing tests** (mirror `FrontPageContentTemplateTest`: create a page with `_wp_page_template` set to `page-templates/page-contact.php`, `go_to` it, include the template in global scope with output buffering):
  - `wp_get_theme()->get_page_templates()` contains `'page-templates/page-contact.php' => 'Contact'`
  - with a stub `hyperpress_map` shortcode returning `<div class="stub-map"></div>`: in the output, `class="banner` comes before `stub-map`, and `stub-map` comes before `main-container`; the page's content is present
  - with the shortcode removed: the output contains no `[hyperpress_map]` and no `Fatal error`
- [ ] **Step 2: Run from `themes/hyperpress-foundation/`; Expected: FAIL.**
- [ ] **Step 3: Implement** the template by copying the structure of `page-templates/page-full-width.php`, replacing the `featured-image` part with the map: `if ( shortcode_exists( 'hyperpress_map' ) )` echo `do_shortcode( '[hyperpress_map height="300"]' )` (add the `phpcs:ignore ... EscapeOutput -- shortcode output is escaped by the plugin` comment the theme lint requires).
- [ ] **Step 4:** Run the theme lint and integration suite. Expected: pass.
- [ ] **Step 5:** Commit: `feat(theme): add Contact page template`.

---

### Task 7: Verify end to end and close out

**Files:**
- Modify: this plan (tick boxes; add the COMPLETED banner), the spec if reality differs from it

- [ ] **Step 1:** Run `composer lint`, `test:unit`, `test:integration` in `plugins/` and in the theme. Expected: all pass, 0 lint violations.
- [ ] **Step 2 (manual, needs a person in a browser):** start `docker/hyperalumni`, activate `hyperpress-contact`, and check that "Contact" appears under the hyperPress menu and is listed on the hyperPress landing page. If the child page does not appear under the custom `hyperpress` parent, record the finding and fix `SettingsPage` (the MB Settings Page docs do not cover custom top-level parents or `admin_menu` priority).
- [ ] **Step 3 (manual):** add the three addresses and the map; confirm the group's `sanitize_callback` receives the list of entries (if the shape differs, fix `Addresses::sanitize` and add a test with the observed shape); confirm slugs are generated and unique after saving.
- [ ] **Step 4 (manual):** make a page with the Contact template containing the form shortcode and the three `[hyperpress_address]` shortcodes; check the card look, the non-interactive map, two maps on one page, and the page with the plugin deactivated.
- [ ] **Step 5:** Tick this plan's boxes, add `> **STATUS: COMPLETED** (date, commit range)`, commit: `docs(plans): complete hyperpress-contact`.
