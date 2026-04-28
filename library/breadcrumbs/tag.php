<?php
if ( ! function_exists( 'hyperpress_breadcrumb_tag' ) ) :
	function hyperpress_breadcrumb_tag( $breadcrumbs ) {
		if ( is_tag() ) {
			global $post;
			// Get tag information
			$term_id  = get_query_var( 'tag_id' );
			$taxonomy = 'post_tag';
			$args     = 'include=' . $term_id;
			$terms    = get_terms( $taxonomy, $args );

			$breadcrumbs[] = [
				"title"   => __( 'Tag', 'hyperpress' ),
				"classes" => [ 'li' => [], 'bread' => [] ]
			];

			// add tag
			$breadcrumbs[] = [
				"title"   => $terms[0]->name,
				"url"     => get_tag_link( $terms[0] ),
				"classes" => [
					'li'    => [
						'item-tag',
						'item-tag-' . $terms[0]->term_id,
						'item-tag-' . $terms[0]->slug
					],
					'bread' => [
						'bread-tag',
						'bread-tag-' . $terms[0]->term_id,
						'bread-tag-' . $terms[0]->slug
					]
				]
			];
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_tag' );
endif;
