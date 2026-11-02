<?php
namespace HyperPress\Countdown;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use HyperPress\Utils\Meta;

/**
 * When a countdown counts down to and when it is meant to be shown (its Hide Before / Hide After dates).
 *
 * The stored values are wall-clock times with no zone, so they are read in the site timezone.
 */
final class Window {

	/**
	 * Only published countdowns, or private ones the current user can read.
	 *
	 * @param mixed $post Result of get_post()/get_page_by_path().
	 */
	public static function is_renderable( $post ): bool {
		if ( ! $post instanceof \WP_Post || 'hyper_countdown' !== $post->post_type ) {
			return false;
		}
		if ( 'publish' === $post->post_status ) {
			return true;
		}

		return 'private' === $post->post_status && current_user_can( 'read_post', $post->ID );
	}

	/**
	 * The Hide Before / Hide After bounds of a countdown; an empty or unparseable field is no bound.
	 *
	 * @param int $post_id Countdown post ID.
	 * @return array{show_from:?\DateTimeImmutable,show_until:?\DateTimeImmutable}
	 */
	public static function visibility( int $post_id ): array {
		return array(
			'show_from'  => self::parse( Meta::get( 'hyperpress_countdown_hide_before', $post_id ) ),
			'show_until' => self::parse( Meta::get( 'hyperpress_countdown_hide_after', $post_id ) ),
		);
	}

	/**
	 * Whether `$now` is inside the window: after `$from` and before `$until`, both strict, each optional.
	 *
	 * @param \DateTimeImmutable|null $from  The window opens after this instant.
	 * @param \DateTimeImmutable|null $until The window closes at this instant.
	 * @param \DateTimeImmutable      $now   The moment to judge.
	 */
	public static function is_open( ?\DateTimeImmutable $from, ?\DateTimeImmutable $until, \DateTimeImmutable $now ): bool {
		return ( null === $from || $from < $now ) && ( null === $until || $now < $until );
	}

	/**
	 * The instant a countdown counts to and its visibility window, or null for a countdown that cannot be shown
	 * (not renderable, or no parseable event date).
	 *
	 * @param int $post_id Countdown post ID.
	 * @return array{start:\DateTimeImmutable,show_from:?\DateTimeImmutable,show_until:?\DateTimeImmutable}|null
	 */
	public static function for_post( int $post_id ): ?array {
		if ( ! self::is_renderable( get_post( $post_id ) ) ) {
			return null;
		}

		$start = self::parse( Meta::get( 'hyperpress_countdown_event_date_time', $post_id ) );
		if ( null === $start ) {
			return null;
		}

		return array_merge( array( 'start' => $start ), self::visibility( $post_id ) );
	}

	/**
	 * A stored date-time string as a DateTimeImmutable in the site timezone, or null when empty or unparseable.
	 *
	 * @param mixed $value Stored value.
	 */
	private static function parse( $value ): ?\DateTimeImmutable {
		if ( ! is_string( $value ) || '' === trim( $value ) ) {
			return null;
		}

		try {
			return new \DateTimeImmutable( trim( $value ), wp_timezone() );
		} catch ( \Exception $error ) {
			return null;
		}
	}
}
