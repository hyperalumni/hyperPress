<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

final class Breadcrumbs {

	public static function register(): void {
		add_filter( 'hyperpress_breadcrumbs_content', array( self::class, 'filter' ) );
	}

	public static function filter( $breadcrumbs ) {
		$term = get_queried_object();
		if ( is_tax( 'hyper_season' ) && ! is_post_type_archive() && $term instanceof \WP_Term ) {
			$breadcrumbs   = (array) $breadcrumbs;
			$breadcrumbs[] = array(
				'title'   => __( 'Seasons', 'hyperpress' ),
				'classes' => array(
					'li'    => array(),
					'bread' => array(),
				),
			);
			$breadcrumbs[] = array(
				'title'   => $term->name,
				'classes' => array(
					'li'    => array(),
					'bread' => array(),
				),
			);
		}

		return $breadcrumbs;
	}
}
