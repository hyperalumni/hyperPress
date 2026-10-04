<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'hyperpress_banner_content', 'hyperpress_banner_attachment' );
function hyperpress_banner_attachment( $banner ) {
	if ( is_attachment() ) {
		$banner['type']     = 'attachment';
		$banner['title']    = __( 'Attachment', 'hyperpress' );
		$banner['subtitle'] = __( 'Uploaded', 'hyperpress' );
	}

	return $banner;
}
