<?php
if ( ! function_exists( 'hyperpress_banner_category' ) ) :
	function hyperpress_banner_category( $banner ) {
		if ( is_category() ) {
			$banner['type']     = 'category';
			$banner['subtitle'] = __( 'Category', 'hyperpress' ) . ': <span class="emphasis">' . single_cat_title( '', false ) . '</span>';
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_category' );
endif;
