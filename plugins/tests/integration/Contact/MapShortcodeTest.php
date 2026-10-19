<?php
namespace HyperPress\Tests\Integration\Contact;

use HyperPress\Contact\MapShortcode;
use HyperPress\Contact\Settings;
use RWMB_OSM_Field;
use WP_UnitTestCase;

final class MapShortcodeTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		MapShortcode::register();
		RWMB_OSM_Field::$calls = array();
		$this->store( '42.2518999,-71.0007911,17' );
		wp_set_current_user( 0 );
	}

	private function store( ?string $map ): void {
		update_option( Settings::OPTION, null === $map ? array() : array( Settings::MAP => $map ) );
	}

	public function test_wraps_the_meta_box_map(): void {
		$out = do_shortcode( '[hyperpress_map]' );

		$this->assertStringContainsString( 'class="hyperpress-map"', $out );
		$this->assertStringContainsString( 'role="img"', $out );
		$this->assertStringContainsString( 'aria-label="Map of our location"', $out );
		$this->assertStringContainsString( 'style="pointer-events:none;"', $out );
		$this->assertStringContainsString( '<div class="rwmb-map-canvas-stub"></div>', $out );
	}

	public function test_calls_meta_box_with_location_and_options(): void {
		do_shortcode( '[hyperpress_map]' );

		$this->assertCount( 1, RWMB_OSM_Field::$calls );
		list( $location, $args ) = RWMB_OSM_Field::$calls[0];
		$this->assertSame( '42.2518999,-71.0007911,17', $location );
		$this->assertArrayNotHasKey( 'api_key', $args );
		$this->assertSame( '100%', $args['width'] );
		$this->assertSame( '250px', $args['height'] );
		$this->assertTrue( $args['marker'] );
		$this->assertSame(
			array(
				'dragging'        => false,
				'zoomControl'     => false,
				'scrollWheelZoom' => false,
				'doubleClickZoom' => false,
				'touchZoom'       => false,
				'boxZoom'         => false,
				'keyboard'        => false,
			),
			$args['js_options']
		);
		$this->assertArrayNotHasKey( 'attributionControl', $args['js_options'] );
	}

	/**
	 * @return array<string,array{0:string,1:string}>
	 */
	public static function locations(): array {
		return array(
			'integers'      => array( '42,-71,10', '42,-71,10' ),
			'default zoom'  => array( '42.5,-71.25', '42.5,-71.25,15' ),
			'tiny value'    => array( '0.00001,-0.00002,3', '0.00001,-0.00002,3' ),
			'zero'          => array( '0,0,21', '0,0,21' ),
			'many decimals' => array( '42.123456789,-71.1,5', '42.1234568,-71.1,5' ),
		);
	}

	/**
	 * @dataProvider locations
	 */
	public function test_location_string_is_plain_decimal( string $stored, string $expected ): void {
		$this->store( $stored );

		do_shortcode( '[hyperpress_map]' );

		$this->assertSame( $expected, RWMB_OSM_Field::$calls[0][0] );
	}

	/**
	 * @return array<string,array{0:string,1:string}>
	 */
	public static function heights(): array {
		return array(
			'valid'      => array( '400', '400px' ),
			'too small'  => array( '5', '100px' ),
			'too large'  => array( '9999', '1000px' ),
			'not number' => array( 'abc', '250px' ),
		);
	}

	/**
	 * @dataProvider heights
	 */
	public function test_height_is_clamped( string $height, string $expected ): void {
		do_shortcode( '[hyperpress_map height="' . $height . '"]' );

		$this->assertSame( $expected, RWMB_OSM_Field::$calls[0][1]['height'] );
	}

	public function test_two_shortcodes_render_two_maps(): void {
		$out = do_shortcode( '[hyperpress_map][hyperpress_map]' );

		$this->assertSame( 2, substr_count( $out, 'class="hyperpress-map"' ) );
		$this->assertCount( 2, RWMB_OSM_Field::$calls );
	}

	/**
	 * @return array<string,array{0:?string}>
	 */
	public static function unusable(): array {
		return array(
			'no location'       => array( null ),
			'invalid location'  => array( 'abc' ),
			'latitude too high' => array( '91,0,10' ),
		);
	}

	/**
	 * @dataProvider unusable
	 */
	public function test_nothing_for_visitors_when_unusable( ?string $map ): void {
		$this->store( $map );

		$this->assertSame( '', do_shortcode( '[hyperpress_map]' ) );
		$this->assertSame( array(), RWMB_OSM_Field::$calls );
	}

	/**
	 * @dataProvider unusable
	 */
	public function test_comment_for_administrators_when_unusable( ?string $map ): void {
		$this->store( $map );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$out = do_shortcode( '[hyperpress_map]' );

		$this->assertStringStartsWith( '<!-- hyperpress_map: ', $out );
		$this->assertStringEndsWith( ' -->', $out );
		$this->assertStringNotContainsString( 'hyperpress-map"', $out );
		$this->assertSame( array(), RWMB_OSM_Field::$calls );
	}
}
