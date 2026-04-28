<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'hyperpress_register_countdown_meta_boxes' ) ) :
	function hyperpress_register_countdown_meta_boxes( $meta_boxes ) {
		$meta_boxes[] = [
			'id'             => 'countdown-event-information',
			'title'          => __( 'Event Information', 'hyper' ),
			'post_types'     => [ 'countdown' ],
			'context'        => 'normal',
			'priority'       => 'high',
			'closed'         => false,
			'autosave'       => false,
			'default_hidden' => false,
			'fields'         => [
				[
					'name'            => __( 'Event Name', 'hyper' ),
					'id'              => 'event_name',
					'type'            => 'text',
					'required'        => true,
					'hide_from_front' => true,
					'limit'           => 10,
					'limit_type'      => 'word',
				],
				[
					'name'            => __( 'Event Location', 'hyper' ),
					'id'              => 'event_location',
					'type'            => 'text',
					'required'        => false,
					'hide_from_front' => true,
					'limit'           => 10,
					'limit_type'      => 'word',
				],
				[
					'name'            => __( 'Event Date & Time', 'hyper' ),
					'id'              => 'event_date_time',
					'type'            => 'datetime',
					'required'        => true,
					'hide_from_front' => true,
					'inline'          => false,
					'format'          => 'yy-mm-dd',
					'timestamp'       => false,
				],
				[
					'name'            => __( 'Count Past Zero', 'hyper' ),
					'id'              => 'countdown_past_zero',
					'type'            => 'checkbox',
					'required'        => false,
					'hide_from_front' => true,
					'std'             => 0,
				]
			]
		];

		$meta_boxes[] = [
			'id'             => 'countdown-visibility',
			'title'          => __( 'Visibility', 'hyper' ),
			'post_types'     => [ 'countdown' ],
			'context'        => 'side',
			'priority'       => 'low',
			'closed'         => false,
			'autosave'       => false,
			'default_hidden' => false,
			'fields'         => [
				[
					'name'              => __( 'Hide Before', 'hyper' ),
					'desc'              => __( 'Do not display the Count Down until', 'hyper' ),
					'label_description' => __( '', 'hyper' ),
					'id'                => 'hide_before',
					'type'              => 'datetime',
					'required'          => false,
					'hide_from_front'   => true,
					'inline'            => false,
					'format'            => 'yy-mm-dd',
					'timestamp'         => false,
				],
				[
					'name'              => __( 'Hide After', 'hyper' ),
					'desc'              => __( 'Do not display the Count Down after', 'hyper' ),
					'label_description' => __( '', 'hyper' ),
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

		$meta_boxes[] = [
			'id'             => 'homepage-countdown',
			'title'          => __( 'Homepage Countdown', 'hyper' ),
			'post_types'     => [ 'page' ],
			'priority'       => 'high',
			'closed'         => false,
			'autosave'       => false,
			'default_hidden' => false,
			'fields'         => [
				[
					'type'      => 'post',
					'id'        => 'homepage_countdown',
					'name'      => __( 'Countdowns', 'hyper' ),
					'post_type' => [ 'countdown' ],
					'multiple'  => true,
				],
			],
		];

		return $meta_boxes;
	}

	add_filter( 'rwmb_meta_boxes', 'hyperpress_register_countdown_meta_boxes' );
endif;