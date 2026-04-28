<?php
if ( ! function_exists( 'hyperpress_breadcrumb_page' ) ) :
	function hyperpress_breadcrumb_page( $breadcrumbs ) {
		if ( is_page() ) {
			// Get the query & post information
			global $post;
			if ( $post->post_parent ) {
				// If child page, get parents
				$anc = get_post_ancestors( $post->ID );

				// Get parents in the right order
				$anc = array_reverse( $anc );
			}

			foreach ( $anc as $ancestor ) {
				$breadcrumbs[] = [
					"title"   => get_the_title( $ancestor ),
					"url"     => get_permalink( $ancestor ),
					"classes" => [
						'li'    => [ 'item-page', 'item-page-' . $ancestor ],
						'bread' => [ 'bread-page', 'bread-page-' . $ancestor ]
					],
				];
			}


			// add current page
			$breadcrumbs[] = [
				"title"   => get_the_title(),
				"url" => get_permalink(),
				"classes" => [
					'li'    => [ 'item-page', 'item-page-' . $post->ID ],
					'bread' => [ 'bread-page', 'bread-page-' . $post->ID ]
				]
			];
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_page' );
endif;
