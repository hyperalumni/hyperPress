<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Content;
use HyperPress\Competition\Map;
use WP_UnitTestCase;

final class BootTest extends WP_UnitTestCase {

	public function test_the_countdown_shortcode_is_registered(): void {
		$this->assertTrue( shortcode_exists( 'hyperpress_competition_countdown' ) );
	}

	public function test_the_competitions_shortcode_is_registered(): void {
		$this->assertTrue( shortcode_exists( 'hyperpress_competitions' ) );
	}

	public function test_the_content_template_filter_is_registered(): void {
		$this->assertNotFalse( has_filter( 'content_template', array( Content::class, 'add_content_template' ) ) );
	}

	public function test_the_map_renders_after_the_banner(): void {
		$this->assertNotFalse( has_action( 'hyperpress_after_banner', array( Map::class, 'render' ) ) );
	}

	public function test_the_admin_autocomplete_fix_is_registered(): void {
		$found = false;
		foreach ( $GLOBALS['wp_filter']['admin_enqueue_scripts']->callbacks[20] ?? array() as $callback ) {
			if ( $callback['function'] instanceof \Closure ) {
				$found = true;
			}
		}

		$this->assertTrue( $found, 'a priority-20 admin_enqueue_scripts closure (VenueMap::register_admin_fix) is registered' );
	}

	public function test_the_header_requires_the_countdown_plugin(): void {
		$headers = get_file_data(
			dirname( __DIR__, 3 ) . '/hyperpress-competition/hyperpress-competition.php',
			array( 'requires' => 'Requires Plugins' )
		);
		$list = array_map( 'trim', explode( ',', $headers['requires'] ) );

		$this->assertContains( 'hyperpress-countdown', $list );
	}
}
