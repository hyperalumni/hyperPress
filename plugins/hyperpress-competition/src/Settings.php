<?php
namespace HyperPress\Competition;

use HyperPress\Utils\ArchiveBannerSettings;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The Competition settings page option, its defaults and accessors.
 */
final class Settings {

	public const OPTION                      = 'hyperpress_competition';
	public const BANNER_SUBTITLE             = 'hyperpress_competition_banner_subtitle';
	public const BANNER_SUBTITLE_DEFAULT     = '';
	public const BANNER_IMAGE                = 'hyperpress_competition_banner_image';
	public const PAST_ZERO                   = 'hyperpress_competition_countdown_past_zero';
	public const DAY_END                     = 'hyperpress_competition_day_end';
	public const DAY_END_DEFAULT             = '20:00';
	public const TIME_PATTERN                = '/^([01]\d|2[0-3]):[0-5]\d$/';
	public const FRONTPAGE                   = 'hyperpress_competition_frontpage';
	public const FRONTPAGE_LEAD_DAYS         = 'hyperpress_competition_frontpage_lead_days';
	public const FRONTPAGE_LEAD_DAYS_DEFAULT = 60;
	public const FRONTPAGE_LEAD_DAYS_MAX     = 365;

	/**
	 * Default values, keyed by field id.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array_merge(
			self::archive_banner()->defaults(),
			array(
				self::PAST_ZERO           => false,
				self::DAY_END             => self::DAY_END_DEFAULT,
				self::FRONTPAGE           => true,
				self::FRONTPAGE_LEAD_DAYS => self::FRONTPAGE_LEAD_DAYS_DEFAULT,
			)
		);
	}

	/**
	 * The shared archive-banner settings helper bound to this plugin's option.
	 */
	public static function archive_banner(): ArchiveBannerSettings {
		return new ArchiveBannerSettings( self::OPTION, self::BANNER_SUBTITLE, self::BANNER_SUBTITLE_DEFAULT, self::BANNER_IMAGE );
	}

	/**
	 * Returns the merged defaults (the banner's plus competition's own extras), so it does not delegate to the helper.
	 *
	 * Filter on default_option_hyperpress_competition: the defaults, unless the caller passed its own.
	 *
	 * @param mixed  $fallback       The default value.
	 * @param string $option         Option name.
	 * @param bool   $passed_default Whether get_option() was given an explicit default.
	 * @return mixed
	 */
	public static function default_option( $fallback, $option = '', $passed_default = false ) {
		unset( $option );
		return $passed_default ? $fallback : self::defaults();
	}

	/**
	 * The competition banner subtitle.
	 *
	 * Option row missing: the default_option filter makes get_option() return the defaults, so the default
	 * text shows until the page is saved. Row present but key missing: Meta Box drops the key of an empty
	 * field, so the editor cleared it and the subtitle is ''. Option not an array, or value not a string:
	 * the default.
	 */
	public static function banner_subtitle(): string {
		return self::archive_banner()->banner_subtitle();
	}

	/**
	 * The archive hero image attachment ID, or 0 when none is set or it is not an image.
	 */
	public static function banner_image(): int {
		return self::archive_banner()->image_id();
	}

	/**
	 * Whether the countdown keeps counting after the start time instead of stopping at zero.
	 *
	 * Meta Box drops the key of an unchecked checkbox, so a missing key (or a non-array option) is false.
	 */
	public static function past_zero(): bool {
		$option = get_option( self::OPTION );
		return is_array( $option ) && ! empty( $option[ self::PAST_ZERO ] );
	}

	/**
	 * The time of day (24-hour `H:i`) a schedule day ends at unless its own row says otherwise.
	 *
	 * Option missing or not an array, key missing (Meta Box drops the key of an empty field), value not a string or
	 * malformed: the default.
	 */
	public static function day_end(): string {
		$option = get_option( self::OPTION );
		$value  = is_array( $option ) ? ( $option[ self::DAY_END ] ?? null ) : null;

		return is_string( $value ) && 1 === preg_match( self::TIME_PATTERN, $value ) ? $value : self::DAY_END_DEFAULT;
	}

	/**
	 * Sanitize callback for the "Days end at" field: a valid `H:i` time, else '' (Meta Box then drops the key).
	 *
	 * @param mixed $value Submitted value.
	 */
	public static function sanitize_day_end( $value ): string {
		$value = is_string( $value ) ? trim( $value ) : '';

		return 1 === preg_match( self::TIME_PATTERN, $value ) ? $value : '';
	}

	/**
	 * Whether competitions are offered to the front page countdown.
	 *
	 * Option row missing: the default_option filter makes get_option() return the defaults (on). Row present: Meta Box
	 * drops the key of an unchecked checkbox, so a missing key (or a non-array option) is off.
	 */
	public static function frontpage_enabled(): bool {
		$option = get_option( self::OPTION );

		return is_array( $option ) && ! empty( $option[ self::FRONTPAGE ] );
	}

	/**
	 * How many days before its first counted day a competition's countdown can appear on the front page.
	 *
	 * Missing, malformed or out of range (0 to 365): the default.
	 */
	public static function frontpage_lead_days(): int {
		$option = get_option( self::OPTION );
		$days   = is_array( $option ) ? self::lead_days( $option[ self::FRONTPAGE_LEAD_DAYS ] ?? null ) : null;

		return $days ?? self::FRONTPAGE_LEAD_DAYS_DEFAULT;
	}

	/**
	 * Sanitize callback for the lead time field: whole days from 0 to 365 as a string, else '' (Meta Box then drops the key).
	 *
	 * @param mixed $value Submitted value.
	 */
	public static function sanitize_lead_days( $value ): string {
		$days = self::lead_days( $value );

		return null === $days ? '' : (string) $days;
	}

	/**
	 * A whole number of days from 0 to 365 from an int or a digit string (surrounding space allowed), else null.
	 *
	 * @param mixed $value Stored or submitted value.
	 */
	private static function lead_days( $value ): ?int {
		$text = is_int( $value ) ? (string) $value : ( is_string( $value ) ? trim( $value ) : '' );
		if ( 1 !== preg_match( '/^\d{1,3}$/', $text ) ) {
			return null;
		}
		$days = (int) $text;

		return $days <= self::FRONTPAGE_LEAD_DAYS_MAX ? $days : null;
	}
}
