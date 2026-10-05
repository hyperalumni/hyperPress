# Plugins 03: Migrate Season, Program, Robot, Award, Competition

> **STATUS: COMPLETED** (2026-10-04). Commits bf57ac0..3e5f687 on branch cleanup/convention-and-tests. Season, program, robot, award, competition (with district and week) are migrated; remaining red tests are for countdown, newsletter, sponsor and tier (plugins-04).

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use checkbox (`- [x]`) syntax.

**Goal:** Move five plugins onto the new structure, one commit per plugin, each driven by the registration-parity test and the plugins-01-test-toolchain contract tests.

**Prerequisite:** plugins-02-shared-utils complete (Utils classes exist and pass).
**Followed by:** plugins-04-remaining-plugins (countdown, newsletter, sponsor, socials, label, student), then plugins-05-standards-and-smoke-test.
**Spec:** `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md`

**Conventions:** as in plugins-01-test-toolchain and plugins-02-shared-utils (run from `plugins/`, docker, commit with explicit pathspec). "Legacy" means the code before this plan.

## Conversion rules (apply to every plugin, in plugins-03-core-plugins and plugins-04-remaining-plugins)

- **R1 Main file.** `git mv <plugin>/<name>.php <plugin>/<plugin>.php`. Edit the header in place: remove `GitHub Plugin URI`; set `Requires PHP: 8.3`, `Requires at least: 6.9`; add `Requires Plugins: <from the table>`; keep every other field; fix any missing/incorrect field named in the task. Copy `License`/`License URI` values from `hyperpress-award/award.php` (or `hyperpress-award/hyperpress-award.php` once migrated) when a plugin lacks them.
- **R2 Main file body.** First READ the legacy body: besides `require` lines it may hold hooks, activation callbacks, constants or filters (for example the `content_template` filters in newsletter and sponsor). Anything that is not a plain `require` must be moved into `Plugin` or the matching concern class, never dropped. Then replace everything after the header with:

```php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'HyperPress\\<Name>\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
);

// Boot after every plugin's main file is loaded, so Utils is always available.
add_action( 'plugins_loaded', array( \HyperPress\<Name>\Plugin::class, 'boot' ) );
register_activation_hook( __FILE__, array( \HyperPress\<Name>\Plugin::class, 'activate' ) );
```

  (`activate` is omitted for plugins that register no post type.)
- **R3 Concern files.** Each `lib/<concern>.php` becomes `src/<Concern>.php`: namespace `HyperPress\<Name>`, `final class <Concern>`, the top-level `add_action`/`add_filter`/`add_shortcode` calls move into `public static function register(): void` using `array( self::class, 'method' )`, and each legacy function becomes a `public static function` with the body unchanged except for identifier renames. Remove the `if ( ! function_exists(...) ) :` / `endif;` wrappers and the `die( '-1' )` guard; start each file with `defined( 'ABSPATH' ) || exit;`. Keep the legacy hook names and priorities exactly.
- **R4 Plugin class.** `src/Plugin.php` with `boot()` (calls each concern's `register()` and hooks registrations), `activate()`, `definition()` where the plugin registers types, and a private `version()` reading the header via `get_file_data`.
- **R5 Identifiers** follow the per-plugin rename table in each task. Afterwards no legacy identifier may remain (verified by the grep step).
- **R6** Delete each legacy `lib/` file once its replacement passes. `assets/` and `languages/` are untouched.
- **R7 Verification per plugin** (replace `<name>` and `<old>`):
  1. `docker compose run --rm php composer lint:syntax` exits 0.
  2. `docker compose run --rm php composer test:unit -- --filter 'PluginHeadersTest.*hyperpress-<name>'` PASS.
  3. `docker compose run --rm php composer test:integration -- --filter 'RegistrationParityTest.*<old>'` PASS (for plugins that register types).
  4. `docker compose run --rm php composer test:unit -- --filter NoLegacyKeysTest 2>&1 | grep 'hyperpress-<name>/'` prints nothing.
  5. The whole integration suite still BOOTS (no fatals): `docker compose run --rm php composer test:integration -- --filter BootTest` PASS.

---

## Task 1: Registration parity test

Compares every new registration with the snapshot from plugins-01-test-toolchain, mapped through `Convention::OLD_TO_NEW`. This is the net that catches a mistyped definition.

**Files:**
- Create: `tests/integration/RegistrationParityTest.php`

- [x] **Step 1: Write the test**

```php
<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class RegistrationParityTest extends WP_UnitTestCase {

	// show_in_rest is intentionally not compared: REST is now off for every type (nothing uses it; see the audit, item 10).
	private const POST_TYPE_PROPS = array( 'public', 'publicly_queryable', 'hierarchical', 'exclude_from_search', 'has_archive', 'menu_position', 'menu_icon', 'description' );
	// Own constant: data providers run before other test classes are autoloaded.
	private const FIXTURE = __DIR__ . '/../fixtures/legacy-registrations.json';
	private const TAXONOMY_PROPS  = array( 'public', 'publicly_queryable', 'hierarchical', 'show_tagcloud', 'show_in_quick_edit', 'show_admin_column' );

	private static function snapshot(): array {
		return json_decode( (string) file_get_contents( self::FIXTURE ), true );
	}

	public static function post_types(): array {
		$cases = array();
		foreach ( array_keys( self::snapshot()['post_types'] ) as $old ) {
			$cases[ $old ] = array( $old );
		}
		return $cases;
	}

	public static function taxonomies(): array {
		$cases = array();
		foreach ( array_keys( self::snapshot()['taxonomies'] ) as $old ) {
			$cases[ $old ] = array( $old );
		}
		return $cases;
	}

	/** Legacy "true" query vars resolve to the registered key, so the expected value follows the rename. */
	private static function map( $value ) {
		return is_string( $value ) && isset( Convention::OLD_TO_NEW[ $value ] ) ? Convention::OLD_TO_NEW[ $value ] : $value;
	}

	/** @dataProvider post_types */
	public function test_post_type_matches_legacy( string $old ): void {
		$legacy = self::snapshot()['post_types'][ $old ];
		$new    = Convention::OLD_TO_NEW[ $old ];
		$pt     = get_post_type_object( $new );
		$this->assertNotNull( $pt, "$new is not registered" );

		foreach ( self::POST_TYPE_PROPS as $prop ) {
			$this->assertSame( $legacy[ $prop ], $pt->$prop, "$new: $prop" );
		}
		$this->assertSame( self::map( $legacy['query_var'] ), $pt->query_var, "$new: query_var" );

		$supports = array_keys( get_all_post_type_supports( $new ) );
		// Countdown's legacy registration listed 'slug', which is not a real post type feature (removed on purpose).
		$expected = array_values( array_diff( $legacy['supports'], array( 'slug' ) ) );
		sort( $supports );
		sort( $expected );
		$this->assertSame( $expected, $supports, "$new: supports" );

		foreach ( array( 'name', 'singular_name', 'menu_name' ) as $label ) {
			$this->assertSame( $legacy['labels'][ $label ], $pt->labels->$label, "$new: label $label" );
		}

		if ( $legacy['publicly_queryable'] ) {
			$this->assertSame( $legacy['rewrite']['slug'], $pt->rewrite['slug'], "$new: rewrite slug" );
			$this->assertSame( $legacy['rewrite']['with_front'], $pt->rewrite['with_front'], "$new: with_front" );
			$this->assertSame( $legacy['rewrite']['hierarchical'] ?? false, $pt->rewrite['hierarchical'] ?? false, "$new: rewrite hierarchical" );
		}
	}

	/** @dataProvider taxonomies */
	public function test_taxonomy_matches_legacy( string $old ): void {
		$legacy = self::snapshot()['taxonomies'][ $old ];
		$new    = Convention::OLD_TO_NEW[ $old ];
		$tax    = get_taxonomy( $new );
		$this->assertNotFalse( $tax, "$new is not registered" );

		foreach ( self::TAXONOMY_PROPS as $prop ) {
			$this->assertSame( $legacy[ $prop ], $tax->$prop, "$new: $prop" );
		}
		$this->assertSame( self::map( $legacy['query_var'] ), $tax->query_var, "$new: query_var" );
		$this->assertSame( $legacy['cap'], (array) $tax->cap, "$new: capabilities" );

		$object_types = array_map( array( self::class, 'map' ), $legacy['object_type'] );
		$actual_types = $tax->object_type;
		sort( $object_types );
		sort( $actual_types );
		$this->assertSame( $object_types, $actual_types, "$new: object types" );

		foreach ( array( 'name', 'singular_name', 'menu_name' ) as $label ) {
			$this->assertSame( $legacy['labels'][ $label ], $tax->labels->$label, "$new: label $label" );
		}

		if ( $legacy['publicly_queryable'] ) {
			$this->assertSame( $legacy['rewrite']['slug'], $tax->rewrite['slug'], "$new: rewrite slug" );
		}
	}
}
```

- [x] **Step 2: Run and confirm FAIL**

Run: `docker compose run --rm php composer test:integration -- --filter RegistrationParityTest`
Expected: every case FAILS with "hyper_* is not registered" (nothing migrated yet).

- [x] **Step 3: Commit**

```bash
git add tests/integration/RegistrationParityTest.php
git commit -m "test: add registration parity test against the legacy snapshot" -- tests/integration/RegistrationParityTest.php
```

---

## Task 2: Migrate `hyperpress-season`

**Requires Plugins:** `hyperpress-utils, meta-box-aio`. **Namespace:** `HyperPress\Season`. **Legacy files:** `season.php`, `lib/{banner,breadcrumbs,custom-taxonomy,customize,functions,meta-boxes,rewrite}.php`, `lib/functions/get-sorted-seasons.php`.

**Target files:** `hyperpress-season.php`, `src/Plugin.php`, `src/SeasonBanner.php`, `src/Breadcrumbs.php`, `src/Customizer.php`, `src/MetaBoxes.php`, `src/Rewrite.php`, `src/SortedSeasons.php`, `src/Shortcodes/SeasonCount.php`.

- [x] **Step 1: Read every legacy file**

Read each legacy file listed above so the conversion keeps hook names, priorities and behavior. Also re-read the Task 0 consumer list from plugins-02-shared-utils for callers of `get_sorted_seasons`.

- [x] **Step 2: R1 and R2** for `hyperpress-season.php`.

- [x] **Step 3: Write `src/Plugin.php`**

```php
<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\TaxonomyRegistrar;

final class Plugin {

	public const TAXONOMY = 'hyper_season';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		SeasonBanner::register();
		Breadcrumbs::register();
		Customizer::register();
		MetaBoxes::register();
		Rewrite::register();
	}

	public static function register(): void {
		TaxonomyRegistrar::register( self::definition() );
	}

	public static function definition(): array {
		return array(
			'key'                => self::TAXONOMY,
			'singular'           => __( 'Season', 'hyperpress' ),
			'plural'             => __( 'Seasons', 'hyperpress' ),
			'description'        => __( 'Build Seasons of Team HYPER', 'hyperpress' ),
			'slug'               => 'seasons',
			'object_types'       => 'hyperpress_season_post_types',
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
		);
	}
}
```

The legacy function was named `hyperpress_register_season_taxonomoy` (typo). It is replaced, not renamed. Object types read the theme mod `hyperpress_season_post_types` with default `[]` (attached to nothing), matching legacy.

- [x] **Step 4: Convert the concern files (R3)** with these specific edits:
  - `SeasonBanner`: `is_tax( 'season' )` becomes `is_tax( 'hyper_season' )`. Keep `$banner['type'] = 'season'` (theme-facing value) and the `hyperpress_gear_blue` / `hyperpress_season_banner_subtitle` theme mods unchanged.
  - `Breadcrumbs`: `is_tax( 'season' )` becomes `is_tax( 'hyper_season' )`. The rest is copied unchanged (the array-based breadcrumb logic is season-specific and stays here).
  - `SortedSeasons`: `get_sorted_seasons` becomes `SortedSeasons::get(...)` with the same parameters and body; `'season'` in `wp_get_post_terms` becomes `'hyper_season'`. Update every caller found in the plugins-02-shared-utils Task 0 list that lives in a plugin.
  - `Customizer`: control ids `season_banner_subtitle` becomes `hyperpress_season_banner_subtitle`; `season_post_types` becomes `hyperpress_season_post_types`; `hyperpress_season_current` is already compliant. Replace `\HYPER_Press_Utils\HYPERpress_Dropdown_Post_Type_Control` with `\HyperPress\Utils\Controls\PostTypeDropdownControl`. Section and panel `hyperpress_season` are unchanged.
  - `MetaBoxes`: panel id `hyperpress_season` is unchanged; apply the R3 conversion only. Keep its `meta-box-aio` dependency.
  - `Shortcodes\SeasonCount` (new, extracted from the theme): read `theme/library/shortcodes/season-count.php` (21 lines) and move the `hyper_season_count` shortcode here unchanged: same tag, same attributes (including the `season` attribute), same output. It calls `[hyper_date_distance]`, which lives in `hyperpress-shortcodes` (plugins-04-remaining-plugins Task 6); when that shortcode is not registered the tag renders unexpanded, which is accepted (season does not hard-depend on the shortcodes plugin). Register it from `Plugin::boot()` via `SeasonCount::register()` on `init`. Add `tests/integration/SeasonCountTest.php` asserting the tag is registered and that, with a stand-in `hyper_date_distance` shortcode registered in the test, the output equals what the theme code produces for the same attributes (capture the expected string by running the theme file once in a scratch test, as in plugins-04-remaining-plugins Task 6 Step 4).
  - `Rewrite` (needs real work, found during plugins-02 Task 5): the legacy rule is `^seasons/([^/]+)/([^/]+)?s$` -> `index.php?hyper_season=$matches[1]&post_type=$matches[2]`. It derives `post_type` from the archive slug by stripping the trailing `s` (`sponsors` becomes `sponsor`), which only equals the post type key for the legacy keys; with `hyper_sponsor` it would query a post type that does not exist. Replace it: `Rewrite::register()` hooks `init` at priority 20 (after the post types register at 10) and, for every public post type whose key starts with `hyper_` and whose `has_archive` is a string, adds `add_rewrite_rule( '^seasons/([^/]+)/' . preg_quote( $archive ) . '/?$', 'index.php?hyper_season=$matches[1]&post_type=' . $key, 'top' )`. This produces the same public URLs as before (`/seasons/2026/sponsors/`) and matches what `HyperPress\Utils\Breadcrumbs` links to. The season rewrite slug `seasons` must be read from `get_taxonomy( 'hyper_season' )->rewrite['slug']`, not hard-coded twice.

- [x] **Step 5: Add a test that the season rewrite rules resolve** `tests/integration/SeasonRewriteTest.php`

```php
<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SeasonRewriteTest extends WP_UnitTestCase {

	public function test_each_hyper_archive_gets_a_season_rule_with_the_real_post_type_key(): void {
		flush_rewrite_rules();
		$rules = (array) get_option( 'rewrite_rules' );

		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $key => $type ) {
			if ( 0 !== strpos( $key, 'hyper_' ) || ! is_string( $type->has_archive ) ) {
				continue;
			}
			$expected = 'index.php?hyper_season=$matches[1]&post_type=' . $key;
			$this->assertContains( $expected, $rules, "No season rule for $key" );
		}
	}

	public function test_season_archive_url_resolves_to_the_post_type_archive(): void {
		$term_id = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026', 'slug' => '2026' ) );
		flush_rewrite_rules();
		$this->go_to( home_url( '/seasons/2026/awards/' ) );
		$this->assertTrue( is_post_type_archive( 'hyper_award' ) );
		$this->assertTrue( is_tax( 'hyper_season' ) );
		$this->assertSame( $term_id, get_queried_object_id() );
	}
}
```

(The archive test uses `hyper_award`, which is migrated in Task 5 of this plan: implement Task 2 Step 5 after Task 5 passes, or run that one test only once award is migrated; the first test is meaningful immediately.) If `is_tax` is false on the archive request, read how WordPress resolves `hyper_season=2026&post_type=hyper_sponsor` (both query vars set) and adjust the assertions to what WordPress actually reports, keeping the `post_type` check: the requirement is that the URL resolves to sponsors of that season, not a 404.
- [x] **Step 6: Delete the legacy `lib/` files, then run R7 verification**, with `<name>`=season and `<old>`=season (taxonomy case):

Run: `docker compose run --rm php composer test:integration -- --filter 'RegistrationParityTest.*season|SeasonRewriteTest'`
Expected: PASS. If parity fails on a value, fix the definition (the failure message names the property); do not edit the parity test.

- [x] **Step 7: Commit**

```bash
git add hyperpress-season tests/integration/SeasonRewriteTest.php
git commit -m "refactor(season): migrate to hyper_season and the shared structure" -- hyperpress-season tests/integration/SeasonRewriteTest.php
```

---

## Task 3: Migrate `hyperpress-program`

**Requires Plugins:** `hyperpress-utils, meta-box-aio`. **Namespace:** `HyperPress\Program`. **Legacy files:** `program.php`, `lib/{custom-taxonomy,customize,meta-boxes}.php`.

**Target files:** `hyperpress-program.php`, `src/Plugin.php`, `src/Customizer.php`, `src/MetaBoxes.php`.

- [x] **Step 1: Read the legacy files.**
- [x] **Step 2: R1 and R2** for `hyperpress-program.php`.
- [x] **Step 3: Write `src/Plugin.php`**

```php
<?php
namespace HyperPress\Program;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\TaxonomyRegistrar;

final class Plugin {

	public const TAXONOMY = 'hyper_program';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		Customizer::register();
		MetaBoxes::register();
	}

	public static function register(): void {
		TaxonomyRegistrar::register( self::definition() );
	}

	public static function definition(): array {
		return array(
			'key'                => self::TAXONOMY,
			'singular'           => __( 'Program', 'hyperpress' ),
			'plural'             => __( 'Programs', 'hyperpress' ),
			'description'        => __( 'Programs within FIRST', 'hyperpress' ),
			'slug'               => 'programs',
			'object_types'       => 'hyperpress_program_post_types',
			'public'             => true,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
		);
	}
}
```

- [x] **Step 4: Convert `Customizer` and `MetaBoxes` (R3)**: control id `program_post_types` becomes `hyperpress_program_post_types`; use `\HyperPress\Utils\Controls\PostTypeDropdownControl`; section `hyperpress_program` and the panel id are unchanged.
- [x] **Step 5: Delete legacy files, run R7** (`<name>`=program, `<old>`=program).
- [x] **Step 6: Commit**

```bash
git add hyperpress-program
git commit -m "refactor(program): migrate to hyper_program and the shared structure" -- hyperpress-program
```

---

## Task 4: Migrate `hyperpress-robot`

**Requires Plugins:** `hyperpress-utils, hyperpress-season`. **Namespace:** `HyperPress\Robot`. **Legacy files:** `robot.php`, `lib/{breadcrumbs,custom-post-type}.php`.

**Target files:** `hyperpress-robot.php`, `src/Plugin.php`.

- [x] **Step 1: Read the legacy files.**
- [x] **Step 2: R1 and R2** for `hyperpress-robot.php`. The legacy description is wrong; set `Description: Adds the Robot custom post type for showcasing the robots Team HYPER has built.`
- [x] **Step 3: Write `src/Plugin.php`** (the shared `Breadcrumbs` replaces the local copy; robot has no banner)

```php
<?php
namespace HyperPress\Robot;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\Capabilities;
use HyperPress\Utils\PostTypeRegistrar;

final class Plugin {

	public const POST_TYPE   = 'hyper_robot';
	private const CAPS_OPTION = 'hyperpress_robot_caps_version';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		Breadcrumbs::register( array( self::POST_TYPE ) );
	}

	public static function register(): void {
		PostTypeRegistrar::register( self::definition() );
	}

	public static function ensure_capabilities(): void {
		Capabilities::ensure( self::CAPS_OPTION, self::version(), array( self::POST_TYPE ) );
	}

	public static function activate(): void {
		self::register();
		self::ensure_capabilities();
		flush_rewrite_rules();
	}

	public static function definition(): array {
		return array(
			'key'           => self::POST_TYPE,
			'singular'      => __( 'Robot', 'hyperpress' ),
			'plural'        => __( 'Robots', 'hyperpress' ),
			'description'   => __( 'Robots made by Team HYPER', 'hyperpress' ),
			'slug'          => 'robot',
			'archive'       => 'robots',
			'menu_position' => 23,
			'menu_icon'     => 'dashicons-superhero',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'revisions' ),
		);
	}

	private static function version(): string {
		$data = get_file_data( dirname( __DIR__ ) . '/hyperpress-robot.php', array( 'version' => 'Version' ) );
		return $data['version'];
	}
}
```

- [x] **Step 4: Delete legacy files, run R7** (`<name>`=robot, `<old>`=robot).
- [x] **Step 5: Commit**

```bash
git add hyperpress-robot
git commit -m "refactor(robot): migrate to hyper_robot and the shared structure" -- hyperpress-robot
```

---

## Task 5: Migrate `hyperpress-award`

**Requires Plugins:** `hyperpress-utils, hyperpress-season`. **Namespace:** `HyperPress\Award`. **Legacy files:** `award.php`, `lib/{breadcrumbs,custom-post-type}.php`.

**Target files:** `hyperpress-award.php`, `src/Plugin.php`.

- [x] **Step 1: Read the legacy files, then print the legacy registration** to get the values this plan does not list (rewrite hierarchical flag):

Run: `docker compose run --rm php php -r 'echo json_encode(json_decode(file_get_contents("tests/fixtures/legacy-registrations.json"),true)["post_types"]["award"]["rewrite"]);'`
Expected: shows `slug`, `with_front` and optionally `hierarchical`. Set `rewrite_hierarchical` below to match.

- [x] **Step 2: R1 and R2** for `hyperpress-award.php`.
- [x] **Step 3: Write `src/Plugin.php`**: identical structure to robot's `Plugin.php` in Task 4 with these differences only: namespace `HyperPress\Award`, `POST_TYPE = 'hyper_award'`, `CAPS_OPTION = 'hyperpress_award_caps_version'`, main file name `hyperpress-award.php`, and this definition:

```php
	public static function definition(): array {
		return array(
			'key'           => self::POST_TYPE,
			'singular'      => __( 'Award', 'hyperpress' ),
			'plural'        => __( 'Awards', 'hyperpress' ),
			'description'   => __( 'Awards won by Team HYPER', 'hyperpress' ),
			'slug'          => 'award',
			'archive'       => 'awards',
			'menu_position' => 24,
			'menu_icon'     => 'dashicons-awards',
			'supports'      => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			'hierarchical'  => false,
			// Add 'rewrite_hierarchical' => true here ONLY if Step 1 printed "hierarchical":true.
		);
	}
```

(Write out the full class, do not reference Task 4 at run time: copy its body with the differences above.)

- [x] **Step 4: Delete legacy files, run R7** (`<name>`=award, `<old>`=award).
- [x] **Step 5: Commit**

```bash
git add hyperpress-award
git commit -m "refactor(award): migrate to hyper_award and the shared structure" -- hyperpress-award
```

---

## Task 6: Migrate `hyperpress-competition`

**Requires Plugins:** `hyperpress-utils, hyperpress-season, hyperpress-robot, meta-box-aio`. **Namespace:** `HyperPress\Competition`. **Legacy files:** `competition.php`, `lib/{banner,breadcrumbs,custom-post-type,district-taxonomy,meta-boxes,week-taxonomy}.php`, `lib/metaboxes/information.php`.

**Target files:** `hyperpress-competition.php`, `src/Plugin.php`, `src/MetaBoxes.php` (holds the merged `lib/meta-boxes.php` + `lib/metaboxes/information.php`).

- [x] **Step 1: Read the legacy files** and print the legacy rewrite flags:

Run: `docker compose run --rm php php -r 'echo json_encode(json_decode(file_get_contents("tests/fixtures/legacy-registrations.json"),true)["post_types"]["competition"]["rewrite"]);'`

- [x] **Step 2: R1 and R2** for `hyperpress-competition.php`.
- [x] **Step 3: Write `src/Plugin.php`**

```php
<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Banner;
use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\Capabilities;
use HyperPress\Utils\PostTypeRegistrar;
use HyperPress\Utils\TaxonomyRegistrar;

final class Plugin {

	public const POST_TYPE    = 'hyper_competition';
	public const DISTRICT     = 'hyper_district';
	public const WEEK         = 'hyper_week';
	private const CAPS_OPTION = 'hyperpress_competition_caps_version';

	public static function boot(): void {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'init', array( self::class, 'ensure_capabilities' ), 20 );
		Breadcrumbs::register( array( self::POST_TYPE ) );
		Banner::register(
			self::POST_TYPE,
			array(
				'type'          => 'competition',
				'subtitle_mod'  => 'hyperpress_competition_banner_subtitle',
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => __( 'Competitions', 'hyperpress' ),
				'term_joiner'   => 'in',
			)
		);
		MetaBoxes::register();
	}

	public static function register(): void {
		PostTypeRegistrar::register( self::definition() );
		TaxonomyRegistrar::register( self::district_definition() );
		TaxonomyRegistrar::register( self::week_definition() );
	}

	public static function ensure_capabilities(): void {
		Capabilities::ensure( self::CAPS_OPTION, self::version(), array( self::POST_TYPE ) );
	}

	public static function activate(): void {
		self::register();
		self::ensure_capabilities();
		flush_rewrite_rules();
	}

	public static function definition(): array {
		return array(
			'key'           => self::POST_TYPE,
			'singular'      => __( 'Competition', 'hyperpress' ),
			'plural'        => __( 'Competitions', 'hyperpress' ),
			'description'   => __( 'Competitions competed at by Team HYPER', 'hyperpress' ),
			'slug'          => 'competition',
			'archive'       => 'competitions',
			'menu_position' => 22,
			'menu_icon'     => 'dashicons-calendar',
			'supports'      => array( 'title', 'thumbnail', 'revisions' ),
			'hierarchical'  => false, // Legacy post type is NOT hierarchical (snapshot); only its rewrite is.
			'rewrite_hierarchical' => true, // Snapshot: competition rewrite.hierarchical is true.
		);
	}

	public static function district_definition(): array {
		return array(
			'key'                => self::DISTRICT,
			'singular'           => __( 'District', 'hyperpress' ),
			'plural'             => __( 'Districts', 'hyperpress' ),
			'description'        => __( 'Subdivisions within FRC', 'hyperpress' ),
			'slug'               => 'districts',
			'object_types'       => array( self::POST_TYPE ),
			'public'             => false,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => false,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
		);
	}

	public static function week_definition(): array {
		return array(
			'key'                => self::WEEK,
			'singular'           => __( 'Week', 'hyperpress' ),
			'plural'             => __( 'Weeks', 'hyperpress' ),
			'description'        => __( 'FRC Competition Weeks', 'hyperpress' ),
			'slug'               => 'weeks',
			'object_types'       => array( self::POST_TYPE ),
			'public'             => false,
			'publicly_queryable' => true,
			'hierarchical'       => true,
			'show_tagcloud'      => true,
			'show_in_quick_edit' => true,
			'show_admin_column'  => false,
		);
	}

	private static function version(): string {
		$data = get_file_data( dirname( __DIR__ ) . '/hyperpress-competition.php', array( 'version' => 'Version' ) );
		return $data['version'];
	}
}
```

- [x] **Step 4: Convert the meta boxes into `src/MetaBoxes.php` (R3) with these renames**

| Legacy | New |
|---|---|
| box id `competition-information` | `hyperpress_competition_information` |
| field `competition_robot` | `hyperpress_competition_robot` |
| field `competition_host_name` | `hyperpress_competition_host_name` |
| field `competition_venue_name` | `hyperpress_competition_venue_name` |
| field `competition_venue_address` | `hyperpress_competition_venue_address` |
| field `competition_venue_map` | `hyperpress_competition_venue_map` |
| field `ne_first_event_url` | `hyperpress_competition_ne_first_event_url` |
| field `first_event_url` | `hyperpress_competition_first_event_url` |
| field `stream_url` | `hyperpress_competition_stream_url` |
| `post_types => ['competition']` | `post_types => ['hyper_competition']` |
| robot field query `robot` (legacy `lib/metaboxes/information.php:32`) | `hyper_robot` |

Keep every field's other settings and the box's other settings exactly as legacy. Do not add `sanitize_callback` here (the missing sanitizers are audit findings for plugins-06-audit).

- [x] **Step 5: Delete legacy `lib/` files.**
- [x] **Step 6: Run R7** (`<name>`=competition) and the parity cases for all three keys:

Run: `docker compose run --rm php composer test:integration -- --filter 'RegistrationParityTest.*(competition|district|week)'`
Expected: PASS.

- [x] **Step 7: Commit**

```bash
git add hyperpress-competition
git commit -m "refactor(competition): migrate to hyper_competition and the shared structure" -- hyperpress-competition
```

---

## Task 7: Checkpoint for plugins-03-core-plugins

- [x] **Step 1: Run the whole suite and record what still fails**

Run: `docker compose run --rm php composer test:unit; docker compose run --rm php composer test:integration`
Expected: no fatals. Remaining failures are only for plugins not yet migrated (countdown, newsletter, sponsor, socials, label, student) and the contract tests that need all plugins (`KeyConventionTest`, `IdentifierConventionTest` and so on). List them in the notes below.

- [x] **Step 2: Flip this plan's checkboxes, add the `STATUS: COMPLETED` banner, commit the plan**

```bash
git add docs/superpowers/plans/2026-10-02-plugins-03-core-plugins.md
git commit -m "docs(plans): complete plugins-03-core-plugins" -- docs/superpowers/plans/2026-10-02-plugins-03-core-plugins.md
```

## Execution notes

- Checkpoint: unit 121 tests (32 errors, 9 failures: header lint for the 8 unmigrated plugins and `NoLegacyKeysTest`); integration 71 tests, 21 failures, 3 skipped. Failures are all for countdown, newsletter, sponsor, `hyper_tier` and the identifier tests that cover those plugins. `lint:syntax` exit 0.
- Season: the legacy rewrite rule derived `post_type` from the archive slug by stripping a trailing `s`, which cannot work with `hyper_*` keys. `Rewrite` now adds one rule per public `hyper_*` archive at `init` priority 20, reading the season slug from the taxonomy. `SeasonRewriteTest` skips until a `hyper_*` archive exists and passed for real once award was migrated.
- Season Customizer: the `class_exists` guard on the dropdown control was removed after review (utils is a required plugin).
- Competition: the legacy post type is NOT hierarchical (snapshot); only its rewrite is hierarchical. The definition uses `hierarchical => false` with `rewrite_hierarchical => true`.
- Robot's legacy header lacked `Domain Path` and `Update URI`; both were added to match the others.
- Open cross-plugin items: `hyperpress-sponsor/lib/labels.php` and the theme (`library/labels/post.php`, `library/breadcrumbs/post.php`) still call `HYPER_Press_Season\get_sorted_seasons` (plugins-04 Task 3 and theme-02).
- Audit candidates added to plugins-06-audit: hard-coded Google Maps `api_key` and `__( '' )` in the competition meta box.
- Deferred minors: robot definition omits `hierarchical => false` (default); `version()` reads the main file on every request; `SeasonCountTest` expectations were derived by hand (reviewer verified the logic); a Customizer dropdown smoke test belongs in plugins-05.
