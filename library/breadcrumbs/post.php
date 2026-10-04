<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_post' );

function hyperpress_breadcrumbs_post( $breadcrumbs ) {
	if ( is_singular( 'post' ) ) {
		// Get the season information
		$seasons = \HyperPress\Season\SortedSeasons::get();
		if ( ! empty( $seasons ) ) {
			$season = $seasons[0];
			if ( ! empty( $season ) ) {
				$breadcrumbs[] = array(
					'title'   => $season->name,
					'url'     => get_category_link( $season->term_id ),
					'classes' => array(
						'li'    => array(
							'item-' . $season->slug,
						),
						'bread' => array(
							'bread-' . $season->slug,
						),
					),
				);
			}
		}

		// Get category information
		$categories = get_the_category();
		if ( ! empty( $categories ) ) {
			$category      = $categories[0];
			$breadcrumbs[] = array(
				'title'   => $category->cat_name,
				'url'     => get_category_link( $category->term_id ),
				'classes' => array(
					'li'    => array(
						'item-cat',
						'item-cat-' . $category->term_id,
						'item-cat-' . $category->category_nicename,
					),
					'bread' => array(
						'bread-cat',
						'bread-cat-' . $category->term_id,
						'bread-cat-' . $category->category_nicename,
					),
				),
			);
		}

		// add current post
		$breadcrumbs[] = array(
			'title'   => get_the_title(),
			'url'     => get_permalink(),
			'classes' => array(
				'li'    => array( 'item-post', 'item-post-' . get_the_ID() ),
				'bread' => array( 'bread-post', 'bread-post-' . get_the_ID() ),
			),
		);
	}

	return $breadcrumbs;
}
