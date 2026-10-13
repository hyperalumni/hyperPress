<?php
namespace HyperPress\Tests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class CompetitionMapApiKeyTest extends WP_UnitTestCase {

	private const OPTION        = 'hyperpress_general';
	private const KEY_FIELD     = 'hyperpress_general_google_maps_api_key';
	private const OLD_SETTING   = 'hyperpress_competition_google_maps_api_key';
	private const SUBTITLE_SET  = 'hyperpress_competition_banner_subtitle';

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

	public function test_customizer_no_longer_registers_the_api_key_but_keeps_the_banner_subtitle(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNull( $manager->get_setting( self::OLD_SETTING ), 'key setting is gone' );
		$this->assertNull( $manager->get_control( self::OLD_SETTING ), 'key control is gone' );
		$this->assertNotNull( $manager->get_setting( self::SUBTITLE_SET ), 'banner subtitle setting remains' );
		$this->assertNotNull( $manager->get_section( 'hyperpress_competition' ), 'section remains' );
	}

	public function test_map_field_uses_the_key_saved_in_the_general_setting(): void {
		delete_option( self::OPTION );
		$this->assertSame( '', $this->map_field()['api_key'], 'blank when not configured' );

		update_option( self::OPTION, array( self::KEY_FIELD => 'test-key-123' ) );
		$this->assertSame( 'test-key-123', $this->map_field()['api_key'] );
	}

	public function test_map_field_ignores_the_old_theme_mod(): void {
		delete_option( self::OPTION );
		set_theme_mod( self::OLD_SETTING, 'old-mod-key' );
		$this->assertSame( '', $this->map_field()['api_key'] );
	}
}
