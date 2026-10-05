<?php
namespace HyperPress\ThemeTests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class MobileMenuLayoutTest extends WP_UnitTestCase {

	private function manager(): WP_Customize_Manager {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );
		return $manager;
	}

	private function translate_everything(): void {
		add_filter(
			'gettext',
			static function ( $translation, $text, $domain ) {
				return 'hyperpress' === $domain ? 'TRANSLATED' : $translation;
			},
			10,
			3
		);
	}

	public function test_layout_setting_defaults_to_topbar(): void {
		$setting = $this->manager()->get_setting( 'wpt_mobile_menu_layout' );

		$this->assertNotNull( $setting );
		$this->assertSame( 'topbar', $setting->default );
	}

	public function test_layout_setting_only_accepts_known_layouts(): void {
		$setting = $this->manager()->get_setting( 'wpt_mobile_menu_layout' );

		$this->assertSame( 'offcanvas', $setting->sanitize( 'offcanvas' ) );
		$this->assertSame( 'topbar', $setting->sanitize( 'topbar' ) );
		$this->assertSame( 'topbar', $setting->sanitize( 'x' ) );
		$this->assertSame( 'topbar', $setting->sanitize( array() ) );
	}

	public function test_translation_does_not_change_the_layout_default(): void {
		$this->translate_everything();

		$setting = $this->manager()->get_setting( 'wpt_mobile_menu_layout' );

		$this->assertSame( 'topbar', $setting->default );
	}

	public function test_translation_does_not_change_the_mobile_menu_lookup(): void {
		$menu_id = wp_create_nav_menu( 'mobile-nav' );
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => 'Mobile Item',
				'menu-item-url'    => 'http://example.org/mobile-item',
				'menu-item-status' => 'publish',
			)
		);
		$this->translate_everything();

		ob_start();
		hyperpress_mobile_nav();
		$html = ob_get_clean();

		$this->assertStringContainsString( 'Mobile Item', $html );
	}
}
