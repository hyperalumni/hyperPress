<?php

namespace HYPER_Press_Competition;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

if ( ! function_exists( '\HYPER_Press_Competition\hyperpress_register_competition_robot_meta_box' ) ) :
	add_filter( 'rwmb_meta_boxes', '\HYPER_Press_Competition\hyperpress_register_competition_robot_meta_box' );

	function hyperpress_register_competition_robot_meta_box( $meta_boxes ) {
		$meta_boxes[] = [
			'id'             => 'competition-information',
			'title'          => __( 'Information', 'hyperpress' ),
			'post_types'     => [ 'competition' ],
			'context'        => 'normal',
			'priority'       => 'high',
			'closed'         => false,
			'autosave'       => false,
			'default_hidden' => false,
			'fields'         => [
				[
					'type'      => 'post',
					'id'        => 'competition_robot',
					'name'      => __( 'Robot', 'hyperpress' ),
					'desc'              => __( 'Select the robot for this competition', 'hyperpress' ),
					'label_description' => __( '', 'hyperpress' ),
					'placeholder' => 'Select a Robot',
					'field_type'  => 'select_advanced',
					'post_type' => [ 'robot' ],
				],
				[
					'type'      => 'text',
					'id'        => 'competition_host_name',
					'name'      => __( 'Host Name', 'hyperpress' ),
					'desc'      => __( 'Enter the name of the competition host', 'hyperpress' ),
					'placeholder' => 'Enter host name',
				],
				[
					'type'      => 'text',
					'id'        => 'competition_venue_name',
					'name'      => __( 'Venue Name', 'hyperpress' ),
					'desc'      => __( 'Enter the name of the competition venue', 'hyperpress' ),
					'placeholder' => 'Enter venue name',
				],
				[
					'id'   => 'competition_venue_address',
					'name' => 'Address',
					'type' => 'text',
				],
				[
					'id'            => 'competition_venue_map',
					'name'          => 'Location',
					'type'          => 'map',
					'std'           => '42.25228605221164, -71.00051344845554,15',
					'address_field' => 'competition_venue_address',
					'api_key'       => '',
				],
				[
					'name' => 'NE FIRST Event Page',
					'id'   => 'ne_first_event_url',
					'type' => 'link',
					'std' => [
						'title' => 'NE FIRST Event Page'
					]
				],
				[
					'name' => 'FIRST Event Page',
					'id'   => 'first_event_url',
					'type' => 'link',
				],
				[
					'name' => 'Stream',
					'id'   => 'stream_url',
					'type' => 'link',
				],
			],
		];

		return $meta_boxes;
	}
endif;