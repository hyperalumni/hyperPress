<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use WP_UnitTestCase;

/**
 * Annually repeating blocks that turn on and off in the same month, evaluated at a frozen time.
 */
final class TimeRestrictAnnualTest extends WP_UnitTestCase {

	private \Closure $clock;

	private function freeze( string $when ): void {
		$timestamp   = (int) strtotime( $when . ' UTC' );
		$this->clock = static fn (): int => $timestamp;
		add_filter( 'hyperpress_shortcode_now', $this->clock );
	}

	public function tear_down(): void {
		if ( isset( $this->clock ) ) {
			remove_filter( 'hyperpress_shortcode_now', $this->clock );
		}
		parent::tear_down();
	}

	private function block( string $attributes ): string {
		return do_shortcode( '[hyperpress_time_restrict_repeat type="annually" ' . $attributes . ' else="HIDDEN"]SHOWN[/hyperpress_time_restrict_repeat]' );
	}

	public static function same_month_cases(): array {
		// Frozen at 15 March 2026 12:00. The block runs from on-date to off-date of March and is "on" for the rest
		// of the year when off-date is earlier than on-date.
		return array(
			'on today, off earlier in the month: on for nearly a year' => array( '2026-03-15 12:00:00', 'onmonth="march" offmonth="march" ondate="15" offdate="10" ontime="00:00:00"', 'SHOWN' ),
			'on today but the on time has not come yet'                => array( '2026-03-15 12:00:00', 'onmonth="march" offmonth="march" ondate="15" offdate="10" ontime="18:00:00"', 'HIDDEN' ),
			'on today, off later in the month'                         => array( '2026-03-15 12:00:00', 'onmonth="march" offmonth="march" ondate="15" offdate="20" ontime="00:00:00"', 'SHOWN' ),
			'on and off today, between the times'                      => array( '2026-03-15 12:00:00', 'onmonth="march" offmonth="march" ondate="15" offdate="15" ontime="08:00:00" offtime="18:00:00"', 'SHOWN' ),
			'before the off-date: still on from last year' => array( '2026-03-05 12:00:00', 'onmonth="march" offmonth="march" ondate="16" offdate="10"', 'SHOWN' ),
			'window between off-date and on-date is hidden'            => array( '2026-03-12 12:00:00', 'onmonth="march" offmonth="march" ondate="15" offdate="10"', 'HIDDEN' ),
		);
	}

	/** @dataProvider same_month_cases */
	public function test_same_month_schedule( string $now, string $attributes, string $expected ): void {
		$this->freeze( $now );

		$this->assertSame( $expected, $this->block( $attributes ) );
	}

	public function test_no_undefined_variable_warning(): void {
		// The WP test case fails the test on any PHP warning or notice.
		$this->freeze( '2026-03-15 12:00:00' );

		$this->block( 'onmonth="march" offmonth="march" ondate="15" offdate="10" ontime="00:00:00"' );

		$this->assertTrue( true );
	}
}
