<?php

namespace HYPER_Press_Season;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Season\hyperpress_season_customize_metabox' ) ) :
	add_filter( 'rwmb_meta_boxes', '\HYPER_Press_Season\hyperpress_season_customize_metabox' );

	function hyperpress_season_customize_metabox( $meta_boxes ) {
		$meta_boxes[] = [
			'title'    => __( 'Seasons', 'hyperpress' ),
			'id'       => 'hyperpress_season',
			'panel'    => '',
			'priority' => 130,
		];

		return $meta_boxes;
	}
endif;