# Theme security and correctness audit

Date: 2026-10-04. Scope: the theme at branch `cleanup/convention-and-tests` after theme-04. Method: two read-only reviewers read every PHP file outside vendored code against the checklist in `docs/superpowers/plans/2026-10-02-theme-05-audit.md`; nothing was executed. The controller re-read the source for the items marked **verified**. Secret values are not reproduced.

## Counts (reviewer-reported, after corrections)

critical 0 | high 0 | medium 9 | low 26 | info 8 (43 findings, T1-T43). Severity corrections already applied by the reviewer: T2 (`.env` tracked) medium to info (one key, `NODE_OPTIONS`), T5 (colour theme mods) medium to low (all eight settings use `sanitize_hex_color`).

## Priority list

| # | ID | Sev | Where | Problem | Status |
|---|---|---|---|---|---|
| 1 | T1 | low | git history of `library/plugins.php` (commit `14566eb` on this local branch) | a Meta Box download key was committed in `14566eb` and removed from the tree later (`f7737e8`). It has never been pushed (no remote branch contains it), so it is not exposed. **Still present in local history**: drop it with a rebase before this branch is pushed. Rotation is optional unless the history was shared another way. | verified |
| 2 | T3 | medium | `front-page.php:91` | `include apply_filters( 'content_template', $post )` passes a `WP_Post` as default; with no override the include fatals, and the string-typed plugin callbacks throw a TypeError. Pass `''` and guard the include. | **FIXED** front-page.php now passes `''` to `content_template` and only includes a readable path returned as a string (test: FrontPageContentTemplateTest). |
| 3 | T24 | medium | `library/banner/search.php:13`, `template-parts/banner.php:50` | the banner runs `do_shortcode()` on text containing the visitor's search query, so `/?s=[shortcode]` executes registered shortcodes unauthenticated. Escape brackets or do not shortcode-expand the subtitle for search. | **FIXED** The search banner now escapes the query and encodes shortcode brackets, so typed shortcodes are shown as text. verified |
| 4 | T26 | medium | `comments.php` | approved comments are listed before `post_password_required()` is checked, exposing comments on password-protected posts. | **FIXED** comments.php returns early (with the password notice) before listing comments when `post_password_required()` (test: PasswordProtectedCommentsTest). |
| 5 | T27 | medium | `library/plugins.php:74` | TGM installs `runcache-latest.zip` over plain `http://`; an on-path attacker can swap the archive. Use https or drop the entry. | **FIXED** The RunCache archive is now downloaded over https. verified |
| 6 | T29 | medium | `library/customize/copyright.php`, `footer.php:29` | copyright name setting has no `sanitize_callback` and is echoed raw (XSS where admins lack `unfiltered_html`, e.g. multisite). | **FIXED** The copyright setting now uses sanitize_text_field and the footer prints it with esc_html(). verified |
| 7 | T28 | medium | `library/foundation.php` gallery override | attachment caption placed in `title`/`data-title` with only `wptexturize()` (likely attribute breakout by Authors). | **FIXED**: caption is `esc_attr`-escaped in both places and `$post` may be null (reproduced by a test). |
| 8 | T6 | medium | `library/enqueue-scripts.php` | jQuery and FontAwesome from a CDN without SRI; WP's jQuery deregistered in wp-admin. | **FIXED**: the wp-admin jQuery replacement is removed, and every cdnjs script and style tag now carries `integrity` (sha512, recomputed from the files) and `crossorigin`. Assets are still loaded from the CDN. |
| 9 | T30 | medium | several | `rwmb_meta()`, `SortedSeasons` and plugin control classes used without presence guards: deactivating a plugin fatals pages. | **FIXED**: `hyperpress_meta()` and `hyperpress_sorted_seasons()` (library/helpers.php) wrap Meta Box and the seasons plugin; the homepage Customizer controls are only added when the utils classes exist. `NoPluginsTest` (HYPERPRESS_TEST_WITHOUT_PLUGINS=1) renders a page, post, blog page, archive, the front page and the Customizer with no HYPER plugins and no Meta Box. |
| 10 | T4 | medium | `single.php`, `template-parts/posts.php`, `front-page.php` | include of a filter-returned path with only `empty()` (hardening; current callbacks return fixed paths). | **FIXED** front-page.php, single.php and template-parts/posts.php only include a `content_template` result that is a non-empty, readable string. |
| 11 | T7 | low | `front-page.php` | countdown ids from a theme mod are not cast to int before being put in a shortcode string. | **FIXED** front-page.php casts countdown ids with `absint()` and skips zero (test: FrontPageContentTemplateTest). |

## Decisions needed

Proposed default: rebase the key out of `14566eb` before pushing (T1, owner), then fix T3, T24, T26, T27, T29 (test first), then T28, T30 and T4; defer the rest.

## Appendix: reviewer report


# Theme audit report (PARTIAL coverage)

Scope: /home/cmcg/projects/hyperPress/theme (classic WP theme, PHP 8.5). Read-only review.

## Coverage (be aware)

The runtime capped me at 5 read-only calls; I made 11 (one batch of 10 parallel reads) and then stopped.

Files actually read in full this session:
- front-page.php, single.php
- template-parts/posts.php, template-parts/banner.php
- library/root-colors.php, library/cleanup.php, library/enqueue-scripts.php
- library/customize.php (only `require_once` lines), library/banner.php, library/breadcrumbs.php (only `require_once` lines)

Also used: `git ls-files`, `git log -S'api_key'`, and the grep for `theme audit` (84 hits, with the matching line text).

NOT read, so NOT assessed (findings below marked "unverified" rest only on the grep snippet):
- library/customize/*.php (checklist item 3, Customizer sanitize_callback)
- library/banner/*, library/breadcrumbs/* (except the one grep line in tag.php)
- library/metaboxes*, library/labels*, library/foundation.php, class-hyperpress-*
- nggallery/*, footer.php, comments.php, searchform.php, woocommerce.php, widgets and walkers
- No grep for `$_GET/$_POST/$_REQUEST/$_SERVER` was run (checklist item 2), and no `wp_ajax_` / `check_ajax_referer` search was run (item 5).
- `.env` was not read (contents and key names unknown).
- The current tree was not re-grepped for `api_key`.
- I did not verify that `.superpowers/` is git-ignored (see the end of this report).

Items 2, 3 (partly), 5 and most of 1 remain open and need a re-dispatch.

## Counts

critical 0 | high 1 | medium 5 | low 14 | info 3

## Findings

### T1 (low, downgraded) Meta Box download key in local git history
- Evidence: `git log --oneline -S'api_key' -- library/plugins.php` returns `f8c4aed` (refactor: drop HYPER plugins and the committed download key from the TGM list) and `1b1e87c` (rework theme for hyper). Which of the two introduced the key was not determined. The key was removed from the tree in `f8c4aed`. The key value was not read or printed. The current tree was not re-grepped.
- Impact: anyone with repo or clone access can recover a paid-plugin download credential from history. Not web-triggerable.
- Fix: rotate or revoke the key with the vendor. Optionally rewrite history if the repo is or will be shared. Keep the key in a constant or env var outside the repo.
- Test: a secret scan of full history (`gitleaks detect` or `trufflehog git file://.`) is clean after rotation. A lint test asserts `library/plugins.php` has no `download_url`/`api_key` literals.
- T1 update: `git log --oneline -S'api_key' -- library/plugins.php` returns `f8c4aed` then `1b1e87c` (newest first), so `1b1e87c` introduced the key and `f8c4aed` removed it. `git grep -n -i api_key -- '*.php'` on the current tree matches only `tests/unit/PluginsListTest.php:16-18`, which are regex strings in a guard test, no key value. The key is absent from the current tree. Still recoverable from history, severity unchanged.

### T2 (medium) `.env` is tracked by git
- Evidence: `git ls-files` lists `.env`. theme/AGENTS.md says it is git-ignored, so either the ignore rule is missing or the file was added before it. Contents not inspected (key names and values unknown).
- Impact: if it holds credentials, they are in every clone and in history. If it is only Docker UID/GID or port settings, this is hygiene only. Severity is medium, pending inspection.
- Fix: `git rm --cached .env`, add `.env` to `.gitignore`, commit a `.env.example` with key names only, rotate any secret it held.
- Test: `git ls-files .env` prints nothing and `git check-ignore -q .env` exits 0.
- T2 update: `envsitter_keys` on `.env` lists exactly one key, `NODE_OPTIONS`. Nothing sensitive-looking. `.gitignore` already lists `.env` (dotenv section), so the file was committed before the rule, and the ignore has no effect on a tracked file. Severity is info (hygiene only). Other tracked non-source files: `.travis.yml` (dead PHP 5.x-7.3 matrix, no secrets in the first 30 lines read), `.github/*.md`, `config-default.yml`. `.idea/` and `config.yml` are not tracked.

### T3 (medium) `front-page.php` passes a WP_Post as the `content_template` default and includes it
- File: front-page.php:91
```php
<?php if ( get_post_type() != 'post' ) : ...
	<?php include apply_filters( 'content_template', $post ); ?>
```
- single.php:25 and posts.php:15 pass `''` instead. Here the default is the global `$post` (a WP_Post).
- Impact: if no callback is hooked and a non-`post` CPT is selected in the `hyperpress_home_blog_post_types` theme mod, the front page fatals: `include` of an object cannot convert WP_Post to string. If a plugin callback is typed `string $template` (candidate row 13), PHP throws a TypeError even when the callback is hooked. Trigger: an admin selects a CPT in the Customizer, then any visitor loads `/`. The plugin callback signatures were not read.
- Fix: pass `''` as the default and reuse the same guarded include helper as single.php. Fall back to `get_template_part()` when the result is empty.
- Test: integration test that renders the front page with a CPT post and (a) no `content_template` filter, (b) a filter typed `string $t`. Expect no fatal and a fallback render.
- T3 update: confirmed (candidate row 13). `../plugins/hyperpress-sponsor/src/Content.php:17` declares `add_content_template( $content_template ): string` and returns `$content_template` unchanged (line 25) for non-sponsor posts. With the WP_Post passed by front-page.php:91 that is a TypeError ("Return value must be of type string, WP_Post returned") whenever the sponsor plugin is active and the front page lists any non-sponsor CPT. The two other callers (single.php:25, posts.php:15) pass `''` and are fine. Not executed at runtime. Also note the Customizer section `hyperpress_homepage` that holds these controls is not registered in this theme (see T35).

### T4 (medium, hardening) Unvalidated `include` of a filter-returned path
- Files: single.php:25-27, template-parts/posts.php:15-17, front-page.php:91
```php
$content_template = apply_filters( 'content_template', '' );
if ( ! empty( $content_template ) ) {
	include $content_template;
}
```
- Impact: only `! empty()` is checked. Not directly reachable by visitors. It becomes local file inclusion, and arbitrary PHP execution of any readable file, if any hooked callback derives the path from tainted data (post meta, query var). A non-string or non-file value raises a warning or Error. The callbacks in `../plugins` were not read.
- Fix: a `hyperpress_include_content_template()` helper that requires `is_string`, `.php` extension, `is_file`, and a `realpath` under `get_stylesheet_directory()`, `get_template_directory()` or `WP_PLUGIN_DIR`. Otherwise fall back to `get_template_part`.
- Test: unit test. Filter returns `'/etc/passwd'`, `'../x.php'`, an array and a WP_Post: none is included and the fallback part is used. A valid in-theme path is included.
- T4 update: the callbacks I could find (`../plugins/hyperpress-sponsor/src/Content.php:19-21`, the newsletter equivalent per `tests/integration/NewsletterContentTemplateTest.php`) return `dirname( __DIR__ ) . '/templates/content.php'` guarded by `file_exists`, a fixed path with no tainted input. Not exploitable today; keep as hardening. Severity stays medium only because of the include-of-arbitrary-string contract. Arguably low.

### T5 (medium) Unescaped Customizer colour mods printed into inline `<style>`
- File: library/root-colors.php:13-20 (blanket `phpcs:disable` at line 10)
```php
echo '<style id="hyper-colors-css">' . ':root {
    --hyper-gear-blue: ' . get_theme_mod( 'hyperpress_gear_blue' ) . ';
    --hyper-gear-orange: ' . get_theme_mod( 'hyperpress_gear_orange' ) . ';
    ... (8 vars total)
```
- Impact: a value like `red;}</style><script>...` breaks out of the style element and runs script on every page, if the setting lacks a `sanitize_callback` or the control allows free text. Trigger needs `edit_theme_options` (admin). On single-site, admins already have `unfiltered_html`, so the gain is small. On multisite, or with `DISALLOW_UNFILTERED_HTML`, this is stored XSS. The `customize/*-colors.php` sanitize callbacks were not read (unverified).
- A separate correctness problem: unset mods print `--x: ;`. That is a valid empty custom property and defeats `var(--x, fallback)`.
- Fix: at output, `$c = sanitize_hex_color( get_theme_mod( $key ) )` and emit only non-empty values. Add `'sanitize_callback' => 'sanitize_hex_color'` to each setting. Remove the blanket phpcs disable.
- Test: unit test with `set_theme_mod('hyperpress_gear_blue', 'red;}</style><script>x</script>')` and capture `wp_head` output. Assert no `<script` and no `</style>` other than the closing tag, and that unset mods emit no variable.
- T5 update: RESOLVED to low. All 8 colour settings (3 in `library/customize/gear-colors.php:17,37,57`, 5 in `library/customize/hyper-colors.php:17,38,59,80,101`) set `'sanitize_callback' => 'sanitize_hex_color'` and use `WP_Customize_Color_Control`. A Customizer save can therefore only store `#rgb`/`#rrggbb` or an empty string, so the Customizer path is not an XSS vector. What remains: (a) `root-colors.php:10` is a blanket disable on unescaped output, which is safe only while that invariant holds; a direct `set_theme_mod()` or DB write bypasses the sanitizer; (b) unset mods still print `--x: ;` (theme-activation.php sets defaults only on theme switch). Fix as proposed (sanitize at output, skip empties).

### T6 (medium) jQuery and FontAwesome JS from a CDN with no SRI, and WP jQuery deregistered in wp-admin
- File: library/enqueue-scripts.php:49-51, 61, 78, 98-106
```php
wp_deregister_script( 'jquery' );   // front end, and again in admin_enqueue_scripts
wp_enqueue_script( 'jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/' . $jquery_version . '/jquery.min.js', array(), ..., false );
wp_enqueue_script( 'fontawesome', 'https://cdnjs.cloudflare.com/.../js/all.min.js', ... );
```
- Impact: a CDN compromise or MITM runs script in every page. In wp-admin, that means admin sessions, which is a full-site takeover vector. There is no `integrity`/`crossorigin` attribute. The admin replacement also drops WP's `jquery` alias (`jquery-core` + `jquery-migrate`), so core and plugin admin scripts that depend on `jquery-migrate` or on `jquery-core` by handle can break. Admin deregistration is needed by nobody on the front end. Trigger: third-party CDN compromise (not user-triggerable).
- Fix: do not touch `jquery` in `admin_enqueue_scripts`. Self-host (bundle) jQuery and FA, or add SRI via the `script_loader_tag` and `style_loader_tag` filters.
- Test: unit test runs `do_action('admin_enqueue_scripts')` and asserts `wp_scripts()->registered['jquery']->src` is unchanged. A test over the front-end queue asserts every external `src` has an `integrity` attribute in the rendered tag.

### T7 (low) Countdown ids from a theme mod are concatenated into a shortcode string without a cast
- File: front-page.php:33-34 (candidate row 3 confirmed: NOT cast to int)
```php
foreach ( get_theme_mod( 'hyperpress_homepage_customize_countdowns' ) as $countdown_id ) :
	echo do_shortcode( '[hyperpress_countdown id="' . $countdown_id . '" /]' );
```
- Impact: a value containing `"` or `]` can add shortcode attributes or chain other shortcodes (shortcode injection). Admin-only (`edit_theme_options`). `foreach` over a non-array theme mod (for example a string) raises a warning. The setting's `sanitize_callback` was not read (unverified), and `$countdowns` is only checked with `! empty`.
- Fix: `foreach ( (array) $countdowns as $id ) { $id = absint( $id ); if ( $id ) echo do_shortcode( sprintf( '[hyperpress_countdown id="%d" /]', $id ) ); }`.
- Test: unit test sets the mod to `['1" x="y', '2']` and asserts only `id="1"`-free, integer ids reach `do_shortcode`.

### T8 (low) `template-parts/banner.php`: class attribute echoed unescaped, missing array keys from the filter
- File: template-parts/banner.php:37, 29-34
```php
$banner['type'] = sanitize_text_field( $banner['type'] );
<header role="banner" class="banner <?php echo $banner['type']; ?>"
```
- Impact: `sanitize_text_field` strips tags but keeps `"`, so a `type` value containing `"` breaks out of the class attribute. The value comes from the `hyperpress_banner_content` filter (plugins and `library/banner/*` routers, which were not read), so it is not visitor-controlled unless a router copies request data. The other echoes are sanitized at lines 29-34 (title/text tags stripped, `sanitize_url` removes `"` and `<`, `sanitize_hex_color` is strict), so they are false positives. If the filter returns an array without `backgroundColor`, `buttonText` or `buttonLink`, lines 32-34 raise "Undefined array key" warnings.
- Fix: use `esc_attr()` for type, `esc_url()` for the link, `esc_html()` for title and button text; `wp_parse_args( $banner, $defaults )` after the filter.
- Test: unit test with the filter returning `['type' => 'x" onmouseover="y']` and asserting the attribute cannot break out; a second test with a partial array asserts no notices.

### T9 (low) `hyperpress_asset_path()` throws a TypeError on an invalid manifest
- File: library/enqueue-scripts.php:25-31
```php
$manifest = json_decode( file_get_contents( $manifest_path ), true );
...
if ( array_key_exists( $filename, $manifest ) ) {
```
- Impact: an empty or corrupt `rev-manifest.json` makes `$manifest` null, and `array_key_exists(): Argument #2 must be of type array, null given` is a fatal error on every page (PHP 8). Also, `file_get_contents` runs up to 4 times per request without caching. Not visitor-triggerable (build artifact).
- Fix: `if ( ! is_array( $manifest ) ) { $manifest = array(); }`; cache in a static.
- Test: unit test writes an empty manifest file and asserts `hyperpress_asset_path('app.css')` returns `'app.css'`.
- **FIXED**: hyperpress_asset_path() ignores a manifest that is empty, null, a JSON scalar or invalid JSON (falls back to the plain filename) and reads each manifest once per request through a static cache; covered by AssetPathTest.

### T10 (low) `main-stylesheet` depends on the `fontawesome` handle, which is not registered when FONT_AWESOME_OFFICIAL_LOADED is defined
- File: library/enqueue-scripts.php:59-63, 66-75
- Impact: WP skips (does not print) a style whose dependency is unregistered, so the whole theme CSS can vanish if that constant is ever defined without the theme's `fontawesome` handle registered. Whether the official plugin defines that constant, and under which handle it registers, was not verified. If it is never defined, the branch is dead code.
- Fix: only add `'fontawesome'` to the dependency list if `wp_style_is( 'fontawesome', 'registered' )`.
- Test: unit test defines the constant and asserts `wp_styles()->registered['main-stylesheet']` is printable (`wp_style_is( 'main-stylesheet', 'done' )` after `wp_print_styles`).
- **FIXED**: `hyperpress_main_style_deps()` lists `fontawesome` as a dependency of `main-stylesheet` only when that handle is registered.

### T11 (low) Small enqueue defects
- File: library/enqueue-scripts.php:55-56, 62, 116
```php
wp_register_style( 'oswald', '...family=Oswald:wght@200..700&display=swa' ); // phpcs:ignore ...MissingVersion -- theme audit
wp_enqueue_style( 'fontawesome', '...all.min.css', array(), $font_awesome_version, true );
if ( get_current_screen()->base == 'post' ) {
```
- `display=swa` is a typo for `swap` (the second font URL has it right).
- The omitted version argument (the suppressed phpcs rule) is a real, small defect: `ver=false` appends the WordPress version to the Google Fonts URL, which contradicts cleanup.php's version hiding. Pass `null`.
- `true` is passed as `$media` (prints `media="1"`). Use `'all'`.
- `get_current_screen()` can return null in some admin contexts, which raises "Attempt to read property on null" (PHP 8). Add a null check.
- Generic handles (`foundation`, `fontawesome`, `oswald`, `opensans`, `main-script`) can collide with plugins (`wp_register_*` silently keeps the first). Prefix with `hyperpress-`.
- Test: unit test on the registered src of `oswald` (`display=swap`, no `ver=`); call the admin hook with `$current_screen` unset and assert no warning.

### T12 (low) cleanup.php removes `rel_canonical` and feed links
- File: library/cleanup.php:39-42, 57
```php
remove_action( 'wp_head', 'feed_links_extra', 3 );
remove_action( 'wp_head', 'feed_links', 2 );
remove_action( 'wp_head', 'rel_canonical', 10 );
```
- Impact: removing the canonical link hurts SEO (duplicate URLs with `?replytocom`, pagination, tracking parameters) unless an SEO plugin emits it. Removing `feed_links` only removes the autodiscovery `<link>`; the feeds still exist. Likely a FoundationPress inheritance. Intentional-ness is unknown. No security impact. Not a defect if an SEO plugin always runs.
- Fix: keep `rel_canonical` (delete line 57) unless a plugin owns it. Document the feed removal.
- Test: assert `has_action('wp_head','rel_canonical')` is truthy, or that `wp_head` output contains a canonical link on a singular view.

### T13 (low) cleanup.php: ineffective and dead removals
- File: library/cleanup.php:24, 48-54, 72
```php
add_filter( 'wp_head', 'hyperpress_remove_wp_widget_recent_comments_style', 1 );
remove_action( 'wp_head', 'index_rel_link' );   // + parent_post_rel_link, start_post_rel_link
remove_action( 'wp_print_styles', 'print_emoji_styles' );
```
- `print_emoji_styles` was deprecated in WP 6.4; emoji styles are enqueued via `wp_enqueue_emoji_styles` on `wp_enqueue_scripts`, so line 72 no longer works. Emoji staying on is the safe outcome.
- `index_rel_link`, `parent_post_rel_link`, `start_post_rel_link` no longer exist in core (dead no-ops).
- Line 24 uses `add_filter` on an action hook; the callback returns void and `wp_widget_recent_comments_style` is not hooked by modern core (dead).
- Emoji removal is also incomplete (no admin print hooks, feeds, email, TinyMCE). REST, embeds and oEmbed are NOT touched in this file (candidate row 8 mentioned them: rejected).
- Fix: `remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' )`; delete the dead lines.
- Test: assert `! has_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' )` after `init`.

### T14 (low) front-page.php query hygiene
- File: front-page.php:23-33, 45-80, 86-105
- Secondary loop over `$the_query` never calls `wp_reset_postdata()`, so `$post` stays as the last listed post for `get_sidebar()` and the footer.
- The combined custom-post-type results are unpaginated, and `found_posts` and `max_num_pages` stay stale.
- `category__in` receives `false` when the mod is unset. WP tolerates it, but it is not an array.
- `get_theme_mod( ... )` in the countdown loop is read twice (see T7).
- Fix: call `wp_reset_postdata()` after the loop; coerce mods with `(array)`.
- Test: integration test on the front page asserting `get_the_ID()` after the loop equals the queried page id.

### T15 (low) nggallery: DivisionByZeroError / TypeError on thumbnail aspect ratio (unverified source of settings)
- File: nggallery/album-compact.php:48 (from grep)
```php
style="aspect-ratio:<?php echo $album_settings['thumbnail_width'] / $album_settings['thumbnail_height']; ?>"
```
- Impact: in PHP 8, `/ 0` throws DivisionByZeroError and a non-numeric string operand throws TypeError, so a zero or empty NextGen thumbnail-height option fatals the album page. Who can set it: NextGen admin. The unescaped echo is numeric but unvalidated.
- Fix: `max( 1, (int) $h )` guard, print with `absint`/`(float)`.
- Test: render the template with height `0` and with `''`; assert no exception.
- **FIXED**: The album placeholder ratio now comes from hyperpress_nextgen_ratio() (library/helpers.php), which returns 1.0 unless both values are numeric and positive; the settings chain is read with ?? and the value printed through esc_attr(); covered by NextGenRatioTest (the template itself needs NextGen and is not rendered).

### T16 (low) nggallery: NextGen fields echoed unescaped (unverified)
- File: nggallery/album-compact.php:29, 33, 53, 65, 71 (from grep; the file was not read)
```php
<?php echo $album_name; ... echo $album_desc; ... echo $gallery->title; ... echo $author_displayname; ... echo $gallery->galdesc; ?>
```
- Impact: stored XSS if NextGen does not sanitize at save time and the editor lacks `unfiltered_html` (multisite). `display_name` is filtered by `wp_filter_kses` on save for such users, so the author field is the lowest risk. Needs reading how NextGen stores these fields.
- Fix: `esc_html()` for names/titles and `wp_kses_post()` for descriptions. `(int)` for counter.
- Test: render with `title = '<script>x</script>'` and assert the script tag is not in the output.
- T15/T16 update: `nggallery/album-compact.php` read in full (78 lines); lines confirmed as cited (29, 33, 48, 53, 58, 65, 71). `Router::esc_url()` at 42-43, 53 is NextGen's own helper (false positive). Line 58 `$gallery->counter` is an NGG integer and the `nggallery` text domain is intentional (false positives). `$album->display_type_settings['photocrati-nextgen_basic_compact_album']` (46) is an unguarded array chain, same crash class as T15. Separately `nggallery/gallery.php` is an empty guard-only file, see T42.

### T17 (low) breadcrumbs/tag.php uses the removed `get_terms( $taxonomy, $args )` signature
- File: library/breadcrumbs/tag.php:15 (from grep)
```php
$terms = get_terms( $taxonomy, $args ); // phpcs:ignore ...Get_termsParam2Found -- theme audit
```
- The suppression hides a real defect: positional taxonomy is deprecated since WP 4.5 and calls `_deprecated_argument()` on every tag archive. With WP_DEBUG_DISPLAY on, a notice is printed into the page.
- Fix: `get_terms( array_merge( array( 'taxonomy' => $taxonomy ), $args ) )`.
- Test: `$this->setExpectedDeprecated()` is NOT expected; the test calls the breadcrumb on a tag archive and asserts no `_deprecated_argument` fired.
 - **FIXED**: the tag breadcrumbs now call get_terms() with the single-array signature and return early on a WP_Error or empty result (the legacy form was not actually deprecated-notice-emitting in this WP version, but an unknown tag crashed with an undefined offset).

### T18 (low) class-hyperpress-comments.php: date string used as a printf format, `htmlspecialchars` instead of `esc_url`
- File: library/class-hyperpress-comments.php:83, 102 (from grep)
```php
<time datetime="<?php echo comment_date( 'c' ); ?>">...<?php printf( get_comment_date(), get_comment_time() ); ?>
<a href="<?php echo htmlspecialchars( get_comment_link( get_comment_ID() ) ); ?>">
```
- `printf()` with a variable format: the time argument is ignored, and an admin date format containing `%` raises ArgumentCountError or garbage. `comment_date()` already echoes, so `echo comment_date(...)` relies on it returning nothing. `htmlspecialchars` on a URL is safe in this attribute (link is site-generated); `esc_url` is the convention.
- The `$GLOBALS['comment']` / `comment_depth` writes (lines 39, 49, 60, 61) are the standard Walker_Comment pattern: false positives.
- Test: render a comment with date format `%s %d`; assert no error and the time is shown.
- T18 update: correction. `printf()` with surplus arguments does not throw in PHP 8 (ArgumentCountError only when there are FEWER arguments than placeholders), so a `%` in the date output is the only failure path and is rare. The real effect is that `get_comment_time()` is silently never displayed (line 83). Also, `__construct` (lines 27-34) and `__destruct` (130-137) echo markup as side effects and `start_el`/`end_el` echo instead of appending to `$output`, so `wp_list_comments( array( 'echo' => false ) )` returns an empty string. `comments.php:23` always echoes, so no live impact. Line 79 `__( '<cite class="fn">%s</cite>' )` is safe: `get_comment_author_link()` escapes. Still low.

### T19 (low) footer.php: theme-mod-driven values echoed unescaped (unverified)
- File: footer.php:29, 103, 104 (from grep; file not read)
```php
<h6><?php echo $copyright_name; ?></h6>
target="<?php echo $target; ?>"
<i class="<?php echo $social['icon']; ?> fa-inverse" ...
```
- Impact: attribute-context breakout for `$target` and `$social['icon']` if the Customizer control is free-text with no sanitize_callback (customize/copyright.php and hyperpress.php not read). Admin-only trigger (`edit_theme_options`).
- Fix: `esc_html`, `esc_attr`; whitelist `$target` to `_self|_blank`; sanitize `icon` with `sanitize_html_class`.
- Test: set the mods to `"><script>` strings and assert escaped output.
- T19 update: mostly a false positive, downgraded to info. footer.php read in full. `$social['icon']` (line 104) is a hard-coded literal from the array at lines 36-94, never user data. `$target` (line 100) is always the literal `_self` or `_blank`. The `url` values are `esc_url()`'d (line 102) and come from the `hyperpress_socials_*` settings registered in `../plugins/hyperpress-socials/src/Customizer.php`. Only `$copyright_name` (line 29) is real: its setting has no sanitize_callback. That is now T29. Minor: `target="_blank"` with no `rel="noopener"` (modern browsers imply it).
- **FIXED**: Escaped the remaining footer values at output (`esc_attr` for the target and icon class), dropped their phpcs:ignore comments and added FooterEscapingTest; the copyright name was already sanitized and escaped under T29.

### T20 (low) Late-escaping gaps for trusted strings
- Files: searchform.php:15, 404.php:35-36, header.php:35, template-parts/content-none.php:28-29, 36, 41, comments.php:57, 63, front-page.php:109
```php
<form ... action="<?php echo home_url( '/' ); ?>">          // searchform.php:15
<button aria-label="<?php _e( 'Main Menu', 'hyperpress' ); ?>"   // header.php:35
```
- `home_url()` and `admin_url()` are not request-derived in core, but a `wp-config.php` that builds `WP_HOME` from `$_SERVER['HTTP_HOST']` would make the search form a reflected XSS. `_e()` inside an attribute (header.php:35) should be `esc_attr_e`. The `_e` calls in element context, and `die( __() )` in comments.php, are low-risk and only matter for untrusted translations.
- `get_permalink( get_option( 'page_for_posts' ) )` at front-page.php:109 returns the current post permalink when the option is 0; harmless but unescaped.
- The searchform input value (likely `get_search_query()`) and its escaping were not checked (file not read).
- Fix: `esc_url`, `esc_attr_e`, `esc_html_e`, and `esc_url( get_permalink(...) )`.
- Test: phpcs `WordPress.Security.EscapeOutput` with no `-- theme audit` suppressions on these lines (run via `composer lint`).

### T21 (info) foundation.php:145-154 menu fallback prints admin URLs (unverified)
- `get_admin_url( ..., 'nav-menus.php' )` and `customize.php` links are rendered by the no-menu fallback. If there is no `current_user_can( 'edit_theme_options' )` gate (not visible from the grep), anonymous visitors see admin links. The links are not secrets. Needs a read of lines ~140-160.

- T21 update: `hyperpress_menu_fallback()` is never called. `grep` finds only its definition (foundation.php:141). `library/navigation.php:36,54` pass `'fallback_cb' => false`. Unreachable, so no exposure today. Delete it. See T41.

### T22 (info) Protocol-relative asset class (candidate row 9), body unverified
- File: library/class-hyperpress-protocol-relative-theme-assets.php:53-88 (only the method signatures came through the grep)
- Hooks `style_loader_src`, `script_loader_src`, `template_directory_uri`, `stylesheet_directory_uri` to strip the scheme. The HTTP-then-HTTPS mixed-content use case is obsolete. Risk: `//host/...` is not a usable URL in feeds, emails, REST responses, cron and server-side `wp_remote_get`/`file_get_contents( get_template_directory_uri() )` consumers. The four `UnusedFunctionParameter` suppressions are false positives. Needs reading the body before assigning a severity.

### T23 (info) False-positive `theme audit` suppressions (confirmed or likely benign)
| Suppressed code | Verdict |
|---|---|
| functions.php:80, library/plugins.php:257, theme-activation.php:37, foundation.php:391 (`CommentedOutCode`) | false positive (comments); delete the dead code instead |
| content.php:45, content-page.php:29 (`$tag = get_the_tags()` "GlobalVariablesOverride") | false positive: template files run inside `load_template()`, so `$tag` is local |
| class-hyperpress-comments.php:39, 49, 60, 61 (`$GLOBALS['comment*']`) | false positive: Walker_Comment pattern |
| front-page.php:58, 90; foundation.php:96, 98, 108, 110, 123, 164, 225, 338, 354; enqueue-scripts.php:116; labels/post.php:55 (loose comparisons) | false positive (string vs string / int); low style only (enqueue-scripts:116 also has the T11 null issue) |
| foundation.php:74, 134 (`echo $paginate_links` / comments pagination) | false positive: core `paginate_links()` output is escaped |
| responsive-images.php:73, class-hyperpress-protocol-relative-theme-assets.php:53, 64, 76, 88 (`UnusedFunctionParameter`) | false positive: filter signatures |
| post-navigation.php:9-10, responsive-images.php:37-40 (`MissingArgDomain`) | low: strings fall back to core's domain, are not in the theme .pot, and are not translated as intended |
| content-none.php:18, 36, 41; 404.php:20, 24, 26, 29, 41; comments.php:63; header.php:35 (`UnsafePrintingFunction`) | low: see T20 (`javascript:history.back()` at 404.php:41 is also CSP-hostile) |

- T22 update: body read. The class is NOT loaded: the only `require_once` is commented out at functions.php:80-81. Dead code, no effect. If it were enabled, `preg_replace( '(https?://)', '//', $url )` is unanchored and would also rewrite URLs embedded in query strings. Delete the file. See T41.

## Rejected candidates

- Row 7, `.idea/` and `config.yml` tracked: neither appears in `git ls-files`, so rejected. `config-default.yml` is intentionally tracked (default config). `.travis.yml` is tracked but documented dead (AGENTS.md); no secret check was done (file not read). `.env` is tracked: kept as T2.
- Row 8, "emoji, feeds, REST, embeds": cleanup.php contains no REST or embed removal. The emoji removal is partly ineffective (T13), and canonical/feed removal is T12.
- Row 11, "banner/breadcrumb routers echo values": `template-parts/banner.php` sanitizes every field before echo (lines 29-34). Only the class attribute (T8) is a residual; title/button are safe in element and href contexts. The routers in `library/banner/*` and `library/breadcrumbs/*` were not read, so their own echoes are open.
- Row 3: not rejected (confirmed, T7); the severity is low, not higher, because the write path is admin-only.
- Row 2: not rejected (T4), but not critical/high because the filter is a code-level contract.

## Not assessed (needs a re-dispatch)

Row 6 (Customizer sanitize_callback audit of `library/customize/*.php`), row 9 (body), row 12 (`woocommerce.php` dead code), input handling (`$_GET/$_POST/$_REQUEST/$_SERVER` search), AJAX and nonce search, `library/metaboxes/*` (save handlers need nonce + capability checks), the TGM config in `library/plugins.php`, `library/labels*`, `library/banner/*`, `library/breadcrumbs/*` (except one line), walkers, widgets, `searchform.php` value handling, `comments.php`, `functions.php`, and PHP 8.5-specific deprecations (none found in the files read).

## Repository hygiene note

`git check-ignore -v .superpowers/audit/theme.md` exited 1 after writing, so this report is NOT git-ignored in the theme repo. Add `.superpowers/` to `.gitignore` or keep the file out of commits (use explicit pathspecs).

Update: `git check-ignore -v .superpowers/audit/theme.md` now reports a match from `.git/info/exclude:7:.superpowers/`, so the report is locally ignored (not via the tracked `.gitignore`, so other clones are not protected).

## Remaining areas

Second pass. Numbering note: the brief says start at T20, but T20-T23 were already used above, so new findings start at T24. Earlier entries are only annotated with `T<n> update:` lines.

### Coverage

Read in full this pass: `library/customize/*` (5 files), `library/customize.php`, `library/banner.php` and `library/banner/*` (14), `library/breadcrumbs.php` and `library/breadcrumbs/*` (11), `library/labels.php`, `library/labels/post.php`, `library/metaboxes.php` and `library/metaboxes/*` (3), `library/foundation.php`, `library/custom-nav.php`, `library/navigation.php`, `library/plugins.php` (excluding the TGM class), `library/class-hyperpress-{comments,mobile-walker,top-bar-walker,protocol-relative-theme-assets}.php`, `library/class-walker-category-dropdown-multi.php`, `library/{widget-areas,sticky-posts,responsive-images,gutenberg,theme-activation,theme-support,root-colors}.php`, `functions.php`, `header.php`, `footer.php`, `searchform.php`, `comments.php`, `woocommerce.php`, `404.php`, `archive.php`, `index.php`, `page.php`, `search.php`, `sidebar.php`, all of `template-parts/*`, `page-templates/page-{full-screen,full-width,sidebar-left}.php`, `nggallery/*`, `template-parts/banner.php` and `front-page.php:10-114` (re-read). `page-templates/kitchen-sink.php` (990 lines) was only grepped for PHP tags: it has 5 PHP calls (`the_title`, `post_class`, `the_ID`, `the_content`, loop), the rest is static HTML, no finding. `library/class-tgm-plugin-activation.php`, `cleanup.php` and `enqueue-scripts.php` were not re-read (the latter two were covered by the first pass).

Greps (tracked `*.php`, excluding vendor/tests/docs/TGM class): `$_GET|$_POST|$_REQUEST|$_SERVER|$_COOKIE|$_FILES|$_SESSION` has 0 hits. `wp_ajax_|check_ajax_referer|wp_verify_nonce|wp_nonce|register_rest_route|admin_post_|admin_init|save_post` has 0 hits. The only `current_user_can` is `template-parts/content-none.php:22`. So the theme has no AJAX/REST/form handlers and no request-input handling, and nonce checks are not applicable; all saves go through Meta Box and the Customizer. `eval|unserialize|exec|system|wp_remote_|curl_` has 0 hits; `file_get_contents` has one hit (enqueue-scripts.php:25, a local manifest, no user path). `include`/`require` with a variable: `front-page.php:91`, `single.php:27`, `template-parts/posts.php:17` only (T3/T4). All other `require_once` take string literals (T40).

Candidate rows: row 5 (root-colors XSS) is T5, downgraded to low. Row 6 (Customizer sanitize) is T29, T35, T34 and the T5 update. Row 8 was rejected earlier. Row 9 is the T22 update (dead code). Row 11 (routers echo) is T24, T25, T32 and the T37 note. Row 12 (woocommerce.php dead code) is rejected: see Rejected candidates. Row 13 is the T3 update (confirmed).

### T24 (medium) Visitor-controlled search string is run through `do_shortcode()` in the banner
- Files: library/banner/search.php:13; template-parts/banner.php:31, 50
```php
$banner['subtitle'] = __( 'Results for', 'hyperpress' ) . ' <span class="emphasis">"' . get_search_query() . '"</span>';   // search.php:13
$banner['subtitle'] = sanitize_text_field( $banner['subtitle'] );                                                        // banner.php:31
<h4 class="subtitle"><?php echo do_shortcode( $banner['subtitle'] ); ?></h4>                                              // banner.php:50
```
- Impact: `get_search_query()` is `esc_attr()`'d, which escapes `<>&"'` but not `[` or `]`. `sanitize_text_field` leaves brackets too. So `GET /?s=[any_registered_shortcode a=b]` is executed on the search page by an unauthenticated visitor. Impact depends on the installed shortcodes (every shortcode that echoes an attribute unescaped, loads a URL, or queries data becomes a reflected injection sink; the HYPER plugins register several). Not executed at runtime. Same pattern, with lower trust (editors), for category/tag names (banner/category.php:12, tag.php:12) and author names (author.php:13), which are also concatenated into the subtitle.
- Fix: run `do_shortcode()` only on subtitles that come from Meta Box content (page.php/post.php/blog.php routers), not on router-generated text. Or neutralise brackets in the untrusted parts: `str_replace( array( '[', ']' ), array( '&#91;', '&#93;' ), $value )`. Escape once, at output, with `wp_kses( ..., array( 'span' => array( 'class' => true ) ) )` (see T25).
- Test: integration test. Request `/?s=[hyperpress_countdown id="1"]` (or a test shortcode that records calls). Assert the shortcode callback is NOT invoked and the banner contains the literal text `[hyperpress_countdown`.

### T25 (low) `sanitize_text_field` strips the `<span class="emphasis">` markup every banner router produces
- File: template-parts/banner.php:31 (routers: banner/{author,category,day,month,search,tag,year}.php; customize default in library/theme-activation.php:26; metabox std at library/metaboxes/homepage-customize.php:35)
```php
$banner['subtitle'] = sanitize_text_field( $banner['subtitle'] );
```
- Impact: `sanitize_text_field()` calls `wp_strip_all_tags`, so the `.emphasis` span is always removed and the design intent ("Category: <emphasis>News</emphasis>") is lost. Also `rwmb_meta()` on a wysiwyg field returns formatted HTML that is then stripped. No security effect; it is also why the search string (T24) is not an HTML injection vector.
- Fix: `wp_kses( $banner['subtitle'], array( 'span' => array( 'class' => true ), 'strong' => array(), 'em' => array(), 'br' => array() ) )`, and escape the dynamic pieces (`esc_html`) inside the routers.
- Test: unit test. Banner for a category archive outputs `<span class="emphasis">`; a subtitle of `<script>x</script>` outputs no `<script`.

### T26 (medium) comments.php renders approved comments of password-protected posts before the password check
- File: comments.php:16-49 (render) vs 59-68 (check)
```php
if ( have_comments() ) : ... wp_list_comments( array( 'walker' => new HyperPress_Theme_Comments(), ...
...
if ( post_password_required() ) {   // line 59, reached only after the list was printed
    ... return;
```
- Impact: core's `comments_template()` does not check the post password, so themes must. Here the comment list (author names, avatars, comment text) is printed for anonymous visitors even when the post content is behind a password. Any visitor can read approved comments on a protected post. Trigger: `GET` of a password-protected single post or page with approved comments (page.php:29, page templates, single.php). Not executed at runtime.
- Fix: move the `post_password_required()` block to the top of the file, as in the default themes. Delete the dead `defined( 'ABSPATH' ) || die(...)` at line 57 (duplicate of line 13).
- Test: integration test. Create a password-protected post with one approved comment, request it without the cookie, assert the body does not contain the comment text. With the password cookie set, assert it does.

### T27 (medium) TGMPA installs an unpinned plugin ZIP over plain HTTP
- File: library/plugins.php:74 (also 81, 206)
```php
'name'   => 'RunCache - Full Page Cache',
'source' => 'http://runcache.site/download/runcache-latest.zip',
```
- Impact: TGMPA downloads this ZIP when an admin clicks "Begin installing plugin" and activates PHP code from it. Over `http://` an on-path attacker can swap the archive, which is remote code execution in the site context. `latest.zip` is unpinned and has no checksum, so even over TLS the vendor (or anyone who compromises it) controls what runs. Needs admin action plus a network position. Line 81 is pinned (`runcloud-hub-1.4.8.zip`) and HTTPS but has no checksum. Line 206 `'source' => '#'` for a `required => true` plugin is not a valid source and leaves a permanent install nag (not verified against TGMPA's source resolution).
- Fix: use `https://` only, pin a version, and verify integrity (host the ZIP yourself and compare a hash before install), or drop `source` and install manually from the vendor. Set `'required' => false` for anything with no downloadable source.
- Test: unit test (as `tests/unit/PluginsListTest.php` does) that parses `library/plugins.php` and asserts every `'source'` is `https://` or empty, and none ends in `latest.zip`.

### T28 (medium) Gallery shortcode puts the attachment caption into HTML attributes with only `wptexturize()`
- File: library/foundation.php:352, 376
```php
$link = str_replace('<a href', '<a class="thumbnail fp-gallery-lightbox" data-gall="fp-gallery-' . $post->ID . '" data-title="' . wptexturize($attachment->post_excerpt) . '" title="' . wptexturize($attachment->post_excerpt) . '" href', $link);
$link = str_replace('<a href', '<a class="thumbnail" title="' . wptexturize($attachment->post_excerpt) . '" href', $link);
```
- Impact: `wptexturize()` is not an escaping function. It converts straight quotes to entities in normal text, but it skips the contents of `<pre>`, `<code>`, `<kbd>`, `<style>`, `<script>` and `<tt>`. An Author (has `upload_files`, no `unfiltered_html`) can set an attachment caption such as `<pre>" autofocus onfocus="…` (`wp_filter_kses` keeps `<pre>` and leaves the quote in text). Rendered on any post that contains `[gallery]` (the theme replaces core's shortcode at foundation.php:208), that breaks out of `title="…"` and injects attributes on the `<a>`: stored XSS against visitors and admins who preview. I did not execute this; the `<pre>` bypass follows from wptexturize's documented skip list. The attack needs a user who can upload and insert a gallery, and no attack on single-user sites where the author is already admin. Related: `$post->ID` (line 352) is a null dereference when `get_post()` is empty (widget, excerpt, REST), and `$post` is read even when `link="file"` is not used.
- Fix: `esc_attr( $attachment->post_excerpt )` in both places (no `wptexturize`), and `$id`/`get_the_ID()` instead of `$post->ID`. Better: build the link with `wp_get_attachment_link()` arguments / `add_filter( 'wp_get_attachment_link_attributes' )` instead of `str_replace` on HTML. This shortcode also belongs in the planned shortcodes plugin.
- Test: integration test. Attachment with excerpt `<pre>" onfocus="x" autofocus x="` inside `[gallery ids="N"]`; assert the rendered HTML has no `onfocus` attribute (DOM parse) and the title attribute equals the escaped caption. A second case with no global `$post`.

### T29 (medium) Copyright name: no sanitize_callback, echoed raw in the footer
- Files: library/customize/copyright.php:9-16; footer.php:29
```php
$wp_customize->add_setting( 'hyperpress_site_copyright_name', array( 'default' => get_bloginfo( 'name' ), 'type' => 'theme_mod', 'transport' => 'refresh' ) );   // no sanitize_callback
<h6><?php echo $copyright_name; // phpcs:ignore ... -- theme audit ?></h6>
```
- Impact: with no sanitize_callback the Customizer stores whatever is submitted, and the footer prints it on every page. `edit_theme_options` holders without `unfiltered_html` (multisite site admins, or `DISALLOW_UNFILTERED_HTML`) can store script that runs for every visitor, including super admins. On a single-site install admins already have `unfiltered_html`, so the gain is nil there. This is the one real item among the footer suppressions (T19 update). `default => get_bloginfo( 'name' )` is captured at `customize_register` time and is fine.
- Fix: `'sanitize_callback' => 'sanitize_text_field'` on the setting, and `esc_html( $copyright_name )` in footer.php:29.
- Test: unit test. `set_theme_mod( 'hyperpress_site_copyright_name', '<script>x</script>' )` and assert the footer output has no `<script`. Check `WP_Customize_Manager::get_setting()->sanitize( '<img onerror=x>' )` returns a tag-free string.

### T30 (medium) Hard dependency on Meta Box and HYPER plugin classes with no guards
- Files: library/banner/{archive.php:18, blog.php:18,20, page.php:13,18,22, post.php:17,19,25} (`rwmb_meta()`); library/breadcrumbs/post.php:12 and library/labels/post.php:28 (`\HyperPress\Season\SortedSeasons::get()`); library/customize/homepage.php:8-9,34,55 (`CategoryDropdownControl`, `PostTypeDropdownControl`); front-page.php:33-34 (shortcode, safe)
```php
$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color' );   // no function_exists()
$seasons = \HyperPress\Season\SortedSeasons::get();                                  // no class_exists()
```
- Impact: `function_exists`/`class_exists` are never checked (grep: the only `function_exists` calls are for `hyperpress_pagination`, `the_custom_logo`, `add_revslider`). TGMPA (`library/plugins.php:66-70`) only nags for Meta Box AIO, it does not enforce. If Meta Box is deactivated, updated with a conflict, or not yet installed on a fresh site, every page, post, blog and archive view fatals with "Call to undefined function rwmb_meta()", and every single post view fatals if the plugin that defines `SortedSeasons` is inactive. The Customizer fatals on a missing control class (`new CategoryDropdownControl` in `customize_register`). Trigger: an admin deactivating a plugin, or a failed plugin update. `front-page.php:14` shows the pattern the rest of the theme should follow.
- Fix: thin wrappers in one place, e.g. `hyperpress_meta( $key, $post_id = null )` that returns `''` when `function_exists( 'rwmb_meta' )`, and `class_exists()` guards around the season and control classes.
- Test: integration test with the Meta Box and seasons plugins unloaded (`HYPERPRESS_TEST_WITHOUT_EXTRACTED=1`): request a page, a post, the blog page, an archive and the front page. Assert HTTP 200 and no fatal.

### T31 (low) `body_class()` is printed on `<html>`, and `<body>` has no classes or `wp_body_open()`
- File: header.php:17, 25
```php
<html <?php language_attributes(); ?> <?php body_class(); ?> >
...
<body>
```
- Impact: `body_class()` echoes `class="…"` so the `<html>` element gets the classes (`home`, `page-template-…`, `topbar`/`offcanvas` from custom-nav.php:66-75). Any CSS or JS that targets `body.offcanvas` or `body.home` does not match. There is no `wp_body_open()`, so plugins that inject at body start (consent banners, tag managers' noscript, skip links) are skipped.
- Fix: `<html <?php language_attributes(); ?>>` and `<body <?php body_class(); ?>>` followed by `<?php wp_body_open(); ?>`. Check `src/assets/scss` for selectors that rely on the current, wrong placement before changing.
- Test: integration test. Rendered header contains `<body class="…` and exactly one `class=` on `<html>` is absent; `did_action( 'wp_body_open' )` is 1.

- **FIXED**: `<html>` now carries only `language_attributes()`, `<body>` carries `body_class()` and is followed by `wp_body_open()`. The admin-bar offsets in `src/assets/scss/global/_wp-admin.scss` were moved from `html.admin-bar…` to `body.admin-bar…`, and the root height rule to `html:has(body.admin-bar)`. Compiling the SCSS before and after shows only those selectors changed, with identical declarations. `:has()` needs Chrome 105, Safari 15.4 or Firefox 121.

### T32 (low) Author banner title: operator precedence drops the prefix
- File: library/banner/author.php:12
```php
$banner['title'] = __( 'Published By ', 'hyperpress' ) . ( ! empty( get_the_author_meta('user_firstname') ) && ! empty( get_the_author_meta('user_lastname') ) ) ? get_the_author_meta('user_firstname') . ' ' . get_the_author_meta('user_lastname') : get_the_author_meta('display_name');
```
- Impact: `.` binds tighter than `?:`, so the condition is `( 'Published By ' . bool )`, a non-empty string, which is always truthy. The title is always `firstname lastname`, "Published By " is never shown, and when both names are empty the title is the single space `' '`, so the `display_name` fallback never runs and the author banner title is blank. The author, category and other subtitles also call `get_the_author_meta()` without an ID (outside the loop it depends on `$authordata`; unverified on an archive before `the_post()`). Correctness only.
- Fix: wrap the ternary: `__(...) . ( $cond ? $full : $display )`, and pass `get_queried_object_id()` to `get_the_author_meta()`.
- Test: unit test on an author archive with (a) both names, (b) neither. Expect "Published By First Last" and "Published By <display_name>".
 - **FIXED**: the ternary is now parenthesised so the title is always "Published By " plus the full name or the display_name fallback, and the author meta is read for the queried author ID.

### T33 (low) "Continue Reading" is a `<button href>` that does not navigate
- File: library/navigation.php:80
```php
return '<button class="hollow button medium-down-expanded more-link" href="' . get_permalink() . '#more-' . get_the_ID() . '">Continue Reading</button>';
```
- Impact: `href` is not a valid attribute on `<button>`; clicking does nothing (a `<button>` outside a form submits nothing). The read-more link is dead UI on any post using `<!--more-->`. The permalink is also not passed through `esc_url()` and the label is not translatable.
- Fix: `<a class="hollow button … more-link" href="' . esc_url( get_permalink() . '#more-' . get_the_ID() ) . '">' . esc_html__( 'Continue Reading', 'hyperpress' ) . '</a>'`.
- Test: integration test. `the_content()` for a post with a more tag; parse the DOM and assert an `a.more-link[href]`.
 - **FIXED**: the read-more filter now returns an <a class="hollow button medium-down-expanded more-link"> with an esc_url()'d permalink#more-ID href and a translatable label instead of a dead <button href>.

### T34 (low) Translated identifiers, and an unsanitized theme setting
- Files: library/navigation.php:50; library/custom-nav.php:40-45
```php
'menu' => __( 'mobile-nav', 'hyperpress' ),                        // navigation.php:50
$wp_customize->add_setting( 'wpt_mobile_menu_layout', array( 'default' => __( 'topbar', 'hyperpress' ) ) );   // no sanitize_callback
```
- Impact: `wp_nav_menu( 'menu' => … )` looks a menu up by id, slug or name, so a translation of `mobile-nav` makes the lookup miss and falls back to the location (fragile). The setting default `topbar` is compared against the literal `'topbar'`/`'offcanvas'` in header.php, footer.php and foundation.php:189-201; translating the default can make the comparison fail in a non-English locale. `wpt_mobile_menu_layout` has no sanitize_callback, so a crafted Customizer request can store any string; all consumers compare with `===` or print a fixed class, so no injection (header/footer/body_class), just a broken layout.
- Fix: literal `'mobile-nav'` and `'topbar'`; `'sanitize_callback' => static fn( $v ) => in_array( $v, array( 'topbar', 'offcanvas' ), true ) ? $v : 'topbar'`.
- Test: unit test. Switch locale with a `.mo` that translates `topbar`; assert `mobile_nav_class()` yields `topbar`. `$setting->sanitize( 'evil' )` returns `'topbar'`.

### T35 (low) Homepage Customizer settings: empty sanitize_callback, unregistered section and setting
- File: library/customize/homepage.php:14-24, 26-32, 46-53
```php
'sanitize_callback' => '',                       // hyperpress_home_blog_categories, hyperpress_home_blog_post_types
'section'  => 'hyperpress_homepage',             // never registered in this theme
'settings' => 'hyperpress_home_banner_button_text',   // no add_setting() in this theme
```
- Impact: (1) An empty callback means `WP_Customize_Setting::sanitize()` is a pass-through. The values feed `WP_Query` (`category__in`, `post_type` at front-page.php:47, 65). `post_type_exists()` (front-page.php:58) is the only check, so an admin can list a non-public CPT (for example an internal one) on the public home page. Admin-only (`edit_theme_options`). (2) `add_section( 'hyperpress_homepage' )` does not exist anywhere in the theme (`add_section` appears only for `hyperpress` and `mobile_menu_layout`), so these controls are not displayed unless another plugin registers the section (I did not find one in `../plugins` for `'hyperpress_homepage'`; only the Meta Box id `hyperpress_homepage` at metaboxes/homepage-customize.php:14, which is a different thing). The first control refers to a setting that is not registered, which can raise notices or drop the control. Net: these two theme mods probably cannot be edited at all, and front-page.php:47/50 silently uses `false`.
- Fix: register the section, add `add_setting( 'hyperpress_home_banner_button_text' … )`, and sanitize with `array_map( 'absint' … )` for categories and `array_filter( array_map( 'sanitize_key' … ), 'post_type_exists' )` (plus `get_post_type_object()->public`) for post types.
- Test: unit test. `$wp_customize->get_section( 'hyperpress_homepage' )` is not null; sanitizing `['post','shop_order','x"y']` returns only public, existing types.
- **FIXED**: Registered the `hyperpress_homepage` section and the banner button text setting, and gave the categories and post types settings sanitizers (existing category ids; registered public post types only).

### T36 (low) Undefined array keys / null dereferences in labels and breadcrumbs
- Files: library/labels/post.php:43-45; library/breadcrumbs/tag.php:16-33; library/breadcrumbs/category.php:12,24
```php
$categories = get_the_category();
$category   = $categories[0];            // read before the empty() check
if ( ! empty( $category ) ) {
...
$terms = get_terms( $taxonomy, $args );   // T17
'title' => $terms[0]->name,               // no check that $terms is a non-empty array
```
- Impact: a post whose category set is empty raises "Undefined array key 0" (PHP 8) before the guard runs. On a tag archive whose `tag_id` matches nothing, or if `get_terms` returns a `WP_Error`, `$terms[0]->name` and `get_tag_link( $terms[0] )` raise warnings and emit an empty crumb. `get_category()` can return a `WP_Error` (category.php:12), after which `$category->cat_name` warns. With WP_DEBUG_DISPLAY the warnings are printed into the page. The labels also hard-code English (`'Posted At'`, `' comments'`, labels/post.php:14,22,55) outside `__()`.
- Fix: `$category = $categories[0] ?? null;` `$term = get_queried_object(); if ( $term instanceof WP_Term ) { … }`; wrap labels in `__()`/`_n()`.
- Test: integration test. Post with `wp_set_post_categories( $id, array() )` and a tag archive for an empty term; assert no notices (`$this->setExpectedIncorrectUsage` not needed, convert warnings to exceptions).
- **FIXED**: The post label and the category breadcrumb now skip themselves when the category set is empty or get_category() returns null or a WP_Error (the tag breadcrumb was already guarded); covered by LabelsBreadcrumbsGuardTest. The hard-coded English label strings are not changed.

### T37 (low) Breadcrumb `href` uses `esc_attr()`, and the author crumb links to the author's own website
- Files: template-parts/breadcrumbs.php:28; library/breadcrumbs/author.php:19-21
```php
href="<?php echo esc_attr( $breadcrumb['url'] ); ?>"
'url' => get_the_author_meta('user_url'),
```
- Impact: `esc_attr` does not validate a URL scheme, so a crumb URL of `javascript:…` from any `hyperpress_breadcrumbs_content` filter callback would be rendered as a live link. All in-theme routers use `get_*_link()`, except the author crumb, which uses `user_url` (saved through `esc_url_raw`, so safe), and that is a bug of its own: the crumb for an author archive links to the author's personal website (or renders as a plain span when empty) instead of `get_author_posts_url()`. The crumb text uses `esc_attr()` in element context, which works but is the wrong function. Low.
- Fix: `esc_url( … )` for href, `esc_html` for the text, `get_author_posts_url( get_queried_object_id() )` for the author crumb.
- Test: unit test. A filter adds a crumb with `url => 'javascript:alert(1)'`; assert the href is empty or `about:blank`. Author archive crumb href equals `get_author_posts_url()`.

### T38 (low) Template parts read the global `$post` and pass an empty size
- Files: template-parts/featured-image.php:11; template-parts/content.php:30
```php
if ( has_post_thumbnail( $post->ID ) ) :
<?php the_post_thumbnail( '', array( 'class' => 'thumbnail' ) ); ?>
```
- Impact: `featured-image.php` is loaded from page.php/page templates, where the main query sets global `$post`; from any non-singular context (a plugin calling the part, or a 404) `$post` is null, giving "Attempt to read property 'ID' on null". `the_post_thumbnail( '' )` passes an empty size instead of `'post-thumbnail'`; I did not verify what size WordPress resolves it to (it may serve the full-size original on every archive/search listing). Unverified.
- Fix: `has_post_thumbnail()` with no argument (uses the loop post), and `'post-thumbnail'`/a registered size.
- Test: integration test. Render the part with `$post` unset, no notices. Inspect the `<img>` `src` of a listing item for a registered size suffix.

### T39 (low) searchform.php: whitespace inside the placeholder, duplicate ids
- File: searchform.php:17-21
```php
placeholder="
        <?php
		esc_attr_e( 'Search', 'hyperpress' );
        ?>
        ">
```
- Impact: the placeholder value is "newline + spaces + Search + newline + spaces". Browsers render the whitespace; some assistive tech reads it. `id="searchform"`, `id="s"`, `id="searchsubmit"` are duplicated if the form is rendered twice on a page (content-none.php:37 plus a sidebar search widget), which is invalid HTML and breaks label associations. The input `value=""` is hard-coded, so the current search is not reflected (no XSS); a UX gap only.
- Fix: one-line `placeholder="<?php echo esc_attr_x( 'Search', 'placeholder', 'hyperpress' ); ?>"`; drop or make the ids unique with `wp_unique_id()`.
- Test: unit test. Render `get_search_form( false )` twice; assert unique ids and a trimmed placeholder.

### T40 (info) Relative `require_once` paths resolve through `include_path` first
- Files: functions.php:22-77; library/banner.php:7-20; library/breadcrumbs.php:7-17; library/labels.php:7; library/metaboxes.php:7-9; library/customize.php:7-11 (contrast library/plugins.php:38 which uses `get_template_directory()`)
```php
require_once 'library/cleanup.php';
```
- Impact: a relative path is looked up in each `include_path` entry (`.` is the process CWD, normally the WordPress root) before the calling script's directory. It works, but costs extra stat calls, and a file named `library/cleanup.php` in the CWD or elsewhere on `include_path` would shadow the theme's. Needs a writable, unusual CWD, so hardening only.
- Fix: `require_once get_template_directory() . '/library/cleanup.php';` (or `__DIR__ . '/…'` in the `library/` subfiles).
- Test: lint test asserting no `require*`/`include*` with a bare string literal path in `functions.php` or `library/`.

### T41 (info) Dead and unreachable code
- Files: library/foundation.php:141 (`hyperpress_menu_fallback`, never called); library/class-hyperpress-protocol-relative-theme-assets.php (never loaded, functions.php:80-81); library/class-walker-category-dropdown-multi.php (no references, empty `// TODO` methods); library/theme-activation.php:40-51 (`hyper_clean_up_theme_mod_values`, its hook is commented out at line 38); library/responsive-images.php:70-79 (`remove_thumbnail_dimensions`: the regex is anchored with `^(width|height)=` but post-thumbnail HTML starts with `<img`, so it never matches); library/foundation.php:390-400 (commented-out caption block); `$config['strings']` block in plugins.php:257-333; library/plugins.php:45-48 (docblock says five plugins)
- Impact: none at runtime, except that the suppressions in the audit table hide them. Dead code is attack surface for later edits (T21/T22). The ones with a legacy prefix (`hyper_*`) also conflict with the naming rule in AGENTS.md.
- Fix: delete. For `remove_thumbnail_dimensions`, either delete or fix the regex (`preg_replace( '/\s(width|height)="\d*"/', '', $html )`).
- Test: a `composer lint` run with the corresponding `-- theme audit` ignores removed stays at 0 violations; a grep test that `hyperpress_menu_fallback` and the protocol-relative class file are gone.

### T42 (low) `nggallery/gallery.php` is an empty template that shadows NextGEN's own
- File: nggallery/gallery.php:1-5
```php
<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
```
- Impact: NextGEN looks for `nggallery/<template>.php` in the theme before its own view. This file outputs nothing, so a `[nggallery]` basic-thumbnail gallery (if it resolves to this override) renders an empty block. I did not verify which NGG template name maps to `gallery.php`. Also note the planned move of NextGEN code to a plugin (AGENTS.md "Direction").
- Fix: delete the file (let NextGEN use its default) or implement the template.
- Test: integration test with NextGEN active: render a gallery and assert that at least one `<img>` is in the output.

### T43 (info) Minor hygiene
- `template-parts/featured-image.php:13`: a second `role="banner"` landmark (the site header and template-parts/banner.php:37 already use one) and an empty `<header>`; invalid for accessibility checkers.
- `library/theme-support.php:70-73`: WooCommerce theme supports are declared although no WooCommerce code exists except the stock `woocommerce.php`.
- `footer.php:100-103`: `target="_blank"` without `rel="noopener"`.
- `library/breadcrumbs/search.php:19-25`: `get_search_query()` is already escaped and is then run through `esc_attr()` again in the template; `esc_attr` does not double-encode, so no visible bug.
- Fix / Test: optional. `axe`-style check for duplicate landmarks; no security test needed.

### phpcs `-- theme audit` suppressions (84): verdict by sniff

Counts verified with `git grep` (33+11+10+6+6+5+4+3+2+1+1+1+1 = 84).

| Sniff | n | Verdict | Real-defect locations |
|---|---|---|---|
| `WordPress.Security.EscapeOutput.OutputNotEscaped` | 33 | mixed | **Real:** footer.php:29 (T29); root-colors.php:10 (T5, latent); template-parts/banner.php:37 (T8, class attr); nggallery/album-compact.php:29,33,53,65,71 (T16) and :48 (T15, crash); 404.php:35-36, content-none.php:28-29, searchform.php:15, comments.php:57, front-page.php:109 (T20, hardening). **False positive:** footer.php:103,104 (literals); template-parts/banner.php:41,47,56,57 (already `sanitize_*`'d at lines 29-34, correct output function would still be `esc_*`); nggallery:42,43 (`Router::esc_url`); foundation.php:74,134 (core `paginate_links` output); foundation.php:145,148,149,153,154 (static admin URLs, function unused, T41); class-hyperpress-comments.php:83 (`comment_date('c')`) and :102 (`htmlspecialchars` on a core link) |
| `WordPress.Security.EscapeOutput.UnsafePrintingFunction` | 11 | low | header.php:35 (`_e` inside an attribute: use `esc_attr_e`). The other 10 are `_e` in element context; only untrusted translations matter (T20) |
| `Universal.Operators.StrictComparisons.LooseEqual` | 10 | false positive | enqueue-scripts.php:116 is the exception for a different reason (null deref, T11). The other 9 compare strings/ints and are safe; foundation.php:96,98,338,354 compare attribute strings; :108,110,123,164 int vs index; labels/post.php:55 int vs int |
| `WordPress.WP.I18n.MissingArgDomain` | 6 | real, low | responsive-images.php:37-40; template-parts/post-navigation.php:9-10 (strings fall back to the core domain and never reach the theme `.pot`) |
| `WordPress.WP.GlobalVariablesOverride.Prohibited` | 6 | false positive | class-hyperpress-comments.php:39,49,60,61 (Walker_Comment pattern); content.php:45 and content-page.php:29 (`$tag` is local to `load_template`) |
| `Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed` | 5 | false positive | filter signatures: responsive-images.php:73; protocol-relative class :53,64,76,88 (file not loaded, T41) |
| `Squiz.PHP.CommentedOutCode.Found` | 4 | false positive | functions.php:80, foundation.php:391, plugins.php:257, theme-activation.php:37: delete the dead code (T41) |
| `Universal.Operators.StrictComparisons.LooseNotEqual` | 3 | false positive | front-page.php:58,90 (string vs string); foundation.php:225 (`'' != $output`) |
| `WordPress.WP.EnqueuedResourceParameters.MissingVersion` | 2 | real, low | enqueue-scripts.php:55,56 (T11: `ver=false` appends the WP version to the Google Fonts URL, and the `display=swa` typo) |
| `WordPress.WP.DeprecatedParameters.Get_termsParam2Found` | 1 | real, low | breadcrumbs/tag.php:15 (T17, plus the unchecked `$terms[0]` in T36) |
| `WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents` | 1 | false positive for security | enqueue-scripts.php:25 reads a local build manifest, no user path. The real defect there is T9 (null manifest TypeError) |
| `OutputNotEscaped` + `I18n.NoHtmlWrappedStrings` | 1 | false positive (XSS), low (i18n) | class-hyperpress-comments.php:79: `get_comment_author_link()` is escaped by core; HTML inside the translatable string is poor practice |
| `OutputNotEscaped` + `UnsafePrintingFunction` + `I18n.TextDomainMismatch` | 1 | false positive | nggallery/album-compact.php:58: `$gallery->counter` is an NGG integer; the `nggallery` domain is intentional |

### Rejected candidates (second pass)

- `.env` holding secrets (row 7 follow-up): rejected. It contains one key, `NODE_OPTIONS` (T2 update).
- Root-colors stored XSS as a Customizer-reachable bug (row 5): rejected as medium. All eight colour settings have `sanitize_hex_color` (T5 update, now low).
- `footer.php` `$target` and `$social['icon']` attribute breakout (T19): rejected. Both values are literals or derived from literals.
- AJAX / nonce / capability gaps on `save_post` or custom endpoints: rejected. The theme registers no `wp_ajax_*`, REST route, `admin_post_*` or form handler and reads no superglobal; the Meta Box fields and the Customizer handle their own nonces and capability checks.
- `eval`/`unserialize`/`exec`/`wp_remote_*` and tainted `include`: rejected. None exist. The only variable includes are T3/T4.
- `woocommerce.php` dead code (row 12): rejected. The 26-line file is the stock WooCommerce wrapper (`woocommerce_content()` between `get_header()` and `get_footer()`), loaded only by WooCommerce's template loader; no defect.
- `library/class-walker-category-dropdown-multi.php` as a vulnerability: rejected, it is an empty stub with no callers (T41).
- `comments_number()` / `the_title()` in `HyperPress_Theme_Comments::__construct` (class-hyperpress-comments.php:30): rejected as an XSS. Both are core functions that escape their output.
- `printf( get_comment_date(), … )` throwing ArgumentCountError (as stated in T18): rejected as written; surplus printf arguments are ignored in PHP 8 (T18 update).
- `wpt_mobile_menu_layout` injection via `body_class`: rejected. The class is one of two fixed strings regardless of the stored value (T34 is layout-only).
- `front-page.php:34` countdown shortcode injection beyond admin: unchanged (T7). The ids come from Meta Box's Customizer storage (`post` field type, integer ids), so I could not show a path for non-integer input; treat as hardening.

### Final counts (whole theme report, after all updates)

critical 0 | high 0 | medium 9 | low 26 | info 8 (43 findings, T1-T43).

Severity changes applied through update notes: T2 medium to info, T5 medium to low, T19 low to info.

- critical: none
- high: T1
- medium: T3, T4, T6, T24, T26, T27, T28, T29, T30
- low: T5, T7-T18, T20, T25, T31-T39, T42
- info: T2, T19, T21, T22, T23, T40, T41, T43

