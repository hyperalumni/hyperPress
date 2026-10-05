<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class CdnAssetsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles']  = null;
	}

	private function front_end_markup(): string {
		do_action( 'wp_enqueue_scripts' );
		ob_start();
		wp_print_styles();
		wp_print_head_scripts();
		wp_print_footer_scripts();
		return (string) ob_get_clean();
	}

	public function test_every_cdn_asset_on_the_front_end_has_subresource_integrity(): void {
		$html = $this->front_end_markup();

		preg_match_all( '~<(?:script|link)\b[^>]*cdnjs\.cloudflare\.com[^>]*>~', $html, $tags );
		$this->assertGreaterThanOrEqual( 3, count( $tags[0] ), 'jQuery, Font Awesome JS and CSS are loaded from the CDN' );

		foreach ( $tags[0] as $tag ) {
			$this->assertMatchesRegularExpression( '~\sintegrity=[\'"]sha512-[A-Za-z0-9+/=]+[\'"]~', $tag, "no integrity on: $tag" );
			$this->assertMatchesRegularExpression( '~\scrossorigin=[\'"]anonymous[\'"]~', $tag, "no crossorigin on: $tag" );
		}
	}

	public function test_local_assets_get_no_integrity_attribute(): void {
		$html = $this->front_end_markup();
		preg_match_all( '~<(?:script|link)\b[^>]*/dist/assets/[^>]*>~', $html, $tags );
		foreach ( $tags[0] as $tag ) {
			$this->assertStringNotContainsString( 'integrity=', $tag );
		}
	}

	public function test_the_admin_keeps_wordpress_own_jquery(): void {
		$before = wp_scripts()->registered['jquery'] ?? null;
		$this->assertNotNull( $before );
		$src_before  = $before->src;
		$deps_before = $before->deps;

		set_current_screen( 'post' );
		do_action( 'admin_enqueue_scripts', 'post.php' );

		$after = wp_scripts()->registered['jquery'];
		$this->assertSame( $src_before, $after->src, 'jquery src is untouched in wp-admin' );
		$this->assertSame( $deps_before, $after->deps );
		$this->assertArrayHasKey( 'jquery-migrate', wp_scripts()->registered, 'core jquery-migrate is still registered' );
		$this->assertStringNotContainsString( 'cdnjs', (string) wp_scripts()->registered['jquery-migrate']->src );
	}
}
