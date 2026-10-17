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

	/**
	 * Locales that are not BCP 47 shaped must give the plain number on every PHP version.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function malformed_locales(): array {
		return array(
			'punctuation only' => array( '!!' ),
			'contains a space' => array( 'en US' ),
		);
	}

	/**
	 * @dataProvider malformed_locales
	 */
	public function test_a_malformed_locale_falls_back_to_the_plain_number( string $locale ): void {
		$year = (string) ( (int) gmdate( 'Y' ) - 10 );

		$this->assertSame(
			'10',
			do_shortcode( '[hyperpress_date_distance date="' . $year . '-01-01" suffix="true" locale="' . $locale . '"]' )
		);
	}

	/**
	 * @return array<string, array{0: string}>
	 */
	public static function english_locales(): array {
		return array(
			'hyphen'     => array( 'en-US' ),
			'underscore' => array( 'en_US' ),
		);
	}

	/**
	 * @dataProvider english_locales
	 */
	public function test_a_valid_english_locale_gets_the_ordinal_suffix( string $locale ): void {
		$year = (string) ( (int) gmdate( 'Y' ) - 10 );

		$this->assertSame(
			'10th',
			do_shortcode( '[hyperpress_date_distance date="' . $year . '-01-01" suffix="true" locale="' . $locale . '"]' )
		);
	}

	public function test_a_valid_non_english_locale_uses_the_number_formatter(): void {
		$year     = (string) ( (int) gmdate( 'Y' ) - 10 );
		$expected = ( new \NumberFormatter( 'de-DE', \NumberFormatter::ORDINAL ) )->format( 10 );

		$this->assertSame(
			esc_html( (string) $expected ),
			do_shortcode( '[hyperpress_date_distance date="' . $year . '-01-01" suffix="true" locale="de-DE"]' )
		);
	}

	public function test_an_explicit_empty_locale_uses_the_default_locale_formatter(): void {
		$year     = (string) ( (int) gmdate( 'Y' ) - 10 );
		$expected = ( new \NumberFormatter( '', \NumberFormatter::ORDINAL ) )->format( 10 );

		$this->assertSame(
			esc_html( (string) $expected ),
			do_shortcode( '[hyperpress_date_distance date="' . $year . '-01-01" suffix="true" locale=""]' )
		);
	}
}
