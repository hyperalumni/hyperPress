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
