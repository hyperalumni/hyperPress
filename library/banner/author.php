<?php
if ( ! function_exists( 'hyperpress_banner_author' ) ) :
	function hyperpress_banner_author( $banner ) {
		if ( is_author() ) {
			global $author;
			$userdata           = get_userdata( $author );
			$banner['type']     = 'author';
			$banner['title']    = __( 'Published By ', 'hyperpress' ) . ( ! empty( $userdata->first_name ) ? $userdata->first_name . ' ' . $userdata->last_name : $userdata->display_name );
			$banner['subtitle'] = __( 'Author', 'hyperpress' ) . ': <span class="emphasis">' . $userdata->user_nicename . '</span>';
		}

		return $banner;
	}

	add_filter( 'hyperpress_banner_content', 'hyperpress_banner_author' );
endif;
