# Theme 02: Theme Updates for the Plugin Renames and the Moved Breadcrumb Builder

> **STATUS: COMPLETED** (2026-10-04). Commits a4ec77f..ead2765. Integration 12 tests pass; the only red unit test is the GitHub header check (theme-04).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use checkbox (`- [x]`) syntax.

**Goal:** Make the theme consume the renamed plugin identifiers, stop depending on the removed `HYPER_Press_Utils` classes, and delete the theme's own copy of the CPT breadcrumb builder (now `HyperPress\Utils\Breadcrumbs`).

**Prerequisite:** theme-01-tooling-and-baseline complete and green except the header test. plugins-01-test-toolchain, plugins-02-shared-utils, plugins-03-core-plugins, plugins-04-remaining-plugins complete.
**Followed by:** theme-03-remove-extracted-code.
**Spec:** `docs/superpowers/specs/2026-10-02-theme-cleanup-and-extraction-design.md` (sections 5 and 7).

**Conventions:** as in theme-01-tooling-and-baseline (run from `theme/`, docker, commit with an explicit pathspec because the repo has unrelated uncommitted work).

---

## Task 1: Find every stale reference (read-only)

- [x] **Step 1: Search the theme for identifiers that changed**

Run (from `theme/`):

```bash
grep -rnE "HYPER_Press_|HYPERpress_Dropdown|hyperpress_breadcrumbs_custom_post_type|get_sorted_seasons|'(season|sponsor|newsletter|competition|award|robot|countdown|tier|program|district|week)'" --include='*.php' . | grep -vE '^\./(vendor|node_modules|dist|packaged|tests)/'
```

Expected: at least these known hits, plus whatever else exists: `library/customize/homepage.php:8-9` (`use HYPER_Press_Utils\...`), `library/metaboxes/homepage-customize.php:69` (`'countdown'`), `library/breadcrumbs.php:21` (function definition, default `'season'`), `library/shortcodes/season-count.php` (the `'season'` attribute is a shortcode attribute name, not a taxonomy key: leave it; the file itself moves in theme-03-remove-extracted-code).

- [x] **Step 2: Classify each hit** as (a) a renamed registered key, fixed in this plan; (b) a shortcode attribute or label that merely contains the word (leave); (c) code that moves in theme-03-remove-extracted-code (leave). Write the list into the execution notes.

- [x] **Step 3: Check the stored-data touch points**

Read `front-page.php` (lines 22 to 75) and `library/customize/homepage.php`, `library/metaboxes/homepage-customize.php`. List every theme mod and meta key that stores a plugin post type key or a post ID (`hyperpress_home_blog_post_types`, `hyperpress_homepage_customize_countdowns`). They are reset with the dev database, per the spec; no code change, but confirm `front-page.php` casts each countdown ID with `(int)` before building the `[hyperpress_countdown id="..." /]` shortcode (an audit item if not; just note it).

---

## Task 2: Test first: the theme no longer references removed symbols

**Files:** Create `tests/unit/NoStaleReferencesTest.php`.

- [x] **Step 1: Write the test**

```php
<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class NoStaleReferencesTest extends TestCase {

	/** @return array<string,string> relative path => contents, theme PHP only. */
	private static function sources(): array {
		$root   = dirname( __DIR__, 2 );
		$result = array();
		$iter   = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iter as $file ) {
			$path = $file->getPathname();
			if ( 'php' !== $file->getExtension() || preg_match( '#/(vendor|node_modules|dist|packaged|tests|docs)/#', $path ) ) {
				continue;
			}
			$result[ substr( $path, strlen( $root ) + 1 ) ] = (string) file_get_contents( $path );
		}
		return $result;
	}

	/** @dataProvider removed_symbols */
	public function test_removed_symbols_are_gone( string $pattern, string $why ): void {
		$hits = array();
		foreach ( self::sources() as $path => $code ) {
			if ( preg_match( $pattern, $code ) ) {
				$hits[] = $path;
			}
		}
		$this->assertSame( array(), $hits, "$why. Still referenced in: " . implode( ', ', $hits ) );
	}

	public static function removed_symbols(): array {
		return array(
			'old utils namespace'     => array( '/HYPER_Press_Utils/', 'Utils namespace is now HyperPress\\Utils' ),
			'old control classes'     => array( '/HYPERpress_Dropdown_/', 'Controls are HyperPress\\Utils\\Controls\\*' ),
			'theme breadcrumb helper' => array( '/hyperpress_breadcrumbs_custom_post_type/', 'Builder moved to HyperPress\\Utils\\Breadcrumbs' ),
			'old season namespace'   => array( '/HYPER_Press_Season/', 'get_sorted_seasons is now \\HyperPress\\Season\\SortedSeasons::get' ),
			'old countdown key'       => array( '/[\'"]countdown[\'"]\s*\]/', "Countdown post type key is now 'hyper_countdown'" ),
		);
	}
}
```

- [x] **Step 2: Run and confirm it FAILS** on the known hits

Run: `docker compose run --rm php composer test:unit -- --filter NoStaleReferencesTest`
Expected: three or four cases fail, naming `library/customize/homepage.php`, `library/metaboxes/homepage-customize.php` and `library/breadcrumbs.php`.

---

## Task 3: Apply the renames

**Files:** Modify `library/customize/homepage.php`, `library/metaboxes/homepage-customize.php`; modify or delete `library/breadcrumbs.php` parts.

- [x] **Step 1: Homepage Customizer controls (`library/customize/homepage.php`)**

Replace the two imports

```php
use HYPER_Press_Utils\HYPERpress_Dropdown_Category_Control;
use HYPER_Press_Utils\HYPERpress_Dropdown_Post_Type_Control;
```

with

```php
use HyperPress\Utils\Controls\CategoryDropdownControl;
use HyperPress\Utils\Controls\PostTypeDropdownControl;
```

and the two instantiations (around lines 27 to 28 and 41 to 42) to the new class names. Remove the surrounding `class_exists( ... )` guards: if the utils plugin is missing the section should fail loudly, not silently disappear. Leave the setting ids (`hyperpress_home_blog_categories`, `hyperpress_home_blog_post_types`) unchanged; their control ids must equal their setting ids (the plugin convention): if a control id differs, set it equal and update any `settings` reference.

- [x] **Step 2: Countdown meta box (`library/metaboxes/homepage-customize.php:69`)**

Change `'post_type' => [ 'countdown' ]` to `'post_type' => [ 'hyper_countdown' ]`.

- [x] **Step 3: Remove the theme's CPT breadcrumb function (`library/breadcrumbs.php`)**

Delete lines 20 to 108 (the whole `if ( ! function_exists( 'hyperpress_breadcrumbs_custom_post_type' ) ) : ... endif;` block). The twelve `require_once` lines at the top stay (they are the core page-type trails and the router).

- [x] **Step 3b: Season helper callers.** The plugin consumer audit found two theme callers of the season plugin's helper: `library/labels/post.php` (line 2 `use HYPER_Press_Season\get_sorted_seasons;` and the call near line 29) and `library/breadcrumbs/post.php` (the call near line 13). Replace each import and call with `\HyperPress\Season\SortedSeasons::get(...)` (same arguments), guarded only if the season plugin is a real optional dependency of that template (it is required by the theme's plugins, so no `function_exists`/`class_exists` guard). Read both files first and keep the surrounding behaviour unchanged.

- [x] **Step 4: Run**

Run: `docker compose run --rm php composer test:unit -- --filter NoStaleReferencesTest` — expected PASS.
Run: `docker compose run --rm php composer test:integration` — expected all PASS (the CPT-single breadcrumb test now definitely exercises the plugin builder).
Run: `docker compose run --rm php composer lint:syntax` — exit 0.

- [x] **Step 5: Commit**

```bash
git add library/customize/homepage.php library/metaboxes/homepage-customize.php library/breadcrumbs.php library/labels/post.php library/breadcrumbs/post.php tests/unit/NoStaleReferencesTest.php
git commit -m "refactor: use HyperPress\\Utils controls and hyper_countdown; drop the theme CPT breadcrumb builder" -- library/customize/homepage.php library/metaboxes/homepage-customize.php library/breadcrumbs.php library/labels/post.php library/breadcrumbs/post.php tests/unit/NoStaleReferencesTest.php
```

---

## Task 4: Pin the content_template contract

The theme includes whatever path the `content_template` filter returns (`single.php:22`, `template-parts/posts.php:12`, `front-page.php:74`). The newsletter and sponsor plugins supply those paths.

**Files:** Create `tests/integration/ContentTemplateTest.php`.

- [x] **Step 1: Write the test**

```php
<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class ContentTemplateTest extends WP_UnitTestCase {

	public static function post_types(): array {
		return array(
			'newsletter' => array( 'hyper_newsletter' ),
			'sponsor'    => array( 'hyper_sponsor' ),
		);
	}

	/** @dataProvider post_types */
	public function test_plugin_supplies_a_readable_template_inside_its_plugin_directory( string $post_type ): void {
		$id = self::factory()->post->create( array( 'post_type' => $post_type ) );
		$this->go_to( get_permalink( $id ) );

		$path = apply_filters( 'content_template', '' );

		$this->assertNotSame( '', $path, "$post_type must supply a content template" );
		$this->assertFileIsReadable( $path );
		$this->assertStringStartsWith( '/plugins/', wp_normalize_path( realpath( $path ) ), 'template must live inside a plugin' );
	}

	public function test_other_types_get_no_override(): void {
		$id = self::factory()->post->create();
		$this->go_to( get_permalink( $id ) );
		$this->assertSame( '', apply_filters( 'content_template', '' ) );
	}
}
```

- [x] **Step 2: Run**

Run: `docker compose run --rm php composer test:integration -- --filter ContentTemplateTest`
Expected: PASS. If `test_plugin_supplies_...` fails because the plugin callback returns a path to a theme file or a different request shape, the plugin migration (plugins-04-remaining-plugins) lost the behaviour: fix the plugin, not this test. If the paths legitimately point elsewhere, record where in the notes and relax only the directory assertion with a comment.

- [x] **Step 3: Commit**

```bash
git add tests/integration/ContentTemplateTest.php
git commit -m "test: pin the content_template plugin contract" -- tests/integration/ContentTemplateTest.php
```

---

## Task 5: Close out theme-02-renames-and-breadcrumbs

- [x] **Step 1:** Run `composer test:unit` and `composer test:integration`; only `ThemeHeaderTest::test_no_github_updater_header` may fail.
- [x] **Step 2:** Flip checkboxes, add the `STATUS: COMPLETED` banner, fill in notes, commit the plan with an explicit pathspec.

## Execution notes

- Renamed in the theme: homepage Customizer controls now use `HyperPress\Utils\Controls\{CategoryDropdownControl,PostTypeDropdownControl}` with control ids equal to setting ids and no `class_exists` guards (so `hyperpress_home_blog_post_types` is registered unconditionally); `get_sorted_seasons` callers (`library/labels/post.php`, `library/breadcrumbs/post.php`) use `HyperPress\Season\SortedSeasons::get()`; the homepage countdown picker queries `hyper_countdown`.
- Removed the theme's `hyperpress_breadcrumbs_custom_post_type()` (it printed debug output and read `->term_id` from a taxonomy object). No caller remains in the theme or in the plugins.
- `NoStaleReferencesTest` (5 cases) failed first and passes now; `RenderTest` and `CustomizerTest` are green; `ContentTemplateTest` pins that newsletter and sponsor supply a readable template inside `/plugins/` and that ordinary posts get none.
- Edited files keep CRLF line endings (the working tree is CRLF, the index LF).
- The `front-page.php` `(int)` cast on countdown ids stays an audit item (theme-05-audit, candidate 3).
