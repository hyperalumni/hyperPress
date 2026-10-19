<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\StartDate;
use WP_UnitTestCase;

final class StartDateTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		update_option( 'timezone_string', 'America/New_York' );
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb'], $GLOBALS['hyperpress_test_rwmb_posts'] );
		parent::tear_down();
	}

	public function test_summer_text_is_an_edt_instant(): void {
		$GLOBALS['hyperpress_test_rwmb'][ StartDate::FIELD ] = '2030-07-01 10:00';

		$instant = StartDate::instant( self::factory()->post->create() );

		$this->assertInstanceOf( \DateTimeImmutable::class, $instant );
		$this->assertSame( '2030-07-01T10:00:00-04:00', $instant->format( 'c' ) );
		$this->assertSame(
			( new \DateTimeImmutable( '2030-07-01T14:00:00+00:00' ) )->getTimestamp(),
			$instant->getTimestamp()
		);
	}

	public function test_winter_text_is_an_est_instant(): void {
		$GLOBALS['hyperpress_test_rwmb'][ StartDate::FIELD ] = '2030-01-01 10:00';

		$instant = StartDate::instant( self::factory()->post->create() );

		$this->assertInstanceOf( \DateTimeImmutable::class, $instant );
		$this->assertSame( '2030-01-01T10:00:00-05:00', $instant->format( 'c' ) );
	}

	/**
	 * @dataProvider invalid_values
	 * @param mixed $value Stored value.
	 */
	public function test_empty_or_invalid_text_gives_null( $value ): void {
		$GLOBALS['hyperpress_test_rwmb'][ StartDate::FIELD ] = $value;

		$this->assertNull( StartDate::instant( self::factory()->post->create() ) );
	}

	/**
	 * @return array<string,array<int,mixed>>
	 */
	public static function invalid_values(): array {
		return array(
			'empty'       => array( '' ),
			'not a date'  => array( 'not a date' ),
			'empty array' => array( array() ),
			'zero'        => array( 0 ),
		);
	}

	public function test_start_field_stores_wall_clock_text_like_the_countdown(): void {
		$field = null;
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			if ( 'hyperpress_competition_information' !== ( $box['id'] ?? '' ) ) {
				continue;
			}
			foreach ( $box['fields'] as $candidate ) {
				if ( StartDate::FIELD === ( $candidate['id'] ?? '' ) ) {
					$field = $candidate;
				}
			}
		}

		$this->assertIsArray( $field );
		$this->assertFalse( $field['timestamp'] );
		$this->assertSame( 'yy-mm-dd', $field['format'] );
	}
}
