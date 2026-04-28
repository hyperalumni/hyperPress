<?php
if ( ! function_exists( 'hyperpress_banner_archive' ) ) :
	function hyperpress_banner_archive( $banner ) {
		if ( empty( $banner['type'] ) ) {
			if ( is_archive() ) {
				$banner['type']  = 'archive';
				$banner['title'] = __( 'Archive', 'hyperpress' );

				$blogPageID = get_option( 'page_for_posts' );
				if ( $blogPageID > 0 ) {
					// if the blog page is not the front page, use its values
					$banner['backgroundColor'] = rwmb_meta( 'hyperpress_banner_background_color', '', $blogPageID );
				}
			}
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_archive' );
endif;
