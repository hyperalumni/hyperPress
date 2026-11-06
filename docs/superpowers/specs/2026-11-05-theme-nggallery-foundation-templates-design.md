# NextGEN legacy templates on Foundation 6

Short design (small change). Approved by the owner in conversation on 2026-11-05.

## Intent

The theme overrides two NextGEN Gallery legacy templates in `themes/hyperpress-foundation/nggallery/`. Make both use Foundation 6 markup, escape output properly and drop legacy NextGEN markup. The owner selects them under NextGEN's display setting "Template" (the parent-theme templates); a display left on "default" renders NextGEN's modern templates and never uses these files (`src/DisplayTypes/Thumbnails.php:193-205` in NextGEN 4.5.5).

## Goals

- `gallery.php`: Foundation XY grid that follows the gallery's `columns` setting; images shown with Foundation's `.thumbnail`.
- `album-compact.php`: correct `cell > card > card-section` nesting with equal-height cards from flex utilities only.
- Tidier, escaped, tested templates.

## Non-goals

- The modern templates. The remaining legacy templates and the random-image widget are in `docs/superpowers/plans/2026-11-05-theme-nggallery-remaining-templates.md`.
- Changes to the NextGEN plugin or the `hyperpress-media` plugin.
- Responsive image files (`srcset`); see the plan's follow-ups.

## Constraints and findings

- NextGEN 4.5.5 ships `static/Thumbnails/nextgen_basic_thumbnails.css`: `.ngg-gallery-thumbnail-box` floats with `max-width:100% !important`, `.ngg-gallery-thumbnail` has a border, background and margin, `.ngg-galleryoverview` has `overflow:hidden`. Keeping those class names would fight the grid, so the templates use their own class names.
- `$image->style` is `style="width:N%;"` (`floor(100/columns)`, empty for 0): it must not be echoed. `$image->hidden` is a flag only.
- `$image->size` is `width='W' height='H'`; `$image->thumbcode` carries the lightbox attributes (`data-image-id`, `data-src`, ...) and goes on the anchor.
- NextGEN is not installed in the theme's test environment; tests stub `Imagely\NGG\Util\Router` and `Imagely\NGG\Display\I18N`.
- `.card-image` is a documented Foundation class (an IE 11 flexbox workaround, `min-height: 1px`); IE 11 is not a target, so it is removed.

## Approaches

1. Full Foundation grid following `columns` (chosen).
2. Hybrid: keep NextGEN's float boxes, replace only the `<br>` hack. Rejected: the inline `%` widths fight the grid cells.
3. Override NextGEN's modern templates. Rejected: no documented override path and upgrade risk.

## Design

`gallery.php`

- `columns` is clamped to 0-8. For N > 0 the grid is `grid-x grid-margin-x small-up-min(2,N) medium-up-min(3,N) large-up-N`; for 0 (automatic, including negative or invalid values) it is `small-up-2 medium-up-3 large-up-4 xxlarge-up-5`: only the automatic case adds the fifth column on extra-wide screens (1440px), an explicit count keeps its own classes.
- The wrapper that holds the grid is not clipped: NextGEN gives `.ngg-galleryoverview` `overflow: hidden`, which cuts off the hover shadow of the first and last columns, so `components/_nggallery.scss` sets `overflow: visible` on `div.ngg-galleryoverview:has(> .grid-x)` only.
- Thumbnails fill their cell: NextGEN's `<img>` carries fixed `width`/`height` attributes and Foundation never enlarges images, so `components/_nggallery.scss` sets `.ngg-galleryoverview a.thumbnail { display: block }` and `img { width: 100%; height: auto }` (scoped to the gallery wrapper, so albums and cards are unaffected). NextGEN's `size` attributes stay (no layout shift). Raise the NextGEN thumbnail size for sharpness, since the stored thumbnails are now enlarged.
- Visible image: `div.cell#ngg-image-<pid> > a.thumbnail{thumbcode} > img{GalleryImage::attributes( loading=lazy )}{size}`; the NextGEN 4.5.5 helper `GalleryImage::attributes()` stays so the `ngg_gallery_image_attributes` filter (used for lazy-load exclusions) keeps working. `$image->style`, the `<br>` hack and the counter are gone.
- The visible anchor's class is merged with the `class` attribute of NextGEN's `thumbcode` through `WP_HTML_Tag_Processor` (`add_class( 'thumbnail' )`): a second `class` attribute would be dropped by the browser and break NextGEN's lightbox JS (`.ngg-simplelightbox`).
- Hidden images render only their anchor in a `hidden` element after the grid, so the lightbox still has them and they take no cell.
- The outer wrapper keeps `class="ngg-galleryoverview"` and `id="<anchor>"`, because NextGEN's AJAX pagination script finds and replaces the gallery through them; the grid, cells and thumbnails use no `ngg-*` classes.
- Pagination: `hyperpress_nextgen_pagination()` (in `library/helpers.php`) rebuilds NextGEN's `$pagination` HTML as `nav > ul.pagination.text-center > li`. Anchors keep their NextGEN classes, `data-pageid` and `href`; previous/next get an `aria-label` chosen by page number (NextGEN's own prev/next classes are swapped). Unrecognised markup is returned unchanged. Both templates print it inside the wrapper through `wp_kses_post()`.
- A gallery that has a description (single-gallery displays) shows a `gallery-info callout primary` above the slideshow link and the grid, inside the wrapper: the title as `h5` (when set) and the description through `wp_kses_post()`; no description, no callout.
- Other Foundation components: the slideshow link is a centred `button small hollow`; a gallery with no images and an album with no galleries show a `callout secondary`. Considered and not adopted: `badge` for the photo count (the "12 Photos" label reads better) and `card-divider` titles (keeps the earlier card design).
- Escaping: `esc_attr` for the anchor, pid and description, `esc_html` for the slideshow link text; `thumbcode`, `size` and `pagination` stay raw with `phpcs:ignore` notes (NextGEN-built HTML).

`album-compact.php`

- `grid-x grid-margin-x small-up-1 medium-up-2 large-up-3 xlarge-up-4`, each item `cell.grid-y > card.flex-child-auto > (a > img | placeholder) + card-section.flex-child-auto`.
- Removed: `card-image`, `align-bottom`, `align-stretch`, the `small-12 medium-6 ...` widths.
- `<img>` gets `alt` and `loading="lazy"`; the author lookup is flattened into one block.
- The album header (`callout primary` with the album name and description) shows only when NextGEN's "Display descriptions" setting (`enable_descriptions`, also read through `original_settings` in drill-down views, as NextGEN does) is on. The gallery cards' own descriptions are not gated by the setting, and neither are the grid or the "no galleries" callout.
- When a template prints its own description callout (the album header, the gallery callout) it unhooks NextGEN's prepended plain description through `hyperpress_nextgen_take_over_description()` (`library/helpers.php`), so the description is not shown twice. For sub-album views NextGEN prepends the description directly in `index_action()` (not through the filter), so the helper also registers a one-time, exact-text-match cleanup filter at priority 20 that removes the matching `<p>` in front of the template's callout.
- If equal heights do not hold in the browser, the fallback is Foundation's Equalizer plugin, not custom CSS.

## Verification

Integration render tests for both templates, `composer lint` at 0 violations, the full unit and integration suites, and the owner's visual check on a local site with the Template setting pointed at the parent theme.
