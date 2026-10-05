<?php
namespace HyperPress\Tests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class SponsorSettingsTest extends WP_UnitTestCase {

	private const SUBTITLE = 'hyperpress_sponsor_banner_subtitle';

	private function sanitizer_for( string $field_id ): callable {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			foreach ( $box['fields'] ?? array() as $field ) {
				if ( $field_id === ( $field['id'] ?? '' ) ) {
					$this->assertArrayHasKey( 'sanitize_callback', $field, "$field_id has an explicit sanitize_callback" );
					$this->assertIsCallable( $field['sanitize_callback'], "$field_id sanitize_callback is a real function" );
					return $field['sanitize_callback'];
				}
			}
		}
		$this->fail( "$field_id not found" );
	}

	public function test_website_drops_hostile_schemes_and_keeps_https(): void {
		$callback = $this->sanitizer_for( 'hyperpress_sponsor_website' );

		$this->assertSame( '', $callback( 'javascript:alert(1)' ) );
		$this->assertSame( 'https://example.com/sponsor', $callback( 'https://example.com/sponsor' ) );
	}

	public function test_description_strips_scripts_and_keeps_allowed_markup(): void {
		$callback = $this->sanitizer_for( 'hyperpress_sponsor_description' );

		$this->assertSame( 'x<b>ok</b>', $callback( '<script>x</script><b>ok</b>' ) );
	}

	public function test_banner_subtitle_setting_strips_scripts_and_keeps_allowed_markup(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$setting = $manager->get_setting( self::SUBTITLE );
		$this->assertNotNull( $setting, 'setting is registered' );
		$this->assertSame( 'x<b>ok</b>', $setting->sanitize( '<script>x</script><b>ok</b>' ) );
	}
}
