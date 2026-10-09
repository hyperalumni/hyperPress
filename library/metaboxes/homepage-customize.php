<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'rwmb_meta_boxes', 'hyperpress_homepage_customize_metabox' );

function hyperpress_homepage_customize_metabox( $meta_boxes ) {
	$prefix = 'hyperpress_homepage_customize_';

	$meta_boxes[] = array(
		'title'    => __( 'Front Page', 'hyperpress' ),
		'id'       => 'hyperpress_front_page',
		'panel'    => '',
		'priority' => 131,
		'fields'   => array(
			array(
				'type'           => 'color',
				'id'             => $prefix . 'banner_background_color',
				'name'           => __( 'Background Color', 'hyperpress' ),
				'hide_from_rest' => true,
			),
			array(
				'type'           => 'text',
				'id'             => $prefix . 'banner_title',
				'name'           => __( 'Banner Title', 'hyperpress' ),
				'limit'          => 4,
				'limit_type'     => 'word',
				'hide_from_rest' => true,
			),
			array(
				'type'           => 'textarea',
				'id'             => $prefix . 'banner_subtitle',
				'std'            => __( 'Dedicated to improving &amp; expanding the abilities of <span class="emphasis">Team HYPER</span>', 'hyperpress' ),
				'name'           => __( 'Banner Subtitle', 'hyperpress' ),
				'raw'            => true,
				'options'        => array(),
				'limit'          => 100,
				'limit_type'     => 'word',
				'hide_from_rest' => true,
			),
			array(
				'type'           => 'text',
				'id'             => $prefix . 'banner_button_text',
				'name'           => __( 'Banner Button Text', 'hyperpress' ),
				'limit'          => 4,
				'limit_type'     => 'word',
				'hide_from_rest' => true,
			),
			array(
				'type'           => 'post',
				'id'             => $prefix . 'banner_button_page',
				'post_type'      => 'page',
				'field_type'     => 'select_advanced',
				'placeholder'    => 'Select a page',
				'name'           => __( 'Banner Button Page', 'hyperpress' ),
				'hide_from_rest' => true,
				'query_args'     => array(
					'post_status'    => 'publish',
					'posts_per_page' => - 1,
				),
			),
			array(
				'type'           => 'post',
				'id'             => $prefix . 'countdowns',
				'name'           => __( 'Countdowns', 'hyperpress' ),
				'post_type'      => array( 'hyper_countdown' ),
				'multiple'       => true,
				'parent'         => false,
				'field_type'     => 'select_advanced',
				'placeholder'    => __( 'Select a countdown', 'hyperpress' ),
				'hide_from_rest' => true,
			),

		),
	);

	return $meta_boxes;
}
