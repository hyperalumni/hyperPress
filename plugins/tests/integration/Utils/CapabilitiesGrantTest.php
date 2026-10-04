<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Capabilities;
use WP_UnitTestCase;

final class CapabilitiesGrantTest extends WP_UnitTestCase {

	private const OPTION = 'hyperpress_test_caps_version';

	public function set_up(): void {
		parent::set_up();
		register_post_type(
			'hyper_gizmo',
			array(
				'public'          => true,
				'capability_type' => array( 'hyper_gizmo', 'hyper_gizmos' ),
				'map_meta_cap'    => true,
			)
		);
		delete_option( self::OPTION );
		foreach ( Capabilities::ROLES as $role ) {
			get_role( $role )->remove_cap( 'edit_hyper_gizmos' );
		}
	}

	public function tear_down(): void {
		unregister_post_type( 'hyper_gizmo' );
		delete_option( self::OPTION );
		parent::tear_down();
	}

	public function test_grant_gives_administrator_and_editor_the_caps_and_not_subscriber(): void {
		Capabilities::grant( array( 'hyper_gizmo' ) );
		$this->assertTrue( get_role( 'administrator' )->has_cap( 'edit_hyper_gizmos' ) );
		$this->assertTrue( get_role( 'editor' )->has_cap( 'edit_hyper_gizmos' ) );
		$this->assertFalse( get_role( 'subscriber' )->has_cap( 'edit_hyper_gizmos' ) );
	}

	public function test_grant_is_idempotent(): void {
		Capabilities::grant( array( 'hyper_gizmo' ) );
		$before = get_role( 'editor' )->capabilities;
		Capabilities::grant( array( 'hyper_gizmo' ) );
		$this->assertSame( $before, get_role( 'editor' )->capabilities );
	}

	public function test_ensure_grants_once_then_skips_until_the_version_changes(): void {
		Capabilities::ensure( self::OPTION, '1.0.0', array( 'hyper_gizmo' ) );
		$this->assertSame( '1.0.0', get_option( self::OPTION ) );
		$this->assertTrue( get_role( 'editor' )->has_cap( 'edit_hyper_gizmos' ) );

		get_role( 'editor' )->remove_cap( 'edit_hyper_gizmos' );
		Capabilities::ensure( self::OPTION, '1.0.0', array( 'hyper_gizmo' ) );
		$this->assertFalse( get_role( 'editor' )->has_cap( 'edit_hyper_gizmos' ), 'same version must not regrant' );

		Capabilities::ensure( self::OPTION, '1.1.0', array( 'hyper_gizmo' ) );
		$this->assertTrue( get_role( 'editor' )->has_cap( 'edit_hyper_gizmos' ), 'stale version must regrant' );
		$this->assertSame( '1.1.0', get_option( self::OPTION ) );
	}

	public function test_grant_ignores_unregistered_post_types(): void {
		Capabilities::grant( array( 'hyper_does_not_exist' ) );
		$this->assertTrue( true ); // No exception, no warning.
	}
}
