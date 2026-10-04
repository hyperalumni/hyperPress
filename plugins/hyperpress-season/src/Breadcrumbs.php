<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

final class Breadcrumbs {

	public static function register(): void {
		add_filter( 'hyperpress_breadcrumbs_content', array( self::class, 'filter' ) );
	}

	public static function filter( $breadcrumbs ) {
		if ( is_tax( 'hyper_season' ) && ! is_post_type_archive() ) {
			$breadcrumbs[] = array(
				'title'   => 'Seasons',
				'classes' => array(
					'li'    => array(),
					'bread' => array(),
				),
			);
			$breadcrumbs[] = array(
				'title'   => get_queried_object()->name,
				'classes' => array(
					'li'    => array(),
					'bread' => array(),
				),
			);
		}

		return $breadcrumbs;
	}
}
