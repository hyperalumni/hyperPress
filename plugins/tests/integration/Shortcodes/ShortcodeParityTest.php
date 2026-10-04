<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use HyperPress\Tests\Support\ShortcodeCases;
use WP_UnitTestCase;

final class ShortcodeParityTest extends WP_UnitTestCase {

	// Own constant: data providers run before other test classes are autoloaded.
	private const FIXTURE = __DIR__ . '/../../fixtures/legacy-shortcodes.json';

	private static function fixture(): array {
		return json_decode( (string) file_get_contents( self::FIXTURE ), true );
	}

	public static function cases(): array {
		$cases = array();
		foreach ( array_keys( self::fixture()['outputs'] ) as $name ) {
			$cases[ $name ] = array( $name );
		}
		return $cases;
	}

	public function test_every_legacy_tag_is_registered_by_the_plugin(): void {
		foreach ( self::fixture()['tags'] as $tag ) {
			$this->assertTrue( shortcode_exists( $tag ), "[$tag] is not registered" );
		}
	}

	/** @dataProvider cases */
	public function test_output_matches_the_theme( string $name ): void {
		$this->assertSame( self::fixture()['outputs'][ $name ], do_shortcode( ShortcodeCases::CASES[ $name ] ), $name );
	}
}
