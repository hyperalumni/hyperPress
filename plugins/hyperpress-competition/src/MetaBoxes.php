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
					'placeholder'       => 'Select a Robot',
					'field_type'        => 'select_advanced',
					'post_type'         => array( 'hyper_robot' ),
					'sanitize_callback' => array( self::class, 'robot_id' ),
				),
				array(
					'type'              => 'text',
					'id'                => 'hyperpress_competition_host_name',
					'name'              => __( 'Host Name', 'hyperpress' ),
					'desc'              => __( 'Enter the name of the competition host', 'hyperpress' ),
					'placeholder'       => 'Enter host name',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'type'              => 'text',
					'id'                => 'hyperpress_competition_venue_name',
					'name'              => __( 'Venue Name', 'hyperpress' ),
					'desc'              => __( 'Enter the name of the competition venue', 'hyperpress' ),
					'placeholder'       => 'Enter venue name',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'id'                => 'hyperpress_competition_venue_address',
					'name'              => 'Address',
					'type'              => 'text',
					'sanitize_callback' => 'sanitize_text_field',
				),
				array(
					'id'                => 'hyperpress_competition_venue_map',
					'name'              => 'Location',
					'type'              => 'map',
					'std'               => '42.25228605221164, -71.00051344845554,15',
					'address_field'     => 'hyperpress_competition_venue_address',
					'api_key'           => Customizer::google_maps_api_key(),
					'sanitize_callback' => array( self::class, 'map_location' ),
				),
				array(
					'name'              => 'NE FIRST Event Page',
					'id'                => 'hyperpress_competition_ne_first_event_url',
					'type'              => 'link',
					'std'               => array(
						'title' => 'NE FIRST Event Page',
					),
					'sanitize_callback' => array( self::class, 'event_url' ),
				),
				array(
					'name'              => 'FIRST Event Page',
					'id'                => 'hyperpress_competition_first_event_url',
					'type'              => 'link',
					'sanitize_callback' => array( self::class, 'event_url' ),
				),
				array(
					'name'              => 'Stream',
					'id'                => 'hyperpress_competition_stream_url',
					'type'              => 'link',
					'sanitize_callback' => array( self::class, 'event_url' ),
				),
			),
		);

		return $meta_boxes;
	}

	/**
	 * Sanitizer for the event and stream links: a safe URL, or '' for anything else.
	 *
	 * @param mixed $value Submitted value.
	 */
	public static function event_url( $value ): string {
		return is_string( $value ) ? esc_url_raw( $value ) : '';
	}

	/**
	 * Sanitizer for the robot field: the ID of a hyper_robot post, or 0.
	 *
	 * @param mixed $value Submitted value.
	 */
	public static function robot_id( $value ): int {
		$id = is_scalar( $value ) ? absint( $value ) : 0;

		return ( $id > 0 && 'hyper_robot' === get_post_type( $id ) ) ? $id : 0;
	}

	/**
	 * Sanitizer for the venue map: keeps a 'lat,lng' or 'lat,lng,zoom' string, clears anything else.
	 *
	 * @param mixed $value Submitted value.
	 */
	public static function map_location( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}
		$value = trim( $value );

		return 1 === preg_match( '/^-?\d+(\.\d+)?,\s*-?\d+(\.\d+)?(,\s*\d+)?$/', $value ) ? $value : '';
	}
}
