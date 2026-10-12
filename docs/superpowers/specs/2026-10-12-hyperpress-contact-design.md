# hyperpress-contact design

Date: 2026-10-12. Status: draft for approval.

## Intent and why

The Contact page content is hand-written HTML pasted into the WordPress editor: a Google Maps iframe, a Foundation grid, and three address blocks (team meeting, administrative, shipping). Two things hurt:

- The addresses cannot be reused. An event post about a location has to copy the address by hand.
- The pasted HTML has to be repeated for every page that needs it, and it broke when the theme moved from Foundation 5 to Foundation 6 (`row`/`columns`/`end`, the `.vcard` box styling).

The fix is to store the addresses and the map location once, as site settings, and render them with shortcodes and a page template. The Contact page content then contains only prose, the form shortcode and the address shortcodes.

## Goals

- A new plugin, `hyperpress-contact`, with a "Contact" settings page under the hyperPress wp-admin menu.
- Addresses are a list the admin can add to, rename and reorder. Each address can be shown anywhere with one shortcode.
- One map location (coordinates and zoom) is set on the same settings page and shown with a shortcode and on a Contact page template.
- A "Contact" page template that shows the map as a full-width banner and then the page's own content in the normal content container.
- Address cards use the theme's Foundation 6 card (`.card`, `.card-divider`, `.card-section`), and the address itself is an `<address>` element with microformat classes.

## Non-goals

- Moving other Customizer options into settings pages. That is a later, separate project.
- The `hyperpress-frontpage` plugin (front page post options). Separate project, after this one.
- A general-purpose maps system, multiple maps, markers, or an interactive map.
- Linking the map out to Google Maps. The map is display only.
- Migrating the Google Maps API key out of the competition plugin's Customizer setting into a global field. This design only reads it through a shared accessor (see Constraints).
- Pasting or editing the live Contact page content. The site owner replaces that content in WordPress by hand once the plugin exists.

## Anti-goals

- The page must not depend on hand-written grid or card HTML in the content.
- The theme must keep working with the plugin inactive: no raw shortcode text or fatals on the Contact page template.
- No new dependency beyond what other hyperPress plugins already require (`hyperpress-utils`, `meta-box-aio`).

## Decisions made with the site owner

- Plugin name `hyperpress-contact`, namespace `HyperPress\Contact`, text domain `hyperpress`, following the conventions in `plugins/AGENTS.md`.
- The settings page is a Meta Box settings page whose `parent` is `HyperPress\Utils\AdminMenu::SLUG` (the hyperPress menu added in commit `2f8ad6e`, `hyperpress-utils`).
- Addresses are a Meta Box cloneable, sortable group, so the admin can add, rename and reorder entries.
- A single shortcode, passed an argument, selects which address to show. The card puts the entry's heading in the card divider and the address in an `<address>` element, with the phone number as a `tel:` link.
- The map is a Meta Box `map` field on the same settings page. A shortcode displays it, and a page template uses it. The map is display only, with no link out and no Google UI card.
- The page template shows the map banner followed by the normal page content. The addresses are added to that content with the shortcodes.
- The Google Maps API key already exists (used by the competition plugin's Meta Box map field, `plugins/hyperpress-competition/src/MetaBoxes.php`). The contact plugin reads it through a shared accessor in `hyperpress-utils` (`GoogleMapsKey::get()`), which is added before this plugin, in its own commit. The accessor reads the global option `hyperpress_google_maps_api_key` and falls back to the competition theme mod `hyperpress_competition_google_maps_api_key`.

## Calls made without the site owner (change them if wrong)

- Address fields: heading, name, extra line (for example "ATTN: Michael Gendron"), street, city, state, zip, phone.
- Each address has an id generated from its heading, editable afterwards, used as the shortcode argument.
- Shortcode names: `[hyperpress_address id="meeting"]` and `[hyperpress_map]` (optional `height`).
- An unknown address id renders nothing; logged-in admins see an HTML comment saying why.
- The page template file lives in the theme (`themes/hyperpress-foundation/page-templates/`), because the theme is presentation-only. It calls the map shortcode only when the plugin is active (guard with `shortcode_exists`).
- The Contact template keeps the standard page title banner (as the current page has: "CONTACT" plus subtitle), with the map banner directly below it and the page content after that.
- Settings are stored in one option, `hyperpress_contact`.
- Meta Box's `map` field requires `address_field`, so the settings page has a "Map address" text field that feeds it. The Geocoding API must be enabled for the Google Maps key for that field to find addresses.
- `Requires Plugins: hyperpress-utils, meta-box-aio`, and the plugin needs a Docker mount entry like the other plugins.
- With no API key configured, the map renders nothing (admins see an HTML comment).
- Theme SCSS gains a small rule so `.vcard` is upright and `.fn` is bold (browsers italicize `<address>`; Foundation 6 has no vcard styling). It goes in `components/_cards.scss`.

## Constraints

- Conventions: `hyperpress-contact/hyperpress-contact.php` bootstraps with an ABSPATH guard, PSR-4 autoloader and `Plugin::boot` on `plugins_loaded`; classes under `src/`, one concern each; registered keys follow `hyper_*` or `hyperpress_{plugin}_{field}`; Requires at least WP 6.9, PHP 8.3, version `2026.0.0`.
- Tooling runs in Docker from `plugins/` (`composer lint`, `test:unit`, `test:integration`). Lint must stay at 0 violations.
- Output is escaped (`esc_html`, `esc_url`, `esc_attr`); the `tel:` href is built from digits and a leading `+`, with the display text left as typed.
- The theme compiles Foundation's XY grid and card (`foundation-card`) only; do not rely on `row`/`columns`.
- The card markup is:

  ```html
  <div class="card">
    <div class="card-divider"><h5>Heading</h5></div>
    <div class="card-section">
      <address class="vcard">
        <span class="fn">Name</span><br>
        <span class="street-address">…</span><br>
        <span class="locality">…</span>, <span class="state">…</span> <span class="zip">…</span><br>
        <span class="phone"><a href="tel:+1…">(617) 000-0000</a></span>
      </address>
    </div>
  </div>
  ```
- The Google Maps JavaScript API must be enabled for the key and the key's referrer restrictions must include the public site domains. This is outside the code and has to be checked in Google Cloud by the site owner.

## Left to the builder

- Meta Box field configuration and settings-page details, including how the cloneable group is declared and how ids are generated and kept unique.
- How the map is rendered non-interactively (for example the JavaScript API with gesture handling disabled and no controls, or another approach that needs no link out and shows no Google UI card), and how the `height` argument and the banner height are applied.
- Template file name, how the template is registered, and its markup within the theme's `main-container` / `full-screen` structure (see `page-templates/page-full-screen.php`).
- Test structure, using the existing unit and integration patterns.
- Any README or `AGENTS.md` notes.
