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

	/** @dataProvider empty_theme_mod_values */
	public function test_empty_theme_mod_values_resolve_to_an_empty_list( $stored, array $expected ): void {
		$this->assertSame( $expected, TaxonomyRegistrar::resolve_object_types( 'm', static fn() => $stored ) );
	}

	public static function empty_theme_mod_values(): array {
		return array(
			'empty string'        => array( '', array() ),
			'false'               => array( false, array() ),
			'null'                => array( null, array() ),
			'zero'                => array( 0, array() ),
			'array of empty'      => array( array( '' ), array() ),
			'mixed with empty'    => array( array( 'hyper_award', '' ), array( 'hyper_award' ) ),
			'sparse keys reindex' => array( array( 1 => 'hyper_award' ), array( 'hyper_award' ) ),
		);
	}

	public function test_invalid_key_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );
		TaxonomyRegistrar::build( $this->definition( array( 'key' => 'district' ) ) );
	}

	public function test_key_with_trailing_newline_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );
		TaxonomyRegistrar::build( $this->definition( array( 'key' => "hyper_district\n" ) ) );
	}
}
