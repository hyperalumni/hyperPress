<?php
if ( ! function_exists( 'hyperpress_breadcrumb_author' ) ) :
	function hyperpress_breadcrumb_author( $breadcrumbs ) {
		if ( is_author() ) {

			$breadcrumbs[] = [
				"title"   => __( 'Author', 'hyperpress' ),
				"classes" => [ 'li' => [ 'item-author' ], 'bread' => [ 'bread-author' ] ]
			];

			// Get the author information
			global $author;
			$userdata = get_userdata( $author );

			$breadcrumbs[] = [
				"title"   => $userdata->display_name,
				"url"     => get_author_posts_url( $author->ID ),
				"classes" => [
					'li'    => [
						'item-author',
						'item-author-' . $userdata->user_nicename
					],
					'bread' => [
						'bread-author',
						'bread-author-' . $userdata->user_nicename
					]
				]
			];
		}

		return $breadcrumbs;
	}

	add_filter( 'hyperpress_breadcrumbs_content', 'hyperpress_breadcrumb_author' );
endif;
