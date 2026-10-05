# Theme Cleanup and Extraction: Design

Date: 2026-10-02
Status: Draft for review
Repo: `theme/` (classic FoundationPress-derived theme, Foundation 6)
Companions: `plugins/docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md` (Spec 1) and `plugins/docs/superpowers/specs/2026-10-02-plugins-tests-and-audit-design.md` (Spec 2).

## 1. Goal

Make the theme presentation-only. Functionality that should survive a theme switch moves into plugins that follow the plugin convention; the theme is then updated for the plugin renames, cleaned up to the same standards, given a test toolchain, and audited.

## 2. Scope

In scope: moving code out of the theme into plugins (including two new plugins); updating the theme for the Spec 1 identifier renames; removing moved code and dead guards; coding standards, header and prefix cleanup; a Docker test toolchain; a theme audit.

Out of scope: redesigning templates or CSS; converting to a block theme; replacing Foundation; changing the Customizer color system; moving the banner system or the core page-type breadcrumb trails (404, author, category, date, search, tag, page, post, paged) out of the theme.

Non-goals: no compatibility shims for old plugin keys; no migration of stored content (the dev database is reset, as in Spec 1).

## 3. Verified current state

- Classic theme; `functions.php` (90 lines) requires 23 files under `library/` (68 PHP files total, one of which is the 3,857-line vendored TGM library).
- `style.css`: Version 2026.0.0, Requires PHP 8.3, Requires at least 6.9, Text Domain `hyperpress`, contains a `GitHub Theme URI` header field.
- Function prefixes: `hyperpress_` (45), `foundationpress_` (37), `hyper_` (9). 84 `function_exists` guards in `library/`.
- Tooling present but unused: `phpunit.xml.dist` (bootstrap `tests/bootstrap.php`, which does not exist), `codesniffer.ruleset.xml`, composer dev dependencies for phpcs. No tests.
- The theme does not register post types, taxonomies, widgets, or Customizer color controls on behalf of plugins. It registers nav menus, sidebars, image sizes, theme supports, six shortcodes (plus `gallery`), and Customizer sections (colors, homepage, copyright, nggallery).
- Plugin-facing hooks the theme fires or consumes: `hyperpress_banner_content` (filter, `template-parts/banner.php:7`; implemented by 14 files under `library/banner/` plus the plugins), `hyperpress_breadcrumbs_content` (filter, `template-parts/breadcrumbs.php:7`; implemented by 12 files under `library/breadcrumbs/` plus the plugins), `content_template` (filter, used by `single.php:22`, `template-parts/posts.php:12`, `front-page.php:74`, and hooked by `plugins/hyperpress-sponsor/sponsor.php:52` and `plugins/hyperpress-newsletter/newsletter.php:43`), and `hyperpress_labels_content` (consumed by the theme's labels template part, hooked by the newsletter and sponsor plugins).
- The theme has no `single-*.php`, `archive-*.php` or `taxonomy-*.php` for plugin types. Plugin types render through `template-parts/content-{post_type}` fallbacks and the `content_template` filter.
- `library/plugins.php` registers 38 plugins with TGM. Seven are HYPER plugins with `'source' => '#'` (a placeholder), so TGM can never install them; the list omits competition, program, label and student.
- Hard-coded references to plugin-owned keys in the theme: `library/breadcrumbs.php:21` (default taxonomy `'season'`), `library/metaboxes/homepage-customize.php:69` (`'post_type' => ['countdown']`), `library/shortcodes/season-count.php` (`'season'` attribute). Theme mods storing plugin data: `hyperpress_home_blog_post_types`, `hyperpress_homepage_customize_countdowns`.
- `library/customize/homepage.php` imports `HYPER_Press_Utils\HYPERpress_Dropdown_Category_Control` and `...Post_Type_Control` from the utils plugin, guarded by `class_exists`.
- `library/breadcrumbs.php` defines `hyperpress_breadcrumbs_custom_post_type()`, called by the plugins' breadcrumb wrappers. It contains leftover debug output (`echo` at lines 24, 26, 29, 53, 55, 66, 85 and `var_dump` at line 31) and a bug on archives: `get_taxonomy()` returns a taxonomy object but line 73 reads `->term_id`, which does not exist.
- `library/plugins.php:70` contains a download URL with an `api_key=` query value committed to the repo.

## 4. What moves to plugins

| From the theme | To | Rules |
|---|---|---|
| `hyperpress_breadcrumbs_custom_post_type()` | `hyperpress-utils` `Breadcrumbs` (section 5) | Logic moves, not just the call. Debug output removed, archive bug fixed, default taxonomy `hyper_season`. |
| Shortcodes `time-restrict`, `time-restrict-repeat`, `time-restrict-repeat-1/2/3` (`show-hide-content.php`), `hyper_date_distance`, `raw`, `hyper_emphasis`, `hyper_banner` | new plugin `hyperpress-shortcodes` | **Tags unchanged** (they are stored in post content). Behavior unchanged by the move; defects go to the audit. |
| `hyper_season_count` | `hyperpress-season` | Tag unchanged. It calls `hyper_date_distance`, so season gains a soft dependency on `hyperpress-shortcodes` (section 6). |
| `supports/svg.php`, `supports/avif.php`, `customize/nggallery.php`, `breadcrumbs/nggallery.php` | new plugin `hyperpress-media` | NextGen code only loads when NextGen is active. |
| HYPER entries in `library/plugins.php` | deleted | Dependencies are declared by `Requires Plugins`. |
| The committed Meta Box API key URL | deleted from the file; key rotated by the owner | See section 9. |

The `gallery` shortcode override in `library/foundation.php:225` is presentation (FoundationPress markup) and stays.

### 4.1 Stays in the theme

Banner router and its renderers, core page-type breadcrumb trails, `template-parts/*`, templates, Customizer color and homepage sections, assets and build, navigation, widget areas, image sizes, theme support, Gutenberg editor support, cleanup hooks, comment and nav walkers, the `foundationpress` protocol-relative asset class.

## 5. The CPT breadcrumb builder in `hyperpress-utils`

`HyperPress\Utils\Breadcrumbs` (already specified in Spec 1 as a delegating registrar) becomes the builder itself.

- `Breadcrumbs::register( array $post_types )` keeps its signature and still filters `hyperpress_breadcrumbs_content`.
- plugins-01-test-toolchain pure method `Breadcrumbs::build( array $breadcrumbs, array $ctx ): array` assembles the trail from a request context (post type label, archive link, season term, current title, flags). It is unit tested.
- Trail, preserved from the theme function: (1) post type label linking to the archive; (2) on a singular, the newest season term by name (descending), on a season archive, the queried term; (3) on a singular, the current title. Crumb array shape (`title`, `url`, `classes.li`, `classes.bread`) and the class names (`item-archive-{label}`, `item-{term slug}-{label}`, `item-{post_type}`, `item-{post_type}{id}` and the `bread-` equivalents) are unchanged, because the theme CSS and template may depend on them. The implementation plan verifies this against the theme's CSS before any class name changes.
- The season crumb URL is `get_term_link( $term )` plus the post type's archive slug, matching the season rewrite rule. The theme's current `get_category_link( term_id ) . has_archive` is wrong for a custom taxonomy and is replaced. The implementation plan reads the season `Rewrite` rule and writes a test that the produced URL resolves to the right query vars.
- `SEASON_TAXONOMY` is `hyper_season`.
- The theme keeps `template-parts/breadcrumbs.php`, the router, and the core page-type trails. It no longer defines or needs the CPT function, so the `function_exists` check in `Breadcrumbs` is removed.

## 6. New plugins

Both follow Spec 1 exactly: folder `hyperpress-{name}/`, main file `hyperpress-{name}.php`, namespace `HyperPress\{Name}`, PSR-4 under `src/`, `Plugin` class booting on `plugins_loaded`, headers per the canonical set with `Requires Plugins`, no `function_exists` guards, identifiers `hyperpress_{plugin}_{field}`.

### `hyperpress-shortcodes`
- Requires Plugins: none.
- One class per shortcode under `src/Shortcodes/`, registered on `init` through a `Shortcodes::register()` loader. Classes: `TimeRestrict` (both `time-restrict` and the `time-restrict-repeat*` family), `DateDistance`, `Raw`, `Emphasis`, `Banner`.
- The behavior of each shortcode is pinned by characterization tests written before the move (section 8).

### `hyperpress-media`
- Requires Plugins: none. NextGen is optional (`class_exists` checks remain because this is a real optional dependency).
- `src/Uploads/Svg.php`, `src/Uploads/Avif.php`, `src/NextGen/Customizer.php`, `src/NextGen/Breadcrumbs.php`.
- The NextGen Customizer setting keeps its id `hyperpress_nggallery_photos_page`; the theme mod is already prefixed. The section id and control id follow the convention (`hyperpress_media_...`); the implementation plan lists the exact legacy ids.

### Dependency edges
`hyper_season_count` (in season) calls `[hyper_date_distance]`. Season does not declare a hard dependency; the shortcode returns an empty string, and the audit notes it, when the `hyper_date_distance` shortcode is not registered. (Alternative considered and rejected: a hard `Requires Plugins` on `hyperpress-shortcodes` in season, because it would force a feature plugin onto every season user.)

## 7. Theme updates for the Spec 1 renames and the moves

- `library/customize/homepage.php`: imports become `HyperPress\Utils\Controls\PostTypeDropdownControl` and `CategoryDropdownControl`. The `class_exists` guards go; if utils is missing, the Customizer section fails loudly instead of silently disappearing.
- `library/metaboxes/homepage-customize.php:69`: `'countdown'` becomes `'hyper_countdown'`.
- Renamed theme mods and meta keys the theme reads are listed in section 11 of this spec's implementation plan; the known ones are `hyperpress_banner_background_color` and `hyperpress_banner_subtitle` (theme-owned, unchanged) and the four color theme mods (theme-owned, unchanged).
- The `content_template` filter contract is kept: a plugin returns a file path, the theme includes it. The newsletter and sponsor migrations (plugins-04-remaining-plugins) must keep their callbacks hooked and returning a path; a test for that is added there. The `include` of a filter-returned path is an audit item.
- Any theme code that calls a plugin function or reads a plugin post type is guarded by a presence check that reflects a real optional dependency, not by a redeclaration guard.
- `library/plugins.php`: HYPER entries and the API-key URL are removed; the remaining third-party recommendations stay. `class-tgm-plugin-activation.php` stays as vendored code.

## 8. Theme cleanup and tooling

- **Header:** remove the `GitHub Theme URI` field from `style.css`. Keep Version, Requires PHP, Requires at least.
- **Prefix:** `foundationpress_` function prefixes become `hyperpress_` (37 functions), classes `FoundationPress_*` become `HyperPress_Theme_*`. The implementation plan first checks whether a child theme or a plugin overrides any `foundationpress_` function; if one does, that function is listed and handled explicitly.
- **Guards:** the 84 `function_exists` wrappers are removed, except any that protect a documented child-theme override point (listed in the plan).
- **Standards:** the theme adopts the same `phpcs` approach as the plugins (WordPress + PHPCompatibilityWP, PHP floor 8.3), non-gating until the cleanup finishes, then gating.
- **Tooling:** a Docker toolchain modelled on Spec 2's (PHP 8.5 image, MariaDB), with `composer lint`, `composer test:unit`, `composer test:integration`. The theme's `phpunit.xml.dist` and `tests/bootstrap.php` are created (the bootstrap is currently missing).
- **Tests:**
  - Unit: theme header lint; a static check that no file defines or calls a removed function; the plugin-facing hook list (`hyperpress_banner_content`, `hyperpress_breadcrumbs_content`, `content_template`, `hyperpress_labels_content`) still exists.
  - Integration (WordPress + the plugins loaded, theme active): the theme renders `/`, a single post, an archive, a 404 and the search page without PHP errors; a CPT single and archive render a banner and the expected breadcrumbs; the Customizer loads; and the same pages render with the extracted plugins deactivated (shortcodes then appear as literal text, which is the accepted degradation).
  - Characterization tests for each shortcode before it moves, run against both the theme version and the plugin version.
- No browser or visual tests.

## 9. Theme audit (candidates to confirm or reject by reading the code)

1. The committed Meta Box API key in `library/plugins.php:70` (remove from code and history policy; the owner rotates the key).
2. `extract()` in `hyper_banner` and in both `time-restrict*` handlers (`show-hide-content.php:11,34`); shortcode attribute values reaching output.
3. SVG uploads: `supports/svg.php` adds the mime type with no sanitization; and a stale `wp_check_filetype_and_ext` branch for WordPress 4.7.1.
4. `include $content_template` from a filter return value (`single.php`, `posts.php`, `front-page.php`): confirm every value comes from a plugin-controlled constant path.
5. `front-page.php` builds `do_shortcode( '[hyperpress_countdown id="' . $countdown_id . '" /]' )` from a theme mod: confirm IDs are cast to integers.
6. Output escaping in `template-parts/*` and `library/banner/*`, `library/breadcrumbs/*`.
7. Customizer settings without `sanitize_callback`.
8. Enqueue handling (versioning, handle names, vendored assets), `cleanup.php` behaviors that remove core features.
9. The `.idea` directory, the root `.env` file and other non-source files tracked or present in the repo (check `git ls-files .env .idea` and the contents of `.env` for secrets, without printing them).

The audit follows the Spec 2 method: findings verified in source, written to `theme/docs/superpowers/audit/`, approved by the user, then fixed test-first, one commit per finding.

## 10. Sequencing

1. **Plugins first** (plans already written, plus amendments in section 11): toolchain, shared utils (now including the real `Breadcrumbs` builder), migrations, the two new plugins, `hyper_season_count`.
2. **Theme plans:** theme-01-tooling-and-baseline tooling and baseline tests; theme-02-renames-and-breadcrumbs theme updates for the renames and moved breadcrumb builder, with the shortcode characterization tests written first; theme-03-remove-extracted-code remove moved code once the plugin copies are verified; theme-04-cleanup cleanup (prefixes, guards, header, standards); theme audit and fixes.

The theme never loses a feature in between: code is removed from the theme only after the plugin version exists and its characterization tests pass.

## 11. Effect on the existing plugin plans

- **plugins-01-test-toolchain:** `Convention` gains `hyperpress-shortcodes` and `hyperpress-media` (14 plugins), with Requires table entries (none) and load order (after utils and season).
- **plugins-02-shared-utils:** Task 0 also greps the theme for renamed symbols; Task 5 becomes the real `Breadcrumbs` builder with unit tests, replacing the delegating version and its theme stub.
- **plugins-03-core-plugins:** season gains `SeasonCount` (`hyper_season_count`).
- **plugins-04-remaining-plugins:** a new task creates `hyperpress-shortcodes` and `hyperpress-media`; newsletter and sponsor tasks add a test that their `content_template` callbacks return a path.
- **plugins-05-standards-and-smoke-test:** README documents the two new plugins; the smoke test includes the shortcodes and the extracted upload types.
- **plugins-06-audit:** the theme audit is separate; the plugin audit gains the shortcode `extract()` candidates in their new location.

## 12. Risks

- **Shortcode behavior drift when moved.** Mitigated by characterization tests written first, and tags unchanged.
- **Content switching to plain-text shortcodes if `hyperpress-shortcodes` is inactive.** Accepted; `Requires Plugins` on the theme is not available, so the README and TGM recommendation list `hyperpress-shortcodes` and `hyperpress-media` as recommended.
- **Breadcrumb CSS class names.** Preserved; verified against theme CSS before any change.
- **Prefix rename breaking an unseen child theme or custom code.** Checked in the plan before the rename.
- **Secret in history.** Removing the key from the file does not remove it from git history; the owner must rotate it. Rewriting history is out of scope.
- **Uncommitted work in the theme repo.** The theme has many uncommitted changes and staged deletions; every commit in the plans uses an explicit pathspec, as in the plugin plans.

## 13. Open items to settle inside the implementation plans (not design questions)

- Contents of `raw.php`, `emphasis.php`, `banner.php` (shortcodes) and the exact `library/labels` wiring, which were not read during design.
- Which `foundationpress_` functions are override points.
- Whether anything outside the theme calls `hyperpress_breadcrumbs_custom_post_type()` (the plugin consumer audit, plugins-02-shared-utils Task 0).
- The exact legacy Customizer ids for the NextGen setting.
