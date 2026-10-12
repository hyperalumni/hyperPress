<?php
namespace HyperPress\Tests\Unit\Contact;

use HyperPress\Contact\Settings;
use PHPUnit\Framework\TestCase;

final class SettingsMapTest extends TestCase {

	public function test_parses_lat_lng_and_zoom(): void {
		$this->assertSame(
			array(
				'lat'  => 42.2518999,
				'lng'  => -71.0007911,
				'zoom' => 17,
			),
			Settings::parse_map( '42.2518999,-71.0007911,17' )
		);
	}

	public function test_zoom_defaults_to_fifteen(): void {
		$map = Settings::parse_map( '42.25,-71.0' );

		$this->assertNotNull( $map );
		$this->assertSame( 15, $map['zoom'] );
	}

	public function test_whitespace_is_tolerated(): void {
		$this->assertSame(
			array(
				'lat'  => 42.25,
				'lng'  => -71.0,
				'zoom' => 17,
			),
			Settings::parse_map( ' 42.25 , -71.0 , 17 ' )
		);
	}

	public function test_zoom_is_clamped(): void {
		$high = Settings::parse_map( '1,2,99' );
		$low  = Settings::parse_map( '1,2,-3' );

		$this->assertNotNull( $high );
		$this->assertNotNull( $low );
		$this->assertSame( 21, $high['zoom'] );
		$this->assertSame( 0, $low['zoom'] );
	}

	/**
	 * Malformed or out-of-range values.
	 *
	 * @return array<string,array{string}>
	 */
	public static function invalid_values(): array {
		return array(
			'empty'             => array( '' ),
			'not numeric'       => array( 'abc' ),
			'latitude too big'  => array( '91,0,10' ),
			'longitude too big' => array( '0,181,10' ),
			'too many parts'    => array( '1,2,3,4' ),
		);
	}

	/**
	 * Values that are not a valid "lat,lng[,zoom]" string.
	 *
	 * @dataProvider invalid_values
	 * @param string $value The raw setting value.
	 */
	public function test_invalid_values_return_null( string $value ): void {
		$this->assertNull( Settings::parse_map( $value ) );
	}
}
