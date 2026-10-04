<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_category' );

function hyperpress_breadcrumbs_category( $breadcrumbs ) {
	if ( is_category() ) {
		// Get the query & post information
		$category = get_category( get_query_var( 'cat' ) );

		$breadcrumbs[] = array(
			'title'   => __( 'Category', 'hyperpress' ),
			'classes' => array(
				'li'    => array(),
				'bread' => array(),
			),
		);

		// add category
		$breadcrumbs[] = array(
			'title'   => $category->cat_name,
			'url'     => get_category_link( $category ),
			'classes' => array(
				'li'    => array(
					'item-category',
					'item-category-' . $category->term_id,
					'item-category-' . $category->category_nicename,
				),
				'bread' => array(
					'bread-category',
					'bread-category-' . $category->term_id,
					'bread-category-' . $category->category_nicename,
				),
			),
		);
	}

	return $breadcrumbs;
}
