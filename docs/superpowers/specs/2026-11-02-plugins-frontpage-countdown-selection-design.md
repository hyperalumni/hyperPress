# Front page countdown selection

## Intent

The front page must show exactly one countdown at a time. Today that comes from the hide-before and hide-after dates on each `hyper_countdown` post: the owner arranges the dates so only one window is open, and the theme prints every selected countdown through `[hyperpress_countdown]`.

Competitions already render a countdown from their own schedule (`[hyperpress_competition_countdown]`), but the front page cannot show them. The owner does not want to duplicate a competition as a countdown post. An events plugin (`hyperpress-event`, pages for events such as Healthy Kids Day or KidsFest, which are hand-built WordPress pages today) was planned later and must be able to join the front page the way competitions do, with no change to the theme or to `hyperpress-frontpage`.

So: the front page asks every source for its candidate countdowns, drops those whose window is closed and shows the one that starts soonest.

## Goals

- One generic contract (a filter) through which any plugin offers countdown candidates to the front page.
- `hyperpress-frontpage` collects candidates, picks one and renders it.
- Sources in this change: the one-off countdowns chosen in the Front Page "Countdowns" field, and every published competition (automatic).
- The theme asks a generic filter for the HTML and knows no plugin.
- The `hyperpress-event` plugin can be added later by hooking the same filter.

## Non-goals / anti-goals

- No `hyperpress-event` plugin or post type now. No change to the `[hyperpress_countdown]` or `[hyperpress_competition_countdown]` shortcodes' output.
- No duplicate countdown posts for competitions. No second rendering path for countdowns.
- Must never print more than one front-page countdown, and must never print a closed one.
- The theme must not name a plugin, shortcode or post type.

## Approaches considered

1. Duplicate each event as a countdown post (rejected: drifts when dates change).
2. Add only a lead-time setting to competitions and keep the theme looping over IDs (rejected: no common rule for choosing one, and a future events plugin would need theme changes).
3. Candidate contract with a selection rule in `hyperpress-frontpage` (chosen by the owner).
4. A new events plugin that replaces `hyper_countdown` (rejected for now: migration of posts, shortcodes and the selector for no current gain; `hyper_countdown` stays for one-off countdowns such as kickoffs, game reveals and stop-build days).

## The contract

Filter `hyperpress_frontpage_countdown_candidates` (declared and applied by `hyperpress-frontpage`) receives and returns a list of candidates. Each candidate is an array:

| key              | type                  | meaning                                                                                                                                    |
| ---------------- | --------------------- | ------------------------------------------------------------------------------------------------------------------------------------------ |
| `id`             | string                | unique and stable, e.g. `countdown-12`, `competition-45`; used as the last tie-break                                                       |
| `start`          | `DateTimeImmutable`   | the instant the countdown counts to; used for ordering                                                                                     |
| `show_from`      | `?DateTimeImmutable`  | the window opens after this instant; `null` = no lower bound                                                                               |
| `show_until`     | `?DateTimeImmutable`  | the window closes at this instant; `null` = no upper bound                                                                                 |
| `fallback_after` | `?DateTimeImmutable`  | optional soft end: once `now` is past it the candidate is used only when no other candidate is open; `null` (or absent) = never a fallback |
| `priority`       | int                   | tie-breaker, higher wins; default 10                                                                                                       |
| `render`         | callable `(): string` | returns the countdown HTML; called only for the chosen candidate                                                                           |

Selection (`Countdowns::pick( array $candidates, DateTimeImmutable $now ): ?array`): ignore anything that is not an array with the keys above, a `DateTimeImmutable` start, bounds that are `null` or `DateTimeImmutable` (`fallback_after` may be absent), and a callable `render`; keep candidates whose window is open: `show_from < $now` and `$now < show_until`, both strict and each bound optional (the same comparison the shortcode makes today). Split the open ones into primary (no `fallback_after`, or `$now` not past it) and fallbacks (`$now` past `fallback_after`). If there are primary candidates, return the one with the earliest `start`, then highest `priority`, then lowest `id`. Otherwise return the fallback with the latest `start` (the most recently started), then highest `priority`, then lowest `id`. Otherwise `null`. "Soonest" is by `start` alone, so a primary countdown that has already reached zero but is still open outranks every future one until its window or its soft end passes. Candidates that are closed or not yet open never count as "another candidate to show".

`Countdowns::html(): string` collects candidates (filter), picks with the current time, and returns the chosen candidate's HTML, or `''` when there is none or `render` throws or returns a non-string. It is the callback of the theme filter below.

Theme extension point: `front-page.php` replaces the loop over countdown IDs with `$countdown_html = apply_filters( 'hyperpress_frontpage_countdown_html', '' );` and prints the existing wrapper (post loop, `entry-content`, the `hyperpress_page_before_entry_content` / `hyperpress_page_after_entry_content` actions) only when the string is non-empty. `hyperpress-frontpage` hooks `Countdowns::html` onto it. The theme documents both filters in its `AGENTS.md`.

## Sources

- **One-off countdowns** (`hyperpress-frontpage`): for each ID in the Front Page "Countdowns" field (`Settings::COUNTDOWNS`, sanitized with `absint`, zeros dropped, duplicates ignored) that is a readable `hyper_countdown`, a candidate `countdown-<ID>` built from a new public helper in `hyperpress-countdown`, `HyperPress\Countdown\Window::for_post( int $post_id ): ?array{start,show_from,show_until}`. `Window` returns `null` for a post that is not renderable (not published, or private and unreadable) or has no parseable event date. It parses the event date time and the two visibility fields as wall-clock times in `wp_timezone()` (an empty visibility field means no bound), and returns raw bounds, not a decision (`show_until` is `null` when Hide After is empty). The shortcode keeps its own strict comparison (`hide_before < now < hide_after`, each optional) on those bounds, so its result at the edges is unchanged; `Countdowns::pick` uses the same strict rule, so the shortcode and the front page agree at every instant. Frontpage cannot depend on the countdown plugin, so the three-line rule exists in both places. The front-page candidate's `show_until` is the countdown's Hide After (hard end, `null` when empty). When Hide After is empty, `fallback_after` is the end of its event's day (23:59:59 on the start's date in `wp_timezone()`): the countdown stays up, at zero, until the end of that day, and afterwards only while no other candidate is open, so a finished countdown yields to the next one but is never replaced by an empty front page. When Hide After is set, `fallback_after` is `null` (the hard end applies). This applies only to the front-page candidate, not to the shortcode. `render` calls `do_shortcode( '[hyperpress_countdown id="<ID>" /]' )`, so there is still one rendering path. `Shortcode::add_countdown_shortcode` is changed to use `Window` for its show/hide decision (single source of truth) and its output and edge cases stay as they are (including `ignoreshowhide`). `hyperpress-frontpage` guards on `class_exists( '\HyperPress\Countdown\Window' )`.
- **Competitions** (`hyperpress-competition`, new class `FrontpageCountdown`, registered in `Plugin::boot`): for each published `hyper_competition` with at least one counted schedule day, a candidate `competition-<ID>` with `start` = the start of the day `Schedule::current()` picks, `show_from` = the start of the first counted day minus the lead time in days (in `wp_timezone()`), `show_until` = the end of the last counted day, `render` = `Countdown::render( $id, true, true, true )` (heading "Title: Day label", venue and the formatted date of the counted day). Private competitions are not offered. Does nothing when the setting is off or `hyperpress-frontpage` is not active (the filter simply has no taker).
- **Settings** (competition settings page, `Settings` + `MetaBoxes`): `hyperpress_competition_frontpage` (checkbox "Show on the front page") and `hyperpress_competition_frontpage_lead_days` (number, 0 to 365, default 60, sanitized like the other settings, a malformed value gives the default). Following the Program/Past-zero pattern, Meta Box drops an unticked checkbox's key, so once a settings row is saved a missing key means off; with no saved row the default is on. My call: this keeps existing sites (which already have a saved row) from changing behaviour until the owner ticks the box.

## Behaviour changes the owner will notice

- A one-off countdown with no Hide After no longer blocks later countdowns: from the end of its event's day it shows only when nothing else is open, and otherwise it stays at zero. The shortcode on event pages is unchanged. An explicit Hide After still removes it outright, and can keep a count-past-zero display running for days. Competition candidates cannot get stuck: their target moves to the next counted day once a day ends, and the window closes after the last counted day.
- The front page now shows one countdown. Where several selected countdowns had overlapping windows, only the soonest-starting one remains.
- A competition within its lead time appears automatically (once the setting is on) and disappears when its last counted day ends.

## Constraints

- PHP 8.3 floor, PHPCS WordPress standard, text domain `hyperpress`. PHP runs in Docker only (`plugins/` and the theme each have their own Compose tooling; see their `AGENTS.md`).
- Time handling in the site timezone (`wp_timezone()`); no `strtotime()`/`current_time( 'timestamp' )` in new code.
- Plugins stay independent: `hyperpress-frontpage` must not hard-require competition or countdown; each guards with `class_exists` or simply hooks the filter.

## Tests the plan must cover

- `Countdowns::pick`: closed windows excluded (before `show_from`, after `show_until`, and exactly at either bound, since both comparisons are strict), soonest start wins, priority then id break ties, past-zero-still-open outranks future, malformed candidates ignored, empty list gives `null`; a candidate past its `fallback_after` loses to any open primary candidate but is shown when it is the only open one; among several fallbacks the latest start wins; a closed or not-yet-open candidate never displaces a fallback; a candidate whose `fallback_after` is still in the future is primary.
- `Countdowns::html`: chosen HTML returned, `''` for none, a throwing or non-string `render` is swallowed.
- Frontpage candidates from the countdown field (no Hide After gives `fallback_after` = 23:59:59 of the start's date in the site timezone, including near midnight and across a DST change, and no hard end; an explicit Hide After gives `show_until` and no `fallback_after`): IDs sanitized (`'12'`, `'7" x="y'`, `'abc'`, `'0'` as in the existing theme test), unpublished and non-countdown IDs skipped, windows taken from `Window`.
- `Window::for_post` and parity with the shortcode's current show/hide behaviour (existing `CountdownShortcodeHardeningTest` must stay green).
- Competition candidates: window opens exactly `lead_days` before the first counted day, closes after the last counted day ends, skipped when the setting is off, no counted day, or not published.
- Theme: `front-page.php` prints the wrapper and the filter's HTML when non-empty and nothing when empty (replaces `test_front_page_only_passes_integer_countdown_ids_to_the_shortcode`, whose ID sanitising moves to the frontpage plugin).

## Left to the builder

Class names and file layout beyond those given, how the competition candidates are queried (it may limit the query to recent seasons if that is simple), the settings field labels, and wording of the AGENTS entries.

## Follow-up resolved

`hyperpress-event` now hooks `hyperpress_frontpage_countdown_candidates` with no change to the theme or `hyperpress-frontpage` (see `2026-11-13-plugins-event-design.md`).
