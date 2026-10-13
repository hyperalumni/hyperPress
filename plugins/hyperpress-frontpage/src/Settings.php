<?php
namespace HyperPress\Frontpage;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The Front Page settings page option and its defaults.
 */
final class Settings {

	public const OPTION                  = 'hyperpress_frontpage';
	public const CATEGORIES              = 'hyperpress_frontpage_categories';
	public const POST_TYPES              = 'hyperpress_frontpage_post_types';
	public const BANNER_TITLE            = 'hyperpress_frontpage_banner_title';
	public const BANNER_SUBTITLE         = 'hyperpress_frontpage_banner_subtitle';
	public const BANNER_BACKGROUND_COLOR = 'hyperpress_frontpage_banner_background_color';
	public const BANNER_BUTTON_TEXT      = 'hyperpress_frontpage_banner_button_text';
	public const BANNER_BUTTON_PAGE      = 'hyperpress_frontpage_banner_button_page';
	public const COUNTDOWNS              = 'hyperpress_frontpage_countdowns';

	/**
	 * The default banner subtitle.
	 */
	public static function default_subtitle(): string {
		return __( 'Dedicated to improving &amp; expanding the abilities of <span class="emphasis">Team HYPER</span>', 'hyperpress' );
	}

	/**
	 * Default values, keyed by field id.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			self::POST_TYPES      => array( 'post' ),
			self::BANNER_SUBTITLE => self::default_subtitle(),
		);
	}

	/**
	 * Filter on default_option_hyperpress_frontpage: the defaults, unless the caller passed its own.
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
}
