<?php

namespace HYPER_Press_Program;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Program\hyperpress_program_customize_metabox' ) ) :
	add_filter( 'rwmb_meta_boxes', '\HYPER_Press_Program\hyperpress_program_customize_metabox' );

	function hyperpress_program_customize_metabox( $meta_boxes ) {
		$meta_boxes[] = [
			'title'    => __( 'Programs', 'hyperpress' ),
			'id'       => 'hyperpress_program',
			'panel'    => '',
			'priority' => 130,
		];

		return $meta_boxes;
	}
endif;