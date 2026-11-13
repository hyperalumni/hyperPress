<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use DateTimeImmutable;

/**
 * Event start and end instants, always in the site timezone.
 */
final class Dates {

	private const FORMAT = '!Y-m-d H:i';

	/**
	 * The current time in the site timezone (filter hyperpress_event_now is a test-only clock).
	 */
	public static function now(): DateTimeImmutable {
		$now = apply_filters( 'hyperpress_event_now', new DateTimeImmutable( 'now', wp_timezone() ) );

		return $now instanceof DateTimeImmutable ? $now : new DateTimeImmutable( 'now', wp_timezone() );
	}

	/**
	 * The event start, or null when missing or malformed.
	 *
	 * @param int $post_id Event ID.
	 */
	public static function start( int $post_id ): ?DateTimeImmutable {
		return self::parse( get_post_meta( $post_id, Fields::START, true ) );
	}

	/**
	 * The event end: the saved end when it is after the start, else the end of the start day. Null without a start.
	 *
	 * @param int $post_id Event ID.
	 */
	public static function end( int $post_id ): ?DateTimeImmutable {
		$start = self::start( $post_id );
		if ( null === $start ) {
			return null;
		}
		$end = self::parse( get_post_meta( $post_id, Fields::END, true ) );
		if ( null !== $end && $end > $start ) {
			return $end;
		}

		return $start->setTime( 23, 59, 59 );
	}

	/**
	 * The saved end when one was set and is after the start, else null. Unlike end(), a missing end is not replaced
	 * by the end of the start day, so callers can tell whether the editor gave an end.
	 *
	 * @param int $post_id Event ID.
	 */
	public static function explicit_end( int $post_id ): ?DateTimeImmutable {
		$start = self::start( $post_id );
		$end   = self::parse( get_post_meta( $post_id, Fields::END, true ) );

		return null !== $start && null !== $end && $end > $start ? $end : null;
	}

	/**
	 * Whether the event has ended: now is strictly after the end. Never true without a start.
	 *
	 * @param int $post_id Event ID.
	 */
	public static function is_over( int $post_id ): bool {
		$end = self::end( $post_id );

		return null !== $end && self::now() > $end;
	}

	/**
	 * Parses a saved 'Y-m-d H:i' value in the site timezone; null on any parse error or impossible date.
	 *
	 * @param mixed $value Saved meta value.
	 */
	private static function parse( $value ): ?DateTimeImmutable {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}
		$date   = DateTimeImmutable::createFromFormat( self::FORMAT, trim( $value ), wp_timezone() );
		$errors = DateTimeImmutable::getLastErrors();
		if ( false === $date || ( false !== $errors && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) ) {
			return null;
		}

		return $date;
	}
}
