<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( 'hyperpress_gallery_metabox' ) ) :
	add_filter( 'rwmb_meta_boxes', 'hyperpress_gallery_metabox' );

	function hyperpress_gallery_metabox( $meta_boxes ) {
		$meta_boxes[] = [
			'title'  => __( 'Gallery', 'hyperpress' ),
			'id'     => 'hyperpress_gallery',
			'panel'  => '',
			'priority' => 140,
			'fields' => []
		];;

		return $meta_boxes;
	}
endif;
