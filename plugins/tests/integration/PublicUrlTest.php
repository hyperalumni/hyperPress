<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class PublicUrlTest extends WP_UnitTestCase {

	private const FIXTURE = __DIR__ . '/../fixtures/legacy-registrations.json';

	private static function snapshot(): array {
		return json_decode( (string) file_get_contents( self::FIXTURE ), true );
	}

	public static function post_types(): array {
		$cases = array();
		foreach ( self::snapshot()['post_types'] as $old => $_ ) {
			$cases[ $old ] = array( $old, Convention::OLD_TO_NEW[ $old ] );
		}
		return $cases;
	}

	public static function taxonomies(): array {
		$cases = array();
		foreach ( self::snapshot()['taxonomies'] as $old => $_ ) {
			$cases[ $old ] = array( $old, Convention::OLD_TO_NEW[ $old ] );
		}
		return $cases;
	}

	/** @dataProvider post_types */
	public function test_post_type_urls_unchanged( string $old, string $new ): void {
		$legacy = self::snapshot()['post_types'][ $old ];
		if ( ! $legacy['publicly_queryable'] ) {
			$this->markTestSkipped( "$old has no public URLs" );
		}
		$pt = get_post_type_object( $new );

		$this->assertNotNull( $pt, "$new is not registered" );
		$this->assertSame( $legacy['has_archive'], $pt->has_archive, "$new archive slug" );
		$this->assertSame( $legacy['rewrite']['slug'] ?? null, $pt->rewrite['slug'] ?? null, "$new rewrite slug" );
		$this->assertSame( $legacy['public'], $pt->public, "$new public" );
		$this->assertSame( $legacy['publicly_queryable'], $pt->publicly_queryable, "$new publicly_queryable" );
	}

	/** @dataProvider taxonomies */
	public function test_taxonomy_urls_unchanged( string $old, string $new ): void {
		$legacy = self::snapshot()['taxonomies'][ $old ];
		if ( ! $legacy['publicly_queryable'] ) {
			$this->markTestSkipped( "$old has no public URLs" );
		}
		$tax = get_taxonomy( $new );

		$this->assertNotFalse( $tax, "$new is not registered" );
		$this->assertSame( $legacy['rewrite']['slug'] ?? null, $tax->rewrite['slug'] ?? null, "$new rewrite slug" );
		$this->assertSame( $legacy['hierarchical'], $tax->hierarchical, "$new hierarchical" );
	}
}
