# Front page hero fallback

When Slider Revolution is not available (`function_exists( 'add_revslider' )` is false) and the static
front page has a featured image, `front-page.php` renders a full-width hero box in the slider's spot
(before `template-parts/banner`). The slider path and the no-image path are unchanged and output nothing extra.

Design: `<div class="hyperpress-front-hero" data-interchange="...">` using Foundation Interchange with four
new hard-cropped image sizes, plus an inline `background-image` (large size) as the no-JS fallback.

## Task 1: Image sizes
- [ ] Add `front-hero-small` 640x300, `front-hero-medium` 1280x400, `front-hero-large` 1440x600, `front-hero-xlarge` 1920x600 (hard crop) to `library/responsive-images.php`

## Task 2: Integration test (write first, see it fail)
- [ ] Add `tests/integration/FrontPageHeroTest.php` covering: hero rendered with four size URLs in `data-interchange` when no `add_revslider` and the front page has a featured image
- [ ] Cover: no featured image => hero not rendered
- [ ] Cover: `add_revslider` defined => hero not rendered (separate-process test with a stub; skip and note if infeasible)

## Task 3: Template markup
- [ ] Render the hero in `front-page.php` when `add_revslider` is missing and `get_queried_object_id()` has a featured image
- [ ] Task 2 tests pass

## Task 4: SCSS
- [ ] Add `src/assets/scss/components/_front-hero.scss` (width 100%, cover, center, no-repeat; 300px small, 400px medium, 600px large and up) and import it in `app.scss` next to the featured-image import

## Task 5: Verification
- [ ] `docker compose run --rm php composer lint` reports 0 violations
- [ ] `docker compose run --rm php composer test:unit` passes
- [ ] `docker compose run --rm php composer test:integration` passes
- [ ] `pnpm build` compiles the SCSS (after `source ~/.nvm/nvm.sh && nvm use`)
