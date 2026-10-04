<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumbs_tag' );

function hyperpress_breadcrumbs_tag( $breadcrumbs ) {
	if ( is_tag() ) {
		// Get tag information
		$term_id  = get_query_var( 'tag_id' );
		$taxonomy = 'post_tag';
		$args     = 'include=' . $term_id;
		$terms    = get_terms( $taxonomy, $args ); // phpcs:ignore WordPress.WP.DeprecatedParameters.Get_termsParam2Found -- theme audit

		$breadcrumbs[] = array(
			'title'   => __( 'Tag', 'hyperpress' ),
			'classes' => array(
				'li'    => array(),
				'bread' => array(),
			),
		);

		// add tag
		$breadcrumbs[] = array(
			'title'   => $terms[0]->name,
			'url'     => get_tag_link( $terms[0] ),
			'classes' => array(
				'li'    => array(
					'item-tag',
					'item-tag-' . $terms[0]->term_id,
					'item-tag-' . $terms[0]->slug,
				),
				'bread' => array(
					'bread-tag',
					'bread-tag-' . $terms[0]->term_id,
					'bread-tag-' . $terms[0]->slug,
				),
			),
		);
	}

	return $breadcrumbs;
}
