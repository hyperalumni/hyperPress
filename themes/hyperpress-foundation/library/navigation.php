<?php
/**
 * Register Menus
 *
 * @link http://codex.wordpress.org/Function_Reference/register_nav_menus#Examples
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'hyperpress_register_nav_menus' );

/**
 * Register the menu locations. The labels are translated, so this runs on after_setup_theme and not while
 * functions.php is included (WordPress 6.7+ flags a text domain that loads that early).
 */
function hyperpress_register_nav_menus(): void {
	register_nav_menus(
		array(
			'top-bar-r'  => esc_html__( 'Right Top Bar', 'hyperpress' ),
			'mobile-nav' => esc_html__( 'Mobile', 'hyperpress' ),
		)
	);
}


/**
 * Desktop navigation - right top bar
 *
 * @link http://codex.wordpress.org/Function_Reference/wp_nav_menu
 */
function hyperpress_top_bar_r(): void {
	wp_nav_menu(
		array(
			'container'      => false,
			'menu_class'     => 'dropdown menu desktop-menu',
			'items_wrap'     => '<ul id="%1$s" class="%2$s" data-dropdown-menu data-alignment="left">%3$s</ul>',
			'theme_location' => 'top-bar-r',
			'depth'          => 3,
			'fallback_cb'    => false,
			'walker'         => new HyperPress_Theme_Top_Bar_Walker(),
		)
	);
}


add_filter( 'nav_menu_css_class', 'hyperpress_strip_dropdown_direction_classes', 10, 3 );

/**
 * Foundation's DropdownMenu adds and flips opens-left, opens-right and opens-inner itself (the desktop menu's
 * data-alignment="left" makes it start with opens-right). Copies saved in a menu item's CSS Classes field (a
 * habit from older FoundationPress sites, used to force opens-right) would sit next to the live one, and the
 * arrow styles could no longer tell which direction is current.
 *
 * @param string[] $classes CSS classes of the menu item.
 * @param WP_Post  $item    The menu item.
 * @param stdClass $args    The wp_nav_menu() arguments.
 * @return string[]
 */
function hyperpress_strip_dropdown_direction_classes( $classes, $item, $args ) {
	if ( ! isset( $args->theme_location ) || 'top-bar-r' !== $args->theme_location ) {
		return $classes;
	}

	return array_values( array_diff( $classes, array( 'opens-left', 'opens-right', 'opens-inner' ) ) );
}

/**
 * Mobile navigation - topbar (default) or offcanvas
 */
function hyperpress_mobile_nav(): void {
	$items_wrap = '<ul id="%1$s" class="%2$s" data-accordion-menu data-submenu-toggle="true">%3$s</ul>';

	if ( hyperpress_mobile_menu_layout() === 'drilldown' ) {
		// Foundation inserts data-back-button as HTML: the label is HTML-escaped, then the whole string attribute-escaped
		// with double encoding (esc_attr() would not re-encode the label's entities, and the browser would decode them
		// back into markup). items_wrap is a sprintf format, so a literal % in a translated label is doubled.
		$back_button = htmlspecialchars( '<li class="js-drilldown-back"><a tabindex="0" role="button">' . esc_html__( 'Back', 'hyperpress' ) . '</a></li>', ENT_QUOTES, 'UTF-8', true );
		$items_wrap  = '<ul id="%1$s" class="%2$s" data-drilldown data-parent-link="true" data-auto-height="true" data-back-button="' . str_replace( '%', '%%', $back_button ) . '">%3$s</ul>';
	}

	wp_nav_menu(
		array(
			'container'      => false,                         // Remove nav container
			'menu'           => 'mobile-nav',
			'menu_class'     => 'vertical menu',
			'theme_location' => 'mobile-nav',
			'items_wrap'     => $items_wrap,
			'fallback_cb'    => false,
			'walker'         => new HyperPress_Theme_Mobile_Walker(),
		)
	);
}


/**
 * Add support for buttons in the top-bar menu:
 * 1) In WordPress admin, go to Apperance -> Menus.
 * 2) Click 'Screen Options' from the top panel and enable 'CSS CLasses' and 'Link Relationship (XFN)'
 * 3) On your menu item, type 'has-form' in the CSS-classes field. Type 'button' in the XFN field
 * 4) Save Menu. Your menu item will now appear as a button in your top-menu
 */
add_filter( 'wp_nav_menu', 'hyperpress_add_menuclass' );

function hyperpress_add_menuclass( $ulclass ): array|string|null {
	$find    = array( '/<a rel="button"/', '/<a title=".*?" rel="button"/' );
	$replace = array( '<a rel="button" class="button"', '<a rel="button" class="button"' );

	return preg_replace( $find, $replace, $ulclass, 1 );
}

add_filter( 'the_content_more_link', 'hyperpress_customize_the_read_more_link' );

function hyperpress_customize_the_read_more_link(): string {
	return '<a class="hollow button medium-down-expanded more-link" href="' . esc_url( get_permalink() . '#more-' . get_the_ID() ) . '">' . esc_html__( 'Continue Reading', 'hyperpress' ) . '</a>';
}
