# NextGEN Remaining Templates on Foundation 6 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Re-theme the remaining NextGEN legacy templates the sites use (`album-extend`, `gallery-caption`, `imagebrowser`, `imagebrowser-caption`, `gallery-carousel`) with Foundation 6 components, and give the NextGEN random-image widget a compact Foundation template.

**Architecture:** Same approach as the first plan: each file in `nggallery/` is a NextGEN legacy template override, rendered only when a display's (or the widget's) "Template" setting selects the parent theme. Shared markup lives in partials under `template-parts/nextgen/` so `gallery.php` and `gallery-caption.php`, and `imagebrowser.php` and `imagebrowser-caption.php`, do not duplicate it. The NextGEN hooks its JS binds to (ids, classes, data attributes) stay. Pagination goes through `hyperpress_nextgen_pagination()` from the first plan.

**Tech Stack:** PHP 8.3 (WordPress, PHPUnit integration tests in Docker), Foundation 6.9.0 (Orbit, Media Object, Thumbnail, Button Group, Pagination, Callout; all Sass components are already included in `src/assets/scss/app.scss`, Orbit at line 40).

**Spec:** `docs/superpowers/specs/2026-11-05-theme-nggallery-foundation-templates-design.md` (the first plan's design); this plan adds the decisions below. **Prerequisite:** `docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md` is complete (stubs, test harness, `hyperpress_nextgen_pagination()`, the new `gallery.php`).

## Global Constraints

Theme root is `themes/hyperpress-foundation/` (paths below are relative to it unless prefixed `docs/`).

- Commit with explicit paths, never `git add -A`; commit only when the owner asks. Style `type(scope): lowercase summary`. Tick this plan's boxes in the same commit as each task's code.
- Tool setup per shell call: `source ~/.nvm/nvm.sh && nvm use && export PATH="$HOME/.local/share/pnpm/bin:$PATH"`; PHP only in Docker: `docker compose run --rm php composer test:integration -- --filter <Class>`; `composer lint` stays at 0 violations.
- Always read the stock template first (`~/projects/nextgen-gallery/nextgen-gallery/src/Legacy/view/<name>.php`, NextGEN 4.5.5) and keep its logic and variable use; only the presentation changes. Escape plain text with `esc_*`; NextGEN-built HTML (`thumbcode`, `size`, `href_link`, `caption`, `pagination`, `effect_code`) is printed as the first plan does, with `phpcs:ignore ... -- built by NextGen` notes or through `wp_kses_post()`.
- The legacy template lookup (`LegacyTemplateLocator`) accepts `<name>.php` and `<name>-template.php` from the child theme's, then the parent theme's `nggallery/` folder.
- NextGEN JS hooks that must survive (4.5.5):
  - `gallery.php` and `gallery-caption.php`: `static/Thumbnails/ajax_pagination.js` handles `a.page-numbers, a.prev, a.next`, finds the gallery through the ancestor `.ngg-galleryoverview` and its `id`, and replaces it (see the first plan).
  - `imagebrowser*.php`: `static/ImageBrowser/imagebrowser.js` finds every `div.ngg-imagebrowser` and starts `new NggPaginatedGallery( dataset.nextgenGalleryId, '#' + id )`. `static/GalleryDisplay/common.js` then handles clicks on `a.ngg-browser-prev, a.ngg-browser-next`, fetches the link's `href` over AJAX and replaces the container. So the wrapper keeps `class="ngg-imagebrowser"`, its `id` and `data-nextgen-gallery-id`, and the two links keep their classes and `id`s.
  - `album-extend.php` and `gallery-carousel.php`: no NextGEN JS. The carousel is plain links and a `<ul>`.
- Foundation JS: the theme loads Foundation globally (`data-orbit` initialises automatically; a working example is `page-templates/kitchen-sink.php:605`).
- Out of scope: `singlepic.php`, `imagebrowser-exif.php`, the modern templates, srcset, and any change to the NextGEN or `hyperpress-media` plugins.

## File Structure

| File                                                               | Change                                                                 |
| ------------------------------------------------------------------ | ---------------------------------------------------------------------- |
| `tests/integration/Concerns/NggalleryTemplateHelpers.php`          | Create: shared `render()` helper moved out of the first test           |
| `tests/integration/NggalleryTemplatesTest.php`                     | Modify: use the trait                                                  |
| `template-parts/nextgen/gallery-grid.php`                          | Create: grid partial extracted from `gallery.php`                      |
| `template-parts/nextgen/imagebrowser.php`                          | Create: image browser partial                                          |
| `nggallery/gallery.php`                                            | Modify: include the grid partial                                       |
| `nggallery/gallery-caption.php`                                    | Create                                                                 |
| `nggallery/album-extend.php`                                       | Create                                                                 |
| `nggallery/imagebrowser.php`, `nggallery/imagebrowser-caption.php` | Create                                                                 |
| `nggallery/gallery-carousel.php`                                   | Create                                                                 |
| `nggallery/Widget/Display/Gallery.php`                             | Create: overrides NextGEN's widget template (no widget setting needed) |
| `tests/integration/Nggallery*Test.php`                             | Create: one test class per task below                                  |

---

### Task 1: Share the test helper and extract the gallery grid partial

Pure refactor: behaviour and the first plan's tests do not change.

**Files:**

- Create: `tests/integration/Concerns/NggalleryTemplateHelpers.php`, `template-parts/nextgen/gallery-grid.php`
- Modify: `tests/integration/NggalleryTemplatesTest.php`, `nggallery/gallery.php`

- [x] **Step 1: Move `render()` (and the stub `require_once`) from `NggalleryTemplatesTest` into a trait** `HyperPress\ThemeTests\Integration\Concerns\NggalleryTemplateHelpers`; the test class `use`s it. Run `docker compose run --rm php composer test:integration -- --filter NggalleryTemplatesTest`. Expected: PASS, unchanged.
- [x] **Step 2: Extract the grid.** Move everything inside `gallery.php`'s wrapper (slideshow link, callout or grid, hidden images, pagination) into `template-parts/nextgen/gallery-grid.php`. The partial reads `$gallery`, `$images`, `$pagination` and an optional `$captions` (default false). `gallery.php` keeps its docblock, the ABSPATH guard and the `ngg-galleryoverview` wrapper, sets `$captions = false;` and does `require __DIR__ . '/../template-parts/nextgen/gallery-grid.php';` (a path relative to the file works in tests and in production regardless of the active theme).
- [x] **Step 3: Run the tests and lint.** `--filter NggalleryTemplatesTest` PASS; `composer lint` 0 violations.
- [x] **Step 4: Tick this task's boxes and commit** (`refactor(theme): share the nextgen gallery grid partial`).

### Task 2: `album-extend.php` (media object)

**Files:** Create `nggallery/album-extend.php`, `tests/integration/NggalleryAlbumExtendTest.php`.

Stock behaviour (4.5.5, 69 lines): `$galleries` with `pagelink`, `title`, `previewurl`, `galdesc`, `counter`; `$pagination`; no JS.

- [x] **Step 1: Write the failing tests.** Cover: one `div.media-object` per gallery (`media-object-section` with the `<a>` + `<img>` thumbnail, a second section with the title link `h4`, the description through `wp_kses_post` and the photo count as a `label primary`); a stack on small screens (`stack-for-small`); `alt` and `loading="lazy"` on the thumbnail; escaping of the title; a gallery without `previewurl` renders no `<img>`; the pagination goes through `hyperpress_nextgen_pagination()`; nothing renders for empty `$galleries`.
- [x] **Step 2: Run, confirm FAIL** (`--filter NggalleryAlbumExtendTest`; the file does not exist yet).
- [x] **Step 3: Write `nggallery/album-extend.php`** from the stock template, replacing the `ngg-album*` markup with the media-object markup above. Keep `Router::esc_url()` for NextGEN URLs and the `nggallery` text domain with its `phpcs:ignore`, as in the first plan's `album-compact.php`.
- [x] **Step 4: Run tests and lint; tick and commit** (`feat(theme): nextgen extended album on foundation media objects`).

### Task 3: `gallery-caption.php`

**Files:** Create `nggallery/gallery-caption.php`, `tests/integration/NggalleryGalleryCaptionTest.php`; modify `template-parts/nextgen/gallery-grid.php`.

Stock behaviour (4.5.5, 106 lines): the gallery template plus a caption under each thumbnail (`$image->caption`, NextGEN-built); the wrapper is `ngg-galleryoverview ngg-template-caption` with the anchor id.

- [x] **Step 1: Write the failing tests** (use `$captions = true` through the real file): each visible image is a `figure` with `a.thumbnail > img` and a `figcaption` carrying the caption (sanitised with `wp_kses_post`); no `figcaption` for plain `gallery.php`; the first plan's wrapper, columns, hidden-image and pagination behaviour still hold; `gallery.php` output is unchanged (no `figure`).
- [x] **Step 2: Run, confirm FAIL.**
- [x] **Step 3: Extend the partial.** When `$captions` is true wrap each cell's content in `<figure>` and add `<figcaption class="text-center">`; otherwise keep the plain markup. Create `nggallery/gallery-caption.php`: docblock, ABSPATH guard, the `ngg-galleryoverview` wrapper with the anchor id, `$captions = true;`, `require` of the partial.
- [x] **Step 4: Run both test classes and lint; tick and commit** (`feat(theme): nextgen gallery with captions on the foundation grid`).

### Task 4: `imagebrowser.php` and `imagebrowser-caption.php`

**Files:** Create `template-parts/nextgen/imagebrowser.php`, `nggallery/imagebrowser.php`, `nggallery/imagebrowser-caption.php`, `tests/integration/NggalleryImageBrowserTest.php`.

Stock behaviour (4.5.5, 67 lines each; the two files are described as identical, so diff them first and add a flag to the partial only if they differ): `$image` (`anchor`, `previous_pid`, `previous_image_link`, `next_pid`, `next_image_link`, `number`, `total`, `alttext`, `href_link` = NextGEN-built link around the image, `description`) and `$displayed_gallery->id()`.

- [ ] **Step 1: Write the failing tests.** Cover: the wrapper keeps `class="ngg-imagebrowser"`, `id="ngg-imagebrowser-<anchor>"` and `data-nextgen-gallery-id` (so the AJAX script keeps working); the title as `h3`; the image from `$image->href_link` inside a centred container with `thumbnail` added to the first `<a>`; previous and next as a `button-group` of `a.button.hollow` that keep `class` `ngg-browser-prev` / `ngg-browser-next`, their `id`s (`ngg-prev-<pid>`, `ngg-next-<pid>`) and `href`s; a missing previous or next link renders a `disabled` button without the NextGEN class; the counter ("Image 3 of 12" with translatable text); the description in a `callout`; escaping of `alttext`.
- [ ] **Step 2: Run, confirm FAIL.**
- [ ] **Step 3: Write the partial and the two files.** To add `thumbnail` to the link NextGEN built, use `WP_HTML_Tag_Processor` (core since WP 6.2, theme needs 6.9): find the first `a` tag and `add_class( 'thumbnail' )`; print the result through `wp_kses_post()`.
- [ ] **Step 4: Run tests and lint; tick and commit** (`feat(theme): nextgen image browser on foundation components`).

### Task 5: `gallery-carousel.php` (decision gate, then Orbit or fallback)

**Files:** Create `nggallery/gallery-carousel.php`, `tests/integration/NggalleryCarouselTest.php`.

Stock behaviour (4.5.5, 99 lines): `$current` (`pid`, `alttext`, `url`), `$images` (each with `hidden`, `pid`, `pidlink`, `description`, `alttext`, `thumbnailURL`, `size`), `$prev` and `$next` page links. The carousel is a `<ul class="ngg-gallery-list">` of linked thumbnails with the current one `selected`; navigation is plain page loads, no NextGEN JS.

- [ ] **Step 1: Gate (read-only).** Read `src/DisplayTypes/Thumbnails.php` around lines 209-243 and the legacy image class. Record in this plan's outcome notes: (a) does `$images` hold the whole gallery, or only a window around `$current`; (b) does each `$image->imageURL` (full size) resolve for the template (`LegacyImage::__get( 'imageURL' )`); (c) what `$prev` and `$next` link to.
- [ ] **Step 2: Decide.**
  - If `$images` is the whole gallery (or the whole page of it) and `imageURL` resolves, use **Orbit**: `div.orbit[data-orbit]` with one `li.orbit-slide` per visible image (`orbit-image` with the full-size `imageURL`, `alt`, caption in `orbit-caption`), the current image's slide `is-active`, previous and next controls, and `orbit-bullets` as the thumbnails (`thumbnailURL` + `size`). Lazy-load every slide except the active one.
  - Otherwise use the **fallback**, which keeps NextGEN's page-load navigation: the current image in an `a.thumbnail`, a `button-group` for `$prev` and `$next`, and the thumbnails as a `grid-x small-up-4 medium-up-6` strip with the current one marked.
  - If neither fits, stop and ask the owner; do not guess.
- [ ] **Step 3: Write the failing tests for the chosen design** (hidden images skipped; the current image marked; `alt` and escaping; missing `$prev` or `$next` handled; no `ngg-*` markup beyond what the stock template needed).
- [ ] **Step 4: Run, confirm FAIL; write the template; run tests and lint; tick and commit** (`feat(theme): nextgen carousel on foundation`).

### Task 6: Random-image widget template

**Files:** Create `nggallery/Widget/Display/Gallery.php` (mixed-case folders, as NextGEN names its own template), `tests/integration/NggalleryWidgetTemplateTest.php`; extend `tests/integration/stubs/nextgen.php`.

What is known (NextGEN 4.5.5, verified in the source and on production on 2026-11-06). The widget form (`templates/Widget/Form/Gallery.php`) has no Template field. `src/Widget/Gallery.php:240` uses `$instance['template']` or, by default, `'Widget/Display/Gallery'` and passes it as the `template` display setting of a Thumbnails display (`display_type` `NGG_BASIC_THUMBNAILS`). The Thumbnails controller sends every non-default template through `legacy_render()`, and `LegacyTemplateLocator::find()` appends `.php` and searches, in order, `<child theme>/nggallery/`, `<parent theme>/nggallery/`, NextGEN's legacy `view/`, `wp-content/ngg/legacy/templates/` and NextGEN's `templates/` (added by the widget constructor, `Gallery.php:36-43`). So `<theme>/nggallery/Widget/Display/Gallery.php` replaces the widget's markup with no setting (`LegacyTemplateLocator.php:267-281`). NextGEN's own version (`templates/Widget/Display/Gallery.php`) is the reference for the variables:

- `$gallery` (with `->displayed_gallery`) and `$images` (legacy image objects). The widget's settings come from `$gallery->displayed_gallery->get_entity()->display_settings`: `widget_setting_before_widget`, `widget_setting_before_title`, `widget_setting_title`, `widget_setting_after_title`, `widget_setting_after_widget` (each printed through `wp_kses_post`), `image_type`, `image_width`, `image_height`.
- Per image: the link goes to `\Imagely\NGG\DataStorage\Manager::get_instance()->get_image_url( $image, 'full', true )`, `title` is the description, `data-image-id` the `pid`, optional `data-tiktok-play-url` / `data-tiktok-share-url` / `data-tiktok-embed-url` / `data-video-url` from `$image->meta_data`, then the lightbox attributes from `( new \Imagely\NGG\DisplayType\Controller() )->get_effect_code( $gallery->displayed_gallery )`; the `<img>` gets `\Imagely\NGG\Display\GalleryImage::attributes()`, a plain-text `alt`, `src` from `get_cache_busted_image_url( $image, $settings['image_type'] )`, and `width` / `height` from the widget's size setting.
- Live markup today (production, 2026-11-06): `section#ngg-images-2.widget.ngg_images > h4 + div.ngg-widget.entry-content > a.ngg-simplelightbox > img`. NextGEN also enqueues `static/Widget/display.css` for it. The widget form on the site is "Random Photos", 8 thumbnails, maximum size 150×150, hidden on the Photos pages and their children by the visibility rule.

**Font Awesome (found on production, 2026-11-06).** NextGEN loads its own Font Awesome stylesheet (`static/FontAwesome/css/ngg-icons.min.css`, enqueued by `DisplayManager::enqueue_fontawesome()`) on pages that render a full gallery display or album. Measured on 2026-11-06 with `disable_fontawesome` false: the Photos pages load it, while the pages that only carry the widget (home, About, Events) do not, and the sidebar social icons measure normally there. Where it does load next to the social sidebar it breaks the icons (`hyperpress-social`'s `DisplaySocialWidget`, which builds `fa-stack` icons). The theme no longer enqueues Font Awesome's stylesheet at all (it used to, with a mistaken `true` as the `$media` argument in `library/enqueue-scripts.php`, giving `media="1"`, which no browser applies, so it was downloaded but never used) and lets Font Awesome's script draw the icons. NextGEN's stylesheet is a normal, active one, and its `.fa-stack-2x { font-size: 2em }` doubles the stack background (160×128px instead of 80×64px), so the squares merge into one blue blob. Measured in the browser: disabling only `ngg-icons.min.css` fixes it. Fix applied on production: NextGEN's setting `disable_fontawesome` is `true` (set with `\Imagely\NGG\Settings\Settings::get_instance()->set( 'disable_fontawesome', true )` then `save()`; the admin UI does not show it on Miscellaneous, Advanced or Image Options), after which NextGEN no longer loads its icon CSS, the sidebar measures 80×64px and the lightbox still works (its buttons are text glyphs, not icon-font glyphs). The owner added the widget back on 2026-11-06 and the social icons stayed correct; keeping `disable_fontawesome` `true` is still the safe default, because a gallery page that shows the social sidebar would bring the doubling back. If the setting ever has to stay off, remove the two icon styles (`nextgen_gallery_icons`, `nextgen_gallery_icons_scope`) in `hyperpress-media` instead. Also relevant: the Miscellaneous settings page has a "Random widget cache duration" (30 on production).

- [x] **Step 1: Confirm (read-only).** Read `static/Widget/display.css` and `static/Thumbnails/nextgen_basic_thumbnails.css` for rules that touch `.ngg-widget` or `.ngg_images` (floats, margins, image sizes) that the new markup must avoid or override, and check that the lightbox still binds to a link that carries NextGEN's `effect_code` (class `ngg-simplelightbox`, `data-image-id`, `rel`) when it sits inside a grid cell. Note in the outcome notes whether anything needs a rule in `components/_nggallery.scss`.
- [x] **Step 2: Write the failing tests** (extend the stub file with `\Imagely\NGG\DataStorage\Manager` and `\Imagely\NGG\DisplayType\Controller` stubs, guarded with `class_exists`; build `$gallery->displayed_gallery->get_entity()->display_settings` in the test). Expect: the before/after widget wrappers and the title are printed through `wp_kses_post`; the images sit in `grid-x grid-margin-x small-up-2` as `div.cell > a.thumbnail`, with the link class merged the way `gallery.php` does it (`WP_HTML_Tag_Processor`, one `class` attribute); `data-image-id`, the effect code, and `data-video-url` / `data-tiktok-*` when present in `meta_data`; `<img>` with `GalleryImage::attributes( loading=lazy )`, a plain-text `alt`, `src` from the cache-busted URL, and `width` / `height` from the widget's size; no per-image `id` (random images can repeat across widgets); escaping of descriptions and alt text; and nothing at all (no wrapper, no title) when `$images` is empty. Keep the columns easy to tune (a single `small-up-2`, with a comment on where to change it).
- [x] **Step 3: Run, confirm FAIL; write the template**, reusing the image markup of `template-parts/nextgen/gallery-grid.php` or a small shared image-cell partial if that avoids duplication; run the tests and lint; then tick and commit (`feat(theme): compact foundation template for the nextgen image widget`). After deploying, check the widget on the homepage and on a page with the social sidebar, and open an image from the widget in the lightbox.

### Task 7: Verification and notes

- [ ] **Step 1:** `docker compose run --rm php composer lint`, `composer test:unit`, `composer test:integration` from `themes/hyperpress-foundation/`: lint 0 violations; suites pass.
- [ ] **Step 2:** `pnpm exec oxfmt --check` on this plan and any new Markdown.
- [ ] **Step 3: Owner visual check (manual).** Set the Template of each display and of the widget to the parent-theme file. Check at 400px, 800px and 1200px: album-extend stacks on small screens; captions line up under the thumbnails; the image browser moves between images in place (the AJAX path) and its buttons work; the carousel behaves as chosen in Task 5; the widget looks right in a real sidebar with random images; no NextGEN float, border or background shows.
- [ ] **Step 4:** Fill in "Outcome / execution notes", add the COMPLETED banner at the top, and update `docs/superpowers/plans/2026-11-05-theme-nggallery-foundation-templates.md` (follow-ups) in the same commit.

## Known follow-ups

- [ ] `singlepic.php` and `imagebrowser-exif.php` stay un-overridden; add them only if the sites use them.
- [ ] Responsive image files (`srcset`), as listed in the first plan.
- [ ] Modern (non-legacy) NextGEN templates stay out of scope.

## Outcome / execution notes

(Filled in on completion.)

**Task 6 (widget template), done in the working tree, not committed; Tasks 1-5 are still open.** Step 1: `static/Widget/display.css` styles only `.ngg-widget` (overflow hidden, padding) and `.ngg-widget img` (border, margin, padding, `float: left`); `nextgen_basic_thumbnails.css` has no rule for `.ngg-widget` or `.ngg_images`, and no NextGEN script touches either class. The new template drops the `ngg-widget` class, so none of those rules apply and nothing had to be neutralised. The lightbox (`nextgen_simple_lightbox_init.js`) binds to every `.ngg-simplelightbox` link wherever it sits, so a link inside a grid cell keeps working. One Sass rule was needed, in `components/_nggallery.scss`: `.ngg_images a.thumbnail` is `display: block` and its `img` is `width: 100%; height: auto`, because the widget's `width`/`height` attributes are only maximum sizes (checked in `dist/assets/css/app.css` after `pnpm dev`). `nggallery/Widget/Display/Gallery.php` is self-contained (it does not use the shared grid partial of Task 1). It also keeps the `title` attribute on the `<img>` that NextGEN's own template prints. Tests: `NggalleryWidgetTemplateTest` has 14 tests, 50 assertions; `composer lint` is clean. Found after deploying (2026-11-06): the first version used `small-up-2` only, and in the narrow sidebar the cells stacked one per row on screens from the medium breakpoint up. Foundation computes a cell's width per breakpoint only for breakpoints that carry a `-up-N` class, and the gutter grows from 20px to 30px at medium, so the width (still using the 20px gutter) plus the larger margins no longer fitted twice into the row. The grid is now `small-up-2 medium-up-2`, verified on the live page (two cells per row, 166px wide). `gallery.php` is not affected because it always lists the small, medium and large classes.
