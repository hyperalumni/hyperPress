<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SponsorMetaBoxesTest extends WP_UnitTestCase {

	public function test_no_box_registered_by_the_sponsor_plugin_has_empty_fields(): void {
		$found = 0;
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			if ( str_starts_with( (string) ( $box['id'] ?? '' ), 'hyperpress_sponsor' ) ) {
				++$found;
				$this->assertNotEmpty( $box['fields'] ?? array(), "{$box['id']} has fields" );
			}
		}
		$this->assertGreaterThan( 0, $found, 'the sponsor information box is registered' );
	}

	public function test_the_banner_subtitle_control_has_a_registered_section(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new \WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$control = $manager->get_control( 'hyperpress_sponsor_banner_subtitle' );
		$this->assertNotNull( $control );
		$section = $manager->get_section( $control->section );
		$this->assertNotNull( $section, 'the control\'s section is registered' );
		$this->assertSame( 135, $section->priority, 'same position in the Customizer as the Meta Box section this replaced' );
	}
}
