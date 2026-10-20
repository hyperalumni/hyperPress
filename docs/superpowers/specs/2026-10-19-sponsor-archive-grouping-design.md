# Sponsor archive grouping: design

Date: 2026-10-19. Scope: the `hyperpress-sponsor` plugin. The Foundation theme already provides everything needed (`hyperpress_posts_template`), so no theme change is planned. The uikit theme is out of scope.

## Intent

The sponsor archive (`/sponsors/` and `/seasons/<slug>/sponsors/`) is now ordered by newest season, then tier, then menu order and title (`HyperPress\Sponsor\Ordering`). The list spans the team's whole history (30+ seasons), so a flat list is hard to read. Group it visibly: a heading per season and, inside it, a heading per tier. The archive stays paginated.

## Goals

- On `/sponsors/`: a season heading when the season group changes, then a tier heading when the tier changes within that season.
- On a season-scoped archive: tier headings only (every sponsor shares the season).
- Pagination stays, with a sponsor-specific page size. A page that starts in the middle of a group repeats that group's heading(s) with " (continued)" appended, so no page starts without a heading.
- The grouping uses the same season and tier rules as `Ordering`, so headings and order can never disagree.
- Each sponsor entry still renders through the existing sponsor `content_template` (title, logo, website).

## Non-goals

- Any theme change, new styling or layout (grid of logos, columns). Headings are plain heading elements.
- Per-season or per-tier archive pages, a stored tier rank field, or changing the ordering rules.
- Grouping on any archive other than the main query of the sponsor archive; widgets and feeds are untouched.

## Anti-goals

- Headings that disagree with the order (a sponsor under the wrong season or tier heading).
- A page that begins with sponsors and no heading, or a heading with no sponsors under it.
- The sponsor plugin printing theme wrapper markup (header, banner, container, pagination, sidebar): the theme keeps those.
- A second, divergent implementation of the season or tier ranking rules.

## Constraints

- Monorepo conventions apply (root, `plugins/` and `themes/hyperpress-foundation` `AGENTS.md`): PHP in Docker only, `hyperpress_` prefix, namespace `HyperPress\Sponsor`, text domain `hyperpress`, explicit-pathspec commits.
- Use only the theme's existing generic extension point `hyperpress_posts_template` (filter returning a template path or `''`, used by `archive.php` and `index.php`). The plugin returns its loop template only for the main query of the sponsor archive and returns the argument unchanged otherwise.
- Season of a sponsor for grouping = its newest season (the rule `Ordering` sorts by); tier of a sponsor = its highest-ranking tier (`Ordering::tier_compare()`); a sponsor without a season or tier sorts, and is grouped, last.

## Decisions

### Loop template and headings

The sponsor plugin ships a loop template (for example `templates/loop.php`) and a small class that registers the `hyperpress_posts_template` filter. The loop prints, for each post, any needed heading(s) and then includes the existing sponsor content template (`Content::add_content_template`), followed by the same `<div class="section-divider"><hr/></div>` that the theme's `template-parts/posts.php` prints after each entry, so entries look as they do today. Empty result: render the theme's `template-parts/content-none` like `template-parts/posts.php` does.

Headings: season = `h2`, tier = `h3`. Sponsor entry titles inside this loop are one level lower (`h4`) so the document outline stays valid; the sponsor content template must therefore accept a title tag from the loop (default `h2` everywhere else, unchanged).

Heading text: the season term name ("Season 31"); the tier term name ("Level 4", so custom tiers show their own names). A sponsor with no season goes last under "Other sponsors" (season level, all-time archive). A sponsor in a season but with no tier goes last in that season under "Other sponsors" (tier level, `h3`). Both texts are filterable.

### Continued headings

The first sponsor on page 2 or later repeats its heading(s) with " (continued)" when it belongs to the same group as the last sponsor of the previous page (compare season for the season heading, season and tier for the tier heading). Otherwise it prints its headings normally. The previous page's last sponsor is found with one small secondary query using the same ordering; `Ordering` currently applies only to the main query, so it gains an explicit opt-in (for example a query var) for that secondary query.

### Page size

The main query of the sponsor archive uses 24 sponsors per page, set with `pre_get_posts` (only the main query, not admin or feeds), filterable through `hyperpress_sponsor_archive_per_page`. The theme's pagination is untouched.

### Shared rules

Grouping keys come from the same rules as `Ordering`: the newest season is chosen with the season plugin's existing helper where it fits (`SortedSeasons`, or the trailing-number rule used by `SeasonOrder`), the tier with `Ordering::tier_compare()`. Do not re-derive a new rule.

## Left to the builder

- Class, file and filter names (within the prefixes above), the exact opt-in mechanism for the previous-page query, and how the loop template receives the title tag.
- Markup details around headings (wrapper elements, CSS classes), and the exact `(continued)` string handling for translation.
- Test layout, following `plugins/tests/integration/Sponsor/`.

## Testing and verification

- Grouping unit/integration tests with fixture sponsors across several seasons and tiers: headings appear only on group changes, in the right order, at the right levels; all-time versus season-scoped archives; sponsors with no season, no tier; a multi-season sponsor appears once under its newest season.
- Pagination: with a small page size, page 2 starting mid-group repeats the heading with " (continued)"; page 2 starting at a group boundary does not; page 1 never says continued; `found_posts` and page count unaffected.
- The hook returns the plugin loop only for the sponsor archive main query; other archives, feeds, widgets, secondary queries and admin keep the theme's default.
- The sponsor content template still renders `h2` titles outside the grouped loop.
- `composer lint`, `test:unit`, `test:integration` from `plugins/` (Docker); check `/sponsors/` and a season-scoped archive on the local hyperonline stack with temporary sponsors (create and delete through wpcli).

## Open items

- None known. Page size 24 and the " (continued)" and "Other sponsors" texts are the design's defaults; the owner confirmed them.
