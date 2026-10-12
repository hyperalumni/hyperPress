<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class ExtractedShortcodesTest extends WP_UnitTestCase {

	private const TAGS = array(
		'hyperpress_time_restrict', 'hyperpress_time_restrict_repeat', 'hyperpress_time_restrict_repeat_1', 'hyperpress_time_restrict_repeat_2', 'hyperpress_time_restrict_repeat_3',
		'hyperpress_date_distance', 'hyperpress_raw', 'hyperpress_emphasis', 'hyperpress_banner', 'hyperpress_season_count',
	);

	public function test_the_theme_does_not_register_the_extracted_shortcodes(): void {
		// With the extracted plugins skipped (HYPERPRESS_TEST_WITHOUT_EXTRACTED=1) only the theme is loaded.
		if ( ! getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ) {
			$this->markTestSkipped( 'Run with HYPERPRESS_TEST_WITHOUT_EXTRACTED=1' );
		}
		foreach ( self::TAGS as $tag ) {
			if ( 'hyperpress_season_count' === $tag ) {
				continue; // Registered by hyperpress-season, which stays loaded.
			}
			$this->assertFalse( shortcode_exists( $tag ), "[$tag] must not be registered by the theme" );
		}
	}

	public function test_with_the_plugins_active_every_tag_exists(): void {
		if ( getenv( 'HYPERPRESS_TEST_WITHOUT_PLUGINS' ) ) {
			$this->markTestSkipped( 'Needs the HYPER plugins' );
		}
		if ( getenv( 'HYPERPRESS_TEST_WITHOUT_EXTRACTED' ) ) {
			$this->markTestSkipped( 'Needs the extracted plugins' );
		}
		foreach ( self::TAGS as $tag ) {
			$this->assertTrue( shortcode_exists( $tag ), "[$tag] must be provided by a plugin" );
		}
	}
}
