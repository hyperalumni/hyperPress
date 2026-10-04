<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'hyperpress_labels_content', 'hyperpress_labels_post' );

function hyperpress_labels_post( $labels ) {
	if ( is_singular( 'post' ) || ( is_main_query() && get_post_type() === 'post' ) ) {
		$labels[] = array(
			/* translators: 1: post date, 2: post time */
			'label'   => sprintf( __( '%1$s @ %2$s', 'hyperpress' ), get_the_date(), get_the_time() ),
			'title'   => 'Posted At',
			'classes' => array( 'dark' ),
			'url'     => '',
			'rel'     => '',
		);
		// add author information
		$labels[] = array(
			'label'   => get_the_author(),
			'title'   => 'Posted By',
			'classes' => array( 'primary', 'byline', 'author' ),
			'url'     => get_author_posts_url( get_the_author_meta( 'ID' ) ),
			'rel'     => 'author',
		);
		// Get the season information
		$seasons = \HyperPress\Season\SortedSeasons::get();
		if ( ! empty( $seasons ) ) {
			$season = $seasons[0];
			if ( ! empty( $season ) ) {
				$labels[] = array(
					'label'   => $season->name,
					'title'   => 'Posted During',
					'classes' => array( 'warning' ),
					'url'     => get_category_link( $season->term_id ),
					'rel'     => '',
				);
			}
		}

		// Get category information
		$categories = get_the_category();
		$category   = $categories[0];
		if ( ! empty( $category ) ) {
			$labels[] = array(
				'label'   => $category->cat_name,
				'title'   => 'Posted In',
				'classes' => array( 'info' ),
				'url'     => get_category_link( $category->term_id ),
				'rel'     => '',
			);
		}
		$labels[] = array(
			'label'   => get_comments_number() . ( get_comments_number() == 1 ? ' comment' : ' comments' ), // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- theme audit
			'title'   => '',
			'classes' => array( 'neutral' ),
			'url'     => get_comments_link(),
			'rel'     => '',
		);
	}

	return $labels;
}
