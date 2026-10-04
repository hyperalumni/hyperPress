<?php
namespace HyperPress\Newsletter;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Code_Editor_Control;

final class Customizer {

	public const SUBTITLE_SETTING = 'hyperpress_newsletter_banner_subtitle';
	public const SUBTITLE_DEFAULT = 'Updates on Team HYPER from our Marketing Team';

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize_newsletter_banner' ) );
		add_filter( 'theme_mod_' . self::SUBTITLE_SETTING, array( self::class, 'subtitle_default' ) );
	}

	/**
	 * Apply the setting default on the front end too, because the customize_register callback only runs inside the Customizer.
	 * The get_theme_mod() function yields false when nothing is stored.
	 *
	 * @param mixed $value Stored value, or false when unset.
	 * @return mixed
	 */
	public static function subtitle_default( $value ) {
		return false === $value ? self::SUBTITLE_DEFAULT : $value;
	}

	public static function customize_newsletter_banner( $wp_customize ): void {
		$wp_customize->add_section(
			'hyperpress_newsletter',
			array(
				'title'    => __( 'Newsletters', 'hyperpress' ),
				'priority' => 107,
			)
		);

		$wp_customize->add_setting(
			self::SUBTITLE_SETTING,
			array(
				'default'           => self::SUBTITLE_DEFAULT,
				'type'              => 'theme_mod',
				'transport'         => 'refresh',
				'sanitize_callback' => 'wp_kses_post',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Code_Editor_Control(
				$wp_customize,
				self::SUBTITLE_SETTING,
				array(
					'label'    => __( 'Newsletter Banner Subtitle', 'hyperpress' ),
					'settings' => self::SUBTITLE_SETTING,
					'section'  => 'hyperpress_newsletter',
				)
			)
		);
	}
}
