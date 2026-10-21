<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Banner;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'register_information_meta_box' ) );
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the Competition settings box.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'id'             => 'hyperpress_competition_box',
			'title'          => __( 'Competition', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				array(
					'id'                => Settings::BANNER_SUBTITLE,
					'name'              => __( 'Banner subtitle', 'hyperpress' ),
					'desc'              => __( 'Shown under the title on the competitions pages. Shortcodes such as [hyperpress_emphasis]text[/hyperpress_emphasis] work, and so do &lt;strong&gt;, &lt;em&gt; and &lt;br&gt; tags; other markup is removed when the banner is displayed.', 'hyperpress' ),
					'type'              => 'textarea',
					'raw'               => true,
					'std'               => Settings::BANNER_SUBTITLE_DEFAULT,
					'sanitize_callback' => array( Banner::class, 'sanitize_subtitle' ),
				),
				array(
					'id'   => Settings::PAST_ZERO,
					'name' => __( 'Count past zero', 'hyperpress' ),
					'desc' => __( 'Keep counting after the start time instead of stopping at zero.', 'hyperpress' ),
					'type' => 'checkbox',
				),
			),
		);

		return $boxes;
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
					'type'              => 'osm',
					'std'               => '42.25228605221164, -71.00051344845554,15',
					'address_field'     => 'hyperpress_competition_venue_address',
					'sanitize_callback' => array( self::class, 'map_location' ),
				),
				array(
					'name'       => 'Event Schedule',
					'id'         => 'hyperpress_competition_event_days',
					'type'       => 'group',
					'clone'      => true,
					'sort_clone' => true,
					'add_button' => __( 'Add another day', 'hyperpress' ),
					'fields'     => array(
						array(
							'name'              => 'Label',
							'id'                => 'label',
							'type'              => 'text',
							'placeholder'       => __( 'Day 1', 'hyperpress' ),
							'sanitize_callback' => 'sanitize_text_field',
						),
						array(
							'name'        => 'Start Date/Time',
							'id'          => 'start',
							'type'        => 'datetime',
							'inline'      => false,
							'timestamp'   => false,
							'save_format' => 'Y-m-d H:i',
						),
						array(
							'name' => "Don't count down to this day",
							'id'   => 'skip',
							'type' => 'checkbox',
						),
					),
				),
				array(
					'name'              => 'The Blue Alliance Event Key',
					'id'                => 'hyperpress_competition_tba_event_key',
					'type'              => 'text',
					'desc'              => __( 'TBA event key, e.g. 2026mabos. Used to link to The Blue Alliance.', 'hyperpress' ),
					'sanitize_callback' => 'sanitize_key',
				),
				array(
					'name'              => 'NE FIRST Event Page',
					'id'                => 'hyperpress_competition_ne_first_event_url',
					'type'              => 'link',
					'sanitize_callback' => array( self::class, 'event_link' ),
				),
				array(
					'name'              => 'FTC MA Event Page',
					'id'                => 'hyperpress_competition_ftc_ma_event_url',
					'type'              => 'link',
					'sanitize_callback' => array( self::class, 'event_link' ),
				),
				array(
					'name'              => 'FIRST Event Page',
					'id'                => 'hyperpress_competition_first_event_url',
					'type'              => 'link',
					'sanitize_callback' => array( self::class, 'event_link' ),
				),
				array(
					'name'              => 'FRC Events Page',
					'id'                => 'hyperpress_competition_frc_events_url',
					'type'              => 'link',
					'sanitize_callback' => array( self::class, 'event_link' ),
				),
				array(
					'name'              => 'Public Agenda',
					'id'                => 'hyperpress_competition_agenda_url',
					'type'              => 'link',
					'sanitize_callback' => array( self::class, 'event_link' ),
				),
				array(
					'name'              => 'Streams',
					'id'                => 'hyperpress_competition_stream_url',
					'type'              => 'link',
					'clone'             => true,
					'clone_as_multiple' => true,
					'add_button'        => __( 'Add another stream', 'hyperpress' ),
					'sanitize_callback' => array( self::class, 'event_link' ),
				),
			),
		);

		return $meta_boxes;
	}

	/**
	 * Sanitizer for the event and stream links. A Meta Box `link` field submits an array
	 * (url, title, target, post_id). Returns that array sanitized, or array() when there is
	 * no safe URL, which makes Meta Box delete the meta. For the cloneable stream field Meta Box
	 * calls this once per clone and drops the clones that come back empty.
	 *
	 * @param mixed $value Submitted value.
	 * @return array{url?:string,title?:string,target?:string,post_id?:int}
	 */
	public static function event_link( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$url = isset( $value['url'] ) && is_string( $value['url'] ) ? esc_url_raw( $value['url'] ) : '';
		if ( '' === $url ) {
			return array();
		}

		return array(
			'url'     => $url,
			'title'   => isset( $value['title'] ) && is_string( $value['title'] ) ? wp_kses_post( $value['title'] ) : '',
			'target'  => isset( $value['target'] ) && '_blank' === $value['target'] ? '_blank' : '',
			'post_id' => isset( $value['post_id'] ) && is_scalar( $value['post_id'] ) ? absint( $value['post_id'] ) : 0,
		);
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
