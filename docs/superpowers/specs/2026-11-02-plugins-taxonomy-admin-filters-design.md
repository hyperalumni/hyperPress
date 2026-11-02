# Taxonomy filter dropdowns in the admin post lists

## Intent

The Competitions list in wp-admin has an "All seasons" dropdown (`hyperpress-season/src/AdminFilter.php`). Give the other plugin taxonomies the same kind of filter so editors can narrow long lists: Program, District, Week, Competition Type and Sponsor Tier.

## Behaviour (same rules as the Season dropdown)

- Shown through `restrict_manage_posts`, only on post types attached to the taxonomy (`is_object_in_taxonomy`). Program's post types come from its settings (`Settings::post_types()`), so the Program dropdown appears exactly on the post types the owner has assigned to Program, and on no others.
- Hidden when the taxonomy has no terms.
- "All …" first option, hierarchical indentation, current selection kept after filtering (term slug as value).

| Taxonomy                      | Plugin      | Post types                       | "All" label           |
| ----------------------------- | ----------- | -------------------------------- | --------------------- |
| `hyper_season`                | season      | from its settings (exists today) | All seasons           |
| `hyper_program`               | program     | from its settings                | All programs          |
| `hyper_district`              | competition | `hyper_competition`              | All districts         |
| `hyper_week`                  | competition | `hyper_competition`              | All weeks             |
| `hyper_competition_type`      | competition | `hyper_competition`              | All competition types |
| `hyper_tier` (sponsor `TIER`) | sponsor     | `hyper_sponsor`                  | All tiers             |

These are the only taxonomies the plugins register through `TaxonomyRegistrar`.

## Goals / non-goals

- Goal: one shared implementation in `hyperpress-utils`; each plugin registers its own dropdown(s). Season's existing behaviour and test stay intact.
- Non-goals: front-end filters, admin columns, multi-select, changing any taxonomy's registration.

## Approaches

1. Shared helper in `hyperpress-utils` (chosen by the owner). 2. Copy Season's class into each plugin (rejected: five copies). 3. One class in the competition plugin (rejected: Program must work on any assigned post type).

## Design

- New `HyperPress\Utils\TaxonomyAdminFilter` (hyperpress-utils, base dependency of every plugin):
  - `register( string $taxonomy, string $all_label ): void` hooks `restrict_manage_posts` for that taxonomy.
  - `render( string $taxonomy, string $all_label, string $post_type ): void` prints the dropdown (the code in Season's `AdminFilter::render`, parameterised).
  - The GET parameter is the taxonomy's `query_var`; core already turns `?hyper_district=slug` into a tax query on the list screen, which is how Season works today.
- `hyperpress-season` `AdminFilter::render()` keeps its signature and calls the helper, so `SeasonAdminFilterTest` is unchanged.
- Program registers "All programs" in `Plugin::boot`; competition registers its three; sponsor registers "All tiers".

## Sponsor Tier: turn its query var on

Tier was registered with `query_var => false` (a legacy choice on an internal taxonomy), so core ignores `?hyper_tier=` and the dropdown would filter nothing. The owner is fine with enabling it, so Tier drops the `'args' => array( 'query_var' => false )` override in `Plugin::tier_definition()` and takes the registrar default (`true`, giving `hyper_tier`). Every dropdown then works the same way, with no special case in the helper. Consequences:

- `?hyper_tier=gold` also narrows front-end queries on sponsors (accepted by the owner).
- `RegistrationParityTest` pins the legacy `query_var: false` for Tier; add a "deliberate change" exception there (the fixture stays a legacy snapshot).
- Nothing else reads Tier's query var (`Ordering::is_season_scoped` reads only Season's).

## Constraints

- PHP 8.3 floor, PHPCS WordPress standard, text domain `hyperpress`, translatable labels, plugin conventions in `plugins/AGENTS.md`. PHP runs in Docker only.
- Escaping and nonce rules as in the Season code (read-only GET, `sanitize_title`).

## Left to the builder

The order of dropdowns when several are present (plugin load order is acceptable), test file layout.
