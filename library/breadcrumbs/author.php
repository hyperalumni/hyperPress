<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyperpress_breadcrumbs_author' ) ) :
	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_author' );

	function hyperpress_breadcrumbs_author( $breadcrumbs ) {
		if ( is_author() ) {
			$breadcrumbs[] = [
				"title"   => __( 'Author', 'hyperpress' ),
				"classes" => [ 'li' => [ 'item-author' ], 'bread' => [ 'bread-author' ] ]
			];

			$breadcrumbs[] = [
				"title"   => get_the_author_meta('display_name'),
				"url"     => get_the_author_meta('user_url'),
				"classes" => [
					'li'    => [
						'item-author',
						'item-author-' . get_the_author_meta('user_nicename')
					],
					'bread' => [
						'bread-author',
						'bread-author-' . get_the_author_meta('user_nicename')
					]
				]
			];
		}

		return $breadcrumbs;
	}
endif;
