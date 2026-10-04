<?php

namespace HYPER_Press_Countdown;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Countdown\hyperpress_register_countdown_event_information_meta_box' ) ) :
	add_filter( 'rwmb_meta_boxes', '\HYPER_Press_Countdown\hyperpress_register_countdown_event_information_meta_box' );

	function hyperpress_register_countdown_event_information_meta_box( $meta_boxes ) {
		$meta_boxes[] = [
			'id'             => 'countdown-event-information',
			'title'          => __( 'Event Information', 'hyperpress' ),
			'post_types'     => [ 'countdown' ],
			'context'        => 'normal',
			'priority'       => 'high',
			'closed'         => false,
			'autosave'       => false,
			'default_hidden' => false,
			'fields'         => [
				[
					'name'            => __( 'Event Name', 'hyperpress' ),
					'id'              => 'event_name',
					'type'            => 'text',
					'required'        => true,
					'hide_from_front' => true,
					'limit'           => 10,
					'limit_type'      => 'word',
				],
				[
					'name'            => __( 'Event Location', 'hyperpress' ),
					'id'              => 'event_location',
					'type'            => 'text',
					'required'        => false,
					'hide_from_front' => true,
					'limit'           => 10,
					'limit_type'      => 'word',
				],
				[
					'name'            => __( 'Event Date & Time', 'hyperpress' ),
					'id'              => 'event_date_time',
					'type'            => 'datetime',
					'required'        => true,
					'hide_from_front' => true,
					'inline'          => false,
					'format'          => 'yy-mm-dd',
					'timestamp'       => false,
				],
				[
					'name'            => __( 'Count Past Zero', 'hyperpress' ),
					'id'              => 'countdown_past_zero',
					'type'            => 'checkbox',
					'required'        => false,
					'hide_from_front' => true,
					'std'             => 0,
				]
			]
		];

		return $meta_boxes;
	}
endif;