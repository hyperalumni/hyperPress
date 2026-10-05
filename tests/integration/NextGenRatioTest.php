<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class NextGenRatioTest extends WP_UnitTestCase {

	/** @return array<string,array{mixed,mixed,float}> */
	public static function ratios(): array {
		return array(
			'normal'          => array( 200, 100, 2.0 ),
			'numeric strings' => array( '150', '100', 1.5 ),
			'float values'    => array( 1.5, 0.5, 3.0 ),
			'zero height'     => array( 200, 0, 1.0 ),
			'zero width'      => array( 0, 100, 1.0 ),
			'empty height'    => array( 200, '', 1.0 ),
			'non-numeric'     => array( 'abc', 100, 1.0 ),
			'null width'      => array( null, 100, 1.0 ),
			'null height'     => array( 200, null, 1.0 ),
			'negative height' => array( 200, -100, 1.0 ),
			'negative width'  => array( -200, 100, 1.0 ),
			'both missing'    => array( null, null, 1.0 ),
		);
	}

	/**
	 * @dataProvider ratios
	 * @param mixed $width    Width.
	 * @param mixed $height   Height.
	 * @param float $expected Expected ratio.
	 */
	public function test_ratio_never_throws_and_falls_back_to_one( $width, $height, float $expected ): void {
		$this->assertSame( $expected, hyperpress_nextgen_ratio( $width, $height ) );
	}
}
