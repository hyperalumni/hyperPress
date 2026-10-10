# Front page hero fallback

> **STATUS: ✅ COMPLETED** (2026-10-09). Lint clean, unit 38 and integration 230 tests passing, `pnpm build` exits 0.

When Slider Revolution is not available (`function_exists( 'add_revslider' )` is false) and the static
front page has a featured image, `front-page.php` renders a full-width hero box in the slider's spot
(before `template-parts/banner`). The slider path and the no-image path are unchanged and output nothing extra.

Design: `<div class="hyperpress-front-hero" style="--hyperpress-hero-small:url('...');...">` with four
new hard-cropped image sizes passed as inline CSS custom properties (`--hyperpress-hero-{small,medium,large,xlarge}`).
The SCSS picks the matching variable per breakpoint (`background-image: var(...)`), so it needs no JavaScript and
the small size is the base rule when nothing else applies. (Originally this used Foundation Interchange; see Outcome.)

## Task 1: Image sizes
- [x] Add `front-hero-small` 640x300, `front-hero-medium` 1280x400, `front-hero-large` 1440x600, `front-hero-xlarge` 1920x900 (hard crop; originally 1920x600, raised to match the Slider Revolution slider height) to `library/responsive-images.php`

## Task 2: Integration test (write first, see it fail)
- [x] Add `tests/integration/FrontPageHeroTest.php` covering: hero rendered with four size URLs in inline custom properties (originally `data-interchange`) when no `add_revslider` and the front page has a featured image
- [x] Cover: no featured image => hero not rendered
- [x] Cover: `add_revslider` defined => hero not rendered (separate-process test with a stub; skip and note if infeasible)

## Task 3: Template markup
- [x] Render the hero in `front-page.php` when `add_revslider` is missing and `get_queried_object_id()` has a featured image
- [x] Task 2 tests pass

## Task 4: SCSS
- [x] Add `src/assets/scss/components/_front-hero.scss` (width 100%, cover, center, no-repeat; 300px small, 400px medium, 600px large, 900px xlarge (1200px and up); `background-image` from the custom properties) and import it in `app.scss` next to the featured-image import

## Task 5: Verification
- [x] `docker compose run --rm php composer lint` reports 0 violations
- [x] `docker compose run --rm php composer test:unit` passes
- [x] `docker compose run --rm php composer test:integration` passes
- [x] `pnpm build` compiles the SCSS (after `source ~/.nvm/nvm.sh && nvm use`)
  - Note: the first attempt aborted with ERR_PNPM_BAD_RUNTIME_VERSION (pnpm 12.9.1 vs Node 26.10.0). After upgrading to Node v26.11.1 and pnpm 12.11.2, `pnpm build` exits 0 (sass and webpack compile). Earlier, `gulp build --production` directly emitted `.hyperpress-front-hero` at 18.75rem, 25rem and 37.5rem.

## Outcome: Interchange replaced by CSS custom properties
The first version (d6d3c84) used Foundation Interchange (`data-interchange`). That breaks with AVIF/WebP featured
images: Interchange only treats URLs ending in `gif|jpe?g|png|svg|tiff` as images (`foundation.interchange.js`);
any other extension is fetched as an HTML template and the raw image bytes are injected into the div as text.
The hero now outputs the four size URLs as inline custom properties and `_front-hero.scss` switches
`background-image` with `var(--hyperpress-hero-*)` at the medium (640), large (1024) and xlarge (1200) breakpoints.
The inline no-JS `background-image` was dropped; the base SCSS rule (small size) replaces it. The style attribute is
built first, then passed through `esc_attr`, so the quotes in `url('...')` are emitted as `&#039;`, which browsers decode.
`FrontPageHeroTest` now asserts the four custom properties (decoding entities), that `data-interchange` is absent,
and that `.avif` URLs stay intact. Verified: lint 0 violations, unit 38 and integration 230 tests (10 skipped) passing,
`pnpm build` exits 0, and the rendered local front page shows the four variables with `.avif` URLs.

## Outcome: xlarge step is 900px tall
The largest step was changed after review to match the Slider Revolution slider: `front-hero-xlarge` is now 1920x900
(hard crop) and `.hyperpress-front-hero` is 900px tall (`rem-calc(900)`, 56.25rem) at the xlarge breakpoint (1200px and up).
Small, medium and large stay 300, 400 and 600px. WordPress only generates a new size for uploads made after it is
registered, so an existing featured image must be regenerated once (for example `wp media regenerate` or a regenerate
thumbnails plugin). Until then the old 1920x600 file is served and is stretched to fill the 900px box.
