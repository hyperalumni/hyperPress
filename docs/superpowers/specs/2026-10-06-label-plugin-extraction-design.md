# hyperpress-label: extract labels from the theme and make them configurable

Date: 2026-10-06
Scope: `plugins/hyperpress-label` (currently a header-only placeholder), `plugins/hyperpress-season`, `-newsletter`, `-sponsor`, and the label code in `theme/`.
Amends: `theme/docs/superpowers/specs/2026-10-02-theme-cleanup-and-extraction-design.md`, which kept labels in the theme.

## Goal

Move label rendering and the post labels out of the theme into `hyperpress-label`, so the theme is presentation-only. Add a Customizer setting that chooses which labels appear on which post types, or turns labels off for a post type entirely.

> **Superseded in part (2026-10-13):** the per-post-type settings (sections 3 and 4, the `theme_mod`s, the Customizer section and `LabelDropdownControl`) now live on a "Labels" Meta Box settings page (option `hyperpress_label`, same ids as keys), and `hyperpress-label` requires `hyperpress-utils` and `meta-box-aio`. See `docs/superpowers/plans/2026-10-13-settings-pages-label.md`.

Nothing here is live, so the old theme entry points are removed rather than shimmed.

## Current state

- `theme/library/labels/post.php` registers `hyperpress_labels_post` on the `hyperpress_labels_content` filter. It emits five labels for `post`: date/time, author, season, category, comment count.
- `theme/template-parts/labels.php` applies the filter and prints `.label` cells (Foundation grid). It is loaded with `get_template_part( 'template-parts/labels' )` from `theme/template-parts/content.php:24`, `plugins/hyperpress-newsletter/templates/content.php:34` and `plugins/hyperpress-sponsor/templates/content.php:17`.
- `theme/library/labels.php` only requires `labels/post.php`; `theme/functions.php:41` requires `labels.php`.
- `hyperpress-newsletter/src/Labels.php` hooks the filter with date, author, season and comment labels for `hyper_newsletter`, duplicating most of `post.php`.
- `hyperpress-sponsor/src/Labels.php` hooks the filter with three labels for `hyper_sponsor`: last sponsored season, tier, season count.
- Each provider guards itself with `is_singular(T) || is_post_type_archive(T) || ( is_main_query() && get_post_type() === T )`.

## Decisions

| Topic | Decision |
|---|---|
| Plugin name | Keep `hyperpress-label` (singular), consistent with every other plugin. The Customizer section says "Labels". |
| Granularity | Per label, per post type, plus an enable switch per post type. |
| Settings UI | A "Labels" section in the Customizer, with `theme_mod` settings, like the other plugins. No separate admin page. |
| Defaults | Each label declares the post types it applies to. With no saved settings the output matches today. |
| Rendering hand-off | Templates call `do_action( 'hyperpress_labels' )`. No-op when the plugin is inactive. |
| Who registers labels | The plugin that owns the data: `hyperpress-label` (post labels), `-season`, `-sponsor`; `-newsletter` registers none and only opts `hyper_newsletter` into the shared labels. Season is not a hard dependency of `hyperpress-label`. |
| Stable hook | `hyperpress_labels_content` stays as the final filter on the resolved list. |
| Markup | Unchanged (`.entry-meta > .grid-x > .cell.shrink.label`). The styling stays in the theme. |

## 1. Plugin units (`hyperpress-label`, namespace `HyperPress\Label`, PSR-4 under `src/`)

| Unit | Responsibility |
|---|---|
| `Plugin` | `boot()` on `plugins_loaded`; wires the units below. |
| `Registry` | Collects definitions. The first call to `all()` after `init` fires `do_action( 'hyperpress_label_register', $registry )` once; earlier calls return what has registered so far and are flagged as incorrect usage. `Registry::add( $id, $args )` takes `group` (display name), `title`, `callback`, `default_post_types` (array), `priority` (int, default 10). The callback returns a list of label arrays (zero or more). |
| `Resolver` | `is_enabled( $post_type, $label_id ): bool`. Rules in section 3. |
| `Renderer` | Hooks the `hyperpress_labels` action; see section 2. |
| `Customizer` | The Customizer section, settings and sanitizers; see section 4. |
| `Controls\LabelDropdownControl` | Multi-select of registered labels for one post type, modelled on `HyperPress\Utils\Controls\PostTypeDropdownControl`. |
| `Labels\PostLabels` | Registers `post.date` (10), `post.author` (20), `post.category` (40), `post.comments` (50), group "Post", default post types `[ 'post' ]`. Callbacks are a direct port of `post.php`. |
| `templates/labels.php` | The markup from `theme/template-parts/labels.php`, unchanged. |

Label array shape is unchanged: `label`, `title`, `classes`, `url`, `rel`.

Default post types can be extended by other plugins through the filter `hyperpress_label_default_post_types( array $types, string $label_id )`, so a plugin can opt an existing label in for its own post type without redefining it.

## 2. Data flow

1. A template calls `do_action( 'hyperpress_labels' )` inside the loop.
2. `Renderer` takes `$type = get_post_type()`. It returns silently unless the context guard holds: `is_singular( $type ) || is_post_type_archive( $type ) || ( is_main_query() && get_post_type() === $type )`. The guard lives here once; providers no longer repeat it.
3. If the post type is disabled, it returns.
4. For each registered label, sorted by priority, where `Resolver::is_enabled( $type, $id )` is true, call the callback and merge the results.
5. `apply_filters( 'hyperpress_labels_content', $labels )`.
6. If the list is non-empty, load `templates/labels.php`, using the theme override at `hyperpress-label/labels.php` when present (`locate_template`).

## 3. Resolver rules

Per post type `T`, two `theme_mod`s exist (section 4): `hyperpress_label_{T}_enabled` and `hyperpress_label_{T}_labels`.

- `_enabled` false: no labels.
- Otherwise a label is shown if its ID is in the `_labels` list.
- Unsaved values use defaults: `_enabled` defaults to true when at least one label lists `T` in its `default_post_types` (after the `hyperpress_label_default_post_types` filter), and `_labels` defaults to those labels' IDs.
- Saved IDs with no registered label are ignored. A label registered after the admin saved does not appear for that post type until it is selected (saved lists are explicit, not merged with defaults).

## 4. Settings

- `Customizer::register()` hooks `customize_register`, as `hyperpress-season`, `-newsletter`, `-sponsor` and `-competition` do. It adds one section, `hyperpress_label` ("Labels"), with the capability `edit_theme_options` that Customizer sections use by default.
- For each public post type (attachment excluded) plus any post type named in a label's defaults, it adds two `theme_mod` settings with `transport => refresh`, so the preview shows the change:
  - `hyperpress_label_{post_type}_enabled`: a core `checkbox` control, "Show labels on {Post Type}". Sanitized with a bool cast.
  - `hyperpress_label_{post_type}_labels`: a `LabelDropdownControl` listing every registered label (option text "Group: Title"). Sanitized with `Customizer::sanitize_labels`, which keeps only registered label IDs.
- `customize_register` runs after `init`, so both the post types and the registry are populated.
- The enable checkbox is separate from the multi-select so that turning labels off for a post type is a single visible action, while the selection stays saved for later.
- Settings are `theme_mod`s, so they belong to the active theme and do not carry over a theme switch. The other plugins' Customizer settings behave the same way.

## 5. Labels registered by other plugins

- `hyperpress-season` (new `src/Labels.php`, registered from `Plugin::boot`, hooks `hyperpress_label_register`): `season.posted_during` ("Posted During"), group "Season", priority 30, defaults to the post types `hyper_season` is attached to (read from the taxonomy at registration time). Amended 2026-10-07: was the fixed `[ 'post' ]`. The defaults follow the taxonomy's actual attachment, which comes from the `hyperpress_season_post_types` theme mod (default `array()` in `Utils\TaxonomyRegistrar::resolve_object_types`), so until that Customizer setting is saved the taxonomy, and therefore the label's default, attaches to no post types, even though the control displays `post` as its default. Uses `SortedSeasons::get()` and `get_term_link()`. The theme version used `get_category_link()`, which builds a category URL for a `hyper_season` term; this is corrected by the move.
- `hyperpress-newsletter`: `Labels.php` becomes a `hyperpress_label_default_post_types` filter that adds `hyper_newsletter` to `post.date`, `post.author`, `post.comments` and `season.posted_during`. Its own copies of those labels are deleted. Output for newsletters is unchanged (same four labels, same order). Newsletter has no category label today, so `post.category` is not extended.
- `hyperpress-sponsor`: registers `sponsor.last_sponsored` (10), `sponsor.tier` (20), `sponsor.season_count` (30), group "Sponsor", default `[ 'hyper_sponsor' ]`. Callbacks are a port of the current `Labels.php`.
- `hyperpress-program` (`src/Labels.php`, registered from `Plugin::boot`): `program.programs`, group "Program", title "Program", priority 35, colour `success`. One label per `hyper_program` term assigned to the post (not a single label), ordered by name with a natural case-insensitive sort, each linking to its term archive (`get_term_link()`, WP_Error becomes `''`). Defaults to the post types `hyper_program` is attached to, read with `TaxonomyPostTypes::for_taxonomy()` at registration time, so until the `hyperpress_program_post_types` setting is saved it defaults to no post types. (Superseded 2026-10-12: program's post types now come from the Program settings page and default to `array( 'post' )`, so the label defaults to `post` on a fresh install. See `docs/superpowers/plans/2026-10-12-settings-pages-program.md`.)
- `hyperpress-competition` (`src/Labels.php`, registered from `Plugin::boot`): `district.district`, group "District", title "District", priority 36, colour `secondary`. At most one label (a competition has a single district; the first term is used) and it never links, because `hyper_district` is not public and has no archive. Defaults to the post types `hyper_district` is attached to (`hyper_competition`), via `TaxonomyPostTypes::for_taxonomy()`.
- `hyperpress-competition` also registers `week.week`, group "Week", title "Week", priority 37, colour `alert`. At most one label (the first `hyper_week` term) and it never links, because `hyper_week` is not public. Defaults to the post types `hyper_week` is attached to (`hyper_competition`), via `TaxonomyPostTypes::for_taxonomy()`.
- Amended 2026-10-07: the program, district and week labels were added. With season (30) and category (40) they display in the order season, program, district, week, category. Neither plugin gains a dependency on `hyperpress-label`.

None of these plugins require `hyperpress-label`; without it the registration action never fires and nothing happens.

## 6. Changes outside the label plugin

Theme (`theme/`):
- Delete `template-parts/labels.php`, `library/labels.php`, `library/labels/post.php`, and the `functions.php:41` require.
- `template-parts/content.php:24`: replace `get_template_part( 'template-parts/labels' )` with `do_action( 'hyperpress_labels' )`.
- `hyperpress_sorted_seasons()` stays; `library/breadcrumbs/post.php` still uses it.
- Update `tests/unit/PluginHooksContractTest.php` so `hyperpress_labels_content` is owned by `hyperpress-label`, and move the label assertions in `tests/integration/LabelsBreadcrumbsGuardTest.php` and `StrictComparisonsTest.php` into `plugins/tests`.
- Amend the 2026-10-02 theme spec: labels are extracted to `hyperpress-label`; `hyperpress_labels_content` is now produced by the plugin. Update `theme/AGENTS.md` where it lists the hook.

Plugins:
- `hyperpress-newsletter/templates/content.php:34` and `hyperpress-sponsor/templates/content.php:17`: switch to the action.
- Replace the `hyperpress-label.php` placeholder description and header with the real plugin; update `plugins/AGENTS.md` (the `label` entry is no longer a placeholder).
- Review `NewsletterLabelsTest`, `SponsorWidgetTest`, `NewsletterSeasonErrorTest`, `NewsletterSeasonEscapingTest` and `EmptyLabelTest` for label assumptions.

Docker stacks (`hyperalumni/`, `hyperonline/`, not git-tracked): add the `hyperpress-label` mount to both volume lists in `compose.main.yaml`; a plugin missing from the list is not loaded, and `hyperpress-season` must also be mounted wherever season labels are wanted.

## 7. Behavior and edge cases

- Plugin inactive: no labels, no fatal. This is a deliberate change from the theme always showing post labels.
- No saved settings: the output matches today's for `post`, `hyper_newsletter` and `hyper_sponsor`, apart from the corrected season link.
- A disabled post type outputs nothing and does not run any callback.
- A callback that returns a non-array is ignored.
- Text domain stays `hyperpress`. All strings go through `__()`; the hard-coded English titles ("Posted At", "Posted By", etc.) remain as they are today and are not part of this change.

## 8. Rollout order

The repos commit separately, and no commit should leave a site without labels.

1. `hyperpress-label`: units, `PostLabels`, settings, template. Season and sponsor registrations, newsletter filter. All still coexist with the theme code.
2. Switch the three call sites to the action; delete the newsletter/sponsor raw filter callbacks at the same time, so labels do not show twice.
3. Delete the theme files and update the theme tests and docs.
4. Add the Docker mounts (any time before step 2 is tested locally).

## 9. Testing

- Unit: `Resolver` (saved vs default, disabled post type, unknown IDs, default-types filter), `Customizer` sanitizers. Integration: Customizer registration, in the style of `NewsletterCustomizerTest` (section exists, both settings per post type exist with the right defaults).
- Integration: `Renderer` output for `post`, `hyper_newsletter`, `hyper_sponsor` with and without saved settings; disabled post type outputs nothing; each old label test moved or rewritten against the registered labels.
- The plugins test toolchain is still being built (see `2026-10-02-plugins-tests-and-audit-design.md`); the implementation plan must confirm which of these can run before relying on them.

## Out of scope

- Per-post overrides, per-role visibility, label reordering in the UI.
- Changing the theme breadcrumbs (`get_category_link` on season terms there is a separate issue).
- Translating the hard-coded label titles.

> Note (2026-10-12): the amended sentence on `season.posted_during` (taxonomy attaches only via the `hyperpress_season_post_types` theme mod, default `array()`) is superseded: season's post types now live on the Season settings page and default to `array( 'post' )`, so the label defaults to posts on a fresh install. See `docs/superpowers/plans/2026-10-12-settings-pages-season.md`.
