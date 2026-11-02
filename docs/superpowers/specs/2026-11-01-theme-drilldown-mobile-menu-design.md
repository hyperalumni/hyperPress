# Drilldown mobile menu (hyperpress-foundation)

## Intent

The current production site (an older FoundationPress, pre Foundation 6) has a mobile menu that the new
`hyperpress-foundation` theme does not match. Reproduce its look and behaviour in the new theme as an
opt-in layout, so the other sites (hyperonline) keep their current mobile menu.

## Target behaviour (from the owner's screenshots of the old site)

- Collapsed: light bar, custom logo on the left, grey hamburger on the right.
- Open: full-width rows, each separated by a dark border, Oswald, uppercase, coloured text, with a
  coloured triangle on the right for items that have children.
- Row colour comes from the CSS class on the **top-level** menu item (`red`, `orange`, `green`,
  `blue`, `purple`, `gray`), set in wp-admin. Sub-items carry no classes.
- Tapping a parent slides over a panel whose background is a darker shade of that top-level item's
  colour (red under About, orange under Media, green under Events). The panel has a large "Back" header,
  the parent repeated as its first item, then white uppercase items; children with their own children get a
  white right-pointing arrow. A third level keeps the same top-level colour.

## Goals

- New `drilldown` value for the `wpt_mobile_menu_layout` Customizer setting (alongside `topbar`,
  `offcanvas`). Default stays `topbar`.
- Drilldown layout uses Foundation's `Drilldown` plugin (Foundation 6.9; JS is already bundled in full,
  Sass `foundation-drilldown-menu` is already included).
- Menu content comes from the existing **Mobile** menu location (`mobile-nav`). The owner may assign a
  dedicated menu there. The theme must also work when that location shares a menu with the desktop top bar
  (extra desktop classes such as `opens-right is-dropdown-submenu-parent` must do no harm).

## Non-goals / anti-goals

- No change to the desktop dropdown, the accordion (`topbar`) layout, the off-canvas layout or the default.
- No new nav menu location, no new walker, no hard-coded per-item colours.
- Must not look broken when an item has no colour class.

## Approaches considered

1. **New opt-in layout using Drilldown (chosen).** hyperonline unchanged; this site opts in.
2. Replace the accordion with Drilldown for all sites. Rejected: changes hyperonline.
3. Restyle the accordion only. Rejected: cannot give the slide-over panel and Back button.

## How it fits together

- `hyperpress_mobile_menu_layout()` returns the sanitized layout; header, toggle id, body class and the
  mobile nav all read it.
- `drilldown` behaves like `topbar` structurally (the `#mobile-menu` nav is toggled by
  `data-responsive-toggle`), so the toggle id and responsive toggle attribute treat it as top-bar.
- `hyperpress_mobile_nav()` swaps `data-accordion-menu` for
  `data-drilldown data-parent-link="true" data-auto-height="true"` and a translated Back label. The existing
  `HyperPress_Theme_Mobile_Walker` (`ul.vertical.nested.menu`) is reused unchanged.
- In `header.php` the drilldown layout renders the custom logo (site name as fallback) on the left and the
  hamburger on the right of the title bar.
- A new Sass module scopes everything under `body.mobile-drilldown`.

## Left to the builder

Exact Sass structure, shade used for the panels (match the screenshots by eye; start from
`scale-color($color, $lightness: -20%)`), border colour, Drilldown tuning if it misbehaves when initialised
inside a hidden toggled bar.
