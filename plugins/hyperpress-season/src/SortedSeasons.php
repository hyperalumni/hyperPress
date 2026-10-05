<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

final class SortedSeasons {

	/**
	 * Retrieves a list of sorted seasons for the current post.
	 *
	 * @return array An array of seasons sorted by name.
	 */
	public static function get(): array {
		$seasons = wp_get_post_terms(
			get_the_ID(),
			'hyper_season',
			array(
				'orderby' => 'name',
				'order'   => 'DESC',
			)
		);

		if ( is_wp_error( $seasons ) ) {
			return array();
		}

		if ( ! empty( $seasons ) ) {
			// still sort them by name, because the result of get_terms is inconsistent even with the orderby flag.
			usort( $seasons, fn( $a, $b ) => strnatcasecmp( $b->name, $a->name ) );
		}

		return $seasons;
	}
}
