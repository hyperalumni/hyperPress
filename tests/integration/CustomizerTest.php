<?php
namespace HyperPress\ThemeTests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class CustomizerTest extends WP_UnitTestCase {
	public function test_customizer_registers_without_errors(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		foreach ( array( 'hyperpress_hyper_green', 'hyperpress_hyper_orange', 'hyperpress_hyper_red', 'hyperpress_gear_blue' ) as $setting ) {
			$this->assertNotNull( $manager->get_setting( $setting ), "colour setting $setting is registered" );
		}
		$this->assertNotNull( $manager->get_setting( 'hyperpress_home_blog_post_types' ) );
	}
}
