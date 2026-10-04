<?php
namespace HyperPress\Media\NextGen;

defined( 'ABSPATH' ) || exit;

use WP_Customize_Control;

final class Customizer {

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize_nggallery' ) );
	}

	public static function customize_nggallery( $wp_customize ): void {
		$wp_customize->add_section(
			'hyperpress_media_nextgen',
			array(
				'title' => __( 'NextGen Gallery', 'hyperpress' ),
			)
		);

		$wp_customize->add_setting(
			'hyperpress_nggallery_photos_page',
			array(
				'type'      => 'theme_mod',
				'transport' => 'refresh',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Control(
				$wp_customize,
				'hyperpress_nggallery_photos_page',
				array(
					'label'       => __( 'Page Displaying Photos', 'hyperpress' ),
					'description' => __( 'For breadcrumb generation', 'hyperpress' ),
					'settings'    => 'hyperpress_nggallery_photos_page',
					'type'        => 'dropdown-pages',
					'section'     => 'hyperpress_media_nextgen',
				)
			)
		);
	}
}
