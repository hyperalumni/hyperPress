<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The top-level "hyperPress" wp-admin menu.
 *
 * Other plugins attach their settings pages to it with `parent => AdminMenu::SLUG`.
 * The menu has no landing page of its own: WordPress sends a click on a top-level menu to its first
 * submenu item, so `reorder()` moves the General page to the front once every page has registered.
 */
final class AdminMenu {

	public const SLUG = 'hyperpress';

	public static function boot(): void {
		// Early, so the parent exists before children attach at the default priority.
		add_action( 'admin_menu', array( self::class, 'register' ), 5 );
		// Late, so every child page has registered before General is moved to the front.
		add_action( 'admin_menu', array( self::class, 'reorder' ), 999 );
	}

	public static function register(): void {
		add_menu_page(
			__( 'hyperPress', 'hyperpress' ),
			__( 'hyperPress', 'hyperpress' ),
			'manage_options',
			self::SLUG,
			'',
			'dashicons-admin-settings',
			80
		);
	}

	/**
	 * Moves the General page to the front of the hyperPress submenu, leaving the other items in order, and drops
	 * the automatic copy of the parent. Does nothing when there is no General page.
	 */
	public static function reorder(): void {
		global $submenu;

		if ( empty( $submenu[ self::SLUG ] ) || ! is_array( $submenu[ self::SLUG ] ) ) {
			return;
		}

		$general = array();
		$others  = array();
		foreach ( $submenu[ self::SLUG ] as $item ) {
			$slug = $item[2] ?? null;
			if ( GeneralPage::ID === $slug ) {
				$general[] = $item;
			} elseif ( self::SLUG !== $slug ) {
				$others[] = $item;
			}
			// Else it is the copy of the parent that WordPress adds as the first submenu item when the first child
			// registers. It has no page of its own, so it is dropped.
		}
		if ( empty( $general ) ) {
			return;
		}

		$submenu[ self::SLUG ] = array_values( array_merge( $general, $others ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}
}
