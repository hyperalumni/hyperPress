<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'rwmb_meta_boxes', 'hyperpress_banner_metabox' );

function hyperpress_banner_metabox( $meta_boxes ) {
	$prefix = 'hyperpress_banner_';

	$meta_boxes[] = array(
		'title'      => __( 'Banner', 'hyperpress' ),
		'id'         => 'banner',
		'post_types' => array( 'post', 'page' ),
		'fields'     => array(
			array(
				'type'           => 'color',
				'id'             => $prefix . 'background_color',
				'name'           => __( 'Background Color', 'hyperpress' ),
				'desc'           => __( 'Defaults to Parent background', 'hyperpress' ),
				'hide_from_rest' => true,
			),
			array(
				'type'       => 'wysiwyg',
				'id'         => $prefix . 'subtitle',
				'name'       => __( 'Banner Subtitle', 'hyperpress' ),
				'desc'       => __( 'Defaults to Homepage subtitle', 'hyperpress' ),
				'raw'        => true,
				'options'    => array(
					'media_buttons'    => false,
					'default_editor'   => 'html',
					'drag_drop_upload' => false,
					'textarea_rows'    => 1,
				),
				'limit'      => 100,
				'limit_type' => 'word',
			),
		),
	);

	return $meta_boxes;
}
