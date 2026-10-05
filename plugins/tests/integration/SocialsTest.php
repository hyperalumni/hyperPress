<?php
namespace HyperPress\Tests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class SocialsTest extends WP_UnitTestCase {

	private const URL_SETTINGS = array(
		'firstinspires',
		'thebluealliance',
		'facebook',
		'instagram',
		'youtube',
		'twitter',
		'github',
		'snapchat',
		'tiktok',
		'discord',
		'add_calendar',
	);

	private function customizer(): WP_Customize_Manager {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );
		return $manager;
	}

	public function tear_down(): void {
		foreach ( self::URL_SETTINGS as $name ) {
			remove_theme_mod( 'hyperpress_socials_' . $name );
		}
		parent::tear_down();
	}

	public function test_every_url_setting_drops_a_javascript_url(): void {
		$manager = $this->customizer();

		foreach ( self::URL_SETTINGS as $name ) {
			$setting = $manager->get_setting( 'hyperpress_socials_' . $name );
			$this->assertNotNull( $setting, $name );
			$this->assertSame( '', $setting->sanitize( 'javascript:alert(1)' ), $name );
		}
	}

	public function test_every_url_setting_keeps_an_https_url(): void {
		$manager = $this->customizer();

		foreach ( self::URL_SETTINGS as $name ) {
			$this->assertSame(
				'https://github.com/x',
				$manager->get_setting( 'hyperpress_socials_' . $name )->sanitize( 'https://github.com/x' ),
				$name
			);
		}
	}

	private function render(): string {
		ob_start();
		the_widget( 'HyperPress\Socials\Widgets\DisplaySocialsWidget', array( 'title' => 'Follow' ) );
		return (string) ob_get_clean();
	}

	public function test_external_links_carry_rel_noopener_noreferrer(): void {
		set_theme_mod( 'hyperpress_socials_github', 'https://github.com/x' );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_blank" rel="noopener noreferrer"', $html );
	}

	public function test_internal_links_open_in_self_without_rel(): void {
		set_theme_mod( 'hyperpress_socials_github', get_site_url() . '/about/' );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_self"', $html );
		$this->assertStringNotContainsString( 'rel="noopener', $html );
	}
}
