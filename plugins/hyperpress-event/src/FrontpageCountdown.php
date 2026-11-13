<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use DateInterval;

/**
 * Offers events to the front page countdown (see hyperpress-frontpage's Countdowns for the candidate format).
 *
 * An event is a candidate from `Settings::frontpage_lead_days()` days before it starts until it ends.
 */
final class FrontpageCountdown {

	public const FILTER = 'hyperpress_frontpage_countdown_candidates';

	public static function register(): void {
		add_filter( self::FILTER, array( self::class, 'candidates' ) );
	}

	/**
	 * Append a candidate for every published event that has a start.
	 *
	 * @param mixed $candidates Candidates offered so far.
	 * @return array<int,mixed>
	 */
	public static function candidates( $candidates ): array {
		$candidates = is_array( $candidates ) ? $candidates : array();
		if ( ! Settings::frontpage_enabled() ) {
			return $candidates;
		}

		$lead = new DateInterval( 'P' . Settings::frontpage_lead_days() . 'D' );
		$ids  = get_posts(
			array(
				'post_type'     => Plugin::POST_TYPE,
				'post_status'   => 'publish',
				'fields'        => 'ids',
				'numberposts'   => -1,
				'orderby'       => 'ID',
				'order'         => 'ASC',
				'no_found_rows' => true,
			)
		);

		update_meta_cache( 'post', $ids );

		foreach ( $ids as $id ) {
			$id    = (int) $id;
			$start = Dates::start( $id );
			$end   = Dates::end( $id );
			if ( null === $start || null === $end ) {
				continue;
			}

			$candidates[] = array(
				'id'         => 'event-' . $id,
				'start'      => $start,
				'show_from'  => $start->sub( $lead ),
				'show_until' => $end,
				'priority'   => 10,
				'render'     => static fn(): string => Countdown::render( $id ),
			);
		}

		return $candidates;
	}
}
