<?php
if ( ! function_exists( 'hyperpress_banner_category' ) ) :
	function hyperpress_banner_attachment( $banner ) {
		if ( is_attachment() ) {
			$banner['type']     = 'attachment';
			$banner['title']    = __( 'Attachment', 'hyperpress' );
			$banner['subtitle'] = __( 'Uploaded', 'hyperpress' );
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_category' );
endif;
