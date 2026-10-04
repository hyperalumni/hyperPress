<?php
/**
 * Register Menus
 *
 * @link http://codex.wordpress.org/Function_Reference/register_nav_menus#Examples
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

register_nav_menus(
	array(
		'top-bar-r'  => esc_html__( 'Right Top Bar', 'foundationpress' ),
		'mobile-nav' => esc_html__( 'Mobile', 'foundationpress' ),
	)
);


/**
 * Desktop navigation - right top bar
 *
 * @link http://codex.wordpress.org/Function_Reference/wp_nav_menu
 */
if ( ! function_exists( 'hyperpress_top_bar_r' ) ) :
	function hyperpress_top_bar_r(): void {
		wp_nav_menu(
			array(
				'container'      => false,
				'menu_class'     => 'dropdown menu desktop-menu',
				'items_wrap'     => '<ul id="%1$s" class="%2$s" data-dropdown-menu>%3$s</ul>',
				'theme_location' => 'top-bar-r',
				'depth'          => 3,
				'fallback_cb'    => false,
				'walker'         => new HyperPress_Theme_Top_Bar_Walker(),
			)
		);
	}
endif;


/**
 * Mobile navigation - topbar (default) or offcanvas
 */
if ( ! function_exists( 'hyperpress_mobile_nav' ) ) :
	function hyperpress_mobile_nav(): void {
		wp_nav_menu(
			array(
				'container'      => false,                         // Remove nav container
				'menu'           => __( 'mobile-nav', 'foundationpress' ),
				'menu_class'     => 'vertical menu',
				'theme_location' => 'mobile-nav',
				'items_wrap'     => '<ul id="%1$s" class="%2$s" data-accordion-menu data-submenu-toggle="true">%3$s</ul>',
				'fallback_cb'    => false,
				'walker'         => new HyperPress_Theme_Mobile_Walker(),
			)
		);
	}
endif;


/**
 * Add support for buttons in the top-bar menu:
 * 1) In WordPress admin, go to Apperance -> Menus.
 * 2) Click 'Screen Options' from the top panel and enable 'CSS CLasses' and 'Link Relationship (XFN)'
 * 3) On your menu item, type 'has-form' in the CSS-classes field. Type 'button' in the XFN field
 * 4) Save Menu. Your menu item will now appear as a button in your top-menu
 */
if ( ! function_exists( 'hyperpress_add_menuclass' ) ) :
	add_filter( 'wp_nav_menu', 'hyperpress_add_menuclass' );

	function hyperpress_add_menuclass( $ulclass ): array|string|null {
		$find    = array( '/<a rel="button"/', '/<a title=".*?" rel="button"/' );
		$replace = array( '<a rel="button" class="button"', '<a rel="button" class="button"' );

		return preg_replace( $find, $replace, $ulclass, 1 );
	}
endif;

if ( ! function_exists( 'hyperpress_customize_the_read_more_link' ) ) :
	add_filter( 'the_content_more_link', 'hyperpress_customize_the_read_more_link' );

	function hyperpress_customize_the_read_more_link(): string {
		return '<button class="hollow button medium-down-expanded more-link" href="' . get_permalink() . '#more-' . get_the_ID() . '">Continue Reading</button>';
	}

endif;
