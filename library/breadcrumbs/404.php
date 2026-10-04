<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyperpress_breadcrumbs_404' ) ) :
	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_404' );

	function hyperpress_breadcrumbs_404( $breadcrumbs ) {
		if ( is_404() ) {
			$breadcrumbs[] = [
				"title"   => __( 'Error 404', 'hyperpress' ),
				"classes" => [
					'li'    => [
						'item-error',
						'item-error-404'
					],
					'bread' => [
						'bread-error',
						'bread-error-404'
					]
				]
			];
		}

		return $breadcrumbs;
	}
endif;
