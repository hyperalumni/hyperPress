<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use DateTimeImmutable;
use WP_Query;

/**
 * Finds the events for a list and puts them in order: upcoming ones soonest first, then past ones newest first,
 * then events without a start.
 */
final class Listing {

	private const UPCOMING = 0;
	private const PAST     = 1;
	private const UNDATED  = 2;

	/**
	 * Published events in listing order.
	 *
	 * @param string                 $series Series slug; '' for every event. An unknown series matches nothing.
	 * @param string                 $show   `upcoming`, `past` or `all` (anything else means all).
	 * @param int                    $limit  Maximum number of IDs, applied after ordering; 0 or less means no limit.
	 * @param DateTimeImmutable|null $now    The moment upcoming is told from past; defaults to Dates::now().
	 *
	 * @return list<int> Event post IDs.
	 */
	public static function ids( string $series = '', string $show = 'all', int $limit = 0, ?DateTimeImmutable $now = null ): array {
		$args = array(
			'post_type'           => Plugin::POST_TYPE,
			'post_status'         => 'publish',
			'posts_per_page'      => -1,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
		);

		$series = trim( $series );
		if ( '' !== $series ) {
			$term = get_term_by( 'slug', sanitize_title( $series ), Plugin::SERIES );
			if ( ! $term instanceof \WP_Term ) {
				return array();
			}
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Bounded by the number of events.
				array(
					'taxonomy' => Plugin::SERIES,
					'field'    => 'term_id',
					'terms'    => array( $term->term_id ),
				),
			);
		}

		$now    ??= Dates::now();
		$groups = array(
			self::UPCOMING => array(),
			self::PAST     => array(),
			self::UNDATED  => array(),
		);
		foreach ( array_map( 'intval', ( new WP_Query( $args ) )->posts ) as $id ) {
			$start = Dates::start( $id );
			$end   = Dates::end( $id );
			if ( null === $start || null === $end ) {
				$groups[ self::UNDATED ][ $id ] = 0;
			} elseif ( $now > $end ) {
				$groups[ self::PAST ][ $id ] = $start->getTimestamp();
			} else {
				$groups[ self::UPCOMING ][ $id ] = $start->getTimestamp();
			}
		}

		$upcoming = self::order( $groups[ self::UPCOMING ], true );
		$past     = self::order( $groups[ self::PAST ], false );
		$undated  = self::order( $groups[ self::UNDATED ], true );

		switch ( $show ) {
			case 'upcoming':
				$ids = $upcoming;
				break;
			case 'past':
				$ids = $past;
				break;
			default:
				$ids = array_merge( $upcoming, $past, $undated );
		}

		return $limit > 0 ? array_slice( $ids, 0, $limit ) : $ids;
	}

	/**
	 * The list markup for these events, or '' for none.
	 *
	 * @param array<int,int> $ids Event post IDs.
	 */
	public static function html( array $ids ): string {
		$items = '';
		foreach ( $ids as $id ) {
			$items .= self::item( (int) $id );
		}

		return '' === $items ? '' : '<div class="event-list">' . $items . '</div>';
	}

	/**
	 * One event's markup.
	 *
	 * @param int $post_id Event post ID.
	 */
	private static function item( int $post_id ): string {
		$start = Dates::start( $post_id );
		$venue = get_post_meta( $post_id, Fields::VENUE_NAME, true );
		$thumb = has_post_thumbnail( $post_id ) ? get_the_post_thumbnail( $post_id, 'medium' ) : '';
		$out   = '<article class="event-list-item">';
		if ( '' !== $thumb ) {
			$out .= '<a class="event-list-thumbnail" href="' . esc_url( get_permalink( $post_id ) ) . '" tabindex="-1" aria-hidden="true">' . $thumb . '</a>';
		}
		$out .= '<h2 class="entry-title"><a href="' . esc_url( get_permalink( $post_id ) ) . '" rel="bookmark">' . get_the_title( $post_id ) . '</a></h2>';
		if ( null !== $start ) {
			$out .= '<p class="event-list-date"><time datetime="' . esc_attr( $start->format( 'c' ) ) . '">'
				. esc_html( wp_date( (string) get_option( 'date_format' ), $start->getTimestamp(), wp_timezone() ) )
				. '</time></p>';
		}
		if ( is_string( $venue ) && '' !== trim( $venue ) ) {
			$out .= '<p class="event-list-venue">' . esc_html( trim( $venue ) ) . '</p>';
		}

		return $out . '</article>';
	}

	/**
	 * Sorts IDs by timestamp, ties by title then ID.
	 *
	 * @param array<int,int> $stamps Timestamp by event ID.
	 * @param bool           $asc    Ascending when true.
	 *
	 * @return list<int>
	 */
	private static function order( array $stamps, bool $asc ): array {
		$ids    = array_keys( $stamps );
		$titles = array();
		foreach ( $ids as $id ) {
			$titles[ $id ] = strtolower( get_the_title( $id ) );
		}
		usort(
			$ids,
			static function ( int $a, int $b ) use ( $stamps, $titles, $asc ): int {
				$by_time = $asc ? $stamps[ $a ] <=> $stamps[ $b ] : $stamps[ $b ] <=> $stamps[ $a ];

				return 0 !== $by_time ? $by_time : array( $titles[ $a ], $a ) <=> array( $titles[ $b ], $b );
			}
		);

		return $ids;
	}
}
