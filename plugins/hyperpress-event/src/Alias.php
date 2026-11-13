<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use DateTimeImmutable;
use WP_Term;

/**
 * The evergreen alias: /event/<series-slug>/ redirects to the series' current event.
 */
final class Alias {

	public static function register(): void {
		// Priority 5: before redirect_canonical (10) makes core's arbitrary slug-prefix guess for the 404.
		add_action( 'template_redirect', array( self::class, 'maybe_redirect' ), 5 );
	}

	/**
	 * Redirects a 404 for an event slug that names a series to that series' current event.
	 */
	public static function maybe_redirect(): void {
		if ( is_admin() || ! is_404() || Plugin::POST_TYPE !== get_query_var( 'post_type' ) ) {
			return;
		}
		$name = get_query_var( 'name' );
		if ( ! is_string( $name ) || '' === $name ) {
			return;
		}
		$term = get_term_by( 'slug', sanitize_title( $name ), Plugin::SERIES );
		if ( ! $term instanceof WP_Term ) {
			return;
		}
		$id = self::target( $term );
		if ( null === $id ) {
			return;
		}
		$url = get_permalink( $id );
		if ( is_string( $url ) && wp_safe_redirect( $url, 302 ) ) {
			exit;
		}
	}

	/**
	 * The event the alias points to: the published event with the nearest upcoming end, else the one starting last.
	 * Ties go to the lowest ID. Null when the series has no dated, published event.
	 *
	 * @param WP_Term               $series The series term.
	 * @param DateTimeImmutable|null $now    The clock; defaults to the site's current time.
	 */
	public static function target( WP_Term $series, ?DateTimeImmutable $now = null ): ?int {
		$now ??= Dates::now();
		$ids   = get_posts(
			array(
				'post_type'        => Plugin::POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				'tax_query'        => array(
					array(
						'taxonomy' => Plugin::SERIES,
						'field'    => 'term_id',
						'terms'    => array( $series->term_id ),
					),
				),
			)
		);

		$upcoming = null;
		$upcoming_end = null;
		$past     = null;
		$past_start = null;
		foreach ( $ids as $id ) {
			$id    = (int) $id;
			$start = Dates::start( $id );
			$end   = Dates::end( $id );
			if ( null === $start || null === $end ) {
				continue;
			}
			// IDs ascend, so a strict comparison keeps the lowest ID on a tie.
			if ( $now <= $end ) {
				if ( null === $upcoming_end || $end < $upcoming_end ) {
					$upcoming     = $id;
					$upcoming_end = $end;
				}
			} elseif ( null === $past_start || $start > $past_start ) {
				$past       = $id;
				$past_start = $start;
			}
		}

		return $upcoming ?? $past;
	}
}
