<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class TaxonomyAttachmentTest extends WP_UnitTestCase {

	public static function fixed(): array {
		$cases = array();
		foreach ( Convention::TAXONOMY_OBJECT_TYPES as $taxonomy => $types ) {
			$cases[ $taxonomy ] = array( $taxonomy, $types );
		}
		return $cases;
	}

	/** @dataProvider fixed */
	public function test_fixed_attachment( string $taxonomy, array $expected ): void {
		$tax = get_taxonomy( $taxonomy );
		$this->assertNotFalse( $tax, "$taxonomy is not registered" );
		$this->assertEqualsCanonicalizing( $expected, $tax->object_type, "$taxonomy object types" );
	}

	public function test_theme_driven_taxonomies_attach_to_nothing_by_default(): void {
		foreach ( Convention::THEME_DRIVEN_TAXONOMIES as $taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			$this->assertNotFalse( $tax, "$taxonomy is not registered" );
			$this->assertSame( array(), $tax->object_type, "$taxonomy must not attach to any post type (incl. 'post') by default" );
		}
	}
}
