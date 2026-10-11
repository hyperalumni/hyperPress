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
			$this->assertNotNull( $manager->get_setting( $setting ), "color setting $setting is registered" );
		}
		$this->assertNotNull( $manager->get_setting( 'hyperpress_home_blog_post_types' ) );
	}

	public function test_the_gear_gray_color_uses_the_american_spelling(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$setting = $manager->get_setting( 'hyperpress_gear_gray' );
		$this->assertNotNull( $setting );
		$this->assertSame( '#222222', $setting->default );
		$this->assertNull( $manager->get_setting( 'hyperpress_gear_grey' ), 'the old British name is gone' );
		$this->assertSame( 'HYPER Gear Gray', $manager->get_control( 'gear_gray' )->label );
	}

	public function test_the_root_color_variables_include_gear_gray(): void {
		set_theme_mod( 'hyperpress_gear_gray', '#123456' );

		ob_start();
		hyperpress_root_colors();
		$css = (string) ob_get_clean();

		$this->assertStringContainsString( '--hyper-gear-gray: #123456;', $css );
		$this->assertStringNotContainsString( 'gear-grey', $css );
	}

	public function test_the_activation_defaults_use_the_gray_name(): void {
		$source = (string) file_get_contents( get_template_directory() . '/library/theme-activation.php' );

		$this->assertStringContainsString( "'hyperpress_gear_gray'", $source );
		$this->assertStringNotContainsString( 'gear_grey', $source );
	}
}
