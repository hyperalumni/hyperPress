<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

add_filter( 'rwmb_meta_boxes', 'hyperpress_gallery_metabox' );

function hyperpress_gallery_metabox( $meta_boxes ) {
	$meta_boxes[] = array(
		'title'    => __( 'Gallery', 'hyperpress' ),
		'id'       => 'hyperpress_gallery',
		'panel'    => '',
		'priority' => 140,
		'fields'   => array(),
	);

	return $meta_boxes;
}
