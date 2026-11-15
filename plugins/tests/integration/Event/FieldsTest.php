<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Fields;
use HyperPress\Event\Plugin;
use HyperPress\Event\Settings;
use HyperPress\Event\SettingsPage;
use WP_UnitTestCase;

final class FieldsTest extends WP_UnitTestCase {

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_event_woocommerce_active' );
		remove_all_filters( 'hyperpress_event_nextgen_active' );
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	private function boxes(): array {
		$boxes = array();
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			$boxes[ $box['id'] ] = $box;
		}

		return $boxes;
	}

	/**
	 * @param array<string,mixed> $box Meta box.
	 * @return array<string,array<string,mixed>>
	 */
	private function fields( array $box ): array {
		$fields = array();
		foreach ( $box['fields'] as $field ) {
			$fields[ $field['id'] ] = $field;
		}

		return $fields;
	}

	public function test_event_box_has_the_six_common_fields(): void {
		$boxes = $this->boxes();
		$this->assertArrayHasKey( 'hyperpress_event_box', $boxes );
		$this->assertSame( array( Plugin::POST_TYPE ), $boxes['hyperpress_event_box']['post_types'] );

		$fields = $this->fields( $boxes['hyperpress_event_box'] );
		foreach ( array( Fields::START, Fields::END, Fields::VENUE_NAME, Fields::VENUE_ADDRESS, Fields::SUBTITLE, Fields::BANNER_COLOR ) as $id ) {
			$this->assertArrayHasKey( $id, $fields, $id );
		}
		$this->assertSame( 'datetime', $fields[ Fields::START ]['type'] );
		$this->assertSame( 'Y-m-d H:i', $fields[ Fields::START ]['save_format'] );
		$this->assertSame( 'color', $fields[ Fields::BANNER_COLOR ]['type'] );
	}

	public function test_venue_map_field_is_an_osm_field_linked_to_the_address(): void {
		$box = $this->boxes()['hyperpress_event_box'];
		$ids = array_column( $box['fields'], 'id' );

		$position = array_search( Fields::VENUE_MAP, $ids, true );
		$this->assertNotFalse( $position );
		$this->assertSame( Fields::VENUE_ADDRESS, $ids[ $position - 1 ] );

		$field = $box['fields'][ $position ];
		$this->assertSame( 'osm', $field['type'] );
		$this->assertSame( Fields::VENUE_ADDRESS, $field['address_field'] );
		$this->assertArrayNotHasKey( 'api_key', $field );
		$this->assertArrayNotHasKey( 'std', $field );
	}

	public function test_map_location_keeps_only_lat_lng_zoom(): void {
		$this->assertSame( '42.1,-71.2,15', Fields::map_location( '42.1,-71.2,15' ) );
		$this->assertSame( '42.25,-71.0', Fields::map_location( '42.25,-71.0' ) );
		$this->assertSame( '', Fields::map_location( '<script>alert(1)</script>' ) );
		$this->assertSame( '', Fields::map_location( '42.25;-71.0' ) );
		$this->assertSame( '', Fields::map_location( '42.25,-71.0,15,extra' ) );
		$this->assertSame( '', Fields::map_location( array( '42.25,-71.0' ) ) );
	}

	public function test_products_and_galleries_are_absent_by_default(): void {
		$fields = $this->fields( $this->boxes()['hyperpress_event_box'] );

		$this->assertArrayNotHasKey( Fields::PRODUCTS, $fields );
		$this->assertArrayNotHasKey( Fields::GALLERIES, $fields );
	}

	public function test_products_present_when_woocommerce_is_active(): void {
		add_filter( 'hyperpress_event_woocommerce_active', '__return_true' );
		$fields = $this->fields( $this->boxes()['hyperpress_event_box'] );

		$this->assertArrayHasKey( Fields::PRODUCTS, $fields );
		$this->assertSame( 'post', $fields[ Fields::PRODUCTS ]['type'] );
		$this->assertSame( 'product', $fields[ Fields::PRODUCTS ]['post_type'] );
		$this->assertTrue( $fields[ Fields::PRODUCTS ]['multiple'] );
		$this->assertArrayNotHasKey( Fields::GALLERIES, $fields );
	}

	public function test_galleries_present_when_nextgen_is_active(): void {
		add_filter( 'hyperpress_event_nextgen_active', '__return_true' );
		set_current_screen( 'edit-' . Plugin::POST_TYPE );
		$fields = $this->fields( $this->boxes()['hyperpress_event_box'] );

		$this->assertArrayHasKey( Fields::GALLERIES, $fields );
		$this->assertSame( 'select_advanced', $fields[ Fields::GALLERIES ]['type'] );
		$this->assertTrue( $fields[ Fields::GALLERIES ]['multiple'] );
		$this->assertIsArray( $fields[ Fields::GALLERIES ]['options'] );
		$this->assertArrayNotHasKey( Fields::PRODUCTS, $fields );
	}

	public function test_gallery_options_are_only_built_in_the_admin(): void {
		add_filter( 'hyperpress_event_nextgen_active', '__return_true' );
		$this->assertFalse( is_admin() );

		$fields = $this->fields( $this->boxes()['hyperpress_event_box'] );

		$this->assertArrayHasKey( Fields::GALLERIES, $fields );
		$this->assertSame( array(), $fields[ Fields::GALLERIES ]['options'] );
	}

	public function test_every_field_id_follows_the_identifier_convention(): void {
		add_filter( 'hyperpress_event_woocommerce_active', '__return_true' );
		add_filter( 'hyperpress_event_nextgen_active', '__return_true' );

		foreach ( $this->boxes() as $id => $box ) {
			if ( 0 !== strpos( $id, 'hyperpress_event' ) ) {
				continue;
			}
			$this->assertMatchesRegularExpression( '/^hyperpress_[a-z0-9_]+$/', $id );
			foreach ( $box['fields'] as $field ) {
				$this->assertMatchesRegularExpression( '/^hyperpress_[a-z0-9_]+$/', $field['id'] );
			}
		}
	}

	public function test_settings_box_has_the_six_settings_fields(): void {
		$boxes = $this->boxes();
		$this->assertArrayHasKey( 'hyperpress_event_settings_box', $boxes );
		$this->assertSame( SettingsPage::ID, $boxes['hyperpress_event_settings_box']['settings_pages'] );

		$fields = $this->fields( $boxes['hyperpress_event_settings_box'] );
		$this->assertSame(
			array(
				Settings::DEFAULT_BANNER_COLOR,
				Settings::FALLBACK_SUBTITLE,
				Settings::HUB_PAGE,
				Settings::FRONTPAGE,
				Settings::FRONTPAGE_LEAD_DAYS,
				Settings::COUNTDOWN_PAST_ZERO,
			),
			array_keys( $fields )
		);
		$this->assertSame( 'color', $fields[ Settings::DEFAULT_BANNER_COLOR ]['type'] );
		$this->assertSame( 'sanitize_text_field', $fields[ Settings::FALLBACK_SUBTITLE ]['sanitize_callback'] );
		$this->assertSame( 'post', $fields[ Settings::HUB_PAGE ]['type'] );
		$this->assertSame( 'page', $fields[ Settings::HUB_PAGE ]['post_type'] );
		$this->assertSame( 'checkbox', $fields[ Settings::FRONTPAGE ]['type'] );
		$this->assertSame( 'checkbox', $fields[ Settings::COUNTDOWN_PAST_ZERO ]['type'] );
		$this->assertSame( 'number', $fields[ Settings::FRONTPAGE_LEAD_DAYS ]['type'] );
		$this->assertSame( 0, $fields[ Settings::FRONTPAGE_LEAD_DAYS ]['min'] );
		$this->assertSame( 365, $fields[ Settings::FRONTPAGE_LEAD_DAYS ]['max'] );
		$this->assertSame(
			array( Settings::class, 'sanitize_lead_days' ),
			$fields[ Settings::FRONTPAGE_LEAD_DAYS ]['sanitize_callback']
		);
	}

	public function test_sanitize_lead_days(): void {
		$this->assertSame( '0', Settings::sanitize_lead_days( '0' ) );
		$this->assertSame( '21', Settings::sanitize_lead_days( ' 21 ' ) );
		$this->assertSame( '365', Settings::sanitize_lead_days( 365 ) );
		$this->assertSame( '', Settings::sanitize_lead_days( '366' ) );
		$this->assertSame( '', Settings::sanitize_lead_days( '-1' ) );
		$this->assertSame( '', Settings::sanitize_lead_days( '1.5' ) );
		$this->assertSame( '', Settings::sanitize_lead_days( 'abc' ) );
		$this->assertSame( '', Settings::sanitize_lead_days( '' ) );
		$this->assertSame( '', Settings::sanitize_lead_days( null ) );
		$this->assertSame( '', Settings::sanitize_lead_days( array( '5' ) ) );
	}
}
