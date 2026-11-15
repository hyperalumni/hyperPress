<?php
namespace HyperPress\Tests\Unit\Utils;

use HyperPress\Utils\VenueMap;
use PHPUnit\Framework\TestCase;

final class VenueMapTest extends TestCase {

	public function test_parse_defaults_and_clamps_the_zoom(): void {
		$this->assertSame(
			array(
				'lat'  => 1.0,
				'lng'  => 2.0,
				'zoom' => 15,
			),
			VenueMap::parse( '1,2' )
		);
		$this->assertSame( 19, VenueMap::parse( '1,2,99' )['zoom'] );
		$this->assertSame( 19, VenueMap::parse( '1,2,21' )['zoom'] );
		$this->assertSame( 0, VenueMap::parse( '1,2,-4' )['zoom'] );
	}

	public function test_parse_accepts_spaces(): void {
		$this->assertSame(
			array(
				'lat'  => 1.0,
				'lng'  => 2.0,
				'zoom' => 3,
			),
			VenueMap::parse( '1, 2 , 3' )
		);
	}

	/** @return array<string,array{0:mixed}> */
	public static function unparsable(): array {
		return array(
			'garbage'        => array( 'garbage' ),
			'latitude high'  => array( '91,0' ),
			'longitude high' => array( '0,181' ),
			'one part'       => array( '1' ),
			'four parts'     => array( '1,2,3,4' ),
			'empty string'   => array( '' ),
			'array'          => array( array() ),
			'null'           => array( null ),
		);
	}

	/**
	 * @dataProvider unparsable
	 * @param mixed $value A value that is not a valid location.
	 */
	public function test_parse_rejects_invalid_values( mixed $value ): void {
		$this->assertNull( VenueMap::parse( $value ) );
	}

	/** @return array<string,array{0:string}> */
	public static function keepable(): array {
		return array(
			'with zoom'      => array( '42.1,-71.2,15' ),
			'without zoom'   => array( '42.25,-71.0' ),
			'space after ,'  => array( '42.25228605221164, -71.00051344845554,15' ),
		);
	}

	/** @dataProvider keepable */
	public function test_sanitize_keeps_a_location( string $value ): void {
		$this->assertSame( $value, VenueMap::sanitize( $value ) );
	}

	public function test_sanitize_trims_whitespace(): void {
		$this->assertSame( '42.25,-71.0', VenueMap::sanitize( "  42.25,-71.0 \n" ) );
	}

	/** @return array<string,array{0:mixed}> */
	public static function droppable(): array {
		return array(
			'markup'      => array( '<script>alert(1)</script>' ),
			'semicolon'   => array( '42.25;-71.0' ),
			'extra part'  => array( '42.25,-71.0,15,extra' ),
			'array'       => array( array( '42.25,-71.0' ) ),
		);
	}

	/**
	 * @dataProvider droppable
	 * @param mixed $value A value that is not a location.
	 */
	public function test_sanitize_drops_anything_else( mixed $value ): void {
		$this->assertSame( '', VenueMap::sanitize( $value ) );
	}
}
