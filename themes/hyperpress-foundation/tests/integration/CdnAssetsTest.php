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

	public function test_cdn_urls_are_built_from_the_version_variables(): void {
		global $jquery_version, $font_awesome_version;

		$this->assertSame( "https://cdnjs.cloudflare.com/ajax/libs/jquery/{$jquery_version}/jquery.min.js", hyperpress_cdn_url( 'jquery', 'jquery.min.js' ) );
		$this->assertSame( "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/{$font_awesome_version}/css/all.min.css", hyperpress_cdn_url( 'font-awesome', 'css/all.min.css' ) );
	}

	public function test_every_enqueued_cdn_url_has_a_known_hash(): void {
		$this->front_end_markup();
		$hashes = hyperpress_cdn_integrity();
		foreach ( array_merge( wp_scripts()->registered, wp_styles()->registered ) as $asset ) {
			if ( is_string( $asset->src ) && false !== strpos( $asset->src, 'cdnjs.cloudflare.com' ) ) {
				$this->assertArrayHasKey( strtok( $asset->src, '?' ), $hashes, "no SRI hash for {$asset->src}" );
			}
		}
	}

	public function test_a_version_bump_without_a_new_hash_loses_integrity_instead_of_keeping_a_stale_one(): void {
		global $jquery_version;
		$original      = $jquery_version;
		$jquery_version = '9.9.9';

		try {
			$hashes = hyperpress_cdn_integrity();
			$this->assertArrayNotHasKey( hyperpress_cdn_url( 'jquery', 'jquery.min.js' ), $hashes );
			$this->assertStringNotContainsString( '9.9.9', implode( ' ', array_keys( $hashes ) ) );
			$this->assertSame( '<script src="x"></script>', hyperpress_add_integrity( '<script src="x"></script>', 'jquery', hyperpress_cdn_url( 'jquery', 'jquery.min.js' ) ) );
		} finally {
			$jquery_version = $original;
		}
	}
}
