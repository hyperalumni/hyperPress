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
			$expected = $legacy[ $prop ];
			if ( 'hyper_season' === $new && 'show_admin_column' === $prop ) {
				$expected = true; // Deliberate change: the Season column and filter show in admin lists.
			}
			$this->assertSame( $expected, $tax->$prop, "$new: $prop" );
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
