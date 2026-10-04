<?php

namespace HYPER_Press_Sponsor;

use function HYPER_Press_Season\get_sorted_seasons;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
if ( ! function_exists( '\HYPER_Press_Sponsor\hyperpress_labels_sponsor' ) ) :
	function hyperpress_labels_sponsor( $labels ) {
		$hyper_sponsor = 'sponsor';
		if ( is_singular( $hyper_sponsor ) || is_post_type_archive( $hyper_sponsor ) || ( is_main_query() && get_post_type() === $hyper_sponsor ) ) {
			// Get season information
			$seasons = get_sorted_seasons();
			if ( ! empty( $seasons ) ) {
				$season = $seasons[0];
				$count  = count( $seasons );
			}

			if ( ! empty( $season ) ) {
				$labels[] = [
					'label'   => $season->name,
					'title'   => 'Last Sponsored',
					'classes' => [ 'warning' ],
					'url'     => get_category_link( $season->term_id ),
					'rel'     => ''
				];
			}

			$level = rwmb_meta( 'tier' );
			if ( ! empty( $level ) ) {
				$labels[] = [
					'label'   => $level->name,
					'title'   => 'Sponsorship Tier' . ( $level->description ? ': ' . $level->description : '' ),
					'classes' => [ 'info' ],
					'url'     => '',
					'rel'     => ''
				];
			}

			if ( ! empty( $count ) ) {
				$labels[] = [
					'label'   => $count . ' Season' . ( $count > 1 ? 's' : '' ),
					'title'   => 'Sponsored For',
					'classes' => [ 'primary' ],
					'url'     => '',
					'rel'     => ''
				];
			}


		}

		return $labels;
	}

	add_filter( 'hyperpress_labels_content', '\HYPER_Press_Sponsor\hyperpress_labels_sponsor' );
endif;