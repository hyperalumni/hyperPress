<?php
namespace HyperPress\Tests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class CompetitionMapApiKeyTest extends WP_UnitTestCase {

	private const SETTING = 'hyperpress_competition_google_maps_api_key';

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

	public function test_customizer_has_a_sanitized_api_key_setting_and_matching_control(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$setting = $manager->get_setting( self::SETTING );
		$this->assertNotNull( $setting, 'setting is registered' );
		$this->assertSame( '', $setting->default );
		$this->assertSame( 'AbC-123_x', $setting->sanitize( "  <b>AbC-123_x</b>\n" ) );

		$control = $manager->get_control( self::SETTING );
		$this->assertNotNull( $control, 'control id equals setting id' );
		$this->assertSame( 'hyperpress_competition', $control->section );
		$this->assertNotNull( $manager->get_section( 'hyperpress_competition' ) );
	}

	public function test_map_field_uses_the_configured_key(): void {
		$this->assertSame( '', $this->map_field()['api_key'], 'blank when not configured' );

		set_theme_mod( self::SETTING, 'test-key-123' );
		$this->assertSame( 'test-key-123', $this->map_field()['api_key'] );
	}
}
