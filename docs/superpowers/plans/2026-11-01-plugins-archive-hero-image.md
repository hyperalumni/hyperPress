# Archive Hero Image Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** An editor sets a hero image per archive in wp-admin, and the theme draws it under the banner for the sponsor, award, robot, newsletter and competition archives and for season and program term pages (term image, then a taxonomy-wide fallback).

**Architecture:** Plugins put an attachment ID in `banner['image']` inside the existing `hyperpress_banner_content` filter; the theme's `template-parts/banner.php` renders it with the existing `.featured-hero` markup. Two behaviour-preserving refactors come first: a shared archive-banner settings helper and a shared request-context helper, both in `hyperpress-utils`.

**Tech Stack:** PHP 8.3 (Docker only), WordPress 6.9, meta-box-aio (`mb_settings_pages`, `rwmb_meta_boxes`, `single_image`), PHPUnit (unit and integration suites), PHPCS, Foundation 6 Interchange.

**Spec:** `docs/superpowers/specs/2026-11-01-archive-hero-image-design.md`

## Global Constraints

- PHP floor `>=8.3`; PHP runs in Docker only. From `plugins/`: `docker compose run --rm php composer lint|test:unit|test:integration`. From `themes/hyperpress-foundation/`: the same three scripts.
- New identifiers use the `hyperpress_` prefix; registered keys use `hyper_*`; settings/field ids follow `hyperpress_{plugin}_{field}`; text domain `hyperpress`.
- The theme must not learn about any plugin: no plugin class, option name or post type in `themes/hyperpress-foundation`.
- Banner filter priorities do not change: plugin banners 10, program 20, theme archive fallbacks 80/90, blog colour 95. The theme's fallback filters never set or clear `banner['image']`.
- Tasks 1 to 3 are behaviour-preserving refactors: no request may resolve differently (which plugin claims it, title, subtitle, colour) and every existing test stays green unchanged.
- `image` follows banner ownership: set only on requests the banner class already claims, only on archive requests (never singular), never overwriting an `image` already present. Taxonomy-only pages never get a post-type plugin's image.
- Commit from the repo root with explicit pathspecs: `git add <paths>` then `git commit -m "type(scope): lowercase summary" -- <paths>`. Never `git add -A`, never a bare `git commit`. Stage this plan file with its updated checkboxes in each task's commit.
- Before committing any task, grep `docs/superpowers/plans/` for the files you changed and update any plan that mentions them in the same commit.

## Review Focus

- A saved image ID whose attachment was deleted, or that is not an image: no hero, no PHP warning (Task 4 `ArchiveImageTest`; Tasks 6, 7, 8 integration tests).
- A single sponsor/robot/etc. page when the archive image is set: `banner['image']` absent (Task 4 unit test, Task 6 integration test).
- `/seasons/<slug>/sponsors/` (season-scoped post-type archive) and `/sponsors/page/2/`: the sponsor image, not the season's (Task 6).
- A season or program term with an image beats the fallback; no image falls back; invalid term image falls back; neither gives no hero (Tasks 7 and 8).
- Editor clears the image field: Meta Box drops the key, the option row still exists, the result is no hero and not a default (Tasks 6, 7, 8 `Settings::banner_image()` tests). And an image set by a plugin survives the theme's priority 80/90/95 filters (Task 5).

## File Structure

New, in `plugins/hyperpress-utils/src/`:
- `ArchiveBannerSettings.php`: per-plugin config object for the archive-banner settings (option defaults, subtitle getter, settings-page array, subtitle and image field definitions, image getter).
- `ArchiveRequest.php`: request-context helper extracted from `Banner::register()`.
- `ArchiveImage.php`: image field definition, sanitizer, attachment validation, term-then-fallback resolution.

New, in the theme: `template-parts/hero.php`: the `.featured-hero` markup for one attachment ID.

Modified: `Banner.php` (uses `ArchiveRequest`; `image` arg and `apply()` key); `Settings.php`, `SettingsPage.php`, `MetaBoxes.php`, `Plugin.php` in sponsor, award, robot, newsletter, competition; `SeasonBanner.php`, `Settings.php`, `MetaBoxes.php` in season; `ProgramBanner.php`, `Settings.php`, `MetaBoxes.php` in program; theme `template-parts/banner.php`, `template-parts/featured-image.php`, `AGENTS.md`.

Tests: `plugins/tests/unit/Utils/BannerTest.php` (extend), `plugins/tests/integration/Utils/{ArchiveBannerSettingsTest,ArchiveRequestTest,ArchiveImageTest}.php` (new), `plugins/tests/integration/{Sponsor,Award,Robot,Newsletter,Competition,Season,Program}/…ArchiveImageTest.php` (new, follow the existing per-plugin test directories), theme `tests/integration/ArchiveHeroTest.php` (new).

---

### Task 0: Commit the spec and this plan

- [ ] **Step 1:** `git add docs/superpowers/specs/2026-11-01-archive-hero-image-design.md docs/superpowers/plans/2026-11-01-plugins-archive-hero-image.md` then `git commit -m "docs(plans): archive hero image" -- <both paths>`. Confirm with `git show --stat --format='%h %s' HEAD`.

---

### Task 1: Archive-banner settings helper, converted in the sponsor plugin

**Files:**
- Create: `plugins/hyperpress-utils/src/ArchiveBannerSettings.php`
- Modify: `plugins/hyperpress-sponsor/src/{Settings,SettingsPage,MetaBoxes}.php`
- Test: `plugins/tests/integration/Utils/ArchiveBannerSettingsTest.php`; guard: `SponsorSettingsTest.php`, `Sponsor/SponsorSubtitleTest.php`

**Interfaces:**
- Produces, namespace `HyperPress\Utils`:
  - `final class ArchiveBannerSettings`
  - `__construct( string $option, string $subtitle_key, string $subtitle_default )`
  - `defaults(): array` (`[ subtitle_key => subtitle_default ]`)
  - `default_option( $fallback, $option = '', $passed_default = false )` (same semantics as today's `Settings::default_option`)
  - `banner_subtitle(): string` (same four cases as today's `Settings::banner_subtitle()`: option not an array gives the default, array without the key gives `''`, non-string value gives the default)
  - `static page( string $id, string $option, string $title ): array` (the `mb_settings_pages` entry: `id`, `option_name`, `menu_title`, `page_title`, `parent => AdminMenu::SLUG`, `capability => 'manage_options'`, `style => 'no-boxes'`)
  - `subtitle_field( string $name, string $desc, bool $with_std = false ): array` (`textarea`, `raw => true`, `sanitize_callback => [Banner::class, 'sanitize_subtitle']`, plus `std => subtitle_default` only when `$with_std`)
- Each plugin keeps `Settings::OPTION`, `BANNER_SUBTITLE`, `BANNER_SUBTITLE_DEFAULT`, `defaults()`, `default_option()` and `banner_subtitle()` as thin pass-throughs, so callers and tests are unchanged.

- [ ] **Step 1: Write the failing test** `ArchiveBannerSettingsTest` (integration, `WP_UnitTestCase`) with one assertion group per case: `banner_subtitle()` returns the default when the option row is absent (default filter registered), `''` when the row exists without the key, the stored string when present, the default when the stored value is not a string; `page()` returns the keys above with `parent === AdminMenu::SLUG`; `subtitle_field()` has `type === 'textarea'`, `raw === true`, the `Banner::sanitize_subtitle` callback, and `std` only when `$with_std`.
- [ ] **Step 2:** Run `docker compose run --rm php composer test:integration -- --filter ArchiveBannerSettingsTest` (from `plugins/`). Expected: FAIL, class not found.
- [ ] **Step 3: Implement `ArchiveBannerSettings`** per the interface; move the logic from `hyperpress-sponsor/src/Settings.php:20-55` unchanged in behaviour.
- [ ] **Step 4:** Convert the sponsor plugin: `Settings` builds one `ArchiveBannerSettings( self::OPTION, self::BANNER_SUBTITLE, self::BANNER_SUBTITLE_DEFAULT )` (private static accessor) and delegates; `SettingsPage::pages()` uses `ArchiveBannerSettings::page( self::ID, Settings::OPTION, __( 'Sponsor', 'hyperpress' ) )`; `MetaBoxes::boxes()` uses `subtitle_field()` with the existing label and description strings and `$with_std = false` (sponsor has no `std` today). Keep `Plugin.php` unchanged.
- [ ] **Step 5:** Run the new test, then `composer test:unit`, `composer test:integration` and `composer lint` from `plugins/`. Expected: all pass, 0 lint violations.
- [ ] **Step 6: Commit** `refactor(utils): archive banner settings helper, used by sponsor` with the paths above plus this plan file.

---

### Task 2: Convert award, robot, newsletter and competition

**Files:**
- Modify: `plugins/hyperpress-{award,robot,newsletter,competition}/src/{Settings,SettingsPage,MetaBoxes}.php`
- Guards: `plugins/tests/integration/{CompetitionSettingsTest,NewsletterBannerTest}.php`, `Award/AwardBannerTest.php`, `Robot/RobotBannerTest.php`, `Competition/BannerTest.php`, `Utils/BannerRegisterTest.php`, the `*SubtitleTest` files.

**Interfaces:** Consumes `ArchiveBannerSettings` from Task 1. Produces nothing new.

- [ ] **Step 1:** Convert each plugin the same way as sponsor, keeping each plugin's own default (`''` for award, robot and competition; `'Updates on Team HYPER from our Marketing Team'` for newsletter), labels and descriptions. `subtitle_field( …, $with_std )`: `true` for award, robot and competition (they set `std` today), `false` for newsletter (it does not).
- [ ] **Step 2:** Competition keeps its own `PAST_ZERO`, `DAY_END`, getters and fields; its `defaults()` returns the helper's defaults merged with its own, and its `default_option()` still returns that merged array.
- [ ] **Step 3:** Run `composer test:unit`, `composer test:integration`, `composer lint` from `plugins/`. Expected: all pass with no test edited.
- [ ] **Step 4: Commit** `refactor(plugins): award, robot, newsletter and competition use the archive banner settings helper`.

---

### Task 3: Request-context helper

**Files:**
- Create: `plugins/hyperpress-utils/src/ArchiveRequest.php`
- Modify: `plugins/hyperpress-utils/src/Banner.php:36-61`, `plugins/hyperpress-season/src/SeasonBanner.php`, `plugins/hyperpress-program/src/ProgramBanner.php`
- Test: `plugins/tests/integration/Utils/ArchiveRequestTest.php`; guards: `Utils/BannerRegisterTest.php`, `unit/Utils/BannerTest.php`, `SeasonBannerTitleTest.php`, `Season/SingularBannerTest.php`, `Competition/BannerTest.php`, Program banner tests

**Interfaces:**
- Produces, `final class HyperPress\Utils\ArchiveRequest`:
  - `static for_post_type( string $post_type, array $taxonomies = array() ): array{ is_singular: bool, is_archive: bool, is_tax_season: bool, season: ?\WP_Term, extra_terms: string[] }`. Exactly today's logic: `season` is `is_tax() ? RequestTerm::get( Banner::SEASON_TAXONOMY ) : null`; `is_tax_season` is `null !== season`; `own_request` and the `extra_terms` loop are as in `Banner.php:49-61`.
  - `static taxonomy_term( string $taxonomy ): ?\WP_Term`: `is_tax() && ! is_post_type_archive() ? RequestTerm::get( $taxonomy ) : null`.
- Consumes `RequestTerm::get( string ): ?WP_Term`.

- [ ] **Step 1: Write the failing test** `ArchiveRequestTest` (integration): `for_post_type( 'hyper_sponsor' )` on the sponsor archive gives `is_archive` true and `is_singular` false; on a single sponsor gives `is_singular` true; on a season term page `taxonomy_term( 'hyper_season' )` returns the term and on a post-type archive carrying a season it returns null; on another post type's archive with a `hyper_week` term query var, `extra_terms` is empty for this post type's call (the existing rule).
- [ ] **Step 2:** Run it. Expected: FAIL, class not found.
- [ ] **Step 3: Implement `ArchiveRequest`**, then change `Banner::register()` to build its `$ctx` from `ArchiveRequest::for_post_type()` plus `subtitle`, `color` and `term_name` (`season ? season->name : ''`). Keep the order of calls (the subtitle callable is still called on every request).
- [ ] **Step 4:** `SeasonBanner::filter()` and `ProgramBanner::filter()` use `ArchiveRequest::taxonomy_term()` in place of their inline `is_tax()` / `is_post_type_archive()` / `RequestTerm::get` checks. Program keeps its `type` guard and priority 20.
- [ ] **Step 5:** Run the full unit and integration suites and lint from `plugins/`. Expected: all pass, no existing test changed.
- [ ] **Step 6: Commit** `refactor(utils): archive request helper shared by banner, season and program`.

---

### Task 4: Image validation, fields and the banner `image` key

**Files:**
- Create: `plugins/hyperpress-utils/src/ArchiveImage.php`
- Modify: `plugins/hyperpress-utils/src/Banner.php`
- Test: `plugins/tests/integration/Utils/ArchiveImageTest.php`; extend `plugins/tests/unit/Utils/BannerTest.php`

**Interfaces:**
- Produces, `final class HyperPress\Utils\ArchiveImage`:
  - `static valid_id( $value ): int`: the positive integer attachment ID if `$value` is an int or numeric string and `wp_attachment_is_image()` is true for it, otherwise `0`.
  - `static sanitize( $value ): int`: `absint` of a scalar, else `0`.
  - `static field( string $id, string $name, string $desc ): array`: a meta-box-aio field, `type => 'single_image'`, `sanitize_callback => [ self::class, 'sanitize' ]`.
  - `static term_image_id( int $term_id, string $meta_key, int $fallback ): int`: `valid_id( get_term_meta( $term_id, $meta_key, true ) )`, else `valid_id( $fallback )`, else `0`.
- `Banner::register( $post_type, $args )` accepts optional `'image' => callable` returning mixed. In the context it sets `'image' => $ctx['is_archive'] ? ArchiveImage::valid_id( call_user_func( $args['image'] ) ) : 0` (callable absent gives `0`).
- `Banner::apply()`: when `$ctx['is_archive']` and `( $ctx['image'] ?? 0 ) > 0` and `empty( $banner['image'] )`, sets `$banner['image'] = (int) $ctx['image']`. Never on singular requests. Document the `image` arg and key in the class docblock.

- [ ] **Step 1: Write failing tests.** Unit (`BannerTest`): `apply()` with `is_archive` true and `image => 12` sets `banner['image'] === 12`; with `is_singular` true and `image => 12` the key is absent; with `image => 0` absent; an `image` already in the incoming banner is not overwritten. Integration (`ArchiveImageTest`): `valid_id` returns the ID for a real image attachment, `0` for a deleted attachment, for a non-image attachment (a PDF), for `'abc'`, `-3`, `0` and `null`; `term_image_id` returns the term meta when valid, the fallback when the meta is absent or invalid, `0` when neither; `field()` has `type === 'single_image'`.
- [ ] **Step 2:** Run both. Expected: FAIL.
- [ ] **Step 3: Implement** `ArchiveImage` and the `Banner` changes per the interfaces.
- [ ] **Step 4:** Run `composer test:unit`, `composer test:integration`, `composer lint` from `plugins/`. Expected: all pass.
- [ ] **Step 5: Commit** `feat(utils): archive image validation and banner image key`.

---

### Task 5: Theme renders the hero

**Files:**
- Create: `themes/hyperpress-foundation/template-parts/hero.php`
- Modify: `template-parts/banner.php`, `template-parts/featured-image.php`, `themes/hyperpress-foundation/AGENTS.md`
- Test: `themes/hyperpress-foundation/tests/integration/ArchiveHeroTest.php`; guard `FeaturedImageTest.php`, `ArchiveBannerPriorityTest.php`

**Interfaces:**
- `template-parts/hero.php` takes `$args['attachment_id']` (int) via `get_template_part( 'template-parts/hero', null, array( 'attachment_id' => $id ) )` and prints exactly today's markup: `<header class="featured-hero" role="banner" data-interchange="[URL, small], [URL, medium], [URL, large], [URL, xlarge]">` with URLs from `wp_get_attachment_image_url( $id, 'featured-small|medium|large|xlarge' )` (the sizes registered in `library/responsive-images.php`), each passed through `esc_url`. Prints nothing when the ID is not a positive image attachment.
- `banner.php`: add `'image' => 0` to the filter's default array; after the `</header>` and before `do_action( 'hyperpress_after_banner' )`, call the hero part when `absint( $banner['image'] )` is an image attachment.
- `featured-image.php` keeps its `get_post() && has_post_thumbnail()` guard and calls the hero part with `get_post_thumbnail_id()`.
- `AGENTS.md`: document the optional `image` banner key (attachment ID, any banner type, rendered under the banner; the theme's own fallback filters never set it).

- [ ] **Step 1: Write the failing test** `ArchiveHeroTest`: render `template-parts/banner` (with `ob_start`) while a test filter on `hyperpress_banner_content` sets `image` to a real attachment: output contains exactly one `class="featured-hero"` with four `data-interchange` URLs for the four sizes, placed after the banner `</header>`; without `image` there is none; with a deleted attachment ID there is none and no PHP notice; with the filter at priority 10 and the theme's priority 80/90/95 filters active on an archive request (as in `ArchiveBannerPriorityTest`), the image is still present. `FeaturedImageTest` stays unchanged and green (one hero for a page with a thumbnail).
- [ ] **Step 2:** From `themes/hyperpress-foundation/`: `docker compose run --rm php composer test:integration -- --filter ArchiveHeroTest`. Expected: FAIL.
- [ ] **Step 3: Implement** the template parts and the `AGENTS.md` note per the interfaces.
- [ ] **Step 4:** Run `composer test:unit`, `composer test:integration` and `composer lint` in the theme. Expected: all pass. Run `pnpm phpcs` if `composer lint` does not cover the new file.
- [ ] **Step 5: Commit** `feat(theme): render a banner image hero from the banner image key`.

---

### Task 6: Image field for sponsor, award, robot, newsletter and competition

**Files:**
- Modify: `plugins/hyperpress-utils/src/ArchiveBannerSettings.php`; `plugins/hyperpress-{sponsor,award,robot,newsletter,competition}/src/{Settings,MetaBoxes,Plugin}.php`
- Test: extend `ArchiveBannerSettingsTest`; one `…ArchiveImageTest.php` per plugin in its existing test directory

**Interfaces:**
- `ArchiveBannerSettings::__construct` gains a fourth parameter `string $image_key = ''`. New: `image_id(): int` (reads the option array key `$image_key`, returns `ArchiveImage::valid_id( value )`, `0` when the option is not an array, the key is absent, or `$image_key === ''`); `image_field( string $name, string $desc ): array` (`ArchiveImage::field( $image_key, … )`).
- Each plugin `Settings` gains `public const BANNER_IMAGE = 'hyperpress_<plugin>_banner_image'` and `public static function banner_image(): int`. Each `MetaBoxes` box gets the `image_field()` right after the subtitle field (description: where it appears, e.g. "Shown as a full-width image under the banner on the sponsors pages"). Each `Plugin::register_banner()` adds `'image' => array( Settings::class, 'banner_image' )`.

- [ ] **Step 1: Write failing tests.** Helper: `image_id()` for the cases valid attachment, deleted attachment, key absent from an existing row, option missing, non-array option. Per plugin (named for the plugin, e.g. `SponsorArchiveImageTest`): with a real attachment saved in the option, `apply_filters( 'hyperpress_banner_content', array() )` on the post-type archive has `image === $id`; on `/page/2/` of the archive, too; on the season-scoped archive (`/seasons/<slug>/<archive>/` or `?post_type=…&hyper_season=<slug>`) the plugin's image, not any season image; on a single post of the type there is no `image` key; on a taxonomy-only page there is none; with the key cleared (row present, key absent) none. The `rwmb_meta_boxes` output contains the `single_image` field with the plugin's id.
- [ ] **Step 2:** Run them. Expected: FAIL.
- [ ] **Step 3: Implement** the helper additions and the five plugin changes per the interfaces.
- [ ] **Step 4:** Run the full unit and integration suites and lint from `plugins/`. Expected: all pass.
- [ ] **Step 5: Commit** `feat(plugins): archive hero image setting for sponsor, award, robot, newsletter and competition`.

---

### Task 7: Season term image with a taxonomy fallback

**Files:**
- Modify: `plugins/hyperpress-season/src/{Settings,MetaBoxes,SeasonBanner}.php`
- Test: `plugins/tests/integration/Season/SeasonArchiveImageTest.php`

**Interfaces:**
- `Season\Settings`: `public const BANNER_IMAGE = 'hyperpress_season_banner_image'` (used as both the settings-page option key for the taxonomy fallback and the term-meta key) and `public static function banner_image(): int` (`ArchiveImage::valid_id` of the saved option value; `0` if absent or the option is not an array).
- `Season\MetaBoxes`: add an `ArchiveImage::field( Settings::BANNER_IMAGE, … )` to the existing settings box (the fallback), and a second box with `'type' => 'term'`, `'taxonomies' => array( 'hyper_season' )` holding the same field (term image). No plugin has declared a term-meta box before, so confirm the `type`/`taxonomies` keys against the installed meta-box-aio version.
- `SeasonBanner::filter()`: after it claims a request, if `empty( $banner['image'] )` and `ArchiveImage::term_image_id( $term->term_id, Settings::BANNER_IMAGE, Settings::banner_image() )` is greater than `0`, set `$banner['image']` to it. The claim logic is untouched.

- [ ] **Step 1: Write failing tests.** On `/seasons/<slug>/`: term meta valid gives the term image; no term meta with a fallback gives the fallback; invalid (deleted) term meta falls back; neither gives no `image` key; term image beats fallback; an `image` already in the banner is kept. A season-scoped post-type archive does not get a season image (the season banner does not claim it). `rwmb_meta_boxes` output contains the term box (`type === 'term'`, `taxonomies === ['hyper_season']`) and the fallback field.
- [ ] **Step 2:** Run it. Expected: FAIL.
- [ ] **Step 3: Implement** per the interfaces.
- [ ] **Step 4:** Run the full unit and integration suites and lint from `plugins/`. Expected: all pass.
- [ ] **Step 5: Commit** `feat(season): archive hero image per season with a taxonomy fallback`.

---

### Task 8: Program term image with a taxonomy fallback

**Files:**
- Modify: `plugins/hyperpress-program/src/{Settings,MetaBoxes,ProgramBanner}.php`
- Test: `plugins/tests/integration/Program/ProgramArchiveImageTest.php` (create the directory if the plugin's tests live elsewhere; follow the existing program banner test location)

**Interfaces:** Same shape as Task 7 with `Program\Settings::BANNER_IMAGE = 'hyperpress_program_banner_image'`, `banner_image(): int`, a `type => 'term'` box for `taxonomies => array( Plugin::TAXONOMY )`, and the fallback field added to the existing Program settings box (Program has no subtitle field today; add only the image field). `ProgramBanner::filter()` sets `image` after it sets `type`, with the same non-overwrite and fallback rules; its `! empty( $banner['type'] )` skip and priority 20 are untouched.

- [ ] **Step 1: Write failing tests** mirroring Task 7 for `/programs/<slug>/`, plus: a program page that a priority-10 banner already claimed (banner `type` set) gets no program image.
- [ ] **Step 2:** Run it. Expected: FAIL.
- [ ] **Step 3: Implement** per the interfaces.
- [ ] **Step 4:** Run the full unit and integration suites and lint from `plugins/`, and the theme's `composer test:integration` and `composer lint`. Expected: all pass.
- [ ] **Step 5: Commit** `feat(program): archive hero image per program with a taxonomy fallback`.

---

### Task 9: Close out

**Files:** this plan, the spec, any plan found by the grep.

- [ ] **Step 1:** From the repo root run `grep -rln "Banner.php\|SeasonBanner\|ProgramBanner\|featured-image.php\|banner.php" docs/superpowers/plans/`; for each completed plan that describes a file changed here, append a dated line to its "Outcome / execution notes" (create the heading if absent).
- [ ] **Step 2:** Add a `STATUS: ✅ COMPLETED` banner with the date and commit range to the top of this plan, and set the spec's status line to "implemented". Add the page-hero consolidation follow-up to this plan's "Known follow-ups" section (copied from the spec).
- [ ] **Step 3:** Run `pnpm fmt:check` from the repo root (after loading Node and pnpm as in the root `AGENTS.md`). Expected: clean.
- [ ] **Step 4: Commit** `docs(plans): archive hero image completed`.

## Known follow-ups

- Page hero consolidation: have the theme's page banner router set `banner['image']` from the page thumbnail so `banner.php` draws every hero and `featured-image.php` can be removed (see the spec).
