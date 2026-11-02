<?php
/**
 * Allow users to select Topbar or Offcanvas menu. Adds body class of offcanvas or topbar based on which they choose.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'wpt_register_theme_customizer' );

function wpt_register_theme_customizer( $wp_customize ): void {

	// Create custom panels
	$wp_customize->add_panel(
		'mobile_menu_settings',
        array(
			'priority'       => 1000,
			'theme_supports' => '',
			'title'          => __( 'Mobile Menu Settings', 'hyperpress' ),
			'description'    => __( 'Controls the mobile menu', 'hyperpress' ),
		)
	);

	// Create custom field for mobile navigation layout
	$wp_customize->add_section(
		'mobile_menu_layout',
        array(
			'title'    => __( 'Mobile navigation layout', 'hyperpress' ),
			'panel'    => 'mobile_menu_settings',
			'priority' => 1000,
		)
	);

	// Set default navigation layout
	$wp_customize->add_setting(
		'wpt_mobile_menu_layout',
		array(
			'default'           => 'topbar',
			'sanitize_callback' => static function ( $value ): string {
				return in_array( $value, array( 'topbar', 'offcanvas', 'drilldown' ), true ) ? $value : 'topbar';
			},
		)
	);

	// Add options for navigation layout
	$wp_customize->add_control(
		new WP_Customize_Control(
			$wp_customize,
			'mobile_menu_layout',
			array(
				'type'     => 'radio',
				'section'  => 'mobile_menu_layout',
				'settings' => 'wpt_mobile_menu_layout',
				'choices'  => array(
					'topbar'    => 'Topbar',
					'offcanvas' => 'Offcanvas',
					'drilldown' => 'Drilldown',
				),
			)
		)
	);
}

/**
 * The saved mobile menu layout: 'topbar' (default), 'offcanvas' or 'drilldown'.
 */
function hyperpress_mobile_menu_layout(): string {
	$layout = get_theme_mod( 'wpt_mobile_menu_layout' );

	return in_array( $layout, array( 'offcanvas', 'drilldown' ), true ) ? $layout : 'topbar';
}

// Add class to body to help w/ CSS
add_filter( 'body_class', 'mobile_nav_class' );
function mobile_nav_class( $classes ) {
	$layout = hyperpress_mobile_menu_layout();
	// Foundation styles every `.drilldown a`, so the drilldown layout gets a prefixed body class.
	$classes[] = 'drilldown' === $layout ? 'mobile-drilldown' : $layout;

	return $classes;
}
