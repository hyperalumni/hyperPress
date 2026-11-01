# Archive hero image

Date: 2026-11-01. Status: draft, awaiting approval.

## Intent

The old hard-coded Sponsors page was a Page, so it had a featured image and the theme drew it as a full-width hero under the red banner. The `hyperpress-sponsor` archive (`/sponsors/`) replaces that page in the menu but has no hero, because a post-type archive has no post to hold a featured image and `template-parts/featured-image.php` only renders for a post with a thumbnail (`has_post_thumbnail()`), and only `page.php` and three page templates include it.

Editors need to set a hero image for each archive without creating a stand-in Page. This applies to every `hyperpress-*` plugin that has an archive, not only sponsors.

## Goals

- An editor sets an archive hero image in wp-admin and it appears as the full-width hero under the banner, in the same style as a page's featured image.
- Works for the five post-type archives that use `HyperPress\Utils\Banner::register()`: sponsor, award, robot, newsletter, competition.
- Works for the season (`/seasons/<slug>/`) and program (`/programs/<slug>/`) taxonomy archives. Each term can have its own image; the taxonomy has one fallback image used when a term has none.
- The theme stays plugin-agnostic and gains only one generic, documented extension point.
- The duplicated settings code across the five plugins is replaced by one shared helper, and the duplicated "is this my archive or term page?" logic is replaced by one shared request-context helper.

## Non-goals

- No change to page, post or front-page heroes (`featured-image.php` keeps serving pages).
- No hero on singular views (a single sponsor, a single robot).
- No generic "base class" for every plugin's Settings/SettingsPage. Only the archive-banner slice is shared.
- No image on core archives (tag, category, date, author) or on the Posts page.
- Not a general media-picker framework.

## Anti-goals

- The theme must not learn about any plugin. A plugin-specific branch or option name in the theme is a failure.
- The refactors must not change what any request resolves to today (which plugin claims it, banner title, subtitle, colour). A passing hero with a changed banner is a failure.
- Do not copy the theme's hero markup or CSS into plugins.

## Constraints

- Conventions from `AGENTS.md`: PHP >=8.3, PHP only in Docker, meta-box-aio for settings and meta boxes, `hyperpress_` prefix for new identifiers, `hyper_*` registered keys, commit with explicit pathspecs, no commit unless asked.
- Banner filter priorities (theme `AGENTS.md`): plugin banners at 10, program at 20, theme archive fallbacks at 80/90, blog colour at 95. The image key must not alter that ordering.
- Existing tests for Settings, banners and subtitles (`SponsorSettingsTest`, `CompetitionSettingsTest`, `NewsletterBannerTest`, `SeasonBannerTitleTest`, the `*SubtitleTest` and `*BannerTest` files) must stay green through the refactors.
- No existing image field or media picker exists in the plugins; this introduces the first one (meta-box-aio `single_image`).

## Approaches considered

How the image gets from the plugins to the screen:

- **A. `image` key on the banner array (chosen).** Plugins put an attachment ID in `banner['image']` inside the `hyperpress_banner_content` filter. The theme's `template-parts/banner.php` renders the hero when it is set. Chosen because the banner filter already carries per-request title, subtitle and colour, every template already includes the banner, and the theme needs no per-template edits.
- **B. Separate filter `hyperpress_archive_featured_image`.** Each plugin hooks it and the theme calls it from `archive.php`/`index.php`. Rejected: every plugin re-implements request detection, and it adds a second mechanism beside the banner filter.
- **C. Plugins print the hero on `hyperpress_after_banner`.** Rejected: duplicates the theme's markup, CSS classes and responsive-image setup in plugins, contrary to the theme's presentation-only direction.

Where the field lives for the five post-type plugins: a settings-page field beside the existing subtitle field (chosen), versus a Page's featured image (rejected by the owner, who wants the archive to supersede the dedicated Page).

How much shared code: a narrow archive-banner settings helper (chosen) versus a full abstract Settings/SettingsPage base class (rejected: forces unrelated plugins into one mould and enlarges the refactor).

## Design

### 1. Shared archive-banner settings helper (`hyperpress-utils`)

Sponsor, award, robot, newsletter and competition each have near-identical `Settings.php` (option name, `BANNER_SUBTITLE` key and default, `default_option()` filter, `banner_subtitle()` getter), `SettingsPage.php` (page registration under `AdminMenu::SLUG`) and the subtitle field in `MetaBoxes.php` (textarea, `raw => true`, `Banner::sanitize_subtitle`). Only namespace, option names, label/description text and the subtitle default differ.

One helper class in `hyperpress-utils` is configured per plugin (option name, subtitle default, labels) and provides: the option default, the subtitle getter, the image-ID getter, the settings-page registration and the field definitions (subtitle plus the new image field). Composition by a config object, not inheritance, because callers use `Settings::banner_subtitle` as a static callable.

Each plugin keeps its `Settings` class with its public constants and `banner_subtitle()` as thin pass-throughs, so existing callers and tests do not change. Competition keeps its own extra settings (`past_zero`, `day_end`) and uses the helper only for the archive-banner slice.

### 2. Shared request-context helper (`hyperpress-utils`)

The context-building closure inside `Banner::register()` (`Banner.php:36-61`) computes `is_singular`, `is_archive`, `is_tax_season`, the queried season term and the requested extra terms, including the `$own_request` rule and its edge cases. Move it into a small class beside `RequestTerm` that returns the same `$ctx` array. `Banner::register()` calls it with no behaviour change. `Banner::apply()` already is a pure function of `$ctx`.

`SeasonBanner` and `ProgramBanner` should use the helper where their request tests overlap, only if that does not change which requests they claim (see "Left to the builder").

### 3. Image resolution

`Banner::register()` accepts an optional `image` arg (a callable returning an attachment ID, like `subtitle`). `Banner::apply()` sets `banner['image']` on `is_archive` requests only, never for singular requests. An unset or non-positive ID leaves the key absent.

Request types that carry the hero for the five post-type plugins: the post type's archive and its paged and season-scoped (`/seasons/<slug>/<archive>/`) views, which are post-type archives (`is_archive` true in the context). All use the post type's single image. Taxonomy-only pages (`is_tax()` with no post-type archive, where `Banner` only appends terms and rewrites the title) never get a post-type plugin's image.

Season and program taxonomy pages resolve per term with a fallback: the term's own image if set, otherwise the taxonomy-wide image from that plugin's settings page, otherwise none.

Ownership rule: `image` follows banner ownership. A banner class sets `image` only on the requests it already claims and never changes which requests it claims (`SeasonBanner`: season term pages that are not post-type archives; `ProgramBanner`: program term pages when no banner type is set yet, at priority 20). It does not overwrite an `image` already present. The theme's fallback filters (priorities 80/90/95) never set or clear `image`; an archive that only the theme claims gets no hero.

### Decisions made while writing (designer's calls)

- No migration of the old Sponsors Page's featured image. The owner sets the new field by hand once.
- Season and program use the shared helper in image-only mode: the helper exposes the image field definition and getter on their own so they can reuse them, and neither plugin's existing subtitle or other settings change in commit 1.
- Taxonomy-only pages never inherit a post-type plugin's image (see above), even when a post-type plugin appends its terms to the title there.

### 4. Admin fields

- Five post-type plugins: a `single_image` field on the existing settings page, in the same meta box as the subtitle, with a description stating where it appears.
- Season and program: a `single_image` term-meta field on the term add/edit screens of `hyper_season` and `hyper_program`, and a `single_image` field for the taxonomy fallback on each plugin's settings page. Program's settings page gains an image field only (it has no subtitle today).
- All store an attachment ID. The getters validate that the ID is a positive integer and an existing image attachment, else return no image.

### 5. Theme rendering

`template-parts/banner.php` renders the hero after the banner markup and before `do_action( 'hyperpress_after_banner' )` when `banner['image']` is a valid attachment ID. It reuses the existing `.featured-hero` class and the Interchange `data-interchange` mapping to `featured-small/medium/large/xlarge`. Extract the shared markup into one function or template part (by attachment ID) so `featured-image.php` and `banner.php` do not duplicate it. `featured-image.php` keeps its behaviour for posts with a thumbnail.

The theme's `AGENTS.md` banner-key documentation gains the `image` key (attachment ID, optional, any banner type). The priority contract in that file does not change.

### Commit sequence

1. Extract the archive-banner settings helper; plugins use it; behaviour preserved.
2. Extract the request-context helper; `Banner::register()` uses it; behaviour preserved.
3. Add the image: `image` arg and banner key, theme rendering, the settings fields, the term-meta fields and the season/program taxonomy fallback.

Steps 1 and 2 are refactors with no user-visible change and are guarded by the existing tests. Step 3 carries the new tests.

## Testing

- Unit/integration tests for the helpers and for each new field's getter (valid ID, missing, deleted attachment, non-image attachment).
- Integration: per plugin, the archive banner includes `image` when set and omits it when unset; singular views never include it; season/program resolve term-first then taxonomy fallback then none.
- Theme integration: banner with `image` renders one `.featured-hero` with the interchange attributes; banner without it renders none; a page with a thumbnail still renders exactly one hero.
- The refactor commits run the full existing suites unchanged.

## Follow-ups (out of scope here)

- Page hero consolidation: in `page.php` and the three `page-templates/*` the hero (`featured-image.php`) is included right after the banner, which is where the new banner hero renders. The theme's page banner router could set `banner['image']` from the page's thumbnail so `banner.php` draws every hero, and `featured-image.php` and its four includes could be removed. That would rewrite `FeaturedImageTest`, needs the page templates checked for markup between banner and hero, and needs a decision on single posts (`content.php:27`). Kept out of this work because it changes page rendering. The shared hero markup extracted in step 3 makes it a small change later.

## Left to the builder

- Verify how the responsive image sizes `featured-small`, `featured-medium`, `featured-large` and `featured-xlarge` are registered (the survey did not find `add_image_size` calls) and that an attachment ID resolves to them.
- Read `SeasonBanner` and `ProgramBanner` fully (priority 10 and 20; program skips when a banner type is already set) and decide how much of the request-context helper they can share without changing the requests they claim.
- Decide the helper's class names and exact config shape.
- Choose the term-meta registration mechanism within meta-box-aio and the field IDs, following the `hyperpress_{plugin}_{field}` convention.
- Confirm `hyperpress_banner_args` and the theme's later filters (priorities 80/90/95) do not drop or overwrite `banner['image']`.
- Check whether season-scoped combined views (a taxonomy archive that also carries a season) should carry the hero and align with what `Banner` does today for the banner title.
- Add the corresponding note to completed plans that mention the changed files (per the plans convention) in the same commits.
