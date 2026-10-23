<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * Formats the span of a competition's days as one short phrase.
 */
final class DateRange {

	/**
	 * "March 13, 2026", "March 13–15, 2026", "March 31 – April 2, 2026" or "December 30, 2026 – January 2, 2027".
	 *
	 * Compares calendar dates in the site timezone; times of day are ignored.
	 *
	 * @param \DateTimeImmutable $first Start of the first day.
	 * @param \DateTimeImmutable $last  Start of the last day.
	 */
	public static function format( \DateTimeImmutable $first, \DateTimeImmutable $last ): string {
		$zone = wp_timezone();
		$from = $first->getTimestamp();
		$to   = $last->getTimestamp();
		$date = static fn( string $format, int $time ): string => wp_date( $format, $time, $zone );

		if ( $date( 'Y-m-d', $from ) === $date( 'Y-m-d', $to ) ) {
			return $date( 'F j, Y', $from );
		}
		if ( $date( 'Y-m', $from ) === $date( 'Y-m', $to ) ) {
			return $date( 'F j', $from ) . '–' . $date( 'j, Y', $to );
		}
		if ( $date( 'Y', $from ) === $date( 'Y', $to ) ) {
			return $date( 'F j', $from ) . ' – ' . $date( 'F j, Y', $to );
		}

		return $date( 'F j, Y', $from ) . ' – ' . $date( 'F j, Y', $to );
	}
}
