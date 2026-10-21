<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Settings;
use WP_UnitTestCase;

final class DayEndSettingTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( Settings::OPTION );
		parent::tear_down();
	}

	public function test_default_is_eight_pm(): void {
		delete_option( Settings::OPTION );
		$this->assertSame( '20:00', Settings::day_end() );
		$this->assertSame( '20:00', Settings::defaults()[ Settings::DAY_END ] );

		update_option( Settings::OPTION, 'garbage' );
		$this->assertSame( '20:00', Settings::day_end() );

		// Meta Box drops the key of an empty field.
		update_option( Settings::OPTION, array() );
		$this->assertSame( '20:00', Settings::day_end() );
	}

	/** @return array<string,array<int,mixed>> */
	public static function malformed(): array {
		return array( 'empty' => array( '' ), 'am pm' => array( '8pm' ), 'hour 24' => array( '24:00' ), 'minute 60' => array( '20:60' ), 'one digit hour' => array( '2:00' ), 'seconds' => array( '20:00:00' ), 'array' => array( array( '20:00' ) ), 'int' => array( 2000 ) );
	}

	/**
	 * @dataProvider malformed
	 * @param mixed $value Stored value.
	 */
	public function test_malformed_values_give_the_default( $value ): void {
		update_option( Settings::OPTION, array( Settings::DAY_END => $value ) );

		$this->assertSame( '20:00', Settings::day_end() );
		$this->assertSame( '', Settings::sanitize_day_end( $value ) );
	}

	public function test_valid_values_are_kept(): void {
		foreach ( array( '21:30', '00:00', '23:59', '08:05' ) as $value ) {
			update_option( Settings::OPTION, array( Settings::DAY_END => $value ) );
			$this->assertSame( $value, Settings::day_end() );
			$this->assertSame( $value, Settings::sanitize_day_end( ' ' . $value . ' ' ) );
		}
	}

	public function test_settings_box_has_a_time_field_after_count_past_zero(): void {
		$box = null;
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $candidate ) {
			if ( 'hyperpress_competition_box' === ( $candidate['id'] ?? '' ) ) {
				$box = $candidate;
			}
		}
		$this->assertNotNull( $box );
		$ids = array_column( $box['fields'], 'id' );
		$this->assertContains( Settings::DAY_END, $ids );
		$this->assertGreaterThan( array_search( Settings::PAST_ZERO, $ids, true ), array_search( Settings::DAY_END, $ids, true ) );

		$field = $box['fields'][ array_search( Settings::DAY_END, $ids, true ) ];
		$this->assertSame( 'time', $field['type'] );
		$this->assertFalse( $field['timestamp'] );
		$this->assertSame( 'H:i', $field['save_format'] );
		$this->assertSame( '20:00', $field['std'] );
		$this->assertSame( array( Settings::class, 'sanitize_day_end' ), $field['sanitize_callback'] );
	}
}
