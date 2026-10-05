# hyperPress plugins: convention and shared code (Spec 1 of 2)

Date: 2026-10-02
Scope: the 12 original plugins in `plugins/`, plus the two plugins (`hyperpress-shortcodes`, `hyperpress-media`) created by the theme extraction. `theme/` changes are specified separately in `theme/docs/superpowers/specs/2026-10-02-theme-cleanup-and-extraction-design.md`. Those two new plugins follow this convention in full.
Companion: Spec 2 (tests and audit), written separately.

## Goal

Make the 12 plugins consistent, de-duplicated and safe to extend. One naming
convention, correct plugin headers and dependencies, and shared registration
code in `hyperpress-utils` instead of six near-identical copies.

Nothing here is live (theme and plugins are in development), so old identifiers
are not preserved and no backwards-compatibility shims are added.

## Decisions (from the design discussion)

| Topic | Decision |
|---|---|
| Shared code | Lives in `hyperpress-utils`. Plugins that use it declare `hyperpress-utils` in `Requires Plugins`. |
| Registered keys | CPTs and taxonomies use the `hyper_` prefix (longest: `hyper_competition`, 17 chars; limit is 20). |
| Public URLs | Unchanged: singular rewrite slug, plural archive slug (`/newsletter/`, `/newsletters/`). Taxonomies keep plural rewrites. |
| Meta, setting, control and panel IDs | `hyperpress_{plugin}_{field}`. |
| Namespaces | `HyperPress\{Plugin}`, PSR-4, classes under `src/`. |
| Main file | Named after the folder (`hyperpress-award/hyperpress-award.php`). |
| Capabilities | Per-CPT, granted to Administrator and Editor. |
| Season | `hyperpress-season` is a hard requirement for the public CPT plugins. |
| `label`, `student` | Kept as placeholders: fixed header and new structure, no behavior. |

The mixed prefixes are deliberate. `hyper_` is for registered WordPress object
keys (short, under the length limit). `hyperpress_` is for everything stored or
registered by name in options, meta and the customizer, which already uses it.

## 1. Plugin headers

Canonical header: the field set of `hyperpress-award/award.php` today, minus
`GitHub Plugin URI`, plus `Requires Plugins`. plugins-01-test-toolchain header lint test (Spec 2) enforces it.

Fixes, all verified against the current files:

| Plugin | Fix |
|---|---|
| robot | Description says "Newsletters". Change to "Custom Post Type for Robots for hyperPress". |
| newsletter | Missing Plugin URI, License, License URI. Add them. |
| all | `GitHub Plugin URI` is only read by GitHub Updater, which is not used. Remove it from all headers. |
| student | Missing Author. Add it. |
| label, student | Descriptions describe behavior that does not exist. Mark as placeholders. |
| competition | Meta box queries the `robot` CPT (`lib/metaboxes/information.php:32`). Add `hyperpress-robot`. |
| award, robot | Register no meta boxes. Drop `meta-box-aio`. |
| socials | Customizer plus widget only. Drop `meta-box-aio`. |
| label, student | No behavior. `Requires Plugins` is empty until behavior lands. |
| season, program | Both register Meta Box panels (`rwmb_meta_boxes`). Keep `meta-box-aio`. |

Resulting `Requires Plugins`:

| Plugin | Requires |
|---|---|
| award | hyperpress-utils, hyperpress-season |
| competition | hyperpress-utils, hyperpress-season, hyperpress-robot, meta-box-aio |
| countdown | hyperpress-utils, meta-box-aio |
| newsletter | hyperpress-utils, hyperpress-season, meta-box-aio |
| robot | hyperpress-utils, hyperpress-season |
| sponsor | hyperpress-utils, hyperpress-season, meta-box-aio |
| season, program | hyperpress-utils, meta-box-aio |
| socials, label, student | none |
| media (added later) | safe-svg (SVG uploads and their sanitizing come from Safe SVG) |
| utils | none |

Countdown does not require season: it is a non-public utility CPT with no
taxonomy. Change this if you want season there too.

## 2. Identifier rename map

Registered keys:

| Old | New | Attached to |
|---|---|---|
| `award` | `hyper_award` | |
| `competition` | `hyper_competition` | |
| `countdown` | `hyper_countdown` | |
| `newsletter` | `hyper_newsletter` | |
| `robot` | `hyper_robot` | |
| `sponsor` | `hyper_sponsor` | |
| `season` | `hyper_season` | object types from `hyperpress_season_post_types` |
| `program` | `hyper_program` | object types from `hyperpress_program_post_types` |
| `district` | `hyper_district` | `hyper_competition` |
| `week` | `hyper_week` | `hyper_competition` |
| `tier` | `hyper_tier` | `hyper_sponsor` |

The season and program taxonomies read their object types from theme mods
whose values are CPT keys chosen in the customizer. The code needs no change,
but saved values must be re-selected (see Risks). plugins-01-test-toolchain contract test covers this
link.

Meta keys and meta box IDs:

| Old | New |
|---|---|
| `competition_robot` | `hyperpress_competition_robot` |
| `competition_host_name`, `competition_venue_name`, `competition_venue_address`, `competition_venue_map` | same names with `hyperpress_` prefix |
| `ne_first_event_url`, `first_event_url`, `stream_url` | `hyperpress_competition_ne_first_event_url`, `hyperpress_competition_first_event_url`, `hyperpress_competition_stream_url` |
| box `competition-information` | `hyperpress_competition_information` |
| `event_name`, `event_location`, `event_date_time` | `hyperpress_countdown_event_name`, `..._event_location`, `..._event_date_time` |
| `countdown_past_zero`, `hide_before`, `hide_after` | `hyperpress_countdown_past_zero`, `..._hide_before`, `..._hide_after` |
| boxes `countdown-event-information`, `countdown-visibility` | `hyperpress_countdown_event_information`, `hyperpress_countdown_visibility` |
| `canva_newsletter_url` | `hyperpress_newsletter_canva_url` |
| `sponsor_website`, `sponsor_description` | `hyperpress_sponsor_website`, `hyperpress_sponsor_description` |
| boxes `information` (newsletter, sponsor) | `hyperpress_newsletter_information`, `hyperpress_sponsor_information` |
| sponsor field `tier` (taxonomy-type field, `taxonomy => 'tier'`) | id `hyperpress_sponsor_tier`, `taxonomy => 'hyper_tier'` |

Meta box definitions also hard-code CPT and taxonomy keys, and these change
with section 2's first table: `post_types => ['sponsor']` (and the equivalents
in competition, countdown and newsletter), competition's robot field querying
`robot`, and the sponsor tier field above. Panels `hyperpress_newsletter`,
`hyperpress_sponsor`, `hyperpress_season` and `hyperpress_program` already
follow the convention.

Customizer control and section IDs. Setting IDs are already `hyperpress_*`;
control IDs change to match their setting ID exactly:

| Old control | New (= setting ID) |
|---|---|
| `newsletter_banner_subtitle` | `hyperpress_newsletter_banner_subtitle` |
| `sponsor_banner_subtitle` | `hyperpress_sponsor_banner_subtitle` |
| `season_banner_subtitle`, `season_post_types` | `hyperpress_season_banner_subtitle`, `hyperpress_season_post_types` |
| `program_post_types` | `hyperpress_program_post_types` |
| `countdown_{days,hours,minutes,seconds}_color` | `hyperpress_countdown_{...}_color` |
| `socials_{firstinspires,thebluealliance,facebook,instagram,youtube,twitter,github,snapchat,tiktok,discord,contact,add_calendar}` | `hyperpress_socials_{...}` |
| section `socials` | `hyperpress_socials` |

Script and style handle `timecircles` becomes `hyperpress-countdown-timecircles`.

Identifiers not listed (widget IDs and base IDs, label strings) follow the same
rule. The plan's first task enumerates them from source, and the contract test
in Spec 2 enforces the rule.

Unchanged and correct: all `hyperpress_*_banner_subtitle` and `hyperpress_socials_*`
setting IDs, `hyperpress_season_current`, `hyperpress_season_post_types`,
`hyperpress_program_post_types`, the `hyperpress_countdown` shortcode.

## 3. Structure and namespaces

Per plugin:

```
hyperpress-award/
  hyperpress-award.php     header, ABSPATH guard, autoloader, boot
  src/Plugin.php           HyperPress\Award\Plugin, registers hooks
  src/...                  one class per concern (PostType, MetaBoxes, Customize, ...)
```

- Function and class names drop the `HYPER_Press_X` namespaces and the
  `hyperpress_`-prefixed function names. Typos (`..._taxonomoy`) disappear with
  the rewrite.
- The roughly 51 `function_exists` guards are removed. They guard against
  double definition, which a namespaced class per concern makes impossible.
- Each plugin registers its own small PSR-4 autoloader for its namespace in the
  main file. `hyperpress-utils` registers one for `HyperPress\Utils`.
- Plugins load alphabetically, so `hyperpress-utils` loads after most of its
  dependents. Rule: a plugin may only call into Utils from inside hook
  callbacks (`plugins_loaded` and later), never at include time. Plugins boot
  on `plugins_loaded`; the registrars below run on `init`.
- Classes do not follow the WordPress `class-*.php` file naming. Spec 2 adjusts
  the phpcs ruleset accordingly.

## 4. Shared code in `hyperpress-utils`

| Class | Replaces | Interface |
|---|---|---|
| `PostTypeRegistrar` | `lib/custom-post-type.php` in 6 plugins (80-line copies that differ only in data) | `register(array $definition)`. Definition: key, singular, plural, rewrite slug, archive slug, icon, position, supports, taxonomies, overrides. Generates labels and the capability set, applies defaults (public, `map_meta_cap`; `show_in_rest` is false because nothing uses the REST API and the classic editor is required). |
| `TaxonomyRegistrar` | `district`, `week`, `tier`, `season`, `program` registrations | `register(array $definition)` with the same approach. Object types may be a list or a theme-mod name. |
| `Capabilities` | nothing (new) | `grant(array $post_type_keys)`: adds the CPT's capabilities to Administrator and Editor. Runs on activation and from a version-gated check on `init`, so plugin updates that skip the activation hook also get it. |
| `Breadcrumbs` | `lib/breadcrumbs.php` in award, competition, newsletter, robot, season, sponsor | `register(array $post_types)` plus a pure `build()`. Owns the custom-post-type breadcrumb logic itself (moved from the theme function `hyperpress_breadcrumbs_custom_post_type()`, with its debug output and archive bug removed) and attaches it to the theme filter `hyperpress_breadcrumbs_content`. |
| `Banner` | `lib/banner.php` in competition, newsletter, season, sponsor | `register($post_type, array $args)` with the subtitle theme-mod name and color theme-mod name. Applies a `hyperpress_banner_args` filter so plugin-specific behavior stays in that plugin. |
| `Controls\PostTypeDropdownControl`, `Controls\CategoryDropdownControl` | `HYPERpress_Dropdown_*_Control` | Moved and renamed. Loaded on `customize_register` only. |

Banners are not pure copies. Newsletter's banner prepends season names to the
title and reads a per-post background color meta key. That logic moves into a
`hyperpress_banner_args` filter in `hyperpress-newsletter` and is not folded
into Utils. Competition's banner is the simple case.

Customizer and meta-box code stays per plugin. Those files differ too much to
consolidate without an abstraction larger than the duplication it would remove.

### Capability names

With `capability_type => ['hyper_award', 'hyper_awards']` and
`map_meta_cap => true`, the capabilities are `edit_hyper_award(s)`,
`publish_hyper_awards` and so on. `Capabilities::grant()` adds each to
Administrator and Editor, and records a per-plugin version in an option so the
grant re-runs when a CPT definition changes.

### Generator leftovers removed

`_slug_changed`, `order`, `sort`, `status_column` and empty `rest_base` are
dropped from all registrations. Countdown's invalid `supports` entry `slug` is
dropped, and its `capability_type => 'page'` is replaced by the new per-CPT
capability set.

## 5. Theme impact (for the separate theme pass)

This spec changes nothing under `theme/`. The theme pass must grep for every
old identifier in section 2 and update it. The theme sweep was not run, so the
list of affected theme files is still unknown. Items known to need a check:

- Templates and loops that name CPTs or taxonomies.
- `theme/library/breadcrumbs.php`: its `hyperpress_breadcrumbs_custom_post_type()` moves into `HyperPress\Utils\Breadcrumbs` and is removed from the theme (see the theme spec).
- The banner `theme_mod`s `hyperpress_hyper_green` and `hyperpress_hyper_orange`, read by plugin banners but owned by the theme. This is an undeclared plugin-to-theme dependency. It stays as is and is documented here.

## 6. Risks and non-goals

Risks:
- **Plugin basename changes.** Renaming main files changes `active_plugins`
  entries. Plugins must be reactivated after the change.
- **Existing dev data is orphaned.** Posts keep their old `post_type`, and the
  saved `hyperpress_season_post_types` and `hyperpress_program_post_types`
  values point to old keys. The `hyperalumni/` and `hyperonline/` local
  databases need a one-off update or a reset.
- **Rewrite rules** need a flush on activation after key renames.
- **Refactor without tests is risky.** Sequencing below puts the test
  infrastructure first.

Non-goals:
- No data migration tool and no backwards-compatible aliases for old keys.
- No removal of capabilities on uninstall.
- No changes to `customize.php` or `meta-boxes.php` logic beyond renames.
- No theme changes, and no security fixes (those belong to the audit in Spec 2).

## 7. Sequencing

1. Spec 2 test infrastructure (Docker composer, phpcs, phpunit) and contract
   tests that encode this spec's convention. They fail against today's code.
2. Header fixes (section 1), verified by the header lint.
3. Utils registrars, breadcrumb, banner and capability helpers, with unit tests.
4. Per-plugin migration to the new structure and identifiers, one plugin per
   commit, each turning its contract tests green.
5. Spec 2 audit, with test-first fixes.

## 8. Success criteria

- All 12 headers pass the lint; `Requires Plugins` matches real usage.
- Every registered key, meta key and customizer ID matches the convention,
  checked by contract tests.
- No `function_exists` guards and no duplicated `custom-post-type.php`,
  `breadcrumbs.php` or `banner.php` bodies remain.
- Each CPT registers with its own capabilities, held by Administrator and Editor.
- `php -l` is clean on every file and the phpcs ruleset passes.
