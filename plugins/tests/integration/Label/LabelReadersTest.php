<?php
namespace HyperPress\Tests\Integration\Label;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class LabelReadersTest extends WP_UnitTestCase {

	public function test_the_customizer_has_no_label_section_or_settings(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNull( $manager->get_section( 'hyperpress_label' ) );
		foreach ( array_keys( $manager->settings() ) as $id ) {
			$this->assertStringStartsNotWith( 'hyperpress_label_', (string) $id );
		}
	}

	public function test_the_dropdown_control_is_gone(): void {
		$plugin = dirname( __DIR__, 3 ) . '/hyperpress-label/src';

		$this->assertFileExists( $plugin . '/Resolver.php' );
		$this->assertFileDoesNotExist( $plugin . '/Controls/LabelDropdownControl.php' );
		$this->assertFalse( class_exists( 'HyperPress\Label\Controls\LabelDropdownControl' ) );
		$this->assertFalse( class_exists( 'HyperPress\Label\Customizer' ) );
	}
}
