<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyperpress_breadcrumbs_category' ) ) :
	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_category' );

	function hyperpress_breadcrumbs_category( $breadcrumbs ) {
		if ( is_category() ) {
			// Get the query & post information
			$category = get_category( get_query_var( 'cat' ) );

			$breadcrumbs[] = [
				"title"   => __( 'Category', 'hyperpress' ),
				"classes" => [ 'li' => [], 'bread' => [] ]
			];

			// add category
			$breadcrumbs[] = [
				"title"   => $category->cat_name,
				"url"     => get_category_link( $category ),
				"classes" => [
					'li'    => [
						'item-category',
						'item-category-' . $category->term_id,
						'item-category-' . $category->category_nicename
					],
					'bread' => [
						'bread-category',
						'bread-category-' . $category->term_id,
						'bread-category-' . $category->category_nicename
					]
				]
			];
		}

		return $breadcrumbs;
	}
endif;
