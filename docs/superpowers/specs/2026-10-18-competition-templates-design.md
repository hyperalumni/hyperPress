# Competition templates: design

Date: 2026-10-18. Scope: the Foundation theme and the `hyperpress-competition` and `hyperpress-countdown` plugins. The uikit theme is out of scope (not ready).

## Intent

A `hyper_competition` has no editor content: it supports only title and thumbnail, and everything about it lives in Meta Box fields. Today the single page and every archive or blog-list entry therefore show little more than the title and image. We want:

- a single competition page that shows the competition properly: a full-width map under the banner (like the contact page), a live countdown to the start, the venue and date/time, and the event links;
- archive and blog-list entries that show the key facts (date/time, venue) but never a map;
- the countdown computed from the competition's own start date, with no separate `hyper_countdown` post to create or keep in sync.

## Goals

- Single competition page: map under the banner, then the countdown, date/time, venue name, address, host and the event links (each opens in a new tab). Empty fields are skipped.
- Archives, blog lists and the front page: a short summary (title, date/time, venue name, address). No map, no countdown, no links.
- The countdown is correct for every visitor regardless of their timezone, including across daylight saving.
- The countdown plugin gets a public render function; the competition plugin consumes it. The countdown plugin never learns about competitions.

## Non-goals

- The uikit theme.
- Creating, syncing or deleting `hyper_countdown` posts from competitions.
- A second, raw-date countdown shortcode for editors (can be added later on top of the function).
- Changing how existing `hyper_countdown` posts behave. `[hyperpress_countdown slug=...]` output and settings stay as they are.
- Filtering or sorting "upcoming competitions" (the date field format below keeps that possible).

## Anti-goals

- A countdown that is off by 4 to 5 hours for some visitors.
- A map on any list, archive or home-page view.
- Wrapping one shortcode's output string in another (double escaping, quoting problems).
- Hard-coded plugin coupling in the theme; the theme gets a generic hook only.

## Constraints

- Monorepo conventions: root `AGENTS.md`, `plugins/AGENTS.md` and `themes/hyperpress-foundation/AGENTS.md` apply. PHP runs in Docker only. New code uses the `hyperpress_` prefix; `HyperPress\<Name>` namespaces; text domain `hyperpress`.
- Keep the plugin-facing theme hooks unchanged (`hyperpress_banner_content`, `hyperpress_breadcrumbs_content`, `content_template`).
- The site timezone is the named zone `America/New_York` (Settings > General). Dates are entered in that zone.
- Nothing is in production, so a field-format change needs no data migration (existing development competitions get their start dates re-entered).
- Plugin headers: `Requires Plugins` follows the table in `2026-10-02-plugins-convention-and-shared-code-design.md`; that table must be updated for the new dependency.

## Decisions

### Date storage (changed field)

`hyperpress_competition_event_start_date` in `plugins/hyperpress-competition/src/MetaBoxes.php` changes from `'timestamp' => true` to `'timestamp' => false`, configured like the countdown's `hyperpress_countdown_event_date_time` (wall-clock text such as `2026-10-18 18:00`, no zone). Both plugins then share one rule: a wall-clock time with no zone, read in `wp_timezone()`. Only `MetaBoxes.php` references the field today. The countdown plugin's field is not changed.

A small helper in the competition plugin turns the stored text into a `DateTimeImmutable` in the site timezone (or `null` when empty or unparseable). It is the single place that reads the field.

### Countdown render function (countdown plugin)

The countdown plugin gets a public function that takes the instant (a `DateTimeImmutable`), an optional event name and location, and the count-past-zero flag. It returns the markup and enqueues the TimeCircles scripts and styles, exactly as `Shortcode::add_countdown_shortcode` does now (the ISO 8601 `data-date` with offset, the unique element id, the inline init script, resize handling, Customizer colours).

`[hyperpress_countdown]` is refactored to load its post, apply its show/hide window, and then call this function. Its output must not change. The function name and where it lives (a new class or on `Shortcode`) are left to the builder.

After the start time the countdown shows zeros, or goes negative when count-past-zero is on. It is not hidden for past competitions; their pages keep the zeros block (confirmed).

### Count past zero (competition plugin)

One option on the competition settings page (`Settings.php` and `SettingsPage.php`), off by default, applies to every competition countdown. Existing countdown posts keep their per-post checkbox.

### Competition views (competition plugin)

A new templates directory plus a `Content` class mirroring `plugins/hyperpress-newsletter/src/Content.php`: it hooks the theme's `content_template` filter and returns the plugin's `templates/content.php` for `hyper_competition`. The theme already includes that template from `single.php`, `template-parts/posts.php` (archives and blog lists) and `front-page.php`. The template branches on `is_singular()`:

- singular: the full view (countdown, date/time, venue name, address, host, event links in new tabs);
- otherwise: the summary (date/time, venue name, address). Whether to show a thumbnail or a link in the summary follows the surrounding list markup and is left to the builder.

The full view is used only on the competition's own page, so the branch is `is_singular( 'hyper_competition' )` and the loop's post is the queried object (`get_the_ID() === get_queried_object_id()`). A competition listed on a static front page or inside another page's loop therefore gets the summary. (My call.)

Date and time are displayed with the site's `date_format` and `time_format` through `wp_date()` in the site timezone, as entered; the field is a date and time, and no time zone label is shown. (My call.)

Plugin template output escapes every value. Link fields carry `url`, `title` and `target`; the competition links open in a new tab with `rel="noopener noreferrer"`.

A shortcode `[hyperpress_competition_countdown id="..."]` renders the same countdown for a given competition elsewhere (for example "next competition" on the home page). It calls the render function and renders nothing when the competition has no usable start date. (My call; small and cheap.)

### Map under the banner

Theme: `single.php` gets one generic action immediately after `get_template_part( 'template-parts/banner' )`, before `.main-container` (hook name left to the builder, `hyperpress_` prefix). Nothing else in the theme changes; archives and lists never call it.

Competition plugin: hooks that action and, only for a singular `hyper_competition`, outputs a full-width map. It draws the competition's `hyperpress_competition_venue_map` ("lat,lng,zoom") with `RWMB_OSM_Field::render_map`, using the same static, non-interactive options as `plugins/hyperpress-contact/src/MapShortcode.php` and the same 300px height used by the contact page template, wrapped in a `.hyperpress-map` container. It must validate and parse the value (`Settings::parse_map` in the contact plugin is the reference behaviour) rather than trust it.

The map field has a default value (`std`), so a competition that was never given a location would still show a map at the default coordinates. The map therefore appears only when the competition has a non-empty venue address and a valid map value. (My call, the builder should confirm it matches how the admin behaves.) Meta Box's `osm` field geocodes the address field into the map value, so a competition with an address has a map pointing at it. A map equal to the `std` default is not treated specially. When Meta Box or the map cannot render, output nothing for visitors.

### Dependencies

The competition plugin gains a dependency on `hyperpress-countdown` (update the `Requires Plugins` header and the convention table). The map needs only Meta Box, which is already a dependency (`meta-box-aio`), so `hyperpress-contact` stays optional. (My call.)

## Left to the builder

- Function, class, hook and CSS class names, within the prefixes above.
- Exact markup and field order inside the full view and summary, and the styling (Foundation grid/utilities, plus SCSS only if needed).
- Whether the render function lives in a new class or on `Shortcode`.
- Test layout; follow `plugins/tests/` and the theme's `tests/integration/` conventions.

## Testing and verification

- Countdown refactor: the existing countdown tests keep passing unchanged; add a test for the render function with an explicit instant.
- Competition: unit tests for the date helper (summer EDT and winter EST instants, empty and invalid input) and for the shortcode; integration tests that the `content_template` filter returns the plugin template only for `hyper_competition`, that the singular view and the summary differ, that the map is output only on a singular competition with an address, and that link targets and escaping are correct.
- Theme: an integration test that the new action fires on a single post after the banner and before `.main-container`.
- Run `composer lint`, `test:unit` and `test:integration` from `plugins/` and from the theme (Docker), and check the pages manually in the local `hyperalumni` and `hyperonline` stacks.

## Open items

- Confirm in the admin that Meta Box's datetime field with `timestamp => false` stores the text format the helper expects (a one-line check when the field is changed).
