<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\DateRange;
use WP_UnitTestCase;

final class DateRangeTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		update_option( 'timezone_string', 'America/New_York' );
	}

	public function tear_down(): void {
		delete_option( 'timezone_string' );
		parent::tear_down();
	}

	private function range( string $first, string $last ): string {
		return DateRange::format( new \DateTimeImmutable( $first, wp_timezone() ), new \DateTimeImmutable( $last, wp_timezone() ) );
	}

	public function test_one_day(): void {
		$this->assertSame( 'March 13, 2026', $this->range( '2026-03-13 09:00', '2026-03-13 09:00' ) );
	}

	public function test_two_times_on_the_same_date_are_one_day(): void {
		$this->assertSame( 'March 13, 2026', $this->range( '2026-03-13 09:00', '2026-03-13 17:00' ) );
	}

	public function test_same_month(): void {
		$this->assertSame( 'March 13–15, 2026', $this->range( '2026-03-13 16:00', '2026-03-15 09:00' ) );
	}

	public function test_across_months(): void {
		$this->assertSame( 'March 31 – April 2, 2026', $this->range( '2026-03-31 16:00', '2026-04-02 09:00' ) );
	}

	public function test_across_years(): void {
		$this->assertSame( 'December 30, 2026 – January 2, 2027', $this->range( '2026-12-30 16:00', '2027-01-02 09:00' ) );
	}

	public function test_dates_follow_the_site_timezone_not_the_input_zone(): void {
		$utc = new \DateTimeZone( 'UTC' );
		$day = new \DateTimeImmutable( '2030-03-14 02:00', $utc );

		$this->assertSame( 'March 13, 2030', DateRange::format( $day, $day ), '02:00 UTC is still the 13th in New York' );
	}

	public function test_a_range_across_the_daylight_saving_change(): void {
		$this->assertSame( 'March 9–11, 2030', $this->range( '2030-03-09 20:00', '2030-03-11 09:00' ) );
	}

	public function test_a_last_day_starting_at_midnight(): void {
		$this->assertSame( 'March 13–15, 2030', $this->range( '2030-03-13 16:00', '2030-03-15 00:00' ) );
	}
}
