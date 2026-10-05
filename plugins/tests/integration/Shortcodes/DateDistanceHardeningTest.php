<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use HyperPress\Shortcodes\Shortcodes\DateDistance;
use WP_UnitTestCase;

final class DateDistanceHardeningTest extends WP_UnitTestCase {

	private function years_ago( int $years ): string {
		return (string) ( (int) gmdate( 'Y' ) - $years ) . '-01-01';
	}

	public function test_html_in_the_format_is_escaped(): void {
		$output = DateDistance::render(
			array(
				'date'   => $this->years_ago( 5 ),
				'format' => '<b onmouseover=1>%y',
			)
		);

		$this->assertStringNotContainsString( '<b', $output );
		$this->assertSame( '&lt;b onmouseover=1&gt;5', $output );
	}

	public function test_inclusive_leaves_a_non_numeric_result_unchanged(): void {
		$output = do_shortcode( '[hyper_date_distance date="' . $this->years_ago( 5 ) . '" format="%R%a days" inclusive="true"]' );

		$this->assertMatchesRegularExpression( '/^[+-]\d+ days$/', $output );
	}

	public function test_inclusive_still_increments_a_numeric_result(): void {
		$this->assertSame( '6', do_shortcode( '[hyper_date_distance date="' . $this->years_ago( 5 ) . '" format="%y" inclusive="true"]' ) );
	}

	public function test_ordinals_are_supported_when_intl_is_loaded(): void {
		$this->assertTrue( DateDistance::supports_ordinals() );
	}

	public function test_suffix_returns_the_plain_number_when_ordinals_are_unsupported(): void {
		add_filter( 'hyperpress_shortcodes_ordinals', '__return_false' );

		$this->assertFalse( DateDistance::supports_ordinals() );
		$this->assertSame( '5', do_shortcode( '[hyper_date_distance date="' . $this->years_ago( 5 ) . '" format="%y" suffix="true"]' ) );
	}

	public function test_suffix_adds_the_ordinal_when_supported(): void {
		$this->assertSame( '5th', do_shortcode( '[hyper_date_distance date="' . $this->years_ago( 5 ) . '" format="%y" suffix="true"]' ) );
	}

	public function test_an_underscore_locale_is_normalized(): void {
		$this->assertSame( '5th', do_shortcode( '[hyper_date_distance date="' . $this->years_ago( 5 ) . '" format="%y" suffix="true" locale="en_US"]' ) );
	}
}
