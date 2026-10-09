<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class DefaultBrandingFaviconTest extends WP_UnitTestCase {

	private function capture_link(): string {
		ob_start();
		hyperpress_default_icon_svg_link();
		return (string) ob_get_clean();
	}

	public function test_link_printed_while_default_icon_in_effect(): void {
		hyperpress_seed_default_branding();
		delete_option( 'site_icon' );

		$output = $this->capture_link();

		$this->assertStringContainsString( 'type="image/svg+xml"', $output );
		$this->assertStringContainsString( 'branding-assets/icon.svg', $output );
	}

	public function test_link_omitted_when_user_icon_set(): void {
		hyperpress_seed_default_branding();
		$other_id = $this->factory()->attachment->create();
		update_option( 'site_icon', $other_id );

		$this->assertSame( '', $this->capture_link() );
	}

	public function test_link_omitted_when_not_seeded(): void {
		$this->assertSame( '', $this->capture_link() );
	}

	public function test_link_hooked_to_head_actions(): void {
		$this->assertNotFalse( has_action( 'wp_head', 'hyperpress_default_icon_svg_link' ) );
		$this->assertNotFalse( has_action( 'login_head', 'hyperpress_default_icon_svg_link' ) );
	}
}
