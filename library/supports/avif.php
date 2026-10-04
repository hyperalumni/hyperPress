<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'upload_mimes', function ( $mimes ) {
	$mimes['avif'] = 'image/avif';

	return $mimes;
} );

add_filter( 'wp_check_filetype_and_ext', function ( $results, $mime, $type ) {
	if ( $type == 'image' && $mime == 'image/avif' ) {
		$results['ext']             = 'avif';
		$results['type']            = 'image/avif';
		$results['proper_filename'] = true;
	}

	return $results;
}, 10, 3 );
