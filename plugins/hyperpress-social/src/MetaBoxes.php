<?php
namespace HyperPress\Social;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Sanitize;

final class MetaBoxes {

	public static function register(): void {
		add_filter( 'rwmb_meta_boxes', array( self::class, 'boxes' ) );
	}

	/**
	 * Adds the Social settings box.
	 *
	 * @param array<int,array<string,mixed>> $boxes Meta boxes.
	 * @return array<int,array<string,mixed>>
	 */
	public static function boxes( array $boxes ): array {
		$boxes[] = array(
			'id'             => 'hyperpress_social_box',
			'title'          => __( 'Social', 'hyperpress' ),
			'settings_pages' => SettingsPage::ID,
			'fields'         => array(
				self::url_field( 'firstinspires', __( 'FIRST Inspires URL', 'hyperpress' ) ),
				self::url_field( 'thebluealliance', __( 'TheBlueAlliance URL', 'hyperpress' ) ),
				self::url_field( 'facebook', __( 'Facebook URL', 'hyperpress' ) ),
				self::url_field( 'instagram', __( 'Instagram URL', 'hyperpress' ) ),
				self::url_field( 'youtube', __( 'YouTube URL', 'hyperpress' ) ),
				self::url_field( 'twitter', __( 'Twitter URL', 'hyperpress' ) ),
				self::url_field( 'github', __( 'GitHub URL', 'hyperpress' ) ),
				self::url_field( 'snapchat', __( 'Snapchat URL', 'hyperpress' ) ),
				self::url_field( 'tiktok', __( 'TikTok URL', 'hyperpress' ) ),
				self::url_field( 'discord', __( 'Discord URL', 'hyperpress' ) ),
				array(
					'id'                => Settings::field_id( Settings::CONTACT ),
					'name'              => __( 'Contact Page', 'hyperpress' ),
					'type'              => 'post',
					'post_type'         => 'page',
					'field_type'        => 'select_advanced',
					'placeholder'       => __( 'Select a page', 'hyperpress' ),
					'query_args'        => array(
						'post_status'    => 'publish',
						'posts_per_page' => -1,
					),
					'sanitize_callback' => 'absint',
				),
				self::url_field( 'add_calendar', __( 'Add Calendar URL', 'hyperpress' ) ),
			),
		);

		return $boxes;
	}

	/**
	 * A URL field for a network key.
	 *
	 * @param string $key   Network key.
	 * @param string $label Translated field label.
	 * @return array<string,mixed>
	 */
	private static function url_field( string $key, string $label ): array {
		return array(
			'id'                => Settings::field_id( $key ),
			'name'              => $label,
			'type'              => 'url',
			'sanitize_callback' => array( Sanitize::class, 'url' ),
		);
	}
}
