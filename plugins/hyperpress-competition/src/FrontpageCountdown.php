<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * Offers competitions to the front page countdown (see hyperpress-frontpage's Countdowns for the candidate format).
 *
 * A competition is a candidate from `Settings::frontpage_lead_days()` days before its first counted day until its
 * last counted day ends. It counts down to the day `Schedule::current()` picks, so the target moves to the next
 * counted day as each day ends.
 */
final class FrontpageCountdown {

	public const FILTER = 'hyperpress_frontpage_countdown_candidates';

	public static function register(): void {
		add_filter( self::FILTER, array( self::class, 'candidates' ) );
	}

	/**
	 * Append a candidate for every published competition that has a counted day.
	 *
	 * @param mixed $candidates Candidates offered so far.
	 * @return array<int,mixed>
	 */
	public static function candidates( $candidates ): array {
		$candidates = is_array( $candidates ) ? $candidates : array();
		if ( ! Settings::frontpage_enabled() ) {
			return $candidates;
		}

		$lead = Settings::frontpage_lead_days();
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

		update_meta_cache( 'post', $ids ); // One query for every schedule instead of one per competition.
		$now = Schedule::now();

		foreach ( $ids as $id ) {
			$id      = (int) $id;
			$days    = Schedule::days( $id );
			$counted = array_values( array_filter( $days, static fn( array $day ): bool => $day['countdown'] ) );
			$current = Schedule::pick( $days, $now );
			if ( array() === $counted || null === $current ) {
				continue;
			}

			$candidates[] = array(
				'id'         => 'competition-' . $id,
				'start'      => $current['start'],
				'show_from'  => $counted[0]['start']->modify( '-' . $lead . ' days' ),
				'show_until' => $counted[ count( $counted ) - 1 ]['end'],
				'priority'   => 10,
				'render'     => static fn(): string => Countdown::render( $id, true, true, true ),
			);
		}

		return $candidates;
	}
}
