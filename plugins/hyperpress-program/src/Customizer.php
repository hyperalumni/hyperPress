<?php
namespace HyperPress\Program;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Controls\PostTypeDropdownControl;

final class Customizer {

	public static function register(): void {
		add_action( 'customize_register', array( self::class, 'customize' ) );
	}

	public static function customize( $wp_customize ): void {
		$wp_customize->add_setting(
			'hyperpress_program_post_types',
			array(
				'type'              => 'theme_mod',
				'default'           => array( 'post' ),
				'sanitize_callback' => '',
			)
		);
		$wp_customize->add_control(
			new PostTypeDropdownControl(
				$wp_customize,
				'hyperpress_program_post_types',
				array(
					'section'     => 'hyperpress_program',
					'label'       => __( 'Programs Post Types', 'hyperpress' ),
					'description' => __( 'Select the Post Types that will use the Program Taxonomy', 'hyperpress' ),
					'settings'    => 'hyperpress_program_post_types',
				)
			)
		);
	}
}
