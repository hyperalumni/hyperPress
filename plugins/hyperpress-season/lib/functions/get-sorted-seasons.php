<?php

namespace HYPER_Press_Season;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Season\get_sorted_seasons' ) ) :
	/**
	 * Retrieves a list of sorted seasons for the current post.
	 *
	 * @return array An array of seasons sorted by name.
	 */
	function get_sorted_seasons(): array {
		$seasons = wp_get_post_terms(
			get_the_ID(),
			'season',
			[
				'orderby' => 'name',
				'order'   => 'DESC'
			]
		);

		if ( ! empty( $seasons ) ) {
			// still sort them by name, because the result of get_terms is inconsistent even with the orderby flag
			usort( $seasons, fn( $a, $b ) => $b->name <=> $a->name );
		}

		return $seasons;
	}
endif;
