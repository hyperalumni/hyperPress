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

	public function test_site_icon_row_present_removal_restores_default_then_new_icon_wins(): void {
		hyperpress_seed_default_branding();
		$icon     = hyperpress_default_branding_id( 'icon' );
		$other_id = $this->factory()->attachment->create();
		$another  = $this->factory()->attachment->create();

		update_option( 'site_icon', $other_id );
		update_option( 'site_icon', '' );
		$this->assertSame( $icon, (int) get_option( 'site_icon' ) );

		update_option( 'site_icon', $another );
		$this->assertSame( $another, (int) get_option( 'site_icon' ) );
	}

	public function test_zero_and_null_custom_logo_resolve_to_default(): void {
		hyperpress_seed_default_branding();
		$logo = hyperpress_default_branding_id( 'logo' );

		set_theme_mod( 'custom_logo', 0 );
		$this->assertSame( $logo, (int) get_theme_mod( 'custom_logo' ) );

		set_theme_mod( 'custom_logo', null );
		$this->assertSame( $logo, (int) get_theme_mod( 'custom_logo' ) );
	}

	public function test_default_logo_survives_customizer_preview_removal(): void {
		hyperpress_seed_default_branding();
		$logo = hyperpress_default_branding_id( 'logo' );
		remove_theme_mod( 'custom_logo' );

		// Core's WP_Customize_Setting::preview() registers its filter at priority 10 and returns the (empty) post value.
		$preview = static function (): string {
			return '';
		};
		add_filter( 'theme_mod_custom_logo', $preview );

		try {
			$this->assertSame( $logo, (int) get_theme_mod( 'custom_logo' ) );
		} finally {
			remove_filter( 'theme_mod_custom_logo', $preview );
		}
	}

	public function test_favicon_fallback_uses_template_directory_uri(): void {
		hyperpress_seed_default_branding();
		delete_option( 'site_icon' );

		ob_start();
		hyperpress_default_icon_svg_link();
		$link = (string) ob_get_clean();

		$this->assertStringContainsString( get_template_directory_uri() . '/library/branding-assets/icon.svg', $link );
	}

	public function test_login_logo_fallback_uses_template_directory_uri(): void {
		remove_theme_mod( 'custom_logo' );

		$this->assertSame( get_template_directory_uri() . '/library/branding-assets/logo.svg', hyperpress_login_logo_url() );
	}
}
