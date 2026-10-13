<?php
namespace HyperPress\Social;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The Social settings page option, its field ids and accessors.
 *
 * There are no non-empty defaults: an unset link is simply not shown.
 */
final class Settings {

	public const OPTION  = 'hyperpress_social';
	public const CONTACT = 'contact';

	/**
	 * The URL fields in display order: key => label.
	 */
	public const NETWORKS = array(
		'firstinspires'   => 'FIRST Inspires URL',
		'thebluealliance' => 'TheBlueAlliance URL',
		'facebook'        => 'Facebook URL',
		'instagram'       => 'Instagram URL',
		'youtube'         => 'YouTube URL',
		'twitter'         => 'Twitter URL',
		'github'          => 'GitHub URL',
		'snapchat'        => 'Snapchat URL',
		'tiktok'          => 'TikTok URL',
		'discord'         => 'Discord URL',
		'add_calendar'    => 'Add Calendar URL',
	);

	/**
	 * The field id (and option array key) for a key.
	 *
	 * @param string $key Network key, or CONTACT.
	 */
	public static function field_id( string $key ): string {
		return 'hyperpress_social_' . $key;
	}

	/**
	 * The saved URL for a network key; '' for an unknown key, a missing value or a wrongly typed one.
	 *
	 * @param string $key Network key (a key of NETWORKS).
	 */
	public static function url( string $key ): string {
		if ( ! array_key_exists( $key, self::NETWORKS ) ) {
			return '';
		}
		$option = get_option( self::OPTION );
		$id     = self::field_id( $key );
		if ( ! is_array( $option ) || ! isset( $option[ $id ] ) || ! is_string( $option[ $id ] ) ) {
			return '';
		}
		return $option[ $id ];
	}

	/**
	 * The saved contact page id; 0 when unset or not a positive integer (or digit string).
	 */
	public static function contact_page(): int {
		$option = get_option( self::OPTION );
		$id     = self::field_id( self::CONTACT );
		if ( ! is_array( $option ) || ! isset( $option[ $id ] ) ) {
			return 0;
		}
		$value = $option[ $id ];
		if ( is_int( $value ) && $value > 0 ) {
			return $value;
		}
		if ( is_string( $value ) && ctype_digit( $value ) ) {
			return absint( $value );
		}
		return 0;
	}
}
