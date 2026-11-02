# Front page countdown selection

## Intent

The front page must show exactly one countdown at a time. Today that comes from the hide-before and hide-after dates on each `hyper_countdown` post: the owner arranges the dates so only one window is open, and the theme prints every selected countdown through `[hyperpress_countdown]`.

Competitions already render a countdown from their own schedule (`[hyperpress_competition_countdown]`), but the front page cannot show them. The owner does not want to duplicate a competition as a countdown post. An events plugin (`hyperpress-event`, pages for events such as Healthy Kids Day or KidsFest, which are hand-built WordPress pages today) is planned later and must be able to join the front page the way competitions do, with no change to the theme or to `hyperpress-frontpage`.

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

| key          | type                  | meaning                                                                              |
| ------------ | --------------------- | ------------------------------------------------------------------------------------ |
| `id`         | string                | unique and stable, e.g. `countdown-12`, `competition-45`; used as the last tie-break |
| `start`      | `DateTimeImmutable`   | the instant the countdown counts to; used for ordering                               |
| `show_from`  | `?DateTimeImmutable`  | the window opens after this instant; `null` = no lower bound                         |
| `show_until` | `?DateTimeImmutable`  | the window closes at this instant; `null` = no upper bound                           |
| `priority`   | int                   | tie-breaker, higher wins; default 10                                                 |
| `render`     | callable `(): string` | returns the countdown HTML; called only for the chosen candidate                     |

Selection (`Countdowns::pick( array $candidates, DateTimeImmutable $now ): ?array`): ignore anything that is not an array with the keys above, a `DateTimeImmutable` start, bounds that are `null` or `DateTimeImmutable`, and a callable `render`; keep candidates whose window is open: `show_from < $now` and `$now < show_until`, both strict and each bound optional (the same comparison the shortcode makes today); sort by `start` ascending, then `priority` descending, then `id`; return the first, or `null`. "Soonest" is by `start` alone, so a countdown that has already reached zero but whose window is still open (count past zero) outranks every future one until its window closes.

`Countdowns::html(): string` collects candidates (filter), picks with the current time, and returns the chosen candidate's HTML, or `''` when there is none or `render` throws or returns a non-string. It is the callback of the theme filter below.

Theme extension point: `front-page.php` replaces the loop over countdown IDs with `$countdown_html = apply_filters( 'hyperpress_frontpage_countdown_html', '' );` and prints the existing wrapper (post loop, `entry-content`, the `hyperpress_page_before_entry_content` / `hyperpress_page_after_entry_content` actions) only when the string is non-empty. `hyperpress-frontpage` hooks `Countdowns::html` onto it. The theme documents both filters in its `AGENTS.md`.

## Sources

- **One-off countdowns** (`hyperpress-frontpage`): for each ID in the Front Page "Countdowns" field (`Settings::COUNTDOWNS`, sanitized with `absint`, zeros dropped, duplicates ignored) that is a readable `hyper_countdown`, a candidate `countdown-<ID>` built from a new public helper in `hyperpress-countdown`, `HyperPress\Countdown\Window::for_post( int $post_id ): ?array{start,show_from,show_until}`. `Window` returns `null` for a post that is not renderable (not published, or private and unreadable) or has no parseable event date. It parses the event date time and the two visibility fields as wall-clock times in `wp_timezone()` (an empty visibility field means no bound), and returns raw bounds, not a decision. The shortcode keeps its own strict comparison (`hide_before < now < hide_after`, each optional) on those bounds, so its result at the edges is unchanged; `Countdowns::pick` uses the same strict rule, so the shortcode and the front page agree at every instant. Frontpage cannot depend on the countdown plugin, so the three-line rule exists in both places. `render` calls `do_shortcode( '[hyperpress_countdown id="<ID>" /]' )`, so there is still one rendering path. `Shortcode::add_countdown_shortcode` is changed to use `Window` for its show/hide decision (single source of truth) and its output and edge cases stay as they are (including `ignoreshowhide`). `hyperpress-frontpage` guards on `class_exists( '\HyperPress\Countdown\Window' )`.
- **Competitions** (`hyperpress-competition`, new class `FrontpageCountdown`, registered in `Plugin::boot`): for each published `hyper_competition` with at least one counted schedule day, a candidate `competition-<ID>` with `start` = the start of the day `Schedule::current()` picks, `show_from` = the start of the first counted day minus the lead time in days (in `wp_timezone()`), `show_until` = the end of the last counted day, `render` = `Countdown::render( $id, true, false, true )` (heading "Title: Day label"). Private competitions are not offered. Does nothing when the setting is off or `hyperpress-frontpage` is not active (the filter simply has no taker).
- **Settings** (competition settings page, `Settings` + `MetaBoxes`): `hyperpress_competition_frontpage` (checkbox "Show on the front page") and `hyperpress_competition_frontpage_lead_days` (number, 0 to 365, default 30, sanitized like the other settings, a malformed value gives the default). Following the Program/Past-zero pattern, Meta Box drops an unticked checkbox's key, so once a settings row is saved a missing key means off; with no saved row the default is on. My call: this keeps existing sites (which already have a saved row) from changing behaviour until the owner ticks the box.

## Behaviour changes the owner will notice

- A one-off countdown whose date has passed and which has no Hide After stays open, as it stays visible today, so it now keeps winning over later candidates (competitions included) until it is hidden. The owner manages this with Hide After, as now. Competition candidates cannot get stuck this way: their target moves to the next counted day once a day ends, and the window closes after the last counted day.
- The front page now shows one countdown. Where several selected countdowns had overlapping windows, only the soonest-starting one remains.
- A competition within its lead time appears automatically (once the setting is on) and disappears when its last counted day ends.

## Constraints

- PHP 8.3 floor, PHPCS WordPress standard, text domain `hyperpress`. PHP runs in Docker only (`plugins/` and the theme each have their own Compose tooling; see their `AGENTS.md`).
- Time handling in the site timezone (`wp_timezone()`); no `strtotime()`/`current_time( 'timestamp' )` in new code.
- Plugins stay independent: `hyperpress-frontpage` must not hard-require competition or countdown; each guards with `class_exists` or simply hooks the filter.

## Tests the plan must cover

- `Countdowns::pick`: closed windows excluded (before `show_from`, after `show_until`, and exactly at either bound, since both comparisons are strict), soonest start wins, priority then id break ties, past-zero-still-open outranks future, malformed candidates ignored, empty list gives `null`.
- `Countdowns::html`: chosen HTML returned, `''` for none, a throwing or non-string `render` is swallowed.
- Frontpage candidates from the countdown field: IDs sanitized (`'12'`, `'7" x="y'`, `'abc'`, `'0'` as in the existing theme test), unpublished and non-countdown IDs skipped, windows taken from `Window`.
- `Window::for_post` and parity with the shortcode's current show/hide behaviour (existing `CountdownShortcodeHardeningTest` must stay green).
- Competition candidates: window opens exactly `lead_days` before the first counted day, closes after the last counted day ends, skipped when the setting is off, no counted day, or not published.
- Theme: `front-page.php` prints the wrapper and the filter's HTML when non-empty and nothing when empty (replaces `test_front_page_only_passes_integer_countdown_ids_to_the_shortcode`, whose ID sanitising moves to the frontpage plugin).

## Left to the builder

Class names and file layout beyond those given, how the competition candidates are queried (it may limit the query to recent seasons if that is simple), the settings field labels, and wording of the AGENTS entries.
