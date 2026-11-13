<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The Events settings page option, its defaults and accessors.
 */
final class Settings {

	public const OPTION                      = 'hyperpress_event';
	public const DEFAULT_BANNER_COLOR        = 'hyperpress_event_default_banner_color';
	public const FALLBACK_SUBTITLE           = 'hyperpress_event_fallback_subtitle';
	public const HUB_PAGE                    = 'hyperpress_event_hub_page';
	public const FRONTPAGE                   = 'hyperpress_event_frontpage';
	public const FRONTPAGE_LEAD_DAYS         = 'hyperpress_event_frontpage_lead_days';
	public const COUNTDOWN_PAST_ZERO         = 'hyperpress_event_countdown_past_zero';
	public const FRONTPAGE_LEAD_DAYS_DEFAULT = 21;
	public const FRONTPAGE_LEAD_DAYS_MAX     = 365;
	private const COLOR_PATTERN              = '/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/';

	/**
	 * Default values, keyed by field id.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			self::DEFAULT_BANNER_COLOR => '',
			self::FALLBACK_SUBTITLE    => '',
			self::HUB_PAGE             => 0,
			self::FRONTPAGE            => true,
			self::FRONTPAGE_LEAD_DAYS  => self::FRONTPAGE_LEAD_DAYS_DEFAULT,
			self::COUNTDOWN_PAST_ZERO  => false,
		);
	}

	/**
	 * Filter on default_option_hyperpress_event: the defaults, unless the caller passed its own.
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
	 * The default banner colour as a hex value, or '' when unset or not a hex colour.
	 */
	public static function default_banner_color(): string {
		$value = self::value( self::DEFAULT_BANNER_COLOR );

		return is_string( $value ) && 1 === preg_match( self::COLOR_PATTERN, $value ) ? $value : '';
	}

	/**
	 * The subtitle used when an event has none, or ''.
	 */
	public static function fallback_subtitle(): string {
		$value = self::value( self::FALLBACK_SUBTITLE );

		return is_string( $value ) ? $value : '';
	}

	/**
	 * The hub page ID: the configured page when it is a published Page, else the published Page at the path
	 * `events` (the conventional hub), else 0.
	 */
	public static function hub_page_id(): int {
		$value = self::value( self::HUB_PAGE );
		$id    = is_int( $value ) ? $value : ( is_string( $value ) && 1 === preg_match( '/^\d+$/', trim( $value ) ) ? (int) trim( $value ) : 0 );
		if ( $id > 0 && self::is_published_page( get_post( $id ) ) ) {
			return $id;
		}
		$fallback = get_page_by_path( 'events' );

		return self::is_published_page( $fallback ) && $fallback instanceof \WP_Post ? $fallback->ID : 0;
	}

	/**
	 * Whether a value is a published Page.
	 *
	 * @param mixed $page Candidate post.
	 */
	private static function is_published_page( mixed $page ): bool {
		return $page instanceof \WP_Post && 'page' === $page->post_type && 'publish' === $page->post_status;
	}

	/**
	 * Whether events are offered to the front page countdown.
	 *
	 * Option row missing: the default_option filter makes get_option() return the defaults (on). Row present: Meta Box
	 * drops the key of an unchecked checkbox, so a missing key (or a non-array option) is off.
	 */
	public static function frontpage_enabled(): bool {
		$option = get_option( self::OPTION );

		return is_array( $option ) && ! empty( $option[ self::FRONTPAGE ] );
	}

	/**
	 * How many days before an event's start its countdown can appear on the front page.
	 *
	 * Missing, malformed or out of range (0 to 365): the default.
	 */
	public static function frontpage_lead_days(): int {
		return self::lead_days( self::value( self::FRONTPAGE_LEAD_DAYS ) ) ?? self::FRONTPAGE_LEAD_DAYS_DEFAULT;
	}

	/**
	 * Whether the countdown keeps counting after the start time instead of stopping at zero.
	 *
	 * Meta Box drops the key of an unchecked checkbox, so a missing key (or a non-array option) is false.
	 */
	public static function past_zero(): bool {
		$option = get_option( self::OPTION );

		return is_array( $option ) && ! empty( $option[ self::COUNTDOWN_PAST_ZERO ] );
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
	 * A saved field value, or null when the option is not an array or the key is missing.
	 *
	 * @param string $key Field id.
	 * @return mixed
	 */
	private static function value( string $key ) {
		$option = get_option( self::OPTION );

		return is_array( $option ) ? ( $option[ $key ] ?? null ) : null;
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
