<?php
if ( ! function_exists( 'hyperpress_banner_post' ) ) :
	function hyperpress_banner_post( $banner ) {
		if ( is_singular( 'post' ) ) {
			$banner['type'] = 'post';

			$blogPageID = get_option( 'page_for_posts' );
			if ( $blogPageID > 0 ) {
				// if the blog page is not the front page, use its values
				$banner['title']           = get_the_title( $blogPageID );
				$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color', '', $blogPageID );

				$blogPostSubtitle = rwmb_meta( 'hyperpress_banner_subtitle' );
				if ( ! empty( $blogPostSubtitle ) ) {
					// if the post has a subtitle set, use it
					$banner['subtitle'] = $blogPostSubtitle;
				} else {
					// if the blog page has a subtitle set, use it
					$blogPageSubtitle = rwmb_meta( 'hyperpress_banner_subtitle', '', $blogPageID );
					if ( ! empty( $blogPageSubtitle ) ) {
						$banner['subtitle'] = $blogPageSubtitle;
					}
				}
			}
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_post' );
endif;
