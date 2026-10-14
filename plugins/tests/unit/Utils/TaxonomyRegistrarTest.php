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

	public function test_locked_definition_denies_adding_editing_and_deleting_terms(): void {
		$caps = TaxonomyRegistrar::build( $this->definition( array( 'locked' => true ) ) )['args']['capabilities'];
		$this->assertSame( 'do_not_allow', $caps['edit_terms'] );
		$this->assertSame( 'do_not_allow', $caps['delete_terms'] );
		$this->assertSame( 'manage_options', $caps['manage_terms'] );
		$this->assertSame( 'edit_posts', $caps['assign_terms'] );
	}

	public function test_unlocked_definition_keeps_default_capabilities(): void {
		$default = TaxonomyRegistrar::build( $this->definition() )['args']['capabilities'];
		$this->assertSame( $default, TaxonomyRegistrar::build( $this->definition( array( 'locked' => false ) ) )['args']['capabilities'] );
		$this->assertSame( 'manage_options', $default['edit_terms'] );
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

	public function test_key_longer_than_a_post_type_key_is_accepted_up_to_32_characters(): void {
		foreach ( array( 'hyper_competition_type', 'hyper_' . str_repeat( 'a', 26 ) ) as $key ) {
			$built = TaxonomyRegistrar::build( $this->definition( array( 'key' => $key ) ) );
			$this->assertSame( $key, $built['key'] );
		}
	}

	public function test_key_longer_than_32_characters_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );
		TaxonomyRegistrar::build( $this->definition( array( 'key' => 'hyper_' . str_repeat( 'a', 27 ) ) ) );
	}

	public function test_key_with_trailing_newline_is_rejected(): void {
		$this->expectException( InvalidArgumentException::class );
		TaxonomyRegistrar::build( $this->definition( array( 'key' => "hyper_district\n" ) ) );
	}

	public function test_invalid_key_is_escaped_in_the_exception_message(): void {
		try {
			TaxonomyRegistrar::build( $this->definition( array( 'key' => 'hyper_<b>x' ) ) );
			$this->fail( 'Expected InvalidArgumentException' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertStringContainsString( '&lt;b&gt;', $e->getMessage() );
			$this->assertStringNotContainsString( '<b>', $e->getMessage() );
		}
	}

	public function test_labels_override_wins_over_the_generated_label(): void {
		$generated = TaxonomyRegistrar::build( $this->definition() )['args']['labels'];
		$labels    = TaxonomyRegistrar::build( $this->definition( array( 'labels' => array( 'singular_name' => 'Kind' ) ) ) )['args']['labels'];

		$this->assertSame( 'Kind', $labels['singular_name'] );
		$this->assertSame( 'Add New District', $labels['add_new_item'] );
		$this->assertSame( array_diff_key( $generated, array( 'singular_name' => true ) ), array_diff_key( $labels, array( 'singular_name' => true ) ) );
	}

	public function test_labels_override_ignores_non_string_values(): void {
		$generated = TaxonomyRegistrar::build( $this->definition() )['args']['labels'];
		$labels    = TaxonomyRegistrar::build(
			$this->definition(
				array(
					'labels' => array(
						'singular_name' => '',
						'edit_item'     => array( 'x' ),
						'view_item'     => 5,
						'update_item'   => null,
						'extra_label'   => 'Kept',
					),
				)
			)
		)['args']['labels'];

		$this->assertSame( $generated['singular_name'], $labels['singular_name'] );
		$this->assertSame( $generated['edit_item'], $labels['edit_item'] );
		$this->assertSame( $generated['view_item'], $labels['view_item'] );
		$this->assertSame( $generated['update_item'], $labels['update_item'] );
		$this->assertSame( 'Kept', $labels['extra_label'] );
	}

	public function test_without_a_labels_override_the_generated_labels_are_unchanged(): void {
		$this->assertSame(
			\HyperPress\Utils\Labels::taxonomy( 'District', 'Districts' ),
			TaxonomyRegistrar::build( $this->definition() )['args']['labels']
		);
	}
}
