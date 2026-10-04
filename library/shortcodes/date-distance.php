<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyper_date_distance_shortcode' ) ) :
	add_shortcode( 'hyper_date_distance', 'hyper_date_distance_shortcode' );

	function hyper_date_distance_shortcode( $atts ): string {
		$args = shortcode_atts( array(
			'date'      => '',
			'format'    => '%y',
			'inclusive' => false,
			'suffix'    => false,
			'locale'    => 'en-US',
		), $atts );

		$date      = $args['date'];
		$format    = $args['format'];
		$inclusive = filter_var( $args['inclusive'], FILTER_VALIDATE_BOOLEAN );
		$suffix    = filter_var( $args['suffix'], FILTER_VALIDATE_BOOLEAN );
		$locale    = $args['locale'];

		$diff = date_create()->diff( date_create( $date ) )->format( $format );

		if ( $inclusive ) {
			$diff ++;
		}

		if ( $suffix ) {
			$nf   = new NumberFormatter( $locale, NumberFormatter::ORDINAL );
			$diff = $nf->format( $diff );
		}

		return $diff;
	}
endif;
