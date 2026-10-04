<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use HyperPress\Tests\Support\ShortcodeCases;
use WP_UnitTestCase;

final class CaptureLegacyShortcodesTest extends WP_UnitTestCase {

	public const FIXTURE = __DIR__ . '/../../fixtures/legacy-shortcodes.json';

	public function test_capture_output_of_the_theme_shortcodes(): void {
		if ( '1' !== getenv( 'HYPERPRESS_WRITE_SNAPSHOT' ) ) {
			$this->markTestSkipped( 'Set HYPERPRESS_WRITE_SNAPSHOT=1 to regenerate (theme code only).' );
		}

		global $shortcode_tags;
		$before = array_keys( $shortcode_tags );

		foreach ( array( 'show-hide-content', 'date-distance', 'raw', 'emphasis', 'banner' ) as $file ) {
			require_once "/theme/library/shortcodes/$file.php";
		}

		$legacy_tags = array_values( array_diff( array_keys( $shortcode_tags ), $before ) );
		$outputs     = array();
		foreach ( ShortcodeCases::CASES as $name => $markup ) {
			$outputs[ $name ] = do_shortcode( $markup );
		}

		$used = implode( ' ', ShortcodeCases::CASES );
		foreach ( $legacy_tags as $tag ) {
			$this->assertStringContainsString( '[' . $tag, $used, "No case exercises the [$tag] shortcode" );
		}

		file_put_contents(
			self::FIXTURE,
			wp_json_encode( array( 'tags' => $legacy_tags, 'outputs' => $outputs ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"
		);
		$this->assertFileExists( self::FIXTURE );
	}
}
