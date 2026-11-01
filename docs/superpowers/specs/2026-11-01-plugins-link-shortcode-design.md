# Link shortcode and mailto/tel/anchor handling: design

Small change, approved in conversation 2026-11-01.

## Intent

Editors write `[hyperpress_link url="…"]Text[/hyperpress_link]` and get a correct `<a>` without choosing `target` or `rel` themselves. The decision comes from `HyperPress\Utils\Link`, which today mis-handles `mailto:`, `tel:` and `#anchor` URLs (no host, so they count as external and open in a new tab).

## Goals

- `hyperpress_link` shortcode in `hyperpress-shortcode`, rendering `<a href target rel>` where `target` and `rel` come only from `Link::target()` and `Link::rel()`.
- `Link::target()` returns `_self` for `mailto:` and `tel:` URLs (case-insensitive scheme); `Link::is_internal()` returns true for URLs starting with `#`.
- `hyperpress-shortcode` formally requires `hyperpress-utils`.

## Non-goals / anti-goals

- No extra shortcode attributes (`class`, `title`, `download`, …).
- No schemes beyond `mailto`, `tel`, `sms` and `#`; no general "any non-http scheme is same-tab" rule (a bare `host:8080/x` must not match it).
- No fallback for a missing `Link` beyond a plain link: utils stays a hard requirement (`Requires Plugins`); the guard in the amendment below only stops a fatal error.
- No new helper in utils for building the whole anchor (the existing callers each build their attributes differently, so there is no second user).

## Constraints

- Plugin conventions in `plugins/AGENTS.md`: `HyperPress\Shortcode\Shortcodes\*` classes with `register()` / `render()`, text domain `hyperpress`, PHP `>=8.3`, WordPress coding standard (tabs).
- `Requires Plugins` in a plugin header must match `tests/src/Support/Convention.php` (`PluginHeadersTest`).
- PHP runs in Docker only (`docker compose run --rm php …` from `plugins/`).

## Approaches considered

- **A. Dedicated shortcode class (chosen).** `Shortcodes\Anchor`, registered as `hyperpress_link`. Follows the existing shortcode pattern and touches only the shortcode plugin plus the utils fix.
- B. Add `Link::anchor( $url, $text )` to utils and make the shortcode a thin wrapper. Rejected: second plugin changed with no second user (YAGNI).
- C. Fold it into an existing shortcode class. Rejected: none of them deal with links.

## Behavior

Shortcode `[hyperpress_link url="…"]text[/hyperpress_link]`:

- Text: enclosed content if non-empty, else the `text` attribute. Enclosed content is run through `do_shortcode()` then `wp_kses_post()` (so editors can nest inline markup or shortcodes; `<a>` tags are stripped to avoid nested anchors, and block markup such as forms and iframes is dropped by kses); the `text` attribute is `esc_html()`-ed. *(Builder's call.)*
- `href` is `esc_url()`-ed. If `url` is empty or `esc_url()` rejects it (e.g. `javascript:`), output the text only, with no link; if there is no text either, output nothing. If the text is empty and the href is valid, the URL is the link text. *(Builder's calls.)*
- `target = Link::target( $url )`, `rel = Link::rel( $target )`; the `rel` attribute is omitted when empty.
- Output shape: `<a href="…" target="…" rel="…">text</a>`.

`Link` changes:

- `target()`: before the internal/external check, return `_self` when the URL, trimmed, matches `/^(mailto|tel|sms):/i`. A regex, not `wp_parse_url()`, because PHP parses a digits-only `tel:5551234` as host `tel` and port `5551234`.
- `is_internal()`: trims the URL, then returns true when it starts with `#` or `?` (both point at the current page). `mailto:` / `tel:` stay "not internal".
- Existing callers (social, competition, newsletter, sponsor) now get `_self` and no `rel` for these URL types; none currently passes such a URL in code, but a settings field could.

## Left to the builder

Class and test file naming beyond what the plan states, docblock wording.

## Amendment 2026-11-01 (after final review)

The review's minors were taken in: `sms:`, query-only (`?x`) URLs and leading-whitespace trimming in `Link`; no output for an unsafe URL with no text, `<a>` stripped from enclosed content, and the missing `href`, `&` and `data:`/mixed-case `javascript:` tests in the shortcode. Still no `class_exists` guard (ruling stands).

Second amendment: `Anchor` guards `class_exists( Link::class )`. If utils is missing at render time the link is a plain `<a href>` without `target` or `rel`, instead of a fatal error. The missing-class branch cannot be covered by the integration suite (the class is already loaded), so it is checked once by hand with stubbed WordPress functions.
