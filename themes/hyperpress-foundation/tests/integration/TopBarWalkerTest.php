<?php
namespace HyperPress\ThemeTests\Integration;

use HyperPress_Theme_Top_Bar_Walker;
use WP_UnitTestCase;

final class TopBarWalkerTest extends WP_UnitTestCase {

	private function desktop_menu_html( array $classes ): string {
		$menu_id = wp_create_nav_menu( 'desktop' );
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'   => 'About',
				'menu-item-url'     => 'http://example.org/about',
				'menu-item-status'  => 'publish',
				'menu-item-classes' => implode( ' ', $classes ),
			)
		);
		set_theme_mod( 'nav_menu_locations', array( 'top-bar-r' => $menu_id ) );

		ob_start();
		hyperpress_top_bar_r();
		return ob_get_clean();
	}

	public function test_saved_dropdown_direction_classes_are_not_output(): void {
		$html = $this->desktop_menu_html( array( 'red', 'opens-right', 'opens-left', 'opens-inner', 'is-dropdown-submenu-parent' ) );

		$this->assertStringNotContainsString( 'opens-right', $html );
		$this->assertStringNotContainsString( 'opens-left', $html );
		$this->assertStringNotContainsString( 'opens-inner', $html );
	}

	public function test_other_item_classes_are_kept(): void {
		$html = $this->desktop_menu_html( array( 'red', 'opens-right', 'is-dropdown-submenu-parent' ) );

		$this->assertMatchesRegularExpression( '/class="[^"]*\bred\b[^"]*"/', $html );
		$this->assertStringContainsString( 'is-dropdown-submenu-parent', $html );
	}

	public function test_nested_list_is_hidden_before_foundation_js_runs(): void {
		$output = '';
		( new HyperPress_Theme_Top_Bar_Walker() )->start_lvl( $output, 0, (object) array() );

		$this->assertStringContainsString( 'is-dropdown-submenu', $output );
		$this->assertStringContainsString( 'dropdown menu vertical', $output );
		$this->assertStringContainsString( 'data-toggle', $output );
	}
}
