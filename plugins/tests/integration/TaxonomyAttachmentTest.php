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

	public function set_up(): void {
		parent::set_up();
		// Earlier tests may leave other post types saved or registered.
		delete_option( 'hyperpress_season' );
		delete_option( 'hyperpress_program' );
		\HyperPress\Season\Plugin::register();
		\HyperPress\Program\Plugin::register();
	}

	public function test_season_attaches_to_posts_until_the_season_settings_page_says_otherwise(): void {
		$this->assertSame( array( 'post' ), get_taxonomy( 'hyper_season' )->object_type );
	}

	public function test_program_attaches_to_posts_until_the_program_settings_page_says_otherwise(): void {
		$this->assertSame( array( 'post' ), get_taxonomy( 'hyper_program' )->object_type );
	}
}
