# Front page hero fallback

> **STATUS: ✅ COMPLETED** (2026-10-09). Lint clean, unit 38 and integration 229 tests passing, `pnpm build` exits 0.

When Slider Revolution is not available (`function_exists( 'add_revslider' )` is false) and the static
front page has a featured image, `front-page.php` renders a full-width hero box in the slider's spot
(before `template-parts/banner`). The slider path and the no-image path are unchanged and output nothing extra.

Design: `<div class="hyperpress-front-hero" data-interchange="...">` using Foundation Interchange with four
new hard-cropped image sizes, plus an inline `background-image` (large size) as the no-JS fallback.

## Task 1: Image sizes
- [x] Add `front-hero-small` 640x300, `front-hero-medium` 1280x400, `front-hero-large` 1440x600, `front-hero-xlarge` 1920x600 (hard crop) to `library/responsive-images.php`

## Task 2: Integration test (write first, see it fail)
- [x] Add `tests/integration/FrontPageHeroTest.php` covering: hero rendered with four size URLs in `data-interchange` when no `add_revslider` and the front page has a featured image
- [x] Cover: no featured image => hero not rendered
- [x] Cover: `add_revslider` defined => hero not rendered (separate-process test with a stub; skip and note if infeasible)

## Task 3: Template markup
- [x] Render the hero in `front-page.php` when `add_revslider` is missing and `get_queried_object_id()` has a featured image
- [x] Task 2 tests pass

## Task 4: SCSS
- [x] Add `src/assets/scss/components/_front-hero.scss` (width 100%, cover, center, no-repeat; 300px small, 400px medium, 600px large and up) and import it in `app.scss` next to the featured-image import

## Task 5: Verification
- [x] `docker compose run --rm php composer lint` reports 0 violations
- [x] `docker compose run --rm php composer test:unit` passes
- [x] `docker compose run --rm php composer test:integration` passes
- [x] `pnpm build` compiles the SCSS (after `source ~/.nvm/nvm.sh && nvm use`)
  - Note: the first attempt aborted with ERR_PNPM_BAD_RUNTIME_VERSION (pnpm 12.9.1 vs Node 26.10.0). After upgrading to Node v26.11.1 and pnpm 12.11.2, `pnpm build` exits 0 (sass and webpack compile). Earlier, `gulp build --production` directly emitted `.hyperpress-front-hero` at 18.75rem, 25rem and 37.5rem.
