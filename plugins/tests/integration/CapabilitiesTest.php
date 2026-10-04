<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

final class CapabilitiesTest extends WP_UnitTestCase {

	public static function post_types(): array {
		$cases = array();
		foreach ( array_keys( Convention::POST_TYPES ) as $key ) {
			$cases[ $key ] = array( $key );
		}
		return $cases;
	}

	/** @return string[] Primitive capabilities that are not meta caps. */
	private function primitive_caps( string $post_type ): array {
		$pt = get_post_type_object( $post_type );
		$this->assertNotNull( $pt, "$post_type is not registered" );
		$caps = (array) $pt->cap;
		// Meta caps are mapped, not stored on roles.
		unset( $caps['edit_post'], $caps['read_post'], $caps['delete_post'] );
		return array_values( array_unique( $caps ) );
	}

	/** @dataProvider post_types */
	public function test_capabilities_are_specific_to_the_post_type( string $post_type ): void {
		$pt = get_post_type_object( $post_type );
		$this->assertNotNull( $pt, "$post_type is not registered" );
		$this->assertTrue( $pt->map_meta_cap, "$post_type must set map_meta_cap" );
		$this->assertSame( "edit_{$post_type}s", $pt->cap->edit_posts, "$post_type edit_posts cap" );
	}

	/** @dataProvider post_types */
	public function test_administrator_and_editor_hold_every_capability( string $post_type ): void {
		foreach ( array( 'administrator', 'editor' ) as $role_name ) {
			$role = get_role( $role_name );
			foreach ( $this->primitive_caps( $post_type ) as $cap ) {
				$this->assertTrue( $role->has_cap( $cap ), "$role_name lacks $cap for $post_type" );
			}
		}
	}

	/** @dataProvider post_types */
	public function test_subscriber_holds_none( string $post_type ): void {
		$role = get_role( 'subscriber' );
		foreach ( $this->primitive_caps( $post_type ) as $cap ) {
			if ( 'read' === $cap ) {
				continue;
			}
			$this->assertFalse( $role->has_cap( $cap ), "subscriber must not hold $cap for $post_type" );
		}
	}
}
