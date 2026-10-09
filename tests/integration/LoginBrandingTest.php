<?php
/**
 * Tests for the login page branding.
 */
class LoginBrandingTest extends WP_UnitTestCase {

	public function tear_down(): void {
		remove_theme_mod( 'custom_logo' );
		wp_deregister_style( 'login' );
		parent::tear_down();
	}

	public function test_logo_url_uses_custom_logo_attachment(): void {
		$id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		set_theme_mod( 'custom_logo', $id );

		$this->assertSame( wp_get_attachment_image_url( $id, 'full' ), hyperpress_login_logo_url() );
	}

	public function test_logo_url_falls_back_to_bundled_svg_when_unseeded(): void {
		remove_theme_mod( 'custom_logo' );

		$this->assertStringEndsWith( 'library/branding-assets/logo.svg', hyperpress_login_logo_url() );
	}

	public function test_css_targets_login_heading_link(): void {
		$css = hyperpress_login_logo_css( 'https://example.org/l.svg' );

		$this->assertStringContainsString( '.login h1 a', $css );
		$this->assertStringContainsString( 'https://example.org/l.svg', $css );
		$this->assertStringContainsString( 'background-size: contain', $css );
		$this->assertStringContainsString( 'height: 84px', $css );
	}

	public function test_enqueue_adds_inline_style(): void {
		wp_register_style( 'login', false );

		hyperpress_login_enqueue_styles();

		$after = wp_styles()->get_data( 'login', 'after' );
		$this->assertNotEmpty( $after );
		$this->assertIsArray( $after );
		$this->assertStringContainsString( '.login h1 a', implode( '', $after ) );
	}

	public function test_header_url_and_text_point_at_the_site(): void {
		$this->assertSame( home_url( '/' ), apply_filters( 'login_headerurl', 'https://wordpress.org/' ) );
		$this->assertSame( get_bloginfo( 'name' ), apply_filters( 'login_headertext', 'Powered by WordPress' ) );
	}
}
