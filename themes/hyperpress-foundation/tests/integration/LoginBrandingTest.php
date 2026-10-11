<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * Tests for the login page branding.
 */
final class LoginBrandingTest extends WP_UnitTestCase {

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

	public function test_logo_url_for_svg_attachment_without_metadata(): void {
		$uploads = wp_upload_dir();
		$file    = trailingslashit( $uploads['path'] ) . 'login-branding-test-logo.svg';
		copy( get_template_directory() . '/library/branding-assets/logo.svg', $file );
		$id = wp_insert_attachment(
			array(
				'post_mime_type' => 'image/svg+xml',
				'post_title'     => 'Login branding test logo',
				'post_status'    => 'inherit',
			),
			$file
		);
		set_theme_mod( 'custom_logo', $id );

		$url = hyperpress_login_logo_url();

		$this->assertNotSame( '', $url );
		$this->assertSame( wp_get_attachment_url( $id ), $url );
	}

	public function test_css_cannot_break_out_of_the_declaration(): void {
		$css = hyperpress_login_logo_css( 'https://example.org/x.svg);}</style><script>alert(1)</script>"; color: red; a{b:url(x' );

		$this->assertStringNotContainsString( '</style>', $css );
		$this->assertStringNotContainsString( '<script', $css );
		$this->assertSame( 1, substr_count( $css, 'background-image' ) );
		$this->assertSame( 1, substr_count( $css, '{' ) );
		$this->assertSame( 1, substr_count( $css, '}' ) );

		$this->assertSame( 1, preg_match( '/background-image: url\( "([^"]*)" \); background-size/', $css, $m ) );
		$this->assertStringNotContainsString( '"', $m[1] );
		$this->assertStringStartsWith( 'https://example.org/x.svg', $m[1] );
	}

	public function test_css_quotes_the_url_and_keeps_ampersands_raw(): void {
		$css = hyperpress_login_logo_css( 'https://example.org/l.svg?a=1&b=2' );

		$this->assertStringContainsString( 'background-image: url( "https://example.org/l.svg?a=1&b=2" );', $css );
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
