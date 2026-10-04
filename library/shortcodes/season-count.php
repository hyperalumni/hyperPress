<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyper_season_count_shortcode' ) ) :
	add_shortcode( 'hyper_season_count', 'hyper_season_count_shortcode' );

	function hyper_season_count_shortcode( $atts ): string {
		$args = shortcode_atts( array(
			'suffix' => false,
			'season' => true
		), $atts );

		$season = filter_var( $args['season'], FILTER_VALIDATE_BOOLEAN );
		$suffix = filter_var( $args['suffix'], FILTER_VALIDATE_BOOLEAN );

		return do_shortcode( '[hyper_date_distance date="1995-09-01" suffix="' . $suffix . '" inclusive="true" /] ' . ( $season ? 'Season' : '' ) );
	}
endif;
