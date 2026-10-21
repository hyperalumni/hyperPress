<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * A competition's days: the stored group rows, normalized and sorted, and the day the countdown targets.
 */
final class Schedule {

	public const FIELD = 'hyperpress_competition_event_days';

	/**
	 * Every usable day of a competition in chronological order.
	 *
	 * Reads the stored rows directly (one serialized list), not rwmb_meta(), which may reformat them.
	 *
	 * @param int $post_id Competition post ID.
	 *
	 * @return list<array{label:string,start:\DateTimeImmutable,countdown:bool}>
	 */
	public static function days( int $post_id ): array {
		return self::normalize( get_post_meta( $post_id, self::FIELD, true ) );
	}

	/**
	 * Drops rows without a parseable start, trims labels and sorts by start (rows with equal starts keep their order).
	 *
	 * Meta Box leaves an unticked checkbox out of the stored row, so a day is counted unless its row has a non-empty `skip`.
	 *
	 * @param mixed $rows Stored group value.
	 *
	 * @return list<array{label:string,start:\DateTimeImmutable,countdown:bool}>
	 */
	public static function normalize( mixed $rows ): array {
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$days = array();
		foreach ( array_values( $rows ) as $order => $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$start = StartDate::parse( $row['start'] ?? null );
			if ( null === $start ) {
				continue;
			}
			$label  = $row['label'] ?? '';
			$days[] = array(
				'order'     => $order,
				'label'     => is_string( $label ) ? trim( $label ) : '',
				'start'     => $start,
				'countdown' => empty( $row['skip'] ),
			);
		}

		usort(
			$days,
			static fn( array $a, array $b ): int => array( $a['start']->getTimestamp(), $a['order'] ) <=> array( $b['start']->getTimestamp(), $b['order'] )
		);

		return array_map(
			static fn( array $day ): array => array(
				'label'     => $day['label'],
				'start'     => $day['start'],
				'countdown' => $day['countdown'],
			),
			$days
		);
	}

	/**
	 * The first counted day starting at or after `$now`, else the last counted day, else null.
	 *
	 * @param list<array{label:string,start:\DateTimeImmutable,countdown:bool}> $days Days in chronological order.
	 * @param \DateTimeImmutable                                                $now  The moment to compare against.
	 *
	 * @return array{label:string,start:\DateTimeImmutable,countdown:bool}|null
	 */
	public static function pick( array $days, \DateTimeImmutable $now ): ?array {
		$index = self::pick_index( $days, $now );

		return null === $index ? null : $days[ $index ];
	}

	/**
	 * The index in `$days` of the day `pick()` returns, or null when no day is counted.
	 *
	 * Lets a caller mark the current day by position (labels can repeat or be empty).
	 *
	 * @param list<array{label:string,start:\DateTimeImmutable,countdown:bool}> $days Days in chronological order.
	 * @param \DateTimeImmutable                                                $now  The moment to compare against.
	 */
	public static function pick_index( array $days, \DateTimeImmutable $now ): ?int {
		$last = null;
		foreach ( $days as $index => $day ) {
			if ( ! $day['countdown'] ) {
				continue;
			}
			if ( $day['start'] >= $now ) {
				return $index;
			}
			$last = $index;
		}

		return $last;
	}

	/**
	 * Foundation `*-up-N` column counts for the schedule cards of a competition with `$count` days.
	 *
	 * Phones get one column up to three days, then two; tablets get up to three, but two for exactly four days
	 * (a 3 + 1 split looks lopsided); desktops get one column per day up to five, then three.
	 *
	 * @param int $count Number of days.
	 *
	 * @return array{small:int,medium:int,large:int}
	 */
	public static function columns( int $count ): array {
		$count = max( 1, $count );

		return array(
			'small'  => $count <= 3 ? 1 : 2,
			'medium' => 4 === $count ? 2 : min( $count, 3 ),
			'large'  => $count <= 5 ? $count : 3,
		);
	}

	/**
	 * The day a competition's countdown targets.
	 *
	 * @param int                     $post_id Competition post ID.
	 * @param \DateTimeImmutable|null $now     Moment to compare against; defaults to self::now().
	 *
	 * @return array{label:string,start:\DateTimeImmutable,countdown:bool}|null
	 */
	public static function current( int $post_id, ?\DateTimeImmutable $now = null ): ?array {
		return self::pick( self::days( $post_id ), $now ?? self::now() );
	}

	/**
	 * The current time in the site timezone; the `hyperpress_competition_now` filter lets tests control the clock.
	 */
	public static function now(): \DateTimeImmutable {
		$now = apply_filters( 'hyperpress_competition_now', new \DateTimeImmutable( 'now', wp_timezone() ) );

		return $now instanceof \DateTimeImmutable ? $now : new \DateTimeImmutable( 'now', wp_timezone() );
	}
}
