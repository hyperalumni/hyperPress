# Plugins 02: Shared Code in `hyperpress-utils`

> **STATUS: COMPLETED** (2026-10-04). Commits dd5b447..c495f85 on branch cleanup/convention-and-tests. All 44 Utils unit tests and 13 Utils integration tests pass; remaining red tests are the contract tests awaiting the plugin migrations.

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans. Steps use checkbox (`- [x]`) syntax.

**Goal:** Build the shared classes every plugin will use (`Labels`, `PostTypeRegistrar`, `TaxonomyRegistrar`, `Capabilities`, `Breadcrumbs`, `Banner`, two Customizer controls), test-first, and convert `hyperpress-utils` to the new structure.

**Prerequisite:** plugins-01-test-toolchain is complete (toolchain works, contract tests fail as designed, snapshot committed).
**Followed by:** plugins-03-core-plugins and plugins-04-remaining-plugins (per-plugin migration), then plugins-05-standards-and-smoke-test.
**Spec:** `docs/superpowers/specs/2026-10-02-plugins-convention-and-shared-code-design.md`

**Conventions:** same as plugins-01-test-toolchain. Run from `plugins/`, use `docker compose run --rm php <cmd>`, and commit with `git add <paths>` then `git commit -m "..." -- <paths>` (the repo has unrelated staged deletions). Namespace `HyperPress\Utils`, PSR-4 rooted at `hyperpress-utils/src/`. Utils has no `Plugin` class: it only registers an autoloader. Other plugins call into Utils only from `plugins_loaded` or later, never at include time.

## File Structure

| Path | Responsibility |
|---|---|
| `hyperpress-utils/hyperpress-utils.php` | Renamed from `utils.php`. Header, ABSPATH guard, PSR-4 autoloader for `HyperPress\Utils` |
| `hyperpress-utils/src/Labels.php` | Pure label arrays for post types and taxonomies |
| `hyperpress-utils/src/PostTypeRegistrar.php` | `build()` (pure) and `register()` for post types |
| `hyperpress-utils/src/TaxonomyRegistrar.php` | `build()` (pure) and `register()` for taxonomies |
| `hyperpress-utils/src/Capabilities.php` | Per-post-type capability grant with a version gate |
| `hyperpress-utils/src/Breadcrumbs.php` | Breadcrumb builder (`build()` is pure) and filter registration; moved from the theme |
| `hyperpress-utils/src/Banner.php` | Banner assembly (`apply()` pure) and filter registration |
| `hyperpress-utils/src/Controls/PostTypeDropdownControl.php`, `CategoryDropdownControl.php` | Moved and renamed Customizer controls |
| `hyperpress-utils/lib/` | Deleted when empty |
| `tests/unit/Utils/*Test.php` | Pure unit tests |
| `tests/integration/Utils/*Test.php` | Hook and role behavior tests |

---

## Task 0: Consumer audit of legacy symbols (read-only)

Renaming namespaces breaks any external caller. Find them before deleting anything.

- [x] **Step 1: Search for cross-plugin and theme callers**

Run (from `plugins/`): `grep -rnE "HYPER_Press_[A-Za-z]+|HYPERpress_Dropdown|get_sorted_seasons|hyperpress_(breadcrumbs|banner)_[a-z_]+" --include='*.php' ../theme . | grep -v '^./vendor' | grep -v "namespace HYPER_Press"`
Expected: a list of every reference outside each plugin's own `namespace` line.

- [x] **Step 2: Classify each hit**

For each hit record: file, line, and whether it is (a) inside the same plugin (will be rewritten in B2), (b) another plugin (must be rewritten in that plugin's task), or (c) the theme (OUT OF SCOPE: add to the "theme checklist" at the bottom of this plan). Save the list in the "Execution notes" section at the end of this file.

- [x] **Step 3: Check how the theme reads banner `type`**

Run: `grep -rn "\['type'\]\|\"type\"" ../theme/library ../theme/template-parts 2>/dev/null | head -30`
Expected: shows how `type` is consumed. Banner `type` values (`newsletter`, `sponsor`, `competition`, `season`) are kept as the unprefixed slug in this plan. If the theme switches on them in a way that would break, record it in the theme checklist. No code change here.

- [x] **Step 4: Commit the notes only after Task 7**, together with the rest of this plan's execution notes.

---

## Task 1: Convert `hyperpress-utils` to the new structure

**Files:**
- Rename: `hyperpress-utils/utils.php` to `hyperpress-utils/hyperpress-utils.php`
- Modify: the renamed file (header and body)

- [x] **Step 1: Rename**

Run: `git mv hyperpress-utils/utils.php hyperpress-utils/hyperpress-utils.php`

- [x] **Step 2: Replace the file contents**

Keep the existing `Plugin Name`, `Plugin URI`, `Description`, `Version`, `Author`, `Author URI`, `License`, `License URI`, `Text Domain`, `Domain Path` values. Remove `GitHub Plugin URI`. Set `Requires at least: 6.9`, `Requires PHP: 8.3`. Add no `Requires Plugins` line (utils requires nothing; per the table). Body:

```php
<?php
/*
 * (header fields as above, no GitHub Plugin URI, no Requires Plugins)
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix = 'HyperPress\\Utils\\';
		if ( 0 !== strpos( $class_name, $prefix ) ) {
			return;
		}
		$file = __DIR__ . '/src/' . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
		if ( is_file( $file ) ) {
			require_once $file;
		}
	}
);
```

Do NOT `require` the old `lib/controls/*` files any more.

- [x] **Step 3: Verify header lint for utils**

Run: `docker compose run --rm php composer test:unit -- --filter 'PluginHeadersTest.*hyperpress-utils'`
Expected: all five utils cases PASS. (If `Requires at least`/`Requires PHP` fail, fix the header values.)

- [x] **Step 4: Commit**

```bash
git add hyperpress-utils/hyperpress-utils.php
git commit -m "refactor(utils): rename main file, add PSR-4 autoloader, fix header" -- hyperpress-utils/utils.php hyperpress-utils/hyperpress-utils.php
```

---

## Task 2: `Labels` and `PostTypeRegistrar` (TDD)

**Files:**
- Create: `hyperpress-utils/src/Labels.php`, `hyperpress-utils/src/PostTypeRegistrar.php`, `tests/unit/Utils/PostTypeRegistrarTest.php`
- Modify: `tests/bootstrap-unit.php`

- [x] **Step 1: Give the unit bootstrap a translation stub**

Append to `tests/bootstrap-unit.php`:

```php

define( 'HYPERPRESS_TESTING', true ); // Lets hyperpress-utils/src files load without WordPress.

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore
		return $text;
	}
}

$hyperpress_utils_autoload = dirname( __DIR__ ) . '/hyperpress-utils/src/';
spl_autoload_register(
	static function ( string $class_name ) use ( $hyperpress_utils_autoload ): void {
		$prefix = 'HyperPress\\Utils\\';
		if ( 0 === strpos( $class_name, $prefix ) ) {
			$file = $hyperpress_utils_autoload . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
			if ( is_file( $file ) ) {
				require_once $file;
			}
		}
	}
);
```

- [x] **Step 2: Write the failing test `tests/unit/Utils/PostTypeRegistrarTest.php`**

```php
<?php
namespace HyperPress\Tests\Unit\Utils;

use HyperPress\Utils\Labels;
use HyperPress\Utils\PostTypeRegistrar;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PostTypeRegistrarTest extends TestCase {

	private function definition( array $overrides = array() ): array {
		return array_merge(
			array(
				'key'           => 'hyper_robot',
				'singular'      => 'Robot',
				'plural'        => 'Robots',
				'description'   => 'Robots made by Team HYPER',
				'slug'          => 'robot',
				'archive'       => 'robots',
				'menu_position' => 23,
				'menu_icon'     => 'dashicons-superhero',
				'supports'      => array( 'title', 'editor', 'thumbnail', 'revisions' ),
			),
			$overrides
		);
	}

	public function test_labels_are_generated_from_singular_and_plural(): void {
		$labels = Labels::post_type( 'Robot', 'Robots' );
		$this->assertSame( 'Robots', $labels['name'] );
		$this->assertSame( 'Robot', $labels['singular_name'] );
		$this->assertSame( 'Robots', $labels['menu_name'] );
		$this->assertSame( 'All Robots', $labels['all_items'] );
		$this->assertSame( 'Add New Robot', $labels['add_new_item'] );
		$this->assertSame( 'No robots found', $labels['not_found'] );
		$this->assertSame( 'No robots found in Trash', $labels['not_found_in_trash'] );
	}

	public function test_build_applies_defaults(): void {
		$built = PostTypeRegistrar::build( $this->definition() );
		$this->assertSame( 'hyper_robot', $built['key'] );
		$args = $built['args'];
		$this->assertTrue( $args['public'] );
		$this->assertTrue( $args['publicly_queryable'] );
		$this->assertFalse( $args['exclude_from_search'] );
		$this->assertFalse( $args['show_in_rest'], 'REST is off by default: nothing uses it, and the classic editor is required' );
		$this->assertTrue( $args['query_var'] );
		$this->assertFalse( $args['hierarchical'] );
		$this->assertSame( 'robots', $args['has_archive'] );
		$this->assertSame( 23, $args['menu_position'] );
		$this->assertSame( 'dashicons-superhero', $args['menu_icon'] );
		$this->assertSame( array( 'title', 'editor', 'thumbnail', 'revisions' ), $args['supports'] );
		$this->assertSame( 'Robots made by Team HYPER', $args['description'] );
	}

	public function test_build_uses_specific_capabilities(): void {
		$args = PostTypeRegistrar::build( $this->definition() )['args'];
		$this->assertSame( array( 'hyper_robot', 'hyper_robots' ), $args['capability_type'] );
		$this->assertTrue( $args['map_meta_cap'] );
	}

	public function test_build_rewrite_uses_public_slug_not_the_key(): void {
		$args = PostTypeRegistrar::build( $this->definition() )['args'];
		$this->assertSame( 'robot', $args['rewrite']['slug'] );
		$this->assertFalse( $args['rewrite']['with_front'] );
		$this->assertArrayNotHasKey( 'hierarchical', $args['rewrite'] );

		$hier = PostTypeRegistrar::build( $this->definition( array( 'rewrite_hierarchical' => true ) ) )['args'];
		$this->assertTrue( $hier['rewrite']['hierarchical'] );
	}

	public function test_build_drops_generator_leftovers(): void {
		$args = PostTypeRegistrar::build( $this->definition() )['args'];
		foreach ( array( '_slug_changed', 'order', 'sort', 'status_column', 'rest_base' ) as $leftover ) {
			$this->assertArrayNotHasKey( $leftover, $args, $leftover );
		}
	}

	public function test_overrides_win_last(): void {
		$args = PostTypeRegistrar::build(
			$this->definition( array( 'args' => array( 'public' => false, 'show_in_rest' => true, 'rewrite' => false ) ) )
		)['args'];
		$this->assertFalse( $args['public'] );
		$this->assertTrue( $args['show_in_rest'] );
		$this->assertFalse( $args['rewrite'] );
	}

	public function test_hierarchical_flag_is_passed_through(): void {
		$args = PostTypeRegistrar::build( $this->definition( array( 'hierarchical' => true ) ) )['args'];
		$this->assertTrue( $args['hierarchical'] );
	}

	/** @dataProvider invalid_keys */
	public function test_invalid_keys_are_rejected( string $key ): void {
		$this->expectException( InvalidArgumentException::class );
		PostTypeRegistrar::build( $this->definition( array( 'key' => $key ) ) );
	}

	public static function invalid_keys(): array {
		return array(
			'no prefix'   => array( 'robot' ),
			'uppercase'   => array( 'hyper_Robot' ),
			'space'       => array( 'hyper robot' ),
			'too long'    => array( 'hyper_' . str_repeat( 'a', 15 ) ),
			'empty'       => array( '' ),
		);
	}

	public function test_20_character_key_is_accepted(): void {
		$key = 'hyper_' . str_repeat( 'a', 14 );
		$this->assertSame( 20, strlen( $key ) );
		$this->assertSame( $key, PostTypeRegistrar::build( $this->definition( array( 'key' => $key ) ) )['key'] );
	}

	public function test_missing_required_field_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );
		$definition = $this->definition();
		unset( $definition['slug'] );
		PostTypeRegistrar::build( $definition );
	}
}
```

- [x] **Step 3: Run and confirm FAIL**

Run: `docker compose run --rm php composer test:unit -- --filter PostTypeRegistrarTest`
Expected: errors "Class HyperPress\Utils\PostTypeRegistrar not found".

- [x] **Step 4: Write `hyperpress-utils/src/Labels.php`**

```php
<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Labels {

	/** @return array<string,string> */
	public static function post_type( string $singular, string $plural ): array {
		$s  = $singular;
		$p  = $plural;
		$ls = strtolower( $singular );
		$lp = strtolower( $plural );

		return array(
			'name'                  => $p,
			'singular_name'         => $s,
			'menu_name'             => $p,
			'name_admin_bar'        => $s,
			'add_new'               => __( 'Add New', 'hyperpress' ),
			/* translators: %s: singular post type name. */
			'add_new_item'          => sprintf( __( 'Add New %s', 'hyperpress' ), $s ),
			/* translators: %s: singular post type name. */
			'edit_item'             => sprintf( __( 'Edit %s', 'hyperpress' ), $s ),
			/* translators: %s: singular post type name. */
			'new_item'              => sprintf( __( 'New %s', 'hyperpress' ), $s ),
			/* translators: %s: singular post type name. */
			'view_item'             => sprintf( __( 'View %s', 'hyperpress' ), $s ),
			/* translators: %s: plural post type name. */
			'view_items'            => sprintf( __( 'View %s', 'hyperpress' ), $p ),
			/* translators: %s: plural post type name. */
			'all_items'             => sprintf( __( 'All %s', 'hyperpress' ), $p ),
			/* translators: %s: plural post type name. */
			'search_items'          => sprintf( __( 'Search %s', 'hyperpress' ), $p ),
			/* translators: %s: singular post type name. */
			'parent_item_colon'     => sprintf( __( 'Parent %s:', 'hyperpress' ), $s ),
			/* translators: %s: lowercase plural post type name. */
			'not_found'             => sprintf( __( 'No %s found', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase plural post type name. */
			'not_found_in_trash'    => sprintf( __( 'No %s found in Trash', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase singular post type name. */
			'featured_image'        => sprintf( __( 'Featured image for this %s', 'hyperpress' ), $ls ),
			/* translators: %s: lowercase singular post type name. */
			'set_featured_image'    => sprintf( __( 'Set featured image for this %s', 'hyperpress' ), $ls ),
			/* translators: %s: lowercase singular post type name. */
			'remove_featured_image' => sprintf( __( 'Remove featured image for this %s', 'hyperpress' ), $ls ),
			/* translators: %s: lowercase singular post type name. */
			'use_featured_image'    => sprintf( __( 'Use as featured image for this %s', 'hyperpress' ), $ls ),
			/* translators: %s: singular post type name. */
			'archives'              => sprintf( __( '%s archives', 'hyperpress' ), $s ),
			/* translators: %s: lowercase singular post type name. */
			'insert_into_item'      => sprintf( __( 'Insert into %s', 'hyperpress' ), $ls ),
			/* translators: %s: lowercase singular post type name. */
			'uploaded_to_this_item' => sprintf( __( 'Uploaded to this %s', 'hyperpress' ), $ls ),
			/* translators: %s: lowercase plural post type name. */
			'filter_items_list'     => sprintf( __( 'Filter %s list', 'hyperpress' ), $lp ),
			/* translators: %s: plural post type name. */
			'items_list_navigation' => sprintf( __( '%s list navigation', 'hyperpress' ), $p ),
			/* translators: %s: plural post type name. */
			'items_list'            => sprintf( __( '%s list', 'hyperpress' ), $p ),
			/* translators: %s: singular post type name. */
			'attributes'            => sprintf( __( '%s attributes', 'hyperpress' ), $s ),
		);
	}

	/** @return array<string,string> */
	public static function taxonomy( string $singular, string $plural ): array {
		$s  = $singular;
		$p  = $plural;
		$lp = strtolower( $plural );

		return array(
			'name'                       => $p,
			'singular_name'              => $s,
			'menu_name'                  => $p,
			/* translators: %s: plural taxonomy name. */
			'all_items'                  => sprintf( __( 'All %s', 'hyperpress' ), $p ),
			/* translators: %s: singular taxonomy name. */
			'edit_item'                  => sprintf( __( 'Edit %s', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'view_item'                  => sprintf( __( 'View %s', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'update_item'                => sprintf( __( 'Update %s', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'add_new_item'               => sprintf( __( 'Add New %s', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'new_item_name'              => sprintf( __( 'New %s Name', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'parent_item'                => sprintf( __( 'Parent %s', 'hyperpress' ), $s ),
			/* translators: %s: singular taxonomy name. */
			'parent_item_colon'          => sprintf( __( 'Parent %s:', 'hyperpress' ), $s ),
			/* translators: %s: plural taxonomy name. */
			'search_items'               => sprintf( __( 'Search %s', 'hyperpress' ), $p ),
			/* translators: %s: plural taxonomy name. */
			'popular_items'              => sprintf( __( 'Popular %s', 'hyperpress' ), $p ),
			/* translators: %s: lowercase plural taxonomy name. */
			'separate_items_with_commas' => sprintf( __( 'Separate %s with commas', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase plural taxonomy name. */
			'add_or_remove_items'        => sprintf( __( 'Add or remove %s', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase plural taxonomy name. */
			'choose_from_most_used'      => sprintf( __( 'Choose from the most used %s', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase plural taxonomy name. */
			'not_found'                  => sprintf( __( 'No %s found', 'hyperpress' ), $lp ),
			/* translators: %s: lowercase plural taxonomy name. */
			'no_terms'                   => sprintf( __( 'No %s', 'hyperpress' ), $lp ),
			/* translators: %s: plural taxonomy name. */
			'items_list_navigation'      => sprintf( __( '%s list navigation', 'hyperpress' ), $p ),
			/* translators: %s: plural taxonomy name. */
			'items_list'                 => sprintf( __( '%s list', 'hyperpress' ), $p ),
			/* translators: %s: plural taxonomy name. */
			'back_to_items'              => sprintf( __( '&larr; Back to %s', 'hyperpress' ), $p ),
		);
	}
}
```

Note on the guard line `defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );`: the unit bootstrap runs without WordPress and defines `HYPERPRESS_TESTING` (Step 1). Use the same guard line in every file under `hyperpress-utils/src/`. Class files only define classes and have no side effects, so this guard is defense in depth, not security.

- [x] **Step 5: Write `hyperpress-utils/src/PostTypeRegistrar.php`**

```php
<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use InvalidArgumentException;

final class PostTypeRegistrar {

	public const KEY_PATTERN = '/^hyper_[a-z0-9_]{1,14}$/'; // 20 chars max including "hyper_".

	private const DEFAULTS = array(
		'public'              => true,
		'exclude_from_search' => false,
		'publicly_queryable'  => true,
		'show_ui'             => true,
		'show_in_nav_menus'   => true,
		'show_in_admin_bar'   => true,
		'show_in_rest'        => false,
		'show_in_menu'        => true,
		'query_var'           => true,
		'can_export'          => true,
		'delete_with_user'    => false,
		'taxonomies'          => array(),
	);

	/**
	 * Register a post type from a definition.
	 *
	 * @param array $definition See build().
	 */
	public static function register( array $definition ): void {
		$built = self::build( $definition );
		register_post_type( $built['key'], $built['args'] );
	}

	/**
	 * Pure: turn a definition into register_post_type() input.
	 *
	 * Required: key, singular, plural, slug.
	 * Optional: description, archive (string|false), menu_position, menu_icon, supports,
	 * hierarchical (bool), rewrite_hierarchical (bool), cap_plural (string), args (overrides, win last).
	 *
	 * @return array{key:string,args:array}
	 */
	public static function build( array $definition ): array {
		foreach ( array( 'key', 'singular', 'plural', 'slug' ) as $required ) {
			if ( empty( $definition[ $required ] ) ) {
				throw new InvalidArgumentException( "Post type definition is missing '$required'." );
			}
		}
		$key = (string) $definition['key'];
		if ( ! preg_match( self::KEY_PATTERN, $key ) ) {
			throw new InvalidArgumentException( "Invalid post type key '$key': use hyper_ + lowercase letters, digits, underscores, max 20 characters." );
		}

		$rewrite = array(
			'with_front' => false,
			'slug'       => $definition['slug'],
		);
		if ( ! empty( $definition['rewrite_hierarchical'] ) ) {
			$rewrite['hierarchical'] = true;
		}

		$derived = array(
			'label'           => $definition['plural'],
			'description'     => $definition['description'] ?? '',
			'labels'          => Labels::post_type( $definition['singular'], $definition['plural'] ),
			'hierarchical'    => $definition['hierarchical'] ?? false,
			'has_archive'     => $definition['archive'] ?? false,
			'menu_position'   => $definition['menu_position'] ?? null,
			'menu_icon'       => $definition['menu_icon'] ?? null,
			'supports'        => $definition['supports'] ?? array( 'title', 'editor' ),
			'rewrite'         => $rewrite,
			'capability_type' => array( $key, $definition['cap_plural'] ?? $key . 's' ),
			'map_meta_cap'    => true,
		);

		return array(
			'key'  => $key,
			'args' => array_merge( self::DEFAULTS, $derived, $definition['args'] ?? array() ),
		);
	}
}
```

- [x] **Step 6: Run and confirm PASS**

Run: `docker compose run --rm php composer test:unit -- --filter PostTypeRegistrarTest`
Expected: all tests PASS. If `test_invalid_keys_are_rejected` with `'too long'` fails, re-check that `hyper_` plus 15 chars is 21 characters and the pattern's `{1,14}` upper bound is 14.

- [x] **Step 7: Commit**

```bash
git add hyperpress-utils/src/Labels.php hyperpress-utils/src/PostTypeRegistrar.php tests/unit/Utils/PostTypeRegistrarTest.php tests/bootstrap-unit.php
git commit -m "feat(utils): add Labels and PostTypeRegistrar" -- hyperpress-utils/src/Labels.php hyperpress-utils/src/PostTypeRegistrar.php tests/unit/Utils/PostTypeRegistrarTest.php tests/bootstrap-unit.php
```

---

## Task 3: `TaxonomyRegistrar` (TDD)

**Files:**
- Create: `hyperpress-utils/src/TaxonomyRegistrar.php`, `tests/unit/Utils/TaxonomyRegistrarTest.php`

- [x] **Step 1: Write the failing test**

```php
<?php
namespace HyperPress\Tests\Unit\Utils;

use HyperPress\Utils\TaxonomyRegistrar;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TaxonomyRegistrarTest extends TestCase {

	private function definition( array $overrides = array() ): array {
		return array_merge(
			array(
				'key'          => 'hyper_district',
				'singular'     => 'District',
				'plural'       => 'Districts',
				'description'  => 'Subdivisions within FRC',
				'slug'         => 'districts',
				'object_types' => array( 'hyper_competition' ),
			),
			$overrides
		);
	}

	public function test_build_applies_defaults(): void {
		$built = TaxonomyRegistrar::build( $this->definition() );
		$args  = $built['args'];
		$this->assertSame( 'hyper_district', $built['key'] );
		$this->assertTrue( $args['public'] );
		$this->assertTrue( $args['publicly_queryable'] );
		$this->assertTrue( $args['hierarchical'] );
		$this->assertTrue( $args['show_ui'] );
		$this->assertFalse( $args['show_in_rest'] );
		$this->assertTrue( $args['query_var'] );
		$this->assertSame( 'districts', $args['rewrite']['slug'] );
		$this->assertFalse( $args['rewrite']['with_front'] );
		$this->assertTrue( $args['rewrite']['hierarchical'] );
	}

	public function test_build_restricts_term_management_to_administrators(): void {
		$caps = TaxonomyRegistrar::build( $this->definition() )['args']['capabilities'];
		$this->assertSame( 'manage_options', $caps['manage_terms'] );
		$this->assertSame( 'manage_options', $caps['edit_terms'] );
		$this->assertSame( 'manage_options', $caps['delete_terms'] );
		$this->assertSame( 'edit_posts', $caps['assign_terms'] );
	}

	public function test_build_drops_generator_leftovers(): void {
		$args = TaxonomyRegistrar::build( $this->definition() )['args'];
		foreach ( array( 'sort', 'order', 'rest_base' ) as $leftover ) {
			$this->assertArrayNotHasKey( $leftover, $args, $leftover );
		}
	}

	public function test_null_slug_disables_rewrite(): void {
		$args = TaxonomyRegistrar::build( $this->definition( array( 'slug' => null, 'public' => false, 'publicly_queryable' => false ) ) )['args'];
		$this->assertFalse( $args['rewrite'] );
		$this->assertFalse( $args['public'] );
		$this->assertFalse( $args['publicly_queryable'] );
	}

	public function test_flags_are_passed_through(): void {
		$args = TaxonomyRegistrar::build(
			$this->definition( array( 'hierarchical' => false, 'show_tagcloud' => true, 'show_in_quick_edit' => false, 'show_admin_column' => true, 'meta_box_cb' => false ) )
		)['args'];
		$this->assertFalse( $args['hierarchical'] );
		$this->assertTrue( $args['show_tagcloud'] );
		$this->assertFalse( $args['show_in_quick_edit'] );
		$this->assertTrue( $args['show_admin_column'] );
		$this->assertFalse( $args['meta_box_cb'] );
	}

	public function test_resolve_object_types_accepts_a_list(): void {
		$this->assertSame( array( 'a', 'b' ), TaxonomyRegistrar::resolve_object_types( array( 'a', 'b' ), static fn() => array( 'x' ) ) );
	}

	public function test_resolve_object_types_reads_a_theme_mod_name(): void {
		$seen = null;
		$got  = TaxonomyRegistrar::resolve_object_types(
			'hyperpress_season_post_types',
			static function ( string $mod, $default ) use ( &$seen ) {
				$seen = array( $mod, $default );
				return array( 'hyper_award' );
			}
		);
		$this->assertSame( array( 'hyperpress_season_post_types', array() ), $seen );
		$this->assertSame( array( 'hyper_award' ), $got );
	}

	public function test_resolve_object_types_defaults_to_empty_not_post(): void {
		$this->assertSame( array(), TaxonomyRegistrar::resolve_object_types( 'hyperpress_x', static fn( $mod, $default ) => $default ) );
		$this->assertSame( array(), TaxonomyRegistrar::resolve_object_types( null, static fn() => array( 'post' ) ) );
	}

	public function test_a_single_string_theme_mod_value_becomes_a_list(): void {
		$this->assertSame( array( 'hyper_award' ), TaxonomyRegistrar::resolve_object_types( 'm', static fn() => 'hyper_award' ) );
	}

	public function test_invalid_key_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );
		TaxonomyRegistrar::build( $this->definition( array( 'key' => 'district' ) ) );
	}
}
```

- [x] **Step 2: Run and confirm FAIL** (`Class ... TaxonomyRegistrar not found`)

Run: `docker compose run --rm php composer test:unit -- --filter TaxonomyRegistrarTest`

- [x] **Step 3: Write `hyperpress-utils/src/TaxonomyRegistrar.php`**

The default `slug` behavior: a missing `slug` key is an error; an explicit `null` disables rewrite.

```php
<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use InvalidArgumentException;

final class TaxonomyRegistrar {

	private const DEFAULTS = array(
		'show_ui'           => true,
		'show_in_menu'      => true,
		'show_in_nav_menus' => true,
		'show_in_rest'      => false,
		'query_var'         => true,
		'capabilities'      => array(
			'manage_terms' => 'manage_options',
			'edit_terms'   => 'manage_options',
			'delete_terms' => 'manage_options',
			'assign_terms' => 'edit_posts',
		),
	);

	/**
	 * Register a taxonomy from a definition. `object_types` may be a list of post types, or the
	 * name of a theme mod holding the list (default: attached to nothing).
	 */
	public static function register( array $definition ): void {
		$built = self::build( $definition );
		register_taxonomy(
			$built['key'],
			self::resolve_object_types( $definition['object_types'] ?? null, 'get_theme_mod' ),
			$built['args']
		);
	}

	/**
	 * @param array|string|null $object_types List, theme mod name, or null.
	 * @param callable          $get_mod      function( string $name, $default ): mixed
	 * @return string[]
	 */
	public static function resolve_object_types( $object_types, callable $get_mod ): array {
		if ( is_array( $object_types ) ) {
			return $object_types;
		}
		if ( is_string( $object_types ) && '' !== $object_types ) {
			return array_values( (array) $get_mod( $object_types, array() ) );
		}
		return array();
	}

	/**
	 * Pure: turn a definition into register_taxonomy() args.
	 *
	 * Required: key, singular, plural, and the `slug` key (null disables rewrite).
	 * Optional: description, public, publicly_queryable, hierarchical, show_tagcloud, show_in_quick_edit,
	 * show_admin_column, meta_box_cb, args (overrides, win last).
	 *
	 * @return array{key:string,args:array}
	 */
	public static function build( array $definition ): array {
		foreach ( array( 'key', 'singular', 'plural' ) as $required ) {
			if ( empty( $definition[ $required ] ) ) {
				throw new InvalidArgumentException( "Taxonomy definition is missing '$required'." );
			}
		}
		if ( ! array_key_exists( 'slug', $definition ) ) {
			throw new InvalidArgumentException( "Taxonomy definition is missing 'slug' (use null for no rewrite)." );
		}
		$key = (string) $definition['key'];
		if ( ! preg_match( PostTypeRegistrar::KEY_PATTERN, $key ) ) {
			throw new InvalidArgumentException( "Invalid taxonomy key '$key': use hyper_ + lowercase letters, digits, underscores, max 20 characters." );
		}

		$rewrite = null === $definition['slug']
			? false
			: array(
				'with_front'   => false,
				'hierarchical' => true,
				'slug'         => $definition['slug'],
			);

		$derived = array(
			'label'              => $definition['plural'],
			'description'        => $definition['description'] ?? '',
			'labels'             => Labels::taxonomy( $definition['singular'], $definition['plural'] ),
			'public'             => $definition['public'] ?? true,
			'publicly_queryable' => $definition['publicly_queryable'] ?? true,
			'hierarchical'       => $definition['hierarchical'] ?? true,
			'show_tagcloud'      => $definition['show_tagcloud'] ?? false,
			'show_in_quick_edit' => $definition['show_in_quick_edit'] ?? true,
			'show_admin_column'  => $definition['show_admin_column'] ?? false,
			'rewrite'            => $rewrite,
		);
		if ( array_key_exists( 'meta_box_cb', $definition ) ) {
			$derived['meta_box_cb'] = $definition['meta_box_cb'];
		}

		return array(
			'key'  => $key,
			'args' => array_merge( self::DEFAULTS, $derived, $definition['args'] ?? array() ),
		);
	}
}
```

- [x] **Step 4: Run and confirm PASS**

Run: `docker compose run --rm php composer test:unit -- --filter TaxonomyRegistrarTest`
Expected: PASS.

- [x] **Step 5: Commit**

```bash
git add hyperpress-utils/src/TaxonomyRegistrar.php tests/unit/Utils/TaxonomyRegistrarTest.php
git commit -m "feat(utils): add TaxonomyRegistrar" -- hyperpress-utils/src/TaxonomyRegistrar.php tests/unit/Utils/TaxonomyRegistrarTest.php
```

---

## Task 4: `Capabilities` (TDD)

The capability set is read from the registered post type object (`cap`), so it always matches what WordPress checks. Granting is idempotent and gated by a per-plugin version option so a plugin update that changes capabilities regrants.

**Files:**
- Create: `hyperpress-utils/src/Capabilities.php`, `tests/unit/Utils/CapabilitiesTest.php`, `tests/integration/Utils/CapabilitiesGrantTest.php`

- [x] **Step 1: Write the failing unit test `tests/unit/Utils/CapabilitiesTest.php`**

```php
<?php
namespace HyperPress\Tests\Unit\Utils;

use HyperPress\Utils\Capabilities;
use PHPUnit\Framework\TestCase;
use stdClass;

final class CapabilitiesTest extends TestCase {
	public function test_meta_caps_and_read_are_excluded_and_values_are_unique(): void {
		$cap                     = new stdClass();
		$cap->edit_post          = 'edit_hyper_award';
		$cap->read_post          = 'read_hyper_award';
		$cap->delete_post        = 'delete_hyper_award';
		$cap->edit_posts         = 'edit_hyper_awards';
		$cap->edit_others_posts  = 'edit_others_hyper_awards';
		$cap->publish_posts      = 'publish_hyper_awards';
		$cap->read_private_posts = 'read_private_hyper_awards';
		$cap->read               = 'read';
		$cap->create_posts       = 'edit_hyper_awards';

		$this->assertSame(
			array( 'edit_hyper_awards', 'edit_others_hyper_awards', 'publish_hyper_awards', 'read_private_hyper_awards' ),
			Capabilities::primitive_caps( $cap )
		);
	}

	public function test_roles_are_administrator_and_editor(): void {
		$this->assertSame( array( 'administrator', 'editor' ), Capabilities::ROLES );
	}
}
```

- [x] **Step 2: Write the failing integration test `tests/integration/Utils/CapabilitiesGrantTest.php`**

```php
<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Capabilities;
use WP_UnitTestCase;

final class CapabilitiesGrantTest extends WP_UnitTestCase {

	private const OPTION = 'hyperpress_test_caps_version';

	public function set_up(): void {
		parent::set_up();
		register_post_type(
			'hyper_gizmo',
			array(
				'public'          => true,
				'capability_type' => array( 'hyper_gizmo', 'hyper_gizmos' ),
				'map_meta_cap'    => true,
			)
		);
		delete_option( self::OPTION );
		foreach ( Capabilities::ROLES as $role ) {
			get_role( $role )->remove_cap( 'edit_hyper_gizmos' );
		}
	}

	public function tear_down(): void {
		unregister_post_type( 'hyper_gizmo' );
		delete_option( self::OPTION );
		parent::tear_down();
	}

	public function test_grant_gives_administrator_and_editor_the_caps_and_not_subscriber(): void {
		Capabilities::grant( array( 'hyper_gizmo' ) );
		$this->assertTrue( get_role( 'administrator' )->has_cap( 'edit_hyper_gizmos' ) );
		$this->assertTrue( get_role( 'editor' )->has_cap( 'edit_hyper_gizmos' ) );
		$this->assertFalse( get_role( 'subscriber' )->has_cap( 'edit_hyper_gizmos' ) );
	}

	public function test_grant_is_idempotent(): void {
		Capabilities::grant( array( 'hyper_gizmo' ) );
		$before = get_role( 'editor' )->capabilities;
		Capabilities::grant( array( 'hyper_gizmo' ) );
		$this->assertSame( $before, get_role( 'editor' )->capabilities );
	}

	public function test_ensure_grants_once_then_skips_until_the_version_changes(): void {
		Capabilities::ensure( self::OPTION, '1.0.0', array( 'hyper_gizmo' ) );
		$this->assertSame( '1.0.0', get_option( self::OPTION ) );
		$this->assertTrue( get_role( 'editor' )->has_cap( 'edit_hyper_gizmos' ) );

		get_role( 'editor' )->remove_cap( 'edit_hyper_gizmos' );
		Capabilities::ensure( self::OPTION, '1.0.0', array( 'hyper_gizmo' ) );
		$this->assertFalse( get_role( 'editor' )->has_cap( 'edit_hyper_gizmos' ), 'same version must not regrant' );

		Capabilities::ensure( self::OPTION, '1.1.0', array( 'hyper_gizmo' ) );
		$this->assertTrue( get_role( 'editor' )->has_cap( 'edit_hyper_gizmos' ), 'stale version must regrant' );
		$this->assertSame( '1.1.0', get_option( self::OPTION ) );
	}

	public function test_grant_ignores_unregistered_post_types(): void {
		Capabilities::grant( array( 'hyper_does_not_exist' ) );
		$this->assertTrue( true ); // No exception, no warning.
	}
}
```

- [x] **Step 3: Run and confirm FAIL**

Run: `docker compose run --rm php composer test:unit -- --filter CapabilitiesTest`
Expected: class not found.

- [x] **Step 4: Write `hyperpress-utils/src/Capabilities.php`**

```php
<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Capabilities {

	public const ROLES = array( 'administrator', 'editor' );

	/**
	 * Primitive capabilities of a post type capability object, without meta caps or `read`.
	 *
	 * @param object $cap The `cap` property of a WP_Post_Type.
	 * @return string[]
	 */
	public static function primitive_caps( object $cap ): array {
		$caps = (array) $cap;
		unset( $caps['edit_post'], $caps['read_post'], $caps['delete_post'], $caps['read'] );
		return array_values( array_unique( $caps ) );
	}

	/**
	 * Grant every primitive capability of the given post types to Administrator and Editor.
	 * Post types must already be registered. Unregistered keys are ignored.
	 *
	 * @param string[] $post_types Registered post type keys.
	 */
	public static function grant( array $post_types ): void {
		$caps = array();
		foreach ( $post_types as $post_type ) {
			$object = get_post_type_object( $post_type );
			if ( $object ) {
				$caps = array_merge( $caps, self::primitive_caps( $object->cap ) );
			}
		}
		foreach ( self::ROLES as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( array_unique( $caps ) as $cap ) {
				if ( ! $role->has_cap( $cap ) ) {
					$role->add_cap( $cap );
				}
			}
		}
	}

	/**
	 * Grant once per version. Call from `init` (after registration) and from activation.
	 *
	 * @param string   $option     Option that stores the last granted version, e.g. hyperpress_award_caps_version.
	 * @param string   $version    Current plugin version.
	 * @param string[] $post_types Post type keys owned by the calling plugin.
	 */
	public static function ensure( string $option, string $version, array $post_types ): void {
		if ( get_option( $option ) === $version ) {
			return;
		}
		self::grant( $post_types );
		update_option( $option, $version );
	}
}
```

- [x] **Step 5: Run unit and integration tests**

Run: `docker compose run --rm php composer test:unit -- --filter CapabilitiesTest`
Expected: PASS.
Run: `docker compose run --rm php composer test:integration -- --filter CapabilitiesGrantTest`
Expected: PASS (these use their own `hyper_gizmo` type, so they do not depend on any plugin migration).

- [x] **Step 6: Commit**

```bash
git add hyperpress-utils/src/Capabilities.php tests/unit/Utils/CapabilitiesTest.php tests/integration/Utils/CapabilitiesGrantTest.php
git commit -m "feat(utils): add Capabilities with version-gated grant" -- hyperpress-utils/src/Capabilities.php tests/unit/Utils/CapabilitiesTest.php tests/integration/Utils/CapabilitiesGrantTest.php
```

---

## Task 5: `Breadcrumbs` builder (TDD)

The theme's `hyperpress_breadcrumbs_custom_post_type()` moves here as real logic (see the theme spec, section 5). The unit-tested pure method `build()` assembles the trail; `register()` gathers the request context from WordPress and hooks the theme's `hyperpress_breadcrumbs_content` filter. The legacy theme function had leftover debug output and a bug on season archives (it read `->term_id` from a taxonomy object); neither is carried over.

The crumb array shape (`title`, `url`, `classes.li`, `classes.bread`) and the CSS class names are preserved exactly, because the theme template and CSS may depend on them.

**Files:**
- Create: `hyperpress-utils/src/Breadcrumbs.php`, `tests/unit/Utils/BreadcrumbsTest.php`, `tests/integration/Utils/BreadcrumbsRequestTest.php`

- [x] **Step 1: Write the failing unit test `tests/unit/Utils/BreadcrumbsTest.php`**

```php
<?php
namespace HyperPress\Tests\Unit\Utils;

use HyperPress\Utils\Breadcrumbs;
use PHPUnit\Framework\TestCase;

final class BreadcrumbsTest extends TestCase {

	private function ctx( array $overrides = array() ): array {
		return array_merge(
			array(
				'post_type'   => 'hyper_sponsor',
				'label'       => 'Sponsors',
				'archive_url' => 'https://example.org/sponsors/',
				'is_singular' => false,
				'season'      => null,
				'title'       => '',
				'id'          => 0,
			),
			$overrides
		);
	}

	private function season(): array {
		return array(
			'name' => '2026',
			'slug' => '2026',
			'url'  => 'https://example.org/seasons/2026/sponsors/',
		);
	}

	public function test_archive_adds_only_the_post_type_crumb(): void {
		$trail = Breadcrumbs::build( array(), $this->ctx() );
		$this->assertCount( 1, $trail );
		$this->assertSame( 'Sponsors', $trail[0]['title'] );
		$this->assertSame( 'https://example.org/sponsors/', $trail[0]['url'] );
		$this->assertSame( array( 'item-archive-Sponsors' ), $trail[0]['classes']['li'] );
		$this->assertSame( array( 'bread-archive-Sponsors' ), $trail[0]['classes']['bread'] );
	}

	public function test_season_archive_adds_the_season_crumb(): void {
		$trail = Breadcrumbs::build( array(), $this->ctx( array( 'season' => $this->season() ) ) );
		$this->assertCount( 2, $trail );
		$this->assertSame( '2026', $trail[1]['title'] );
		$this->assertSame( 'https://example.org/seasons/2026/sponsors/', $trail[1]['url'] );
		$this->assertSame( array( 'item-2026-Sponsors' ), $trail[1]['classes']['li'] );
		$this->assertSame( array( 'bread-2026-Sponsors' ), $trail[1]['classes']['bread'] );
	}

	public function test_singular_adds_the_current_title_last_without_a_url(): void {
		$trail = Breadcrumbs::build(
			array(),
			$this->ctx( array( 'is_singular' => true, 'season' => $this->season(), 'title' => 'Acme', 'id' => 42 ) )
		);
		$this->assertCount( 3, $trail );
		$this->assertSame( 'Acme', $trail[2]['title'] );
		$this->assertArrayNotHasKey( 'url', $trail[2] );
		$this->assertSame( array( 'item-hyper_sponsor', 'item-hyper_sponsor42' ), $trail[2]['classes']['li'] );
		$this->assertSame( array( 'bread-hyper_sponsor', 'bread-hyper_sponsor42' ), $trail[2]['classes']['bread'] );
	}

	public function test_singular_without_a_season_skips_the_season_crumb(): void {
		$trail = Breadcrumbs::build( array(), $this->ctx( array( 'is_singular' => true, 'title' => 'Acme', 'id' => 7 ) ) );
		$this->assertSame( array( 'Sponsors', 'Acme' ), array_column( $trail, 'title' ) );
	}

	public function test_existing_crumbs_are_kept_in_front(): void {
		$trail = Breadcrumbs::build( array( array( 'title' => 'Home' ) ), $this->ctx() );
		$this->assertSame( array( 'Home', 'Sponsors' ), array_column( $trail, 'title' ) );
	}

	public function test_empty_label_skips_the_post_type_crumb(): void {
		$trail = Breadcrumbs::build( array(), $this->ctx( array( 'label' => '' ) ) );
		$this->assertSame( array(), $trail );
	}

	public function test_season_taxonomy_constant_is_the_new_key(): void {
		$this->assertSame( 'hyper_season', Breadcrumbs::SEASON_TAXONOMY );
	}
}
```

- [x] **Step 2: Run and confirm FAIL**

Run: `docker compose run --rm php composer test:unit -- --filter 'Utils.BreadcrumbsTest'`
Expected: class not found.

- [x] **Step 3: Write `hyperpress-utils/src/Breadcrumbs.php`**

```php
<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Breadcrumbs {

	public const SEASON_TAXONOMY = 'hyper_season';

	/**
	 * Add a trail for each post type to the theme's breadcrumb filter.
	 *
	 * @param string[] $post_types Post type keys.
	 */
	public static function register( array $post_types ): void {
		foreach ( $post_types as $post_type ) {
			add_filter(
				'hyperpress_breadcrumbs_content',
				static function ( $breadcrumbs ) use ( $post_type ) {
					$ctx = self::context( $post_type );
					return null === $ctx ? $breadcrumbs : self::build( (array) $breadcrumbs, $ctx );
				}
			);
		}
	}

	/**
	 * Pure: append the post type, season and current-item crumbs.
	 *
	 * @param array $breadcrumbs Existing crumbs.
	 * @param array $ctx         post_type, label, archive_url, is_singular, season (name, slug, url) or null, title, id.
	 */
	public static function build( array $breadcrumbs, array $ctx ): array {
		$label = (string) $ctx['label'];

		if ( '' !== $label ) {
			$breadcrumbs[] = array(
				'title'   => $label,
				'url'     => $ctx['archive_url'],
				'classes' => array(
					'li'    => array( 'item-archive-' . $label ),
					'bread' => array( 'bread-archive-' . $label ),
				),
			);
		}

		if ( ! empty( $ctx['season'] ) ) {
			$season        = $ctx['season'];
			$breadcrumbs[] = array(
				'title'   => $season['name'],
				'url'     => $season['url'],
				'classes' => array(
					'li'    => array( 'item-' . $season['slug'] . '-' . $label ),
					'bread' => array( 'bread-' . $season['slug'] . '-' . $label ),
				),
			);
		}

		if ( ! empty( $ctx['is_singular'] ) ) {
			$name          = (string) $ctx['post_type'];
			$breadcrumbs[] = array(
				'title'   => $ctx['title'],
				'classes' => array(
					'li'    => array( 'item-' . $name, 'item-' . $name . $ctx['id'] ),
					'bread' => array( 'bread-' . $name, 'bread-' . $name . $ctx['id'] ),
				),
			);
		}

		return $breadcrumbs;
	}

	/**
	 * Read the request into a context array, or null when the request is not this post type's
	 * singular or archive.
	 */
	private static function context( string $post_type ): ?array {
		$is_singular = is_singular( $post_type );
		if ( ! $is_singular && ! is_post_type_archive( $post_type ) ) {
			return null;
		}

		$object = get_post_type_object( $post_type );
		if ( ! $object ) {
			return null;
		}

		$term = null;
		if ( $is_singular ) {
			$terms = wp_get_object_terms(
				get_the_ID(),
				self::SEASON_TAXONOMY,
				array(
					'orderby' => 'name',
					'order'   => 'DESC',
				)
			);
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				// get_terms ordering is inconsistent even with orderby, so sort again by name.
				usort( $terms, static fn( $a, $b ) => $b->name <=> $a->name );
				$term = $terms[0];
			}
		} elseif ( is_tax( self::SEASON_TAXONOMY ) ) {
			$queried = get_queried_object();
			$term    = $queried instanceof \WP_Term ? $queried : null;
		}

		$season = null;
		if ( $term ) {
			$link   = get_term_link( $term );
			$season = array(
				'name' => $term->name,
				'slug' => $term->slug,
				'url'  => is_wp_error( $link ) ? '' : trailingslashit( $link ) . ( is_string( $object->has_archive ) ? $object->has_archive : '' ),
			);
		}

		return array(
			'post_type'   => $post_type,
			'label'       => (string) ( $object->labels->name ?? '' ),
			'archive_url' => (string) get_post_type_archive_link( $post_type ),
			'is_singular' => $is_singular,
			'season'      => $season,
			'title'       => $is_singular ? get_the_title() : '',
			'id'          => $is_singular ? (int) get_the_ID() : 0,
		);
	}
}
```

- [x] **Step 4: Run and confirm the unit test PASSES**

Run: `docker compose run --rm php composer test:unit -- --filter 'Utils.BreadcrumbsTest'`

- [x] **Step 5: Verify the season URL shape against the real rewrite rule**

The legacy theme code built the season crumb URL as `get_category_link( term_id ) . has_archive`, which is wrong for a custom taxonomy. The intended URL is the season term link followed by the post type's archive slug. Read the legacy season rule (`hyperpress-season/lib/rewrite.php`, or `src/Rewrite.php` once plugins-03-core-plugins Task 2 has run) and confirm that `{term link}{has_archive}/` matches its regex and maps to `hyper_season=<term>&post_type=<post type>`. If the rule's shape differs (for example it expects the post type key rather than the archive slug), change the URL expression in `context()` and the unit-test URLs to match, and record the difference in the notes. Do not leave the mismatch.

- [x] **Step 6: Write the integration test `tests/integration/Utils/BreadcrumbsRequestTest.php`**

```php
<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Breadcrumbs;
use WP_UnitTestCase;

final class BreadcrumbsRequestTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		if ( ! taxonomy_exists( 'hyper_season' ) ) {
			register_taxonomy( 'hyper_season', array(), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
		}
		register_post_type( 'hyper_gizmo', array( 'public' => true, 'has_archive' => 'gizmos', 'labels' => array( 'name' => 'Gizmos' ) ) );
		register_taxonomy_for_object_type( 'hyper_season', 'hyper_gizmo' );
		flush_rewrite_rules();
		Breadcrumbs::register( array( 'hyper_gizmo' ) );
	}

	public function tear_down(): void {
		unregister_post_type( 'hyper_gizmo' );
		parent::tear_down();
	}

	public function test_other_requests_are_untouched(): void {
		$this->go_to( home_url( '/' ) );
		$this->assertSame( array( array( 'title' => 'Home' ) ), apply_filters( 'hyperpress_breadcrumbs_content', array( array( 'title' => 'Home' ) ) ) );
	}

	public function test_post_type_archive_trail(): void {
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos' ), array_column( $trail, 'title' ) );
	}

	public function test_singular_uses_the_newest_season_and_the_title(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_gizmo', 'post_title' => 'Widget One' ) );
		$old     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2025' ) );
		$new     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026' ) );
		wp_set_object_terms( $post_id, array( $old, $new ), 'hyper_season' );

		$this->go_to( get_permalink( $post_id ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', '2026', 'Widget One' ), array_column( $trail, 'title' ) );
		$this->assertStringContainsString( '/gizmos', $trail[1]['url'] );
	}

	public function test_debug_output_is_gone(): void {
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		ob_start();
		apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( '', ob_get_clean(), 'Breadcrumb building must not print anything' );
	}
}
```

- [x] **Step 7: Run and confirm PASS**

Run: `docker compose run --rm php composer test:integration -- --filter BreadcrumbsRequestTest`
Expected: PASS. If `is_singular` or `is_post_type_archive` is false after `go_to`, confirm the permalink filter from plugins-01-test-toolchain Task 4 is active and `flush_rewrite_rules()` ran after registration.

- [x] **Step 8: Commit**

```bash
git add hyperpress-utils/src/Breadcrumbs.php tests/unit/Utils/BreadcrumbsTest.php tests/integration/Utils/BreadcrumbsRequestTest.php
git commit -m "feat(utils): add Breadcrumbs builder (moved from the theme, debug output and archive bug removed)" -- hyperpress-utils/src/Breadcrumbs.php tests/unit/Utils/BreadcrumbsTest.php tests/integration/Utils/BreadcrumbsRequestTest.php
```

Theme follow-up (handled by the theme plans, not here): once this class is in use, the theme's `hyperpress_breadcrumbs_custom_post_type()` and the plugins' per-plugin `breadcrumbs.php` wrappers are dead code and are removed.

---

## Task 6: `Banner` (TDD)

Replaces the three near-identical `banner.php` copies (competition, newsletter, sponsor). Legacy differences are preserved as arguments: newsletter always overwrites the archive title (`always_set_archive_title`) and joins the season with "from"; competition and sponsor only set an empty title and use "in". Newsletter's season-aware singular title and per-post color move into a `hyperpress_banner_args` filter in the newsletter plugin (plugins-04-remaining-plugins).

**Files:**
- Create: `hyperpress-utils/src/Banner.php`, `tests/unit/Utils/BannerTest.php`, `tests/integration/Utils/BannerRegisterTest.php`

- [x] **Step 1: Write the failing unit test**

```php
<?php
namespace HyperPress\Tests\Unit\Utils;

use HyperPress\Utils\Banner;
use PHPUnit\Framework\TestCase;

final class BannerTest extends TestCase {

	private function args( array $overrides = array() ): array {
		return array_merge(
			array(
				'type'          => 'sponsor',
				'subtitle_mod'  => 'hyperpress_sponsor_banner_subtitle',
				'color_mod'     => 'hyperpress_hyper_red',
				'archive_title' => 'Sponsors',
				'term_joiner'   => 'in',
			),
			$overrides
		);
	}

	private function ctx( array $overrides = array() ): array {
		return array_merge(
			array(
				'is_singular'   => false,
				'is_archive'    => false,
				'is_tax_season' => false,
				'subtitle'      => 'Thank you',
				'color'         => '#c00',
				'term_name'     => '',
			),
			$overrides
		);
	}

	public function test_untouched_when_neither_singular_nor_archive(): void {
		$this->assertSame( array( 'title' => 'T' ), Banner::apply( array( 'title' => 'T' ), $this->args(), $this->ctx() ) );
	}

	public function test_singular_sets_type_subtitle_and_color_but_keeps_title(): void {
		$banner = Banner::apply( array( 'title' => 'Acme' ), $this->args(), $this->ctx( array( 'is_singular' => true ) ) );
		$this->assertSame( 'sponsor', $banner['type'] );
		$this->assertSame( 'Thank you', $banner['subtitle'] );
		$this->assertSame( '#c00', $banner['backgroundColor'] );
		$this->assertSame( 'Acme', $banner['title'] );
	}

	public function test_archive_sets_title_only_when_empty_by_default(): void {
		$empty = Banner::apply( array( 'title' => '' ), $this->args(), $this->ctx( array( 'is_archive' => true ) ) );
		$this->assertSame( 'Sponsors', $empty['title'] );

		$set = Banner::apply( array( 'title' => 'Custom' ), $this->args(), $this->ctx( array( 'is_archive' => true ) ) );
		$this->assertSame( 'Custom', $set['title'] );
	}

	public function test_archive_title_can_always_be_overwritten(): void {
		$banner = Banner::apply(
			array( 'title' => 'Custom' ),
			$this->args( array( 'always_set_archive_title' => true ) ),
			$this->ctx( array( 'is_archive' => true ) )
		);
		$this->assertSame( 'Sponsors', $banner['title'] );
	}

	public function test_season_archive_appends_the_term_with_the_configured_joiner(): void {
		$in = Banner::apply( array( 'title' => '' ), $this->args(), $this->ctx( array( 'is_archive' => true, 'is_tax_season' => true, 'term_name' => '2026' ) ) );
		$this->assertSame( 'Thank you in 2026', $in['subtitle'] );

		$from = Banner::apply( array( 'title' => '' ), $this->args( array( 'term_joiner' => 'from' ) ), $this->ctx( array( 'is_archive' => true, 'is_tax_season' => true, 'term_name' => '2026' ) ) );
		$this->assertSame( 'Thank you from 2026', $from['subtitle'] );
	}

	public function test_no_term_suffix_without_a_subtitle(): void {
		$banner = Banner::apply( array( 'title' => '' ), $this->args(), $this->ctx( array( 'is_archive' => true, 'is_tax_season' => true, 'term_name' => '2026', 'subtitle' => '' ) ) );
		$this->assertSame( '', $banner['subtitle'] );
	}
}
```

- [x] **Step 2: Run and confirm FAIL**

Run: `docker compose run --rm php composer test:unit -- --filter BannerTest`

- [x] **Step 3: Write `hyperpress-utils/src/Banner.php`**

```php
<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Banner {

	public const SEASON_TAXONOMY = 'hyper_season';

	/**
	 * Hook the theme's banner filter for one post type.
	 *
	 * @param string $post_type Post type key.
	 * @param array  $args      type, subtitle_mod, color_mod, archive_title (all required);
	 *                          term_joiner ('in' default), always_set_archive_title (false default).
	 */
	public static function register( string $post_type, array $args ): void {
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) use ( $post_type, $args ) {
				$is_tax_season = is_tax( self::SEASON_TAXONOMY );
				$ctx           = array(
					'is_singular'   => is_singular( $post_type ),
					'is_archive'    => is_post_type_archive( $post_type ),
					'is_tax_season' => $is_tax_season,
					'subtitle'      => get_theme_mod( $args['subtitle_mod'] ),
					'color'         => get_theme_mod( $args['color_mod'] ),
					'term_name'     => $is_tax_season ? get_term( get_queried_object_id() )->name : '',
				);
				if ( ! $ctx['is_singular'] && ! $ctx['is_archive'] ) {
					return $banner;
				}
				$banner = self::apply( $banner, $args, $ctx );

				/**
				 * Lets a plugin adjust the assembled banner for its own post type.
				 *
				 * @param array  $banner    Banner data.
				 * @param string $post_type Post type key.
				 * @param array  $ctx       Request context (is_singular, is_archive, ...).
				 */
				return apply_filters( 'hyperpress_banner_args', $banner, $post_type, $ctx );
			}
		);
	}

	/**
	 * Pure: assemble banner data from the request context.
	 *
	 * @param array $banner Incoming banner.
	 * @param array $args   See register().
	 * @param array $ctx    is_singular, is_archive, is_tax_season, subtitle, color, term_name.
	 */
	public static function apply( array $banner, array $args, array $ctx ): array {
		if ( ! $ctx['is_singular'] && ! $ctx['is_archive'] ) {
			return $banner;
		}

		$banner['type']            = $args['type'];
		$banner['subtitle']        = $ctx['subtitle'];
		$banner['backgroundColor'] = $ctx['color'];

		if ( $ctx['is_archive'] && ( ! empty( $args['always_set_archive_title'] ) || empty( $banner['title'] ) ) ) {
			$banner['title'] = $args['archive_title'];
			if ( $ctx['is_tax_season'] && ! empty( $ctx['subtitle'] ) ) {
				$banner['subtitle'] = $ctx['subtitle'] . ' ' . ( $args['term_joiner'] ?? 'in' ) . ' ' . $ctx['term_name'];
			}
		}

		return $banner;
	}
}
```

- [x] **Step 4: Run and confirm PASS**

Run: `docker compose run --rm php composer test:unit -- --filter BannerTest`

- [x] **Step 5: Write the integration test `tests/integration/Utils/BannerRegisterTest.php`**

```php
<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Banner;
use WP_UnitTestCase;

final class BannerRegisterTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		register_post_type( 'hyper_gizmo', array( 'public' => true, 'has_archive' => 'gizmos' ) );
		flush_rewrite_rules();
		set_theme_mod( 'hyperpress_gizmo_banner_subtitle', 'Sub' );
		set_theme_mod( 'hyperpress_hyper_green', '#0a0' );
		Banner::register(
			'hyper_gizmo',
			array(
				'type'          => 'gizmo',
				'subtitle_mod'  => 'hyperpress_gizmo_banner_subtitle',
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => 'Gizmos',
			)
		);
	}

	public function tear_down(): void {
		unregister_post_type( 'hyper_gizmo' );
		parent::tear_down();
	}

	public function test_other_requests_are_untouched(): void {
		$this->go_to( home_url( '/' ) );
		$this->assertSame( array( 'title' => 'Blog' ), apply_filters( 'hyperpress_banner_content', array( 'title' => 'Blog' ) ) );
	}

	public function test_archive_request_gets_the_banner(): void {
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
		$this->assertSame( 'gizmo', $banner['type'] );
		$this->assertSame( 'Gizmos', $banner['title'] );
		$this->assertSame( 'Sub', $banner['subtitle'] );
		$this->assertSame( '#0a0', $banner['backgroundColor'] );
	}

	public function test_hyperpress_banner_args_filter_can_adjust_the_banner(): void {
		$seen = array();
		add_filter(
			'hyperpress_banner_args',
			static function ( $banner, $post_type, $ctx ) use ( &$seen ) {
				$seen             = array( $post_type, $ctx['is_archive'] );
				$banner['title'] = 'Adjusted';
				return $banner;
			},
			10,
			3
		);
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
		$this->assertSame( 'Adjusted', $banner['title'] );
		$this->assertSame( array( 'hyper_gizmo', true ), $seen );
	}
}
```

- [x] **Step 6: Run and confirm PASS**, then commit

Run: `docker compose run --rm php composer test:integration -- --filter BannerRegisterTest`
Expected: PASS. If `is_post_type_archive` is false after `go_to`, confirm the permalink filter from plugins-01-test-toolchain Task 4 is active and `flush_rewrite_rules()` ran after registration.

```bash
git add hyperpress-utils/src/Banner.php tests/unit/Utils/BannerTest.php tests/integration/Utils/BannerRegisterTest.php
git commit -m "feat(utils): add Banner with hyperpress_banner_args filter" -- hyperpress-utils/src/Banner.php tests/unit/Utils/BannerTest.php tests/integration/Utils/BannerRegisterTest.php
```

---

## Task 7: Move the Customizer controls

Loaded lazily by the autoloader on first reference, which only happens inside `customize_register` (when `WP_Customize_Control` exists), so the legacy `class_exists` guards and the `require_once` of core are no longer needed. The legacy `postTypes` dynamic property (deprecated since PHP 8.2) becomes a declared property.

**Files:**
- Create: `hyperpress-utils/src/Controls/PostTypeDropdownControl.php`, `hyperpress-utils/src/Controls/CategoryDropdownControl.php`, `tests/integration/Utils/ControlsTest.php`
- Delete: `hyperpress-utils/lib/controls/customize-post-type-control.php`, `hyperpress-utils/lib/controls/customize-category-control.php` (and `lib/` if empty)

- [x] **Step 1: Write the failing test**

```php
<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Controls\CategoryDropdownControl;
use HyperPress\Utils\Controls\PostTypeDropdownControl;
use ReflectionMethod;
use WP_Customize_Control;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class ControlsTest extends WP_UnitTestCase {

	private function manager(): WP_Customize_Manager {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		$manager->add_setting( 'hyperpress_test_setting' );
		return $manager;
	}

	public function test_post_type_control_lists_public_post_types(): void {
		$control = new PostTypeDropdownControl( $this->manager(), 'hyperpress_test_setting', array( 'settings' => 'hyperpress_test_setting', 'label' => 'Types' ) );
		$this->assertInstanceOf( WP_Customize_Control::class, $control );
		$this->assertSame( 'dropdown-post-types', $control->type );

		ob_start();
		( new ReflectionMethod( $control, 'render_content' ) )->invoke( $control );
		$html = ob_get_clean();
		$this->assertStringContainsString( '<option value="post"', $html );
	}

	public function test_category_control_type(): void {
		$control = new CategoryDropdownControl( $this->manager(), 'hyperpress_test_setting', array( 'settings' => 'hyperpress_test_setting' ) );
		$this->assertSame( 'dropdown-category', $control->type );
	}
}
```

- [x] **Step 2: Run and confirm FAIL** (class not found)

Run: `docker compose run --rm php composer test:integration -- --filter ControlsTest`

- [x] **Step 3: Move with history, then edit**

```bash
mkdir -p hyperpress-utils/src/Controls
git mv hyperpress-utils/lib/controls/customize-post-type-control.php hyperpress-utils/src/Controls/PostTypeDropdownControl.php
git mv hyperpress-utils/lib/controls/customize-category-control.php hyperpress-utils/src/Controls/CategoryDropdownControl.php
```

- [x] **Step 4: Rewrite `PostTypeDropdownControl.php`**

Logic and markup are unchanged from the legacy file except where marked.

```php
<?php
namespace HyperPress\Utils\Controls;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Control;

class PostTypeDropdownControl extends WP_Customize_Control {
	public $type = 'dropdown-post-types';

	/** @var array<string,\WP_Post_Type> Declared (was a dynamic property, deprecated in PHP 8.2). */
	protected array $post_types = array();

	public function __construct( $manager, $id, $args = array(), $options = array() ) {
		$post_args        = wp_parse_args( $options, array( 'public' => true ) );
		$this->post_types = get_post_types( $post_args, 'object' );
		parent::__construct( $manager, $id, $args );
	}

	protected function render_content() {
		?>
		<label for="<?php esc_attr( $this->id ); ?>">
		<?php
		if ( ! empty( $this->label ) ) :
			?>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php
		endif;

		if ( ! empty( $this->description ) ) :
			?>
			<span class="description customize-control-description"><?php echo $this->description; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php
		endif;
		?>
		</label>
		<?php
		if ( ! empty( $this->post_types ) ) :
			?>
			<select <?php $this->link(); ?> name="<?php esc_attr( $this->id ); ?>"
											id="<?php esc_attr( $this->id ); ?>"
											multiple size="<?php echo count( $this->post_types ); ?>">
				<?php
				foreach ( $this->post_types as $post_type ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $post_type->name ),
						selected( $this->value(), $post_type->name, false ),
						esc_html( $post_type->labels->name )
					);
				}
				?>
			</select>
			<?php
		endif;
	}
}
```

Known legacy defects deliberately NOT fixed here (a move only): `esc_attr( $this->id )` is called without `echo` (the `for`, `name`, `id` attributes are empty), and `selected( $this->value(), ... )` compares an array value to a string. Both go into the plugins audit.

- [x] **Step 5: Rewrite `CategoryDropdownControl.php`**

```php
<?php
namespace HyperPress\Utils\Controls;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Control;

class CategoryDropdownControl extends WP_Customize_Control {
	public $type = 'dropdown-category';

	protected array $dropdown_args = array();

	protected function render_content(): void {
		?>
		<label>
		<?php
		if ( ! empty( $this->label ) ) :
			?>
			<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php
		endif;

		if ( ! empty( $this->description ) ) :
			?>
			<span class="description customize-control-description"><?php echo $this->description; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
			<?php
		endif;
		?>
		</label>
		<?php

		$dropdown_args = wp_parse_args(
			$this->dropdown_args,
			array(
				'taxonomy'          => 'category',
				'show_option_none'  => 'Uncategorized',
				'show_option_all'   => '',
				'orderby'           => 'id',
				'order'             => 'ASC',
				'show_count'        => 1,
				'hide_empty'        => 1,
				'child_of'          => 0,
				'exclude'           => '',
				'hierarchical'      => 1,
				'depth'             => 0,
				'tab_index'         => 0,
				'hide_if_empty'     => false,
				'option_none_value' => 0,
				'value_field'       => 'term_id',
				'echo'              => false,
			)
		);

		$dropdown       = wp_dropdown_categories( $dropdown_args );
		$dropdown       = str_replace( '<select', '<select' . $this->get_link(), $dropdown );
		$multi_dropdown = str_replace( '<select', '<select multiple size="10"', $dropdown );

		echo $multi_dropdown; // phpcs:ignore WordPress.Security.EscapeOutput
	}
}
```

The legacy `TODO: build a multiselect drop down by extending Walker_Category_Checklist?` comment is dropped per the no-TODO rule; note it in the plugins audit as a possible improvement if wanted.

- [x] **Step 6: Remove the empty legacy directory, run tests, commit**

```bash
rmdir hyperpress-utils/lib/controls hyperpress-utils/lib 2>/dev/null; ls hyperpress-utils
```
Expected: `hyperpress-utils.php`, `src`, plus any assets/languages, no `lib`.

Run: `docker compose run --rm php composer test:integration -- --filter ControlsTest`
Expected: PASS.

```bash
git add hyperpress-utils/src/Controls tests/integration/Utils/ControlsTest.php
git commit -m "refactor(utils): move Customizer controls to HyperPress\\Utils\\Controls" -- hyperpress-utils tests/integration/Utils/ControlsTest.php
```

Note: the commit pathspec `hyperpress-utils` includes the `git mv` deletions of the old `lib/controls` files.

---

## Task 8: Full Utils verification

- [x] **Step 1: Run every Utils test and the syntax lint**

Run: `docker compose run --rm php composer test:unit -- --filter Utils`
Run: `docker compose run --rm php composer test:integration -- --filter Utils`
Run: `docker compose run --rm php composer lint:syntax`
Expected: all PASS, lint exit 0.

- [x] **Step 2: Confirm the header lint for utils is green and the rest still fails as designed**

Run: `docker compose run --rm php composer test:unit -- --filter PluginHeadersTest`
Expected: all `hyperpress-utils` cases PASS; other plugins still fail (their migration is plugins-03 and plugins-04).

- [x] **Step 3: Flip this plan's checkboxes, add the `STATUS: COMPLETED` banner with the commit range, commit the plan**

```bash
git add docs/superpowers/plans/2026-10-02-plugins-02-shared-utils.md
git commit -m "docs(plans): complete plugins-02-shared-utils" -- docs/superpowers/plans/2026-10-02-plugins-02-shared-utils.md
```

---

## Execution notes

- Task 0 consumer audit (107 same-plugin, 8 cross-plugin, 17 theme references): cross-plugin callers are `hyperpress-sponsor/lib/labels.php` (calls `HYPER_Press_Season\get_sorted_seasons`) and the five plugin breadcrumb wrappers (call the theme function). Theme callers: `library/labels/post.php` and `library/breadcrumbs/post.php` (`get_sorted_seasons`), `library/customize/homepage.php` (control classes), `template-parts/banner.php` (banner `type`, read as an unprefixed slug, so kept unprefixed). The two `get_sorted_seasons` theme callers were added to theme-02-renames-and-breadcrumbs.
- Found in Task 5: the legacy season rewrite rule derives `post_type` from the archive slug by stripping a trailing `s`, which breaks with `hyper_*` keys. plugins-03-core-plugins Task 2 now replaces it with one rule per `hyper_*` post type archive and a test.
- Until plugins-03 migrates season and program, their "post types" Customizer dropdowns are silently absent (class_exists-guarded; old control classes no longer exist).
- Baseline after this plan: unit 112 tests, 66 red (header lint for the 13 unmigrated plugins, NoLegacyKeysTest); integration 55 tests, 36 red (contract tests), 3 skipped; `lint:syntax` exit 0.
- Deferred minors (to triage at the end): `KEY_PATTERN` allows a trailing newline (use `\z`); `resolve_object_types` returns `['']`/`[false]` for a stored falsy theme mod; `Capabilities::ensure` records the version even if no type was registered yet (callers must run it at `init` priority 20 after registration at 10); `BreadcrumbsRequestTest` leaks the `hyper_season` taxonomy until season is migrated; season crumb URL has no trailing slash; Banner builds its context before the early return and callers must pass pre-translated `archive_title`; several untested edge cases listed in the per-task reports.

### Theme checklist (OUT OF SCOPE here, for the theme plans)
- Plugin identifiers the theme must learn: post types `hyper_award`, `hyper_competition`, `hyper_countdown`, `hyper_newsletter`, `hyper_robot`, `hyper_sponsor`; taxonomies `hyper_season`, `hyper_program`, `hyper_district`, `hyper_week`, `hyper_tier`.
- The theme's `hyperpress_breadcrumbs_custom_post_type()` is replaced by `HyperPress\Utils\Breadcrumbs` and becomes dead code; theme-02-renames-and-breadcrumbs removes it.
- Undeclared plugin-to-theme dependency: banner color theme mods `hyperpress_hyper_green`, `hyperpress_hyper_orange`, `hyperpress_hyper_red`, `hyperpress_gear_blue`, and the theme meta field `hyperpress_banner_background_color`.
- Filters the theme fires: `hyperpress_breadcrumbs_content`, `hyperpress_banner_content`.

> Note (2026-10-11): `PostTypeDropdownControl` takes an optional `array( 'show_ui' => true )` option (lists `show_ui` post types instead of `public`) and has a matching `sanitize_show_ui_post_types()` callback; default behavior is unchanged. Only the season Customizer uses it.

> Note (2026-10-11): `HyperPress\Utils\TermSeeder` was added to `hyperpress-utils` (idempotent `seed()`, and `ensure()` which seeds once per version via an option); `hyperpress-program` and `hyperpress-competition` use it to seed their terms.

> Note (2026-10-11): `TaxonomyRegistrar` validates taxonomy keys with its own `KEY_PATTERN` (`hyper_` + lowercase letters, digits, underscores, 32 characters max, WordPress's `register_taxonomy` limit); `PostTypeRegistrar` keeps the 20-character post type limit.
