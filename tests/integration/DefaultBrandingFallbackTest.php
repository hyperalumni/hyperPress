<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class DefaultBrandingFallbackTest extends WP_UnitTestCase {

	public function test_empty_custom_logo_returns_default(): void {
		hyperpress_seed_default_branding();
		remove_theme_mod( 'custom_logo' );

		$this->assertSame( hyperpress_default_branding_id( 'logo' ), (int) get_theme_mod( 'custom_logo' ) );
		$this->assertTrue( has_custom_logo() );
	}

	public function test_custom_logo_set_to_empty_string_returns_default(): void {
		hyperpress_seed_default_branding();
		set_theme_mod( 'custom_logo', '' );

		$this->assertSame( hyperpress_default_branding_id( 'logo' ), (int) get_theme_mod( 'custom_logo' ) );
		$this->assertTrue( has_custom_logo() );
	}

	public function test_user_logo_wins(): void {
		hyperpress_seed_default_branding();
		$id = $this->factory()->attachment->create();
		set_theme_mod( 'custom_logo', $id );

		$this->assertSame( $id, (int) get_theme_mod( 'custom_logo' ) );
	}

	public function test_site_icon_default_when_option_deleted_or_empty(): void {
		hyperpress_seed_default_branding();
		$icon = hyperpress_default_branding_id( 'icon' );

		delete_option( 'site_icon' );
		$this->assertSame( $icon, (int) get_option( 'site_icon' ) );
		$this->assertTrue( has_site_icon() );

		update_option( 'site_icon', '' );
		$this->assertSame( $icon, (int) get_option( 'site_icon' ) );
		$this->assertTrue( has_site_icon() );
	}

	public function test_user_site_icon_wins(): void {
		hyperpress_seed_default_branding();
		$other_id = $this->factory()->attachment->create();
		update_option( 'site_icon', $other_id );

		$this->assertSame( $other_id, (int) get_option( 'site_icon' ) );
	}

	public function test_unseeded_logo_falls_back_to_bundled_svg(): void {
		remove_theme_mod( 'custom_logo' );
		$html = get_custom_logo();

		$this->assertStringContainsString( 'custom-logo-link', $html );
		$this->assertStringContainsString( 'class="custom-logo"', $html );
		$this->assertStringContainsString( 'branding-assets/logo.svg', $html );
	}

	public function test_seeded_logo_uses_attachment_not_fallback(): void {
		hyperpress_seed_default_branding();
		$html = get_custom_logo();

		$this->assertStringNotContainsString( 'branding-assets/logo.svg', $html );
		$this->assertStringContainsString( (string) wp_get_attachment_url( hyperpress_default_branding_id( 'logo' ) ), $html );
	}
}
