> **STATUS: ✅ COMPLETED** (2026-11-02). Unit 64/64 and integration 347 (12 skipped) pass, `composer lint` clean, theme build OK; menu checked in the browser at 400px on the hyperalumni stack. Awaiting owner's visual review and merge.

# Drilldown Mobile Menu Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add an opt-in `drilldown` mobile menu layout to the hyperpress-foundation theme that matches the old site (logo + grey hamburger bar, coloured rows, slide-over parent-coloured panels with a Back header).

**Architecture:** A new `drilldown` value for the `wpt_mobile_menu_layout` Customizer setting, read through one helper. It reuses the top-bar toggle structure, swaps the accordion attributes in `hyperpress_mobile_nav()` for Foundation Drilldown attributes, and is styled by a new Sass module scoped to `body.mobile-drilldown`. Row and panel colours come from the CSS classes on top-level menu items.

**Tech Stack:** PHP 8.3 (WordPress, PHPUnit integration tests in Docker), Foundation 6.9 (Drilldown plugin; JS and Sass already bundled), Sass, Nx/pnpm.

**Spec:** `docs/superpowers/specs/2026-11-01-theme-drilldown-mobile-menu-design.md`

## Global Constraints

Theme root is `themes/hyperpress-foundation/` (paths below are relative to it unless prefixed `docs/`).

- Layout setting key stays `wpt_mobile_menu_layout`; allowed values exactly `topbar`, `offcanvas`, `drilldown`; default `topbar`; unknown values sanitize to `topbar`.
- `topbar`, `offcanvas` and the desktop dropdown render exactly as today.
- Reuse `HyperPress_Theme_Mobile_Walker` unchanged; no new nav menu location (`mobile-nav` stays).
- No hard-coded per-item colours: colours come from the item classes `gray`, `red`, `orange`, `green`, `blue`, `purple` on top-level items, mapped to `$hyper-gear-gray`, `$hyper-red`, `$hyper-orange`, `$hyper-green`, `$hyper-blue`, `$hyper-purple` (`src/assets/scss/global/_colors.scss`).
- Drilldown attributes: `data-drilldown data-parent-link="true" data-auto-height="true"` plus a translatable "Back" button label (text domain `hyperpress`).
- Font: `$header-font-family` (Oswald), uppercase.
- PHP floor 8.3; PHPCS WordPress standard (tabs). PHP runs in Docker only. Env setup for Node/pnpm: `source ~/.nvm/nvm.sh && nvm use && export PATH="$HOME/.local/share/pnpm/bin:$PATH"`.
- Commit with explicit paths: `git add <paths>` then `git commit -m "..." -- <paths>`; style `type(scope): lowercase summary`. Commit only when asked.

## Review Focus

- Drilldown initialised while its `#mobile-menu` bar is hidden by the responsive toggle may measure height 0 or show a broken panel when opened. Expect a working open/close; fallback is re-initialising on `toggled.zf.responsiveToggle`.
- A top-level item with no colour class should still be readable (neutral `gray`), not invisible.
- No menu assigned to the Mobile location: render nothing and do not error (`fallback_cb` is false).
- Mobile and desktop sharing one menu (items carry `opens-right is-dropdown-submenu-parent`) must still drill down correctly.
- No custom logo set: the title bar falls back to the site name, not an empty bar.

---

### Task 1: Layout setting and helper

**Files:**

- Modify: `library/custom-nav.php`
- Modify: `library/foundation.php:192-207`
- Test: `tests/integration/MobileMenuLayoutTest.php`

**Interfaces:**

- Produces: `hyperpress_mobile_menu_layout(): string` in `library/custom-nav.php`, returning `'topbar'`, `'offcanvas'` or `'drilldown'` (theme mod, defaulting to and sanitizing unknown values to `'topbar'`). Later tasks call it.
- Consumes: the existing `wpt_mobile_menu_layout` theme mod.

- [x] **Step 1: Write failing tests** in `MobileMenuLayoutTest`: `test_layout_setting_accepts_drilldown` (`sanitize('drilldown') === 'drilldown'`), `test_layout_setting_offers_a_drilldown_choice` (the `mobile_menu_layout` control's `choices` has key `drilldown`), `test_layout_helper_defaults_to_topbar` (no mod set, `hyperpress_mobile_menu_layout() === 'topbar'`), `test_layout_helper_returns_drilldown_when_set` (`set_theme_mod('wpt_mobile_menu_layout','drilldown')`), `test_body_class_is_drilldown` (`body_class` filter output via `get_body_class()` contains `drilldown`), `test_toggle_and_menu_id_for_drilldown` (`hyperpress_title_bar_responsive_toggle()` output is `data-responsive-toggle="mobile-menu"`; `hyperpress_mobile_menu_id()` echoes `mobile-menu`; both captured with `ob_start`).
- [x] **Step 2: Run to verify failure.** From `themes/hyperpress-foundation/`: `docker compose run --rm php composer test:integration -- --filter MobileMenuLayoutTest`. Expected: new tests FAIL.
- [x] **Step 3: Implement.** In `custom-nav.php` add `hyperpress_mobile_menu_layout()`, add `drilldown => 'Drilldown'` to the control choices and to the sanitizer's allowed list, and extend `mobile_nav_class()` to add the `drilldown` body class (use the helper). In `foundation.php`, make `hyperpress_title_bar_responsive_toggle()` emit the attribute for `topbar` and `drilldown`; `hyperpress_mobile_menu_id()` keeps `off-canvas-menu` for `offcanvas` only.
- [x] **Step 4: Run tests.** Same command. Expected: PASS, including the pre-existing tests in the class.
- [x] **Step 5: Lint.** `docker compose run --rm php composer lint`. Expected: exit 0.
- [x] **Step 6: Commit** `feat(theme-foundation): add drilldown mobile menu layout setting`.

### Task 2: Drilldown markup (nav and title bar)

**Files:**

- Modify: `library/navigation.php` (`hyperpress_mobile_nav()`)
- Modify: `header.php`
- Test: `tests/integration/MobileMenuLayoutTest.php`, `tests/integration/HeaderMarkupTest.php` (follow its existing render pattern)

**Interfaces:**

- Consumes: `hyperpress_mobile_menu_layout(): string` (Task 1).
- Produces: with layout `drilldown`, `hyperpress_mobile_nav()` outputs `<ul ... data-drilldown data-parent-link="true" data-auto-height="true" data-back-button="...">`; `header.php` renders `.title-bar-left` (custom logo, site-name link fallback) and `.title-bar-right` (the `.menu-icon` toggle button) and includes `template-parts/mobile-top-bar` for `topbar` and `drilldown`.

- [x] **Step 1: Write failing tests.** In `MobileMenuLayoutTest`: `test_drilldown_layout_renders_drilldown_attributes` (create a `mobile-nav` menu with a parent and child, set layout `drilldown`; output contains `data-drilldown`, `data-parent-link="true"`, `data-auto-height="true"`, `Back`, and does not contain `data-accordion-menu`); `test_topbar_layout_still_renders_accordion` (output contains `data-accordion-menu`, not `data-drilldown`); `test_drilldown_back_label_is_translatable` (with `translate_everything()` the output contains `TRANSLATED`). In `HeaderMarkupTest`: `drilldown` header contains `title-bar-right` with a `menu-icon` button and a `data-responsive-toggle="mobile-menu"` bar; it falls back to the site name when no custom logo is set; `topbar` header keeps the current `title-bar-left` menu-icon markup.
- [x] **Step 2: Run to verify failure.** `docker compose run --rm php composer test:integration -- --filter 'MobileMenuLayoutTest|HeaderMarkupTest'`. Expected: new tests FAIL.
- [x] **Step 3: Implement `hyperpress_mobile_nav()`.** Choose `items_wrap` by layout: `drilldown` gets the Drilldown attributes above and a `data-back-button` of `<li class="js-drilldown-back"><a tabindex="0">%s</a></li>` using `esc_attr( esc_html__( 'Back', 'hyperpress' ) )`-safe escaping so the attribute stays valid HTML; other layouts keep today's accordion `items_wrap`. Keep the walker, `menu`, `theme_location`, `fallback_cb` args as is.
- [x] **Step 4: Implement `header.php`.** Use the helper instead of the two inline `get_theme_mod` checks. Render the drilldown title bar (logo left, hamburger right) when layout is `drilldown`, otherwise the markup that exists today; include `mobile-top-bar` for `topbar` and `drilldown`.
- [x] **Step 5: Run tests and lint.** The Step 2 command, then `docker compose run --rm php composer lint` and `docker compose run --rm php composer test:unit`. Expected: all PASS, exit 0.
- [x] **Step 6: Commit** `feat(theme-foundation): render drilldown mobile menu markup`.

### Task 3: Drilldown styling

**Files:**

- Create: `src/assets/scss/modules/_mobile-drilldown.scss`
- Modify: `src/assets/scss/app.scss` (add `@import 'modules/mobile-drilldown';` right after `modules/navigation`, line 82)

**Interfaces:**

- Consumes: markup and classes from Task 2 (`body.mobile-drilldown`, `.site-title-bar`, `.mobile-menu`, Foundation-generated `.is-drilldown`, `.is-drilldown-submenu`, `.is-drilldown-submenu-parent`, `.js-drilldown-back`); `$hyper-*` colours; `$header-font-family`.
- Produces: `@mixin drilldown-color($color)` used for each of `.gray .red .orange .green .blue .purple` on top-level items.

- [x] **Step 1: Write the Sass**, all scoped under `body.mobile-drilldown`:
  - Title bar: light background (`$topbar-background`), logo left (max-height about 3rem), grey hamburger right.
  - Top-level rows: Oswald, uppercase, 1px dark bottom border, coloured text and coloured right-pointing triangle from `drilldown-color`; items without a colour class use the `gray` colour.
  - `.red > .is-drilldown-submenu` etc.: background is the darker shade, items white uppercase and smaller, white arrows, `.js-drilldown-back` larger. Start from `scale-color($color, $lightness: -20%)` and adjust by eye against the owner's screenshots.
- [x] **Step 2: Build.** From the repo root with the env line: `pnpm exec nx build @hyper/press-foundation`. Expected: success, no Sass errors.
- [x] **Step 3: Verify in a browser** (hyperalumni stack, `cd docker/hyperalumni && docker compose --profile local up -d`, site on port 3080). Create a menu on the Mobile location with top-level items classed `red orange green blue purple` and children two levels deep; set the layout with `docker compose --profile local run --rm wpcli wp theme mod set wpt_mobile_menu_layout drilldown`. At a phone width check: closed bar (logo left, hamburger right); open list; drill into a parent (parent-coloured panel, Back, repeated parent link, white arrows); third level; Back returns; second open/close of the toggle still works; an unclassed top-level item is readable; the desktop menu is unchanged at desktop width; switching the layout back to `topbar` restores the accordion. If the panel is mis-sized from initialising inside the hidden bar, re-initialise the Drilldown on `toggled.zf.responsiveToggle` in `src/assets/js/foundation.js`, then rebuild and recheck.
- [x] **Step 4: Lint.** `pnpm exec nx run @hyper/press-foundation:lint-js` (and `lint-css` if it is inferred for this project). Expected: no new errors.
- [x] **Step 5: Commit** `feat(theme-foundation): style the drilldown mobile menu`.

### Task 4: Documentation and close-out

**Files:**

- Modify: `themes/hyperpress-foundation/AGENTS.md` (only if it describes the mobile menu or the layout setting; otherwise skip)
- Modify: this plan (banner and outcome notes)

- [x] **Step 1:** `grep -rn "offcanvas\|wpt_mobile_menu_layout\|mobile menu" themes/hyperpress-foundation/AGENTS.md docs/superpowers/plans` and update any file that describes the layouts to mention `drilldown`.
- [x] **Step 2:** Run the full theme suites once: `docker compose run --rm php composer test:unit && docker compose run --rm php composer test:integration` and `pnpm fmt:check`. Expected: all pass.
- [x] **Step 3:** Add the `STATUS: ✅ COMPLETED` banner to this plan with what passed; commit `docs(plans): complete drilldown mobile menu plan`.

## Outcome / execution notes

- The mobile walker needed no change; only `items_wrap` in `hyperpress_mobile_nav()` differs by layout.
- Drilldown initialised inside the hidden toggled bar sized correctly (no re-init needed in `foundation.js`).
- Nested (third-level) panels needed the colour rule on every `.is-drilldown-submenu`, not only direct children of the top-level item.
- On phones `.site-navigation` has 10px side padding; it is removed under `body.mobile-drilldown` (`small only`) so rows run edge to edge.
- Owner review tweak: "Back" is 1rem with a white left arrow; the repeated parent link (`.is-submenu-parent-item`) is the larger 1.25rem panel title.
- The drilldown body class is `mobile-drilldown`, not `drilldown`: Foundation styles every `.drilldown a` (white background, padding), so a plain `drilldown` body class restyled all links on the page, including the desktop dropdown's. Found when switching the desktop dropdown to Foundation's triangle arrows.
- Desktop dropdown arrows: the `»` glyph in `_navigation.scss` is replaced by Foundation's own triangle, recoloured white for `opens-right` (left border) and `opens-left` (right border) items.
- Desktop arrow direction: saved `opens-right`/`opens-left`/`opens-inner` classes in a menu item's CSS Classes field sat next to the ones Foundation's DropdownMenu adds and flips (default `alignment: 'auto'`, and a menu inside `.top-bar-right` starts as `opens-left`), so the arrow could not follow the real direction. The desktop `<ul>` now has `data-alignment="left"`, so Foundation starts every item at `opens-right` (the old site's behaviour, previously forced by saving `opens-right` on each item) and flips it only when there is no room. `hyperpress_strip_dropdown_direction_classes()` (`library/navigation.php`) removes them from the `top-bar-r` output; `_navigation.scss` colours the arrow per class and centres it on the row (`margin-top: -$dropdownmenu-arrow-size`).
- Final review (2026-11-02) fixes: `body.admin-bar.mobile-drilldown` joins `.topbar`/`.offcanvas` in `_wp-admin.scss` (the sticky header offset was missed for logged-in editors); `data-back-button` is now HTML-escaped and then attribute-escaped with double encoding (`esc_attr()` does not re-encode entities, so a translated label containing markup became live HTML). Tests pin both, plus the other layouts' body classes and the deleted-logo fallback (already correct in WP 6.9).
- Final review, deferred: hamburger `aria-expanded`/`aria-controls` and the Back control's `role`; WCAG contrast of orange/green row text on white (brand colours); `opens-inner` arrow colour; `footer.php` still reads the theme mod directly; Customizer choice labels are untranslated (pre-existing); `&.gray` emits the same CSS twice; hamburger markup duplicated in both `header.php` branches.
- Final review, decision for the owner: the desktop dropdown changes (`data-alignment="left"`, stripped direction classes, triangle arrows) apply to every site using the theme, including hyperonline, which previously opened left-aligned by Foundation's default.
- The `lint-css` target is not inferred for this project, so Sass is checked by the build only.

## Known follow-ups

- The owner creates the dedicated Mobile menu and sets the colour classes in wp-admin.
- Panel shades are matched by eye; revisit if the brand palette changes.
