<?php
namespace HyperPress\Tests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class NewsletterCustomizerTest extends WP_UnitTestCase {

	private const MOD     = 'hyperpress_newsletter_banner_subtitle';
	private const DEFAULT = 'Updates on Team HYPER from our Marketing Team';

	private function customizer(): WP_Customize_Manager {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );
		return $manager;
	}

	public function tear_down(): void {
		remove_theme_mod( self::MOD );
		parent::tear_down();
	}

	public function test_the_subtitle_control_sits_in_a_registered_section(): void {
		$manager = $this->customizer();
		$section = $manager->get_section( 'hyperpress_newsletter' );

		$this->assertNotNull( $section );
		$this->assertSame( 'Newsletters', $section->title );
		$this->assertSame( 107, $section->priority );
		$this->assertSame( 'hyperpress_newsletter', $manager->get_control( self::MOD )->section );
	}

	public function test_the_default_applies_on_the_front_end_until_a_value_is_stored(): void {
		remove_theme_mod( self::MOD );
		$this->assertSame( self::DEFAULT, get_theme_mod( self::MOD ) );

		set_theme_mod( self::MOD, 'Custom subtitle' );
		$this->assertSame( 'Custom subtitle', get_theme_mod( self::MOD ) );
	}

	public function test_the_setting_sanitises_scripts_away(): void {
		$setting = $this->customizer()->get_setting( self::MOD );

		$sanitized = $setting->sanitize( '<script>x</script><b>ok</b>' );

		$this->assertStringNotContainsString( '<script', $sanitized );
		$this->assertStringContainsString( '<b>ok</b>', $sanitized );
	}
}
