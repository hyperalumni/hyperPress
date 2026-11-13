> **STATUS: ✅ COMPLETED** (2026-11-13). Code in `236196e8`, admin-bar fix after it; drawer checked in a browser at 700, 800, 900 and 1000px, desktop menu at 1024px.

# Foundation theme: nav drawer up to the large breakpoint

**Goal:** the Foundation theme switches between the desktop menu and the mobile drawer at `large` (1024px) instead of `medium` (640px). Every width below 1024px uses the drawer. The owner approved the change.

**Scope:** only rules tied to the menu switch. `$table-stack-breakpoint` and every other `medium` use (grids, heroes, cards, content layout) stay as they are.

## Task 1: Switch the breakpoints

Files (all under `themes/hyperpress-foundation/`):

- `src/assets/scss/modules/_navigation.scss`: `.mobile-menu` / `.site-title-bar` use `hide-for(large)`, `.site-navigation` keeps `breakpoint(medium down)` (Foundation's `down` includes the named range, so it already compiles to `max-width: 63.99875em`; `large down` would stretch the rule to 1199px), `.desktop-menu` / `.site-navigation .top-bar-left` use `show-for(large)`.
- `src/assets/scss/_settings.scss`: `$topbar-unstack-breakpoint: large;`.
- `library/foundation.php`: `hyperpress_title_bar_responsive_toggle()` also prints `data-hide-for="large"`, so Foundation's ResponsiveToggle (which assumes `medium`) keeps the hamburger visible up to 1023px.
- `src/assets/scss/modules/_mobile-drilldown.scss`: the edge-to-edge rows rule (`breakpoint(small only)`) becomes `breakpoint(medium down)`, because the drawer now shows on tablets too.

- [x] Edit the five files above
- [x] Check the remaining header/nav rules (`_wp-admin.scss`, the `offcanvas` layout in `hyperpress_mobile_menu_layout()`) for anything else tied to the switch; change only what is tied to it, report the rest

## Task 2: Test

- [x] `tests/integration/HeaderMarkupTest.php` (topbar and drilldown) and `tests/integration/MobileMenuLayoutTest.php` assert `data-hide-for="large"` next to `data-responsive-toggle="mobile-menu"`

## Task 3: Verify

- [x] Build the theme (`nx build @hyper/press-foundation`) and find the 1024px (`64em`) media queries for the menu in `dist`
- [x] `lint-js` (the theme has no `lint-css` target) and `pnpm fmt:check` on the changed files
- [x] Theme integration tests (`composer test:integration`) and `composer lint` in Docker
- [x] Add an Outcome section to this plan

## Outcome

- Compiled CSS (`dist/assets/css/app-*.css`): `.mobile-menu, .site-title-bar` are hidden from `min-width: 64em`, `.desktop-menu` / `.site-navigation .top-bar-left` are hidden below `max-width: 63.99875em`, and the edge-to-edge drilldown rule applies below `63.99875em`.
- Other rules checked and left alone: the existing `_wp-admin.scss` rules (`breakpoint(783)` is the WP admin bar height switch, not the menu switch; the admin-bar wrap fix added afterwards is in the follow-ups), the `offcanvas` layout (its trigger sits in `.site-title-bar`, now hidden from 1024px by CSS; no ResponsiveToggle attribute is printed for it), `$table-stack-breakpoint`.
- Verification: `nx build @hyper/press-foundation`, `nx run-many -t lint-js`, `oxfmt --check` on the theme and this plan, theme `composer test:integration` (559 tests, 12 skipped) and `composer lint` in Docker.

## Known follow-ups

- Done: the owner checked the drawer at 700, 800, 900 and 1000px and the desktop menu at 1024px in a browser.
- Owner report after the drawer moved to large: between 783px and 1023px the WP admin bar (full width above its own 782px collapse) wraps its right group onto a second row over the header when plugins add items. `src/assets/scss/global/_wp-admin.scss` now hides the admin-bar item labels and the "Howdy" name in that range (`breakpoint(783)` with `breakpoint(medium down)`, compiled `min-width:48.9375em` and `max-width:63.99875em`). Icons alone still wrapped below about 856px, and WordPress's own 782px collapse is hard-coded in core's admin-bar CSS, so a second rule (`min-width: 783px` to `max-width: 856px`, 856px measured on the owner's site with its plugin items) keeps only the WP logo, site name, New, Edit and the account menu. The owner checked it in a browser and the admin bar stays on one row.
