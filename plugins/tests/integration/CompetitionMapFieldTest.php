<?php
namespace HyperPress\Tests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class CompetitionMapFieldTest extends WP_UnitTestCase {

	private const OLD_SETTING  = 'hyperpress_competition_google_maps_api_key';
	private const SUBTITLE_SET = 'hyperpress_competition_banner_subtitle';

	private function map_field(): array {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			foreach ( $box['fields'] ?? array() as $field ) {
				if ( 'hyperpress_competition_venue_map' === ( $field['id'] ?? '' ) ) {
					return $field;
				}
			}
		}
		$this->fail( 'map field not found' );
	}

	public function test_customizer_registers_no_competition_settings(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNull( $manager->get_setting( self::OLD_SETTING ), 'key setting is gone' );
		$this->assertNull( $manager->get_control( self::OLD_SETTING ), 'key control is gone' );
		$this->assertNull( $manager->get_setting( self::SUBTITLE_SET ), 'banner subtitle setting is gone' );
		$this->assertNull( $manager->get_section( 'hyperpress_competition' ), 'section is gone' );
	}

	public function test_venue_map_field_is_an_osm_field_without_an_api_key(): void {
		$field = $this->map_field();

		$this->assertSame( 'osm', $field['type'] );
		$this->assertSame( 'hyperpress_competition_venue_address', $field['address_field'] );
		$this->assertArrayNotHasKey( 'api_key', $field );
	}
}
