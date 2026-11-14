# `hyperpress_noautop` shortcode design

Date: 2026-11-13. Size: small change (short design). Approved by the owner in conversation.

## Intent and why

`wpautop` runs on `the_content` at priority 10 and `do_shortcode` at priority 11. By the time a shortcode callback runs, `<p>` and `<br />` tags are already in its content, so `hyperpress_raw` (`Raw::render()`) can only strip a stray edge tag and cannot stop `wpautop`. Hand-written HTML wrapped in `[hyperpress_raw]` (for example the Foundation accordion on hyperalumni.org `/get-involved/invest/`) still gets `<p></p>` / `<br />` inside indented text, which breaks alignment.

`[hyperpress_noautop]...[/hyperpress_noautop]` keeps `wpautop` (and `wptexturize`) from touching the block's content.

## Goals, non-goals, anti-goals

- Goal: content between the tags reaches `do_shortcode` exactly as authored; nested shortcodes (banner, date distance, ...) still render.
- Goal: text outside the block is still autop'd as usual.
- Non-goal: `hyperpress_raw` is unchanged (tag stable by contract, other pages rely on it).
- Non-goal: nesting the tag inside itself; areas outside `the_content` (widgets, term descriptions, excerpts).
- Anti-goal: disabling `wpautop` for a whole post or site.

## Constraints

- Plugin `hyperpress-shortcode`, namespace `HyperPress\Shortcode\Shortcodes`, one class per concern, registered from `Plugin::register()` (runs on `init`, after core added `wpautop`/`wptexturize`/`do_shortcode` to `the_content`).
- PHP `>=8.3`, WordPress coding standard (tabs, `array()`), text domain `hyperpress`.

## Approaches

- A (chosen): new tag with a stash-and-restore filter pair. `hyperpress_raw` untouched.
- B: change `hyperpress_raw` to use the same mechanism. No content edits, but every page using `hyperpress_raw` loses autop inside the block and the audit's "tag is stable by contract" note is reversed.
- C: disable `wpautop` per post or site. Too broad.

## Design

- `Shortcodes\NoAutop::register()` adds the shortcode `hyperpress_noautop` and two `the_content` filters.
- Stash, priority 9: `preg_replace_callback` with `get_shortcode_regex( array( 'hyperpress_noautop' ) )`. Escaped matches (`[[...]]`, group 1 is `[`) are returned untouched. Every other match (full text including the tags) is stored in a static array and replaced by a placeholder: a per-request random alphanumeric prefix plus the index (no characters that `wptexturize` or `wpautop` change).
- Restore, priority 10 (registered after core's `wpautop`, so it runs after it): replace `<p>PLACEHOLDER</p>` (with optional surrounding whitespace) and any bare `PLACEHOLDER` with the stored text, then clear the store.
- Callback: `render( $atts = null, $content = '' ): string` returns `do_shortcode( (string) $content )`, so the nested shortcodes render at priority 11 and a null body equals an empty one.
- Placeholder next to other text on the same line can still end up inside a `<p>` (documented limit).

## Left to the builder

Method names inside `NoAutop`, the placeholder format, test fixture wording.

## Owner's follow-up

After the plugin is deployed, change the page's opening and closing tags from `hyperpress_raw` to `hyperpress_noautop`.
