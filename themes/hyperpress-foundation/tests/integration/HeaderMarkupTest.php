<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class HeaderMarkupTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		// Core still hooks the deprecated the_block_template_skip_link() on wp_body_open.
		remove_action( 'wp_body_open', 'the_block_template_skip_link' );
	}

	private function render_header(): string {
		$this->go_to( home_url( '/' ) );
		ob_start();
		include get_template_directory() . '/header.php'; // get_header() would require_once, so only the first render in a process would output.
		return ob_get_clean();
	}

	public function test_topbar_header_keeps_the_hamburger_in_the_left_title_bar(): void {
		$html = $this->render_header();

		$this->assertMatchesRegularExpression( '/title-bar-left">\s*<button[^>]*class="menu-icon"/', $html );
		$this->assertStringNotContainsString( 'title-bar-right', $html );
	}

	public function test_drilldown_header_has_the_logo_left_and_the_hamburger_right(): void {
		set_theme_mod( 'wpt_mobile_menu_layout', 'drilldown' );

		$html = $this->render_header();

		$this->assertMatchesRegularExpression( '/title-bar-left">\s*<span class="site-mobile-logo/', $html );
		$this->assertMatchesRegularExpression( '/title-bar-right">\s*<button[^>]*class="menu-icon"/', $html );
		$this->assertStringContainsString( 'data-responsive-toggle="mobile-menu"', $html );
		$this->assertStringContainsString( 'class="mobile-menu vertical menu"', $html );
	}

	public function test_drilldown_header_falls_back_to_the_site_name_without_a_custom_logo(): void {
		set_theme_mod( 'wpt_mobile_menu_layout', 'drilldown' );
		remove_theme_mod( 'custom_logo' );

		$html = $this->render_header();

		$this->assertMatchesRegularExpression( '/site-mobile-logo[^>]*>\s*<a [^>]*rel="home"[^>]*>' . preg_quote( get_bloginfo( 'name' ), '/' ) . '<\/a>/', $html );
	}

	public function test_drilldown_header_falls_back_to_the_site_name_when_the_saved_logo_is_gone(): void {
		set_theme_mod( 'wpt_mobile_menu_layout', 'drilldown' );
		set_theme_mod( 'custom_logo', 999999 );

		$html = $this->render_header();

		$this->assertMatchesRegularExpression( '/site-mobile-logo[^>]*>\s*<a [^>]*rel="home"[^>]*>' . preg_quote( get_bloginfo( 'name' ), '/' ) . '<\/a>/', $html );
	}

	/**
	 * @dataProvider toggle_attribute_provider
	 */
	public function test_the_menu_toggle_button_names_the_menu_it_controls( string $layout, string $controls, bool $reports_expanded ): void {
		set_theme_mod( 'wpt_mobile_menu_layout', $layout );

		$html = $this->render_header();

		$this->assertMatchesRegularExpression( '/<button[^>]*class="menu-icon"[^>]*aria-controls="' . preg_quote( $controls, '/' ) . '"/', $html );
		$this->assertSame( $reports_expanded ? 1 : 0, preg_match( '/<button[^>]*class="menu-icon"[^>]*aria-expanded="false"/', $html ) );
	}

	public static function toggle_attribute_provider(): array {
		return array(
			'topbar'    => array( 'topbar', 'mobile-menu', true ),
			'drilldown' => array( 'drilldown', 'mobile-menu', true ),
			// Foundation's OffCanvas plugin manages its own trigger state, so no initial aria-expanded.
			'offcanvas' => array( 'offcanvas', 'off-canvas-menu', false ),
		);
	}

	public function test_wp_body_open_fires_right_after_the_body_tag(): void {
		add_action(
			'wp_body_open',
			static function () {
				echo '<!--body-open-marker-->';
			}
		);
		$before = did_action( 'wp_body_open' );

		$html = $this->render_header();

		$this->assertSame( $before + 1, did_action( 'wp_body_open' ) );
		$this->assertMatchesRegularExpression( '/<body[^>]*>\s*<!--body-open-marker-->/', $html );
	}

	public function test_body_classes_are_on_the_body_not_on_html(): void {
		$html = $this->render_header();

		$this->assertMatchesRegularExpression( '/<html\b[^>]*>/', $html );
		preg_match( '/<html\b[^>]*>/', $html, $html_tag );
		$this->assertStringNotContainsString( 'class=', $html_tag[0], 'the <html> element must not carry body classes' );
		$this->assertStringContainsString( 'lang=', $html_tag[0], 'language_attributes() stays on <html>' );

		preg_match( '/<body\b[^>]*>/', $html, $body_tag );
		$this->assertStringContainsString( 'class="', $body_tag[0] );
		$this->assertMatchesRegularExpression( '/class="[^"]*\btopbar\b/', $body_tag[0], 'the mobile menu layout class (library/custom-nav.php) is on <body>' );
	}

	public function test_the_admin_bar_class_lands_on_the_body(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter( 'show_admin_bar', '__return_true' );
		_wp_admin_bar_init();

		preg_match( '/<body\b[^>]*>/', $this->render_header(), $body_tag );

		$this->assertMatchesRegularExpression( '/class="[^"]*\badmin-bar\b/', $body_tag[0] );
	}}
