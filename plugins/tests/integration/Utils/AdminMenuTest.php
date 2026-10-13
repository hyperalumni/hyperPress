<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\AdminMenu;
use HyperPress\Utils\GeneralPage;
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

	private function add_page( string $slug ): void {
		add_submenu_page( AdminMenu::SLUG, $slug, $slug, 'manage_options', $slug, '__return_null' );
	}

	/** @return list<string> */
	private function submenu_slugs(): array {
		return array_map(
			static fn( array $item ): string => $item[2],
			(array) ( $GLOBALS['submenu'][ AdminMenu::SLUG ] ?? array() )
		);
	}

	public function test_slug_is_hyperpress(): void {
		$this->assertSame( 'hyperpress', AdminMenu::SLUG );
	}

	public function test_registers_the_top_level_menu(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		$this->assertTrue( $this->menu_visible() );
		$this->assertSame( 'dashicons-admin-settings', $GLOBALS['menu'][80][6] );
	}

	public function test_editor_does_not_see_the_top_level_menu(): void {
		$this->user_as( 'editor' );
		AdminMenu::register();
		$this->assertFalse( $this->menu_visible() );
	}

	public function test_has_no_overview_submenu(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		$this->assertNotContains( AdminMenu::SLUG, $this->submenu_slugs() );
	}

	public function test_boot_hooks_register_early_and_reorder_late(): void {
		AdminMenu::boot();
		$this->assertSame( 5, has_action( 'admin_menu', array( AdminMenu::class, 'register' ) ) );
		$this->assertSame( 999, has_action( 'admin_menu', array( AdminMenu::class, 'reorder' ) ) );
	}

	public function test_reorder_puts_general_first_when_it_registered_after_other_pages(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		$this->add_page( 'hyperpress-first' );
		$this->add_page( GeneralPage::ID );
		AdminMenu::reorder();
		$this->assertSame( GeneralPage::ID, $this->submenu_slugs()[0] );
	}

	public function test_reorder_keeps_the_other_items_in_their_order(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		$this->add_page( 'hyperpress-a' );
		$this->add_page( 'hyperpress-b' );
		$this->add_page( GeneralPage::ID );
		$this->add_page( 'hyperpress-c' );
		AdminMenu::reorder();
		$this->assertSame(
			array( GeneralPage::ID, 'hyperpress-a', 'hyperpress-b', 'hyperpress-c' ),
			$this->submenu_slugs()
		);
		$this->assertSame( array_keys( $GLOBALS['submenu'][ AdminMenu::SLUG ] ), range( 0, 3 ) );
	}

	public function test_reorder_drops_the_automatic_copy_of_the_parent(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		$this->add_page( 'hyperpress-a' );
		$this->add_page( GeneralPage::ID );
		$this->assertContains( AdminMenu::SLUG, $this->submenu_slugs() ); // WordPress adds it with the first child.
		AdminMenu::reorder();
		$this->assertNotContains( AdminMenu::SLUG, $this->submenu_slugs() );
	}

	public function test_reorder_does_nothing_without_a_general_page(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		$this->add_page( 'hyperpress-a' );
		$this->add_page( 'hyperpress-b' );
		$before = $GLOBALS['submenu'];
		AdminMenu::reorder();
		$this->assertSame( $before, $GLOBALS['submenu'] );
	}

	public function test_reorder_does_nothing_without_a_submenu(): void {
		$this->user_as( 'administrator' );
		AdminMenu::register();
		AdminMenu::reorder();
		$this->assertArrayNotHasKey( AdminMenu::SLUG, $GLOBALS['submenu'] );
	}
}
