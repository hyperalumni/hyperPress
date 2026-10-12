<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use WP_UnitTestCase;

/**
 * Attribute validation of the hyperpress_time_restrict shortcodes (audit S25), evaluated at a frozen time.
 */
final class TimeRestrictValidationTest extends WP_UnitTestCase {

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

	private static function repeat( string $attributes ): string {
		return '[hyperpress_time_restrict_repeat ' . $attributes . ' else="HIDDEN"]SHOWN[/hyperpress_time_restrict_repeat]';
	}

	private static function restrict( string $attributes ): string {
		return '[hyperpress_time_restrict ' . $attributes . ' else="HIDDEN"]SHOWN[/hyperpress_time_restrict]';
	}

	public static function cases(): array {
		return array(
			// Required annual attributes: a missing or unparsable month counts as missing, so the content shows.
			'annually, onmonth missing: shows'                           => array( self::repeat( 'type="annually" offmonth="june" ondate="1" offdate="1"' ), '2026-09-15 12:00:00', 'SHOWN' ),
			'annually, offmonth missing: shows'                          => array( self::repeat( 'type="annually" onmonth="march" ondate="1" offdate="1"' ), '2026-02-15 12:00:00', 'SHOWN' ),
			'annually, onmonth unparsable: shows'                        => array( self::repeat( 'type="annually" onmonth="notamonth" offmonth="june" ondate="1" offdate="1"' ), '2026-09-15 12:00:00', 'SHOWN' ),
			'annually, offmonth unparsable: shows'                       => array( self::repeat( 'type="annually" onmonth="march" offmonth="garbage" ondate="1" offdate="1"' ), '2026-02-15 12:00:00', 'SHOWN' ),
			'annually, ondate missing: shows (unchanged)'                => array( self::repeat( 'type="annually" onmonth="march" offmonth="june" offdate="1"' ), '2026-09-15 12:00:00', 'SHOWN' ),

			// A zero-padded date is the same date.
			'annually, ondate="05" on the 5th'                           => array( self::repeat( 'type="annually" onmonth="march" offmonth="september" ondate="05" offdate="20"' ), '2026-03-05 12:00:00', 'SHOWN' ),
			'annually, ondate="5" on the 5th (same)'                     => array( self::repeat( 'type="annually" onmonth="march" offmonth="september" ondate="5" offdate="20"' ), '2026-03-05 12:00:00', 'SHOWN' ),
			'annually, offdate="05" on the 5th, time passed'             => array( self::repeat( 'type="annually" onmonth="march" offmonth="september" ondate="5" offdate="05" offtime="23:00:00"' ), '2026-09-05 12:00:00', 'SHOWN' ),
			'monthly, ondate="05" on the 5th'                            => array( self::repeat( 'type="monthly" ondate="05" offdate="20"' ), '2026-03-05 12:00:00', 'SHOWN' ),

			// Unpadded times compare as times, not strings.
			'daily, ontime="9:00" before it starts'                      => array( self::repeat( 'type="daily" ontime="9:00" offtime="17:00:00"' ), '2026-03-05 08:00:00', 'HIDDEN' ),
			'daily, ontime="09:00:00" before it starts (same)'           => array( self::repeat( 'type="daily" ontime="09:00:00" offtime="17:00:00"' ), '2026-03-05 08:00:00', 'HIDDEN' ),
			'daily, ontime="9:00" once it has started'                   => array( self::repeat( 'type="daily" ontime="9:00" offtime="17:00:00"' ), '2026-03-05 10:00:00', 'SHOWN' ),
			'daily, offtime="5:00pm" after it ends'                      => array( self::repeat( 'type="daily" ontime="09:00:00" offtime="5:00pm"' ), '2026-03-05 18:00:00', 'HIDDEN' ),
			'daily, unparsable ontime falls back to midnight'            => array( self::repeat( 'type="daily" ontime="garbage" offtime="17:00:00"' ), '2026-03-05 08:00:00', 'SHOWN' ),

			// hyperpress_time_restrict: an unparsable on or off behaves like the attribute being unset.
			'hyperpress_time_restrict, on unparsable, off in the future' => array( self::restrict( 'on="garbage" off="2026-06-01"' ), '2026-03-05 12:00:00', 'SHOWN' ),
			'hyperpress_time_restrict, on unparsable, off in the past'   => array( self::restrict( 'on="garbage" off="2026-01-01"' ), '2026-03-05 12:00:00', 'HIDDEN' ),
			'hyperpress_time_restrict, on in the past, off unparsable'   => array( self::restrict( 'on="2026-01-01" off="garbage"' ), '2026-03-05 12:00:00', 'SHOWN' ),
			'hyperpress_time_restrict, on in the future, off unparsable' => array( self::restrict( 'on="2026-06-01" off="garbage"' ), '2026-03-05 12:00:00', 'HIDDEN' ),

			// Regressions: identical before and after.
			'regression: annually window, inside'                        => array( self::repeat( 'type="annually" onmonth="march" offmonth="september" ondate="5" offdate="20"' ), '2026-06-15 12:00:00', 'SHOWN' ),
			'regression: annually window, outside'                       => array( self::repeat( 'type="annually" onmonth="march" offmonth="september" ondate="5" offdate="20"' ), '2026-12-15 12:00:00', 'HIDDEN' ),
			'regression: hyperpress_time_restrict window, inside'        => array( self::restrict( 'on="2026-01-01" off="2026-06-01"' ), '2026-03-05 12:00:00', 'SHOWN' ),
			'regression: hyperpress_time_restrict window, expired'       => array( self::restrict( 'on="2026-01-01" off="2026-02-01"' ), '2026-03-05 12:00:00', 'HIDDEN' ),
		);
	}

	/** @dataProvider cases */
	public function test_attribute_validation( string $markup, string $now, string $expected ): void {
		$this->freeze( $now );

		$this->assertSame( $expected, do_shortcode( $markup ) );
	}
}
