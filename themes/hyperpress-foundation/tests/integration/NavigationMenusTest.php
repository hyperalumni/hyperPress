<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class NavigationMenusTest extends WP_UnitTestCase {

	/**
	 * The menu labels call esc_html__(), which must not run before the theme's own after_setup_theme: WordPress 6.7+
	 * flags a text domain that loads earlier (functions.php is included before that action).
	 */
	public function test_menus_are_registered_on_after_setup_theme(): void {
		$this->assertNotFalse( has_action( 'after_setup_theme', 'hyperpress_register_nav_menus' ) );
	}

	public function test_both_menu_locations_are_registered(): void {
		$locations = get_registered_nav_menus();

		$this->assertArrayHasKey( 'top-bar-r', $locations );
		$this->assertArrayHasKey( 'mobile-nav', $locations );
	}
}
