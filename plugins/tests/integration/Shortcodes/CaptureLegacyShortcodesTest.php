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

		// The hyperpress-shortcodes plugin registers the same tags on init: drop them so the theme code is what gets captured.
		remove_all_shortcodes();

		foreach ( array( 'show-hide-content', 'date-distance', 'raw', 'emphasis', 'banner' ) as $file ) {
			require_once "/theme/library/shortcodes/$file.php";
		}

		global $shortcode_tags;
		$legacy_tags = array_keys( $shortcode_tags );
		$this->assertSame(
			array(
				'time-restrict',
				'time-restrict-repeat',
				'time-restrict-repeat-1',
				'time-restrict-repeat-2',
				'time-restrict-repeat-3',
				'hyper_date_distance',
				'raw',
				'hyper_emphasis',
				'hyper_banner',
			),
			$legacy_tags
		);
		$this->assertSame( 'hyper_emphasis_shortcode', $shortcode_tags['hyper_emphasis'], 'Tags must come from the theme functions' );
		$outputs     = array();
		foreach ( ShortcodeCases::CASES as $name => $markup ) {
			$outputs[ $name ] = do_shortcode( $markup );
		}

		$used = implode( ' ', ShortcodeCases::CASES );
		foreach ( $legacy_tags as $tag ) {
			$this->assertMatchesRegularExpression( '/\[' . preg_quote( $tag, '/' ) . '[\s\]\/]/', $used, "No case exercises the [$tag] shortcode" );
		}

		file_put_contents(
			self::FIXTURE,
			wp_json_encode( array( 'tags' => $legacy_tags, 'outputs' => $outputs ), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) . "\n"
		);
		$this->assertFileExists( self::FIXTURE );
	}
}
