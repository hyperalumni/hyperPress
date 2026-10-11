<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * The theme's own assets are versioned with the `Version:` header of style.css, so a release changes their
 * cache-busting query string without a second version number to keep in sync.
 */
final class AssetVersionTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$GLOBALS['wp_scripts'] = null;
		$GLOBALS['wp_styles']  = null;
	}

	private function header_version(): string {
		$version = wp_get_theme( get_template() )->get( 'Version' );
		$this->assertIsString( $version );
		$this->assertNotSame( '', $version, 'style.css needs a Version header' );
		return $version;
	}

	public function test_front_end_theme_assets_use_the_theme_header_version(): void {
		do_action( 'wp_enqueue_scripts' );

		$this->assertSame( $this->header_version(), wp_styles()->registered['main-stylesheet']->ver );
		$this->assertSame( $this->header_version(), wp_scripts()->registered['main-script']->ver );
	}

	public function test_foundation_keeps_its_own_library_version(): void {
		do_action( 'wp_enqueue_scripts' );

		$this->assertSame( '6.9.0', wp_scripts()->registered['foundation']->ver );
	}

	public function test_admin_svg_stylesheet_uses_the_theme_header_version(): void {
		set_current_screen( 'post' );
		do_action( 'admin_enqueue_scripts', 'post.php' );

		$this->assertSame( $this->header_version(), wp_styles()->registered['wp-admin-svg-support']->ver );
	}
}
