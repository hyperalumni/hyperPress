<?php
if ( ! function_exists( 'hyperpress_banner_blog' ) ) :
	function hyperpress_banner_blog( $banner ) {
		if ( is_home() && ! is_front_page() ) {
			$blogPageID = get_option( 'page_for_posts' );

			$banner['type'] = 'blog';

			if ( $blogPageID > 0 ) {
				// if the blog page is not the front page, use its values
				$banner['title']           = get_the_title( $blogPageID );
				$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color', '', $blogPageID );

				$pageSubtitle = rwmb_meta( 'hyperpress_banner_subtitle', '', $blogPageID );
				if ( ! empty( $pageSubtitle ) ) {
					$banner['subtitle'] = $pageSubtitle;
				}
			}
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_blog' );
endif;
