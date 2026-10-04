<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class ExtractedShortcodesTest extends WP_UnitTestCase {

	private const TAGS = array(
		'time-restrict', 'time-restrict-repeat', 'time-restrict-repeat-1', 'time-restrict-repeat-2', 'time-restrict-repeat-3',
		'hyper_date_distance', 'raw', 'hyper_emphasis', 'hyper_banner', 'hyper_season_count',
	);

	public function test_the_theme_does_not_register_the_extracted_shortcodes(): void {
		// With the extracted plugins skipped (HYPERPRESS_TEST_WITHOUT_EXTRACTED=1) only the theme is loaded.
		if ( ! getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ) {
			$this->markTestSkipped( 'Run with HYPERPRESS_TEST_WITHOUT_EXTRACTED=1' );
		}
		foreach ( self::TAGS as $tag ) {
			if ( 'hyper_season_count' === $tag ) {
				continue; // Registered by hyperpress-season, which stays loaded.
			}
			$this->assertFalse( shortcode_exists( $tag ), "[$tag] must not be registered by the theme" );
		}
	}

	public function test_with_the_plugins_active_every_tag_exists(): void {
		if ( getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ) {
			$this->markTestSkipped( 'Needs the extracted plugins' );
		}
		foreach ( self::TAGS as $tag ) {
			$this->assertTrue( shortcode_exists( $tag ), "[$tag] must be provided by a plugin" );
		}
	}
}
