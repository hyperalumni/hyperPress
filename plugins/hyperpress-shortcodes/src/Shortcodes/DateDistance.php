<?php
namespace HyperPress\Shortcodes\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class DateDistance {

	public static function register(): void {
		add_shortcode( 'hyper_date_distance', array( self::class, 'render' ) );
	}

	public static function render( $atts = array(), $content = null ): string {
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
			$nf   = new \NumberFormatter( $locale, \NumberFormatter::ORDINAL );
			$diff = $nf->format( $diff );
		}

		return $diff;
	}
}
