<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use WP_UnitTestCase;

final class DateDistanceInvalidTest extends WP_UnitTestCase {

	public function test_an_unparsable_date_returns_an_empty_string(): void {
		$this->assertSame( '', do_shortcode( '[hyperpress_date_distance date="not a date"]' ) );
	}

	public function test_an_impossible_date_returns_an_empty_string(): void {
		$this->assertSame( '', do_shortcode( '[hyperpress_date_distance date="2026-13-45"]' ) );
	}

	public function test_a_valid_date_still_works(): void {
		$year = (string) ( (int) gmdate( 'Y' ) - 10 );

		$this->assertSame( '10', do_shortcode( '[hyperpress_date_distance date="' . $year . '-01-01" format="%y"]' ) );
	}

	public function test_an_invalid_locale_falls_back_to_the_plain_number(): void {
		$year = (string) ( (int) gmdate( 'Y' ) - 10 );

		$this->assertSame(
			'10',
			do_shortcode( '[hyperpress_date_distance date="' . $year . '-01-01" suffix="true" locale="\\\\"]' )
		);
	}
}
