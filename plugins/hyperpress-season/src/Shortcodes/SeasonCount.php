<?php
namespace HyperPress\Season\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class SeasonCount {

	public static function register(): void {
		add_action( 'init', array( self::class, 'add_shortcode' ) );
	}

	public static function add_shortcode(): void {
		add_shortcode( 'hyperpress_season_count', array( self::class, 'render' ) );
	}

	public static function render( $atts ): string {
		if ( ! shortcode_exists( 'hyperpress_date_distance' ) ) {
			return '';
		}

		$args = shortcode_atts(
			array(
				'suffix' => false,
				'season' => true,
			),
			$atts
		);

		$season = filter_var( $args['season'], FILTER_VALIDATE_BOOLEAN );
		$suffix = filter_var( $args['suffix'], FILTER_VALIDATE_BOOLEAN );

		return do_shortcode( '[hyperpress_date_distance date="1995-09-01" suffix="' . ( $suffix ? 'true' : 'false' ) . '" inclusive="true" /] ' . ( $season ? __( 'Season', 'hyperpress' ) : '' ) );
	}
}
