<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\AdminMenu;
use WP_UnitTestCase;

final class AdminMenuTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		unset( $GLOBALS['menu'], $GLOBALS['submenu'] );
		$GLOBALS['menu']    = array();
		$GLOBALS['submenu'] = array();
	}

	public function tear_down(): void {
		unset( $GLOBALS['menu'], $GLOBALS['submenu'], $GLOBALS['admin_page_hooks'], $GLOBALS['_registered_pages'], $GLOBALS['_parent_pages'] );
		parent::tear_down();
	}

	private function user_as( string $role ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => $role ) ) );
	}

	/** Whether the current user would see the top-level menu (wp-admin drops entries whose capability the user lacks). */
	private function menu_visible(): bool {
		foreach ( $GLOBALS['menu'] as $item ) {
			if ( AdminMenu::SLUG === $item[2] ) {
				return current_user_can( $item[1] );
			}
		}
		return false;
	}

	private function landing_page(): string {
		ob_start();
		AdminMenu::render();
		return (string) ob_get_clean();
	}

	public function test_slug_is_hyperpress(): void {
		$this->assertSame( 'hyperpress', AdminMenu::SLUG );
	}

	public function test_administrator_sees_the_top_level_menu(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		$this->assertTrue( $this->menu_visible() );
	}

	public function test_editor_does_not_see_the_top_level_menu(): void {
		$this->user_as( 'editor' );
		AdminMenu::register();
		$this->assertFalse( $this->menu_visible() );
	}

	public function test_landing_page_shows_empty_message_without_children(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		$html = $this->landing_page();
		$this->assertStringContainsString( 'No settings pages yet.', $html );
		$this->assertStringNotContainsString( 'Overview', $html );
	}

	public function test_landing_page_lists_a_child_page(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		add_submenu_page( AdminMenu::SLUG, 'Child Page', 'Child <b>Settings</b>', 'manage_options', 'hyperpress-child', '__return_null' );
		$html = $this->landing_page();
		$this->assertStringContainsString( 'Child Settings', $html );
		$this->assertStringContainsString( 'admin.php?page=hyperpress-child', $html );
		$this->assertStringNotContainsString( 'No settings pages yet.', $html );
	}

	public function test_landing_page_skips_children_the_user_cannot_access(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		add_submenu_page( AdminMenu::SLUG, 'Secret', 'Secret Page', 'manage_network', 'hyperpress-secret', '__return_null' );
		$html = $this->landing_page();
		$this->assertStringNotContainsString( 'Secret Page', $html );
		$this->assertStringContainsString( 'No settings pages yet.', $html );
	}
}
