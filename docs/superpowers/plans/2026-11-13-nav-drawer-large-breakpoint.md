# Foundation theme: nav drawer up to the large breakpoint

**Goal:** the Foundation theme switches between the desktop menu and the mobile drawer at `large` (1024px) instead of `medium` (640px). Every width below 1024px uses the drawer. The owner approved the change.

**Scope:** only rules tied to the menu switch. `$table-stack-breakpoint` and every other `medium` use (grids, heroes, cards, content layout) stay as they are.

## Task 1: Switch the breakpoints

Files (all under `themes/hyperpress-foundation/`):

- `src/assets/scss/modules/_navigation.scss`: `.mobile-menu` / `.site-title-bar` use `hide-for(large)`, `.site-navigation` uses `breakpoint(large down)`, `.desktop-menu` / `.site-navigation .top-bar-left` use `show-for(large)`.
- `src/assets/scss/_settings.scss`: `$topbar-unstack-breakpoint: large;`.
- `library/foundation.php`: `hyperpress_title_bar_responsive_toggle()` also prints `data-hide-for="large"`, so Foundation's ResponsiveToggle (which assumes `medium`) keeps the hamburger visible up to 1023px.
- `src/assets/scss/modules/_mobile-drilldown.scss`: the edge-to-edge rows rule (`breakpoint(small only)`) becomes `breakpoint(medium down)`, because the drawer now shows on tablets too.

- [ ] Edit the five files above
- [ ] Check the remaining header/nav rules (`_wp-admin.scss`, the `offcanvas` layout in `hyperpress_mobile_menu_layout()`) for anything else tied to the switch; change only what is tied to it, report the rest

## Task 2: Test

- [ ] `tests/integration/HeaderMarkupTest.php` asserts `data-hide-for="large"` next to `data-responsive-toggle="mobile-menu"` (every layout that is tested)

## Task 3: Verify

- [ ] Build the theme (`nx build @hyper/press-foundation`) and find the 1024px (`64em`) media queries for the menu in `dist`
- [ ] `lint-js`, `lint-css` (where the targets exist) and `pnpm fmt:check` on the changed files
- [ ] Theme integration tests (`composer test:integration`) and `composer lint` in Docker
- [ ] Add an Outcome section to this plan

## Known follow-ups

- Browser checks at 700, 900, 1000 and 1024px, with the admin bar on and off (drawer opens, hamburger visible below 1024px, desktop menu from 1024px, no overlap with the admin bar, drilldown rows edge to edge on tablet widths), are manual and still to be done by the owner.
- The code changes are not committed yet; the owner commits them when satisfied.
