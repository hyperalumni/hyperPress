<?php
namespace HyperPress\Countdown;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'register_event_information_meta_box' ) );
		add_filter( 'rwmb_meta_boxes', array( self::class, 'register_visibility_meta_boxes' ) );
	}

	public static function register_event_information_meta_box( $meta_boxes ) {
		$meta_boxes[] = [
			'id'             => 'hyperpress_countdown_event_information',
			'title'          => __( 'Event Information', 'hyperpress' ),
			'post_types'     => [ 'hyper_countdown' ],
			'context'        => 'normal',
			'priority'       => 'high',
			'closed'         => false,
			'autosave'       => false,
			'default_hidden' => false,
			'fields'         => [
				[
					'name'            => __( 'Event Name', 'hyperpress' ),
					'id'              => 'hyperpress_countdown_event_name',
					'type'            => 'text',
					'required'        => true,
					'hide_from_front' => true,
					'limit'           => 10,
					'limit_type'      => 'word',
				],
				[
					'name'            => __( 'Event Location', 'hyperpress' ),
					'id'              => 'hyperpress_countdown_event_location',
					'type'            => 'text',
					'required'        => false,
					'hide_from_front' => true,
					'limit'           => 10,
					'limit_type'      => 'word',
				],
				[
					'name'            => __( 'Event Date & Time', 'hyperpress' ),
					'id'              => 'hyperpress_countdown_event_date_time',
					'type'            => 'datetime',
					'required'        => true,
					'hide_from_front' => true,
					'inline'          => false,
					'format'          => 'yy-mm-dd',
					'timestamp'       => false,
				],
				[
					'name'            => __( 'Count Past Zero', 'hyperpress' ),
					'id'              => 'hyperpress_countdown_past_zero',
					'type'            => 'checkbox',
					'required'        => false,
					'hide_from_front' => true,
					'std'             => 0,
				]
			]
		];

		return $meta_boxes;
	}

	public static function register_visibility_meta_boxes( $meta_boxes ) {
		$meta_boxes[] = [
			'id'             => 'hyperpress_countdown_visibility',
			'title'          => __( 'Visibility', 'hyperpress' ),
			'post_types'     => [ 'hyper_countdown' ],
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
					'id'                => 'hyperpress_countdown_hide_before',
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
					'id'                => 'hyperpress_countdown_hide_after',
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
}
