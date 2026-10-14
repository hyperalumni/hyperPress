<?php
namespace HyperPress\Season;

use HyperPress\Utils\RequestTerm;

defined( 'ABSPATH' ) || exit;

final class Breadcrumbs {

	public static function register(): void {
		add_filter( 'hyperpress_breadcrumbs_content', array( self::class, 'filter' ) );
	}

	public static function filter( $breadcrumbs ) {
		// is_tax( $taxonomy ) only matches the one taxonomy WP picks as the queried object, so ask the season itself.
		$term = is_tax() ? RequestTerm::get( 'hyper_season' ) : null;
		if ( $term && ! is_post_type_archive() ) {
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
