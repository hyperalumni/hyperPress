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

| File                                                               | Change                                                       |
| ------------------------------------------------------------------ | ------------------------------------------------------------ |
| `tests/integration/Concerns/NggalleryTemplateHelpers.php`          | Create: shared `render()` helper moved out of the first test |
| `tests/integration/NggalleryTemplatesTest.php`                     | Modify: use the trait                                        |
| `template-parts/nextgen/gallery-grid.php`                          | Create: grid partial extracted from `gallery.php`            |
| `template-parts/nextgen/imagebrowser.php`                          | Create: image browser partial                                |
| `nggallery/gallery.php`                                            | Modify: include the grid partial                             |
| `nggallery/gallery-caption.php`                                    | Create                                                       |
| `nggallery/album-extend.php`                                       | Create                                                       |
| `nggallery/imagebrowser.php`, `nggallery/imagebrowser-caption.php` | Create                                                       |
| `nggallery/gallery-carousel.php`                                   | Create                                                       |
| `nggallery/gallery-widget.php`                                     | Create (widget; shape confirmed in Task 6)                   |
| `tests/integration/Nggallery*Test.php`                             | Create: one test class per task below                        |

---

### Task 1: Share the test helper and extract the gallery grid partial

Pure refactor: behaviour and the first plan's tests do not change.

**Files:**

- Create: `tests/integration/Concerns/NggalleryTemplateHelpers.php`, `template-parts/nextgen/gallery-grid.php`
- Modify: `tests/integration/NggalleryTemplatesTest.php`, `nggallery/gallery.php`

- [ ] **Step 1: Move `render()` (and the stub `require_once`) from `NggalleryTemplatesTest` into a trait** `HyperPress\ThemeTests\Integration\Concerns\NggalleryTemplateHelpers`; the test class `use`s it. Run `docker compose run --rm php composer test:integration -- --filter NggalleryTemplatesTest`. Expected: PASS, unchanged.
- [ ] **Step 2: Extract the grid.** Move everything inside `gallery.php`'s wrapper (slideshow link, callout or grid, hidden images, pagination) into `template-parts/nextgen/gallery-grid.php`. The partial reads `$gallery`, `$images`, `$pagination` and an optional `$captions` (default false). `gallery.php` keeps its docblock, the ABSPATH guard and the `ngg-galleryoverview` wrapper, sets `$captions = false;` and does `require __DIR__ . '/../template-parts/nextgen/gallery-grid.php';` (a path relative to the file works in tests and in production regardless of the active theme).
- [ ] **Step 3: Run the tests and lint.** `--filter NggalleryTemplatesTest` PASS; `composer lint` 0 violations.
- [ ] **Step 4: Tick this task's boxes and commit** (`refactor(theme): share the nextgen gallery grid partial`).

### Task 2: `album-extend.php` (media object)

**Files:** Create `nggallery/album-extend.php`, `tests/integration/NggalleryAlbumExtendTest.php`.

Stock behaviour (4.5.5, 69 lines): `$galleries` with `pagelink`, `title`, `previewurl`, `galdesc`, `counter`; `$pagination`; no JS.

- [ ] **Step 1: Write the failing tests.** Cover: one `div.media-object` per gallery (`media-object-section` with the `<a>` + `<img>` thumbnail, a second section with the title link `h4`, the description through `wp_kses_post` and the photo count as a `label primary`); a stack on small screens (`stack-for-small`); `alt` and `loading="lazy"` on the thumbnail; escaping of the title; a gallery without `previewurl` renders no `<img>`; the pagination goes through `hyperpress_nextgen_pagination()`; nothing renders for empty `$galleries`.
- [ ] **Step 2: Run, confirm FAIL** (`--filter NggalleryAlbumExtendTest`; the file does not exist yet).
- [ ] **Step 3: Write `nggallery/album-extend.php`** from the stock template, replacing the `ngg-album*` markup with the media-object markup above. Keep `Router::esc_url()` for NextGEN URLs and the `nggallery` text domain with its `phpcs:ignore`, as in the first plan's `album-compact.php`.
- [ ] **Step 4: Run tests and lint; tick and commit** (`feat(theme): nextgen extended album on foundation media objects`).

### Task 3: `gallery-caption.php`

**Files:** Create `nggallery/gallery-caption.php`, `tests/integration/NggalleryGalleryCaptionTest.php`; modify `template-parts/nextgen/gallery-grid.php`.

Stock behaviour (4.5.5, 106 lines): the gallery template plus a caption under each thumbnail (`$image->caption`, NextGEN-built); the wrapper is `ngg-galleryoverview ngg-template-caption` with the anchor id.

- [ ] **Step 1: Write the failing tests** (use `$captions = true` through the real file): each visible image is a `figure` with `a.thumbnail > img` and a `figcaption` carrying the caption (sanitised with `wp_kses_post`); no `figcaption` for plain `gallery.php`; the first plan's wrapper, columns, hidden-image and pagination behaviour still hold; `gallery.php` output is unchanged (no `figure`).
- [ ] **Step 2: Run, confirm FAIL.**
- [ ] **Step 3: Extend the partial.** When `$captions` is true wrap each cell's content in `<figure>` and add `<figcaption class="text-center">`; otherwise keep the plain markup. Create `nggallery/gallery-caption.php`: docblock, ABSPATH guard, the `ngg-galleryoverview` wrapper with the anchor id, `$captions = true;`, `require` of the partial.
- [ ] **Step 4: Run both test classes and lint; tick and commit** (`feat(theme): nextgen gallery with captions on the foundation grid`).

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

**Files:** Create `nggallery/gallery-widget.php` (name confirmed in Step 1), `tests/integration/NggalleryWidgetTemplateTest.php`.

What is known (4.5.5, `src/Widget/Gallery.php`): the widget (settings: `type` recent or random, `items`, `width`, `height`, `show` thumbnail or original, `exclude`, `list`) renders through NextGEN's own view (`Widget/Display/Gallery`, with `static/Widget/display.css` and the thumbnails CSS) unless the widget's "Template" setting selects a legacy template, in which case that template renders. So with the owner's setup (Template = parent theme) the widget would use whichever `nggallery/*.php` they pick, and our `gallery.php` would show a 2/3/4-column grid in a narrow sidebar.

**Font Awesome (found on production, 2026-11-06).** The widget makes NextGEN load its own Font Awesome stylesheet (`static/FontAwesome/css/ngg-icons.min.css`, enqueued by `DisplayManager::enqueue_fontawesome()`) on every page it appears on. It breaks the sidebar social icons (`hyperpress-social`'s `DisplaySocialWidget`, which builds `fa-stack` icons). The theme no longer enqueues Font Awesome's stylesheet at all (it used to, with a mistaken `true` as the `$media` argument in `library/enqueue-scripts.php`, giving `media="1"`, which no browser applies, so it was downloaded but never used) and lets Font Awesome's script draw the icons. NextGEN's stylesheet is a normal, active one, and its `.fa-stack-2x { font-size: 2em }` doubles the stack background (160×128px instead of 80×64px), so the squares merge into one blue blob. Measured in the browser: disabling only `ngg-icons.min.css` fixes it. Fix applied on production: NextGEN's setting `disable_fontawesome` is `true` (set with `\Imagely\NGG\Settings\Settings::get_instance()->set( 'disable_fontawesome', true )` then `save()`; the admin UI does not show it on Miscellaneous, Advanced or Image Options), after which NextGEN no longer loads its icon CSS, the sidebar measures 80×64px and the lightbox still works (its buttons are text glyphs, not icon-font glyphs). So before bringing the widget back confirm the setting is still `true`; after adding it, check a page without a gallery for the social icons. If the setting ever has to stay off, remove the two icon styles (`nextgen_gallery_icons`, `nextgen_gallery_icons_scope`) in `hyperpress-media` instead. Also relevant: the Miscellaneous settings page has a "Random widget cache duration" (30 on production).

- [ ] **Step 1: Confirm (read-only).** In `src/Widget/Gallery.php` (around lines 137-155 for the form, 227-260 for rendering) and `LegacyTemplateLocator`: (a) does the widget's Template dropdown list any `nggallery/*.php` filename, or only known names; (b) which variables reach the template from the widget (`$gallery->columns`, `$images`, `$pagination`, anything widget-specific such as `$gallery->anchor`); (c) whether `ngg-image-<pid>` ids can repeat across widgets and the page (random images). Record the answers in the outcome notes. If the dropdown lists only known names, stop and ask the owner which of those names to use (for example a dedicated widget display type), and revise this task.
- [ ] **Step 2: Write the failing tests.** A compact grid: `grid-x small-up-2` (one column below a width the owner can tune), each image `cell > a.thumbnail{thumbcode} > img` with `GalleryImage::attributes( loading=lazy )` and `size`; no pagination, no slideshow link, no per-image `id` that could repeat; hidden images skipped (a widget has no lightbox navigation); empty `$images` renders nothing; escaping as in `gallery.php`.
- [ ] **Step 3: Run, confirm FAIL; write the template** (from the grid partial's image markup, or a small shared image-cell partial if that avoids duplication); run tests and lint; tick and commit (`feat(theme): compact foundation template for the nextgen image widget`).

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
