<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'register_information_meta_box' ) );
	}

	public static function register_information_meta_box( $meta_boxes ) {
		$meta_boxes[] = array(
			'id'             => 'hyperpress_competition_information',
			'title'          => __( 'Information', 'hyperpress' ),
			'post_types'     => array( 'hyper_competition' ),
			'context'        => 'normal',
			'priority'       => 'high',
			'closed'         => false,
			'autosave'       => false,
			'default_hidden' => false,
			'fields'         => array(
				array(
					'type'              => 'post',
					'id'                => 'hyperpress_competition_robot',
					'name'              => __( 'Robot', 'hyperpress' ),
					'desc'              => __( 'Select the robot for this competition', 'hyperpress' ),
					'label_description' => __( '', 'hyperpress' ),
					'placeholder'       => 'Select a Robot',
					'field_type'        => 'select_advanced',
					'post_type'         => array( 'hyper_robot' ),
				),
				array(
					'type'        => 'text',
					'id'          => 'hyperpress_competition_host_name',
					'name'        => __( 'Host Name', 'hyperpress' ),
					'desc'        => __( 'Enter the name of the competition host', 'hyperpress' ),
					'placeholder' => 'Enter host name',
				),
				array(
					'type'        => 'text',
					'id'          => 'hyperpress_competition_venue_name',
					'name'        => __( 'Venue Name', 'hyperpress' ),
					'desc'        => __( 'Enter the name of the competition venue', 'hyperpress' ),
					'placeholder' => 'Enter venue name',
				),
				array(
					'id'   => 'hyperpress_competition_venue_address',
					'name' => 'Address',
					'type' => 'text',
				),
				array(
					'id'            => 'hyperpress_competition_venue_map',
					'name'          => 'Location',
					'type'          => 'map',
					'std'           => '42.25228605221164, -71.00051344845554,15',
					'address_field' => 'hyperpress_competition_venue_address',
					'api_key'       => '',
				),
				array(
					'name' => 'NE FIRST Event Page',
					'id'   => 'hyperpress_competition_ne_first_event_url',
					'type' => 'link',
					'std'  => array(
						'title' => 'NE FIRST Event Page',
					),
				),
				array(
					'name' => 'FIRST Event Page',
					'id'   => 'hyperpress_competition_first_event_url',
					'type' => 'link',
				),
				array(
					'name' => 'Stream',
					'id'   => 'hyperpress_competition_stream_url',
					'type' => 'link',
				),
			),
		);

		return $meta_boxes;
	}
}
