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

	public function test_layout_setting_accepts_drilldown(): void {
		$setting = $this->manager()->get_setting( 'wpt_mobile_menu_layout' );

		$this->assertSame( 'drilldown', $setting->sanitize( 'drilldown' ) );
	}

	public function test_layout_setting_offers_a_drilldown_choice(): void {
		$control = $this->manager()->get_control( 'mobile_menu_layout' );

		$this->assertArrayHasKey( 'drilldown', $control->choices );
	}

	public function test_layout_helper_defaults_to_topbar(): void {
		$this->assertSame( 'topbar', hyperpress_mobile_menu_layout() );
	}

	public function test_layout_helper_returns_the_saved_layout(): void {
		set_theme_mod( 'wpt_mobile_menu_layout', 'drilldown' );
		$this->assertSame( 'drilldown', hyperpress_mobile_menu_layout() );

		set_theme_mod( 'wpt_mobile_menu_layout', 'offcanvas' );
		$this->assertSame( 'offcanvas', hyperpress_mobile_menu_layout() );

		set_theme_mod( 'wpt_mobile_menu_layout', 'nonsense' );
		$this->assertSame( 'topbar', hyperpress_mobile_menu_layout() );
	}

	public function test_body_class_for_drilldown_does_not_collide_with_foundation(): void {
		set_theme_mod( 'wpt_mobile_menu_layout', 'drilldown' );

		$classes = mobile_nav_class( array() );

		$this->assertContains( 'mobile-drilldown', $classes );
		// Foundation styles every `.drilldown a`, so a body class of exactly `drilldown` restyles all links.
		$this->assertNotContains( 'drilldown', $classes );
		$this->assertNotContains( 'topbar', $classes );
	}

	public function test_drilldown_uses_the_topbar_toggle_and_menu_id(): void {
		set_theme_mod( 'wpt_mobile_menu_layout', 'drilldown' );

		ob_start();
		hyperpress_title_bar_responsive_toggle();
		$toggle = ob_get_clean();
		ob_start();
		hyperpress_mobile_menu_id();
		$id = ob_get_clean();

		$this->assertSame( 'data-responsive-toggle="mobile-menu"', $toggle );
		$this->assertSame( 'mobile-menu', $id );
	}

	private function mobile_nav_html(): string {
		$menu_id   = wp_create_nav_menu( 'mobile-nav' );
		$parent_id = wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'  => 'Parent Item',
				'menu-item-url'    => 'http://example.org/parent',
				'menu-item-status' => 'publish',
			)
		);
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => 'Child Item',
				'menu-item-url'       => 'http://example.org/child',
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent_id,
			)
		);

		ob_start();
		hyperpress_mobile_nav();
		return ob_get_clean();
	}

	public function test_drilldown_layout_renders_drilldown_attributes(): void {
		set_theme_mod( 'wpt_mobile_menu_layout', 'drilldown' );

		$html = $this->mobile_nav_html();

		$this->assertStringContainsString( 'data-drilldown', $html );
		$this->assertStringContainsString( 'data-parent-link="true"', $html );
		$this->assertStringContainsString( 'data-auto-height="true"', $html );
		$this->assertStringContainsString( '>Back</a>', $html );
		$this->assertStringNotContainsString( 'data-accordion-menu', $html );
		$this->assertStringContainsString( 'Child Item', $html );
	}

	public function test_topbar_layout_still_renders_the_accordion(): void {
		$html = $this->mobile_nav_html();

		$this->assertStringContainsString( 'data-accordion-menu', $html );
		$this->assertStringNotContainsString( 'data-drilldown', $html );
	}

	public function test_drilldown_back_label_is_translatable(): void {
		set_theme_mod( 'wpt_mobile_menu_layout', 'drilldown' );
		$this->translate_everything();

		$html = $this->mobile_nav_html();

		$this->assertStringContainsString( '>TRANSLATED</a>', $html );
		$this->assertStringNotContainsString( '>Back</a>', $html );
	}

	public function test_drilldown_without_a_mobile_menu_renders_nothing(): void {
		set_theme_mod( 'wpt_mobile_menu_layout', 'drilldown' );

		ob_start();
		hyperpress_mobile_nav();

		$this->assertSame( '', ob_get_clean() );
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
