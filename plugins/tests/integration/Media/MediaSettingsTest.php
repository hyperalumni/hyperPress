<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\Settings;
use WP_UnitTestCase;

final class MediaSettingsTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_media' );
		parent::tear_down();
	}

	public function test_the_constants_are_the_option_and_field_ids(): void {
		$this->assertSame( 'hyperpress_media', Settings::OPTION );
		$this->assertSame( 'hyperpress_media_photos_page', Settings::PHOTOS_PAGE );
	}

	public function test_nothing_saved_gives_zero(): void {
		delete_option( 'hyperpress_media' );

		$this->assertSame( 0, Settings::photos_page() );
		$this->assertFalse( get_option( 'hyperpress_media' ) );
	}

	public function test_a_positive_integer_or_digit_string_gives_that_integer(): void {
		foreach ( array(
			array( 12, 12 ),
			array( '12', 12 ),
			array( '007', 7 ),
		) as $case ) {
			update_option( 'hyperpress_media', array( 'hyperpress_media_photos_page' => $case[0] ) );

			$this->assertSame( $case[1], Settings::photos_page(), var_export( $case[0], true ) );
		}
	}

	public function test_everything_else_gives_zero(): void {
		foreach ( array( '12abc', ' 12', '0', 0, -5, '-5', 1.5, true, array( 12 ), null, '' ) as $value ) {
			update_option( 'hyperpress_media', array( 'hyperpress_media_photos_page' => $value ) );

			$this->assertSame( 0, Settings::photos_page(), var_export( $value, true ) );
		}
	}

	public function test_a_missing_key_or_a_non_array_option_gives_zero(): void {
		update_option( 'hyperpress_media', array( 'other' => 12 ) );
		$this->assertSame( 0, Settings::photos_page() );

		foreach ( array( 'garbage', 5, true ) as $value ) {
			update_option( 'hyperpress_media', $value );

			$this->assertSame( 0, Settings::photos_page(), var_export( $value, true ) );
		}
	}
}
