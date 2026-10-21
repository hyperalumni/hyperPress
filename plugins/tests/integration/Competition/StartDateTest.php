<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\StartDate;
use WP_UnitTestCase;

final class StartDateTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		update_option( 'timezone_string', 'America/New_York' );
	}

	public function test_parse_reads_summer_and_winter_text_in_the_site_zone(): void {
		$summer = StartDate::parse( '2030-07-01 10:00' );
		$winter = StartDate::parse( '2030-01-01 10:00' );

		$this->assertInstanceOf( \DateTimeImmutable::class, $summer );
		$this->assertInstanceOf( \DateTimeImmutable::class, $winter );
		$this->assertSame( '2030-07-01T10:00:00-04:00', $summer->format( 'c' ) );
		$this->assertSame( '2030-01-01T10:00:00-05:00', $winter->format( 'c' ) );
	}

	/**
	 * @dataProvider unparseable_values
	 * @param mixed $value Candidate text.
	 */
	public function test_parse_rejects_blank_or_invalid_input( $value ): void {
		$this->assertNull( StartDate::parse( $value ) );
	}

	/**
	 * @return array<string,array<int,mixed>>
	 */
	public static function unparseable_values(): array {
		return array(
			'empty'       => array( '' ),
			'blank'       => array( '  ' ),
			'not a date'  => array( 'not a date' ),
			'impossible'  => array( '2030-02-31 10:00' ),
			'empty array' => array( array() ),
			'zero'        => array( 0 ),
			'null'        => array( null ),
		);
	}
}
