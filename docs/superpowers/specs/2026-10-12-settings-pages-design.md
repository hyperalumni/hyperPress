# Move Customizer options into wp-admin settings pages

Date: 2026-10-12. Status: draft for approval.

## Intent

Settings that are data or configuration (which categories to show, an API key, social links, labels) live in the Customizer only because that was the easiest place to put them. The Customizer needs custom controls as workarounds (`CategoryDropdownControl`, `PostTypeDropdownControl`), and none of these settings use live preview (no `postMessage` transport anywhere), so the Customizer gives no benefit for them.

Move them to Meta Box settings pages under the top-level "hyperPress" wp-admin menu, the pattern `hyperpress-contact` already proves in a browser. The Customizer keeps only colors and two theme-owned layout settings (see below); there is no live preview in use, so only genuinely visual settings are worth keeping there.

## Goals

- One Meta Box settings page per plugin under the hyperPress menu, owned by that plugin.
- A new "General" page in `hyperpress-utils` holding the shared Google Maps API key, and made the menu's default (first) page. The Overview landing page is removed.
- A new plugin `hyperpress-frontpage` with a "Front Page" page replacing both Customizer sections for the front page: "Front Page Posts" (categories, post types) and the Meta Box section "Front Page" (banner fields and countdown picker).
- `hyperpress-utils` requires `meta-box-aio` (header `Requires Plugins` and `Convention::REQUIRES_PLUGINS`), because General and the menu are Meta Box based. No Meta Box-less code path.
- The theme stays presentation-only and keeps working with every plugin inactive (`NoPluginsTest`): it only reads values, with defaults.
- Delete the two dropdown controls (and their tests) once their last user has moved.

## Non-goals and anti-goals

- No runtime migration code, no read fallback to the old theme mods, no admin notice about old values. Nothing outside the owner's local dev DB uses the themes or plugins yet.
- No redirect from `admin.php?page=hyperpress`; no tabs; no new settings UI inside the theme.
- No live-preview work. Anti-goal: leaving a setting readable from two places (new option and old theme mod).

## What stays in the Customizer

- Theme colors: `hyperpress_gear_*`, `hyperpress_hyper_*`.
- Countdown colors registered in `plugins/hyperpress-countdown/src/Customizer.php` (4 settings; re-verify ids when that plugin is reviewed).
- The theme's own `hyperpress_site_copyright_name` and `wpt_mobile_menu_layout` (my call: the theme has no plugin to host them).

The front-page banner fields and countdown picker (a Meta Box-registered Customizer section in the theme) do not stay: they move to the Front Page page, see below.

## What moves

| Page (plugin) | Settings (old theme_mod id) |
|---|---|
| General (`hyperpress-utils`) | Google Maps API key, replaces `hyperpress_competition_google_maps_api_key`; read through `GoogleMapsKey::get()` |
| Front Page (`hyperpress-frontpage`, new) | `hyperpress_home_blog_categories`, `hyperpress_home_blog_post_types`, and the theme's front-page Meta Box Customizer section "Front Page": `hyperpress_homepage_customize_banner_title`, `_banner_subtitle`, `_banner_background_color`, `_banner_button_text`, `_banner_button_page`, `_countdowns` |
| Season | `hyperpress_season_current`, `hyperpress_season_banner_subtitle`, `hyperpress_season_post_types` |
| Program | `hyperpress_program_post_types` |
| Newsletter | `hyperpress_newsletter_banner_subtitle` |
| Sponsor | `hyperpress_sponsor_banner_subtitle` |
| Competition | `hyperpress_competition_banner_subtitle`; its Google Maps key moves to General and competition calls `GoogleMapsKey::get()` |
| Social | the 12 `hyperpress_social_*` settings (links plus the contact page) |
| Label | the per-post-type `hyperpress_label_*` labels |
| Media | `hyperpress_nggallery_photos_page` |

Season and Program also have empty Meta Box entries (`MetaBoxes::customize_metabox`, `'panel' => ''`) whose only job is to create the Customizer section their `add_setting` controls sit in. When a plugin's last Customizer setting moves, delete that entry too (and keep `EmptyMetaBoxSectionsTest` consistent).

This list comes from a quick scan of the code, which has already been wrong (a non-existent competition map color; and Customizer fields registered through Meta Box instead of `add_setting`, which a grep for `add_setting` cannot see); re-verify each plugin with grep for `customize_register` and `get_theme_mod` when its turn comes. The scan found Label and Social registering through Customizer classes at `plugins/hyperpress-label/src/Customizer.php` and `plugins/hyperpress-social/src/Customizer.php`. A third control, `LabelDropdownControl`, also exists (modelled on `PostTypeDropdownControl`); check whether it can go once Label moves.

## Design

### Menu

- `AdminMenu::register()` keeps adding the top-level "hyperPress" menu but no longer adds the "Overview" submenu or the `render()` landing list (`children()` can go if nothing else uses it).
- General becomes the first submenu item, so clicking "hyperPress" opens it. WordPress sends a top-level click to the first submenu item. A late `admin_menu` hook in `AdminMenu` moves the General item to the front of `$submenu['hyperpress']`, so order does not depend on plugin load order.
- General's page id and slug is `hyperpress_general` (my call), so the page URL is `admin.php?page=hyperpress_general`. The parent slug `hyperpress` itself has no landing page of its own.
- This reorders a core admin global and depends on Meta Box's handling of the custom parent, so it gets an explicit browser-verification step.

### Settings pages (pattern from `hyperpress-contact`)

- Each page is registered through the `mb_settings_pages` filter with `parent => AdminMenu::SLUG`, and its fields through `rwmb_meta_boxes`. Meta Box's MB Settings Page stores one option, an array keyed by field id, via `update_option`.
- Option name `hyperpress_<plugin>`; field ids are `hyperpress_<plugin>_<field>`, and the option array is keyed by them (required by `IdentifierConventionTest` and consistent with `hyperpress_contact_map` in option `hyperpress_contact`). General uses option `hyperpress_general`, page id `hyperpress_general`, field id `hyperpress_general_google_maps_api_key` (my call; a Meta Box settings page cannot write a bare string option, so the earlier flat option `hyperpress_google_maps_api_key` is retired).
- Readers use `get_option` directly with defaults. Meta Box is not installed in the test environment (`rwmb_meta` is stubbed), so tests assert the `mb_settings_pages` and `rwmb_meta_boxes` filter output and the real UI is verified in a browser.
- Sanitization: the sanitizers that exist today (`hyperpress_sanitize_home_categories`, `hyperpress_sanitize_home_post_types`) move to the new plugin as per-field `sanitize_callback`s (neither Front Page field is cloneable, so no `pre_update_option_` filter is needed there; the multi-select fields always submit arrays, so the old single-id case is dropped; my call). Because the theme must be robust with the plugin inactive and with hand-edited options, the theme also validates on read (unregistered or non-public post types dropped, ids cast to int). For any later cloneable group, whole-list rules belong in a `pre_update_option_{option}` filter, because Meta Box calls a cloneable group's `sanitize_callback` once per clone.
- With `hyperpress-frontpage` inactive and no saved option, the front page behaves exactly as today with no theme mod set: empty category query, `post` only for post types. This is intended and asserted in `NoPluginsTest` and `FrontPageQueryTest`.

### Front Page (`hyperpress-frontpage`)

- New plugin following the plugin conventions: header, PSR-4 autoloader, `Plugin::boot` on `plugins_loaded`, `Requires Plugins: hyperpress-utils, meta-box-aio`, namespace `HyperPress\Frontpage`.
- Eight fields: categories (Meta Box `taxonomy` field for `category`, multiple), post types (a select with `multiple`, options from registered public post types, default `['post']`), banner title, banner subtitle (textarea, keeps its current default text "Dedicated to improving &amp; expanding the abilities of <span class="emphasis">Team HYPER</span>", raw, 100-word limit), banner background color (color field), banner button text, banner button page (post field, pages, `select_advanced`), and countdowns (post field on `hyper_countdown`, multiple). The titles and limits are copied from the existing `library/metaboxes/homepage-customize.php`. The page does not hard-require `hyperpress-countdown`; with it inactive the countdown picker is simply empty (my call).
- The theme reads the `hyperpress_frontpage` option in `front-page.php` (lines ~35-67: categories, post types, countdowns), `library/banner/front.php` (banner fields) and `template-parts/banner.php:24` (subtitle), replacing the `get_theme_mod` reads. A small theme helper may centralize the read with a default (builder's call). Behavior with nothing saved or the plugin inactive stays as today with no theme mod set: empty category query, `post` only for post types, empty banner fields, no countdowns. The default subtitle text lives in the plugin's field, as it does in the Meta Box field today.
- Theme deletions: remove the "Front Page Posts" section and the two settings from `themes/hyperpress-foundation/library/customize/homepage.php` (with the control `class_exists` guards and the moved sanitizers), and delete `library/metaboxes/homepage-customize.php` and its `require` in `functions.php` (or wherever it is loaded), together with the "Front Page" Customizer section it creates. Update the theme tests that touch them: `HomepageSettingsTest` (line 35 calls `hyperpress_homepage_customize_metabox`), `EmptyMetaBoxSectionsTest`, `BannerSubtitleTest`, `FrontPageContentTemplateTest`, `FrontPageQueryTest`, `CustomizerTest`, `NoPluginsTest`.
- Register the plugin everywhere a plugin is listed: `plugins/tests/src/Support/Convention.php` (PLUGINS, LOAD_ORDER, REQUIRES_PLUGINS), `plugins/phpcs.xml.dist`, `plugins/tests/bootstrap-unit.php`, `docker/compose.main.yaml`, and the plugin counts in the root `AGENTS.md` and `plugins/AGENTS.md`.

### Google Maps key

- General exposes one text field, saved in option `hyperpress_general` under `hyperpress_general_google_maps_api_key`.
- `GoogleMapsKey::get()` reads only `hyperpress_general['hyperpress_general_google_maps_api_key']`; the flat option and the theme-mod fallback are removed. Existing tests that write the flat option (e.g. `ContactSettingsPageTest`, `MapShortcodeTest`) are updated.
- Competition switches its reads to `GoogleMapsKey::get()` and drops its Customizer key setting. Both settings in its `Customizer` class (the key and the banner subtitle) move, so the class and its Customizer section are deleted; `CompetitionMapApiKeyTest` is rewritten for the accessor.
- Meta Box's map field falls back to a hardcoded demo API key unless `api_key` is passed, so any map field must pass `GoogleMapsKey::get()`.

### Control deletion

`CategoryDropdownControl` and `PostTypeDropdownControl` are used by the front page, program and season. Delete them from `plugins/hyperpress-utils/src/Controls/`, together with `ControlsTest`, `ControlDescriptionEscapingTest`, `ControlsRenderTest` and the control assertion in `NoPluginsTest`, only when the last of those three has moved.

### Dev data

Each plugin's plan carries a short `wpcli` command that copies the current theme-mod values into the new option once (run after `wpcli db export -`). Using it is optional; re-entering the values by hand is fine.

## Order of work

1. Shared: `hyperpress-utils` requires `meta-box-aio`, General page, `AdminMenu` change, `GoogleMapsKey` fallback removal, competition switch.
2. `hyperpress-frontpage` plus the theme changes.
3. One plugin at a time: season, program, newsletter, sponsor, social, label, media. Delete the controls after the last of front page, program and season.

One spec and one plan cover steps 1-2 in detail with a short outline per later plugin; each later plugin gets its own small plan.

## Verification

- Per task: `docker compose run --rm php composer lint|test:unit|test:integration` from `plugins/` and from `themes/hyperpress-foundation/`; lint stays at 0.
- Theme integration tests to update: `HomepageSettingsTest`, `FrontPageQueryTest`, `CustomizerTest`, `NoPluginsTest`, `FrontPageContentTemplateTest`.
- Browser (needs a person, as in the contact plan Task 7): menu order and General as the default page; saving each new page; the front page rendering from the new option; the theme with all plugins deactivated.
- Doc upkeep per repo rule: grep `docs/superpowers/plans/` for every changed file or component and append a dated note to any completed plan that mentions it. Known mentions: `2026-10-02-theme-02-renames-and-breadcrumbs.md`, `2026-10-02-plugins-03-core-plugins.md`, `2026-10-02-plugins-02-shared-utils.md`, `2026-10-02-plugins-06-audit.md`, and the contact plan for the `AdminMenu` change.

## Left to the builder

Exact field ids and labels, the admin-menu reordering mechanism (any approach that makes General first regardless of load order), the form of the sanitizers, and the shape of each `wpcli` copy command.
