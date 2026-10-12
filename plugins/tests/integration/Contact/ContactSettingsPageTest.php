<?php
namespace HyperPress\Tests\Integration\Contact;

use HyperPress\Contact\Addresses;
use WP_UnitTestCase;

final class ContactSettingsPageTest extends WP_UnitTestCase {

	/**
	 * The two Contact boxes from the Meta Box filter.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function boxes(): array {
		$found = array();
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			if ( str_starts_with( (string) ( $box['id'] ?? '' ), 'hyperpress_contact' ) ) {
				$found[] = $box;
			}
		}
		return $found;
	}

	/**
	 * Finds a field by id across the Contact boxes.
	 *
	 * @param string $id Field id.
	 * @return array<string,mixed>|null
	 */
	private function field( string $id ): ?array {
		foreach ( $this->boxes() as $box ) {
			foreach ( $box['fields'] as $field ) {
				if ( ( $field['id'] ?? '' ) === $id ) {
					return $field;
				}
			}
		}
		return null;
	}

	public function test_the_settings_page_is_registered_under_the_hyperpress_menu(): void {
		$pages = array_values(
			array_filter(
				apply_filters( 'mb_settings_pages', array() ),
				static fn( $page ) => 'hyperpress_contact' === ( $page['id'] ?? '' )
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_contact', $pages[0]['option_name'] );
		$this->assertSame( 'Contact', $pages[0]['menu_title'] );
		$this->assertSame( 'hyperpress', $pages[0]['parent'] );
		$this->assertSame( 'manage_options', $pages[0]['capability'] );
	}

	public function test_two_boxes_are_attached_to_the_settings_page_with_fields(): void {
		$boxes = $this->boxes();

		$this->assertCount( 2, $boxes );
		foreach ( $boxes as $box ) {
			$this->assertSame( 'hyperpress_contact', $box['settings_pages'] );
			$this->assertNotEmpty( $box['fields'] );
		}
	}

	public function test_the_address_group_is_a_sortable_cloneable_group(): void {
		$group = $this->field( 'hyperpress_contact_addresses' );

		$this->assertNotNull( $group );
		$this->assertSame( 'group', $group['type'] );
		$this->assertTrue( $group['clone'] );
		$this->assertTrue( $group['sort_clone'] );
		$this->assertSame( array( Addresses::class, 'sanitize' ), $group['sanitize_callback'] );
		$this->assertSame(
			array( 'heading', 'slug', 'name', 'extra', 'street', 'city', 'state', 'zip', 'phone' ),
			array_column( $group['fields'], 'id' )
		);
	}

	public function test_the_map_field_uses_the_api_key_and_a_map_address_field(): void {
		update_option( 'hyperpress_google_maps_api_key', 'abc' );

		$map = $this->field( 'hyperpress_contact_map' );

		$this->assertNotNull( $map );
		$this->assertSame( 'map', $map['type'] );
		$this->assertSame( 'hyperpress_contact_map_address', $map['address_field'] );
		$this->assertSame( '42.2518999,-71.0007911,17', $map['std'] );
		$this->assertSame( 'abc', $map['api_key'] );

		$address = $this->field( 'hyperpress_contact_map_address' );
		$this->assertNotNull( $address );
		$this->assertSame( 'text', $address['type'] );
	}
}
