<?php
namespace HyperPress\Tests\Integration\Contact;

use HyperPress\Contact\MapShortcode;
use HyperPress\Contact\Settings;
use WP_UnitTestCase;

final class MapShortcodeTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		MapShortcode::register();
		// Handles survive between tests, and registering a known handle is ignored.
		wp_dequeue_script( 'hyperpress-contact-google-maps' );
		wp_deregister_script( 'hyperpress-contact-google-maps' );
		wp_deregister_script( 'hyperpress-contact-map' );
		$this->store( '42.2518999,-71.0007911,17', 'test-key' );
		wp_set_current_user( 0 );
	}

	private function store( ?string $map, ?string $key ): void {
		update_option( Settings::OPTION, null === $map ? array() : array( Settings::MAP => $map ) );
		if ( null === $key ) {
			delete_option( 'hyperpress_google_maps_api_key' );
		} else {
			update_option( 'hyperpress_google_maps_api_key', $key );
		}
	}

	public function test_renders_the_map_element(): void {
		$out = do_shortcode( '[hyperpress_map]' );

		$this->assertStringContainsString( 'class="hyperpress-map"', $out );
		$this->assertStringContainsString( 'data-lat="42.2518999"', $out );
		$this->assertStringContainsString( 'data-lng="-71.0007911"', $out );
		$this->assertStringContainsString( 'data-zoom="17"', $out );
		$this->assertStringContainsString( 'role="img"', $out );
		$this->assertStringContainsString( 'aria-label="Map of our location"', $out );
		$this->assertStringContainsString( 'style="height:250px;width:100%;pointer-events:none;"', $out );
	}

	/**
	 * @return array<string,array{0:string,1:int}>
	 */
	public static function heights(): array {
		return array(
			'valid'      => array( '400', 400 ),
			'too small'  => array( '5', 100 ),
			'too large'  => array( '9999', 1000 ),
			'not number' => array( 'abc', 250 ),
		);
	}

	/**
	 * @dataProvider heights
	 */
	public function test_height_is_clamped( string $height, int $expected ): void {
		$out = do_shortcode( '[hyperpress_map height="' . $height . '"]' );

		$this->assertStringContainsString( 'style="height:' . $expected . 'px;width:100%;pointer-events:none;"', $out );
	}

	public function test_enqueues_the_google_script_once_rendered_twice(): void {
		$out = do_shortcode( '[hyperpress_map][hyperpress_map]' );

		$this->assertSame( 2, substr_count( $out, 'class="hyperpress-map"' ) );
		$this->assertTrue( wp_script_is( 'hyperpress-contact-google-maps', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'hyperpress-contact-map', 'registered' ) );

		$script = wp_scripts()->registered['hyperpress-contact-google-maps'];
		$this->assertStringContainsString( 'key=test-key', $script->src );
		$this->assertStringContainsString( 'callback=hyperpressContactInitMaps', $script->src );
		$this->assertStringContainsString( 'loading=async', $script->src );
		$this->assertContains( 'hyperpress-contact-map', $script->deps );
	}

	public function test_key_is_url_encoded(): void {
		$this->store( '42.2518999,-71.0007911,17', 'a&b' );

		do_shortcode( '[hyperpress_map]' );

		$this->assertStringContainsString( 'key=a%26b', wp_scripts()->registered['hyperpress-contact-google-maps']->src );
	}

	/**
	 * @return array<string,array{0:?string,1:?string}>
	 */
	public static function unusable(): array {
		return array(
			'no key'           => array( '42.25,-71.0,17', null ),
			'no location'      => array( null, 'test-key' ),
			'invalid location' => array( 'nowhere', 'test-key' ),
		);
	}

	/**
	 * @dataProvider unusable
	 */
	public function test_nothing_for_visitors_when_unusable( ?string $map, ?string $key ): void {
		$this->store( $map, $key );

		$this->assertSame( '', do_shortcode( '[hyperpress_map]' ) );
		$this->assertFalse( wp_script_is( 'hyperpress-contact-google-maps', 'enqueued' ) );
	}

	/**
	 * @dataProvider unusable
	 */
	public function test_comment_for_administrators_when_unusable( ?string $map, ?string $key ): void {
		$this->store( $map, $key );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$out = do_shortcode( '[hyperpress_map]' );

		$this->assertStringStartsWith( '<!-- hyperpress_map: ', $out );
		$this->assertStringEndsWith( ' -->', $out );
		$this->assertStringNotContainsString( 'hyperpress-map"', $out );
	}
}
