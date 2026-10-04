<?php
namespace HyperPress\Newsletter;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Code_Editor_Control;

final class Customizer {

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize_newsletter_banner' ) );
	}

	public static function customize_newsletter_banner( $wp_customize ): void {
		$wp_customize->add_setting(
			'hyperpress_newsletter_banner_subtitle',
			array(
				'default'   => 'Updates on Team HYPER from our Marketing Team',
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Code_Editor_Control(
				$wp_customize,
				'hyperpress_newsletter_banner_subtitle',
				array(
					'label'    => __( 'Newsletter Banner Subtitle', 'hyperpress' ),
					'settings' => 'hyperpress_newsletter_banner_subtitle',
					'section'  => 'hyperpress_newsletter',
				)
			)
		);
	}
}
