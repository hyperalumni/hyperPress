<?php

namespace HYPER_Press_Countdown;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Countdown\hyperpress_register_countdown_visibility_meta_boxes' ) ) :
	add_filter( 'rwmb_meta_boxes', '\HYPER_Press_Countdown\hyperpress_register_countdown_visibility_meta_boxes' );

	function hyperpress_register_countdown_visibility_meta_boxes( $meta_boxes ) {
		$meta_boxes[] = [
			'id'             => 'countdown-visibility',
			'title'          => __( 'Visibility', 'hyperpress' ),
			'post_types'     => [ 'countdown' ],
			'context'        => 'side',
			'priority'       => 'low',
			'closed'         => false,
			'autosave'       => false,
			'default_hidden' => false,
			'fields'         => [
				[
					'name'              => __( 'Hide Before', 'hyperpress' ),
					'desc'              => __( 'Do not display the Count Down until', 'hyperpress' ),
					'label_description' => __( '', 'hyperpress' ),
					'id'                => 'hide_before',
					'type'              => 'datetime',
					'required'          => false,
					'hide_from_front'   => true,
					'inline'            => false,
					'format'            => 'yy-mm-dd',
					'timestamp'         => false,
				],
				[
					'name'              => __( 'Hide After', 'hyperpress' ),
					'desc'              => __( 'Do not display the Count Down after', 'hyperpress' ),
					'label_description' => __( '', 'hyperpress' ),
					'id'                => 'hide_after',
					'type'              => 'datetime',
					'required'          => false,
					'hide_from_front'   => true,
					'inline'            => false,
					'format'            => 'yy-mm-dd',
					'timestamp'         => false,
				]
			]
		];

		return $meta_boxes;
	}
endif;