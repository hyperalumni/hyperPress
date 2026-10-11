<?php
namespace HyperPress\ThemeTests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class CopyrightSettingTest extends WP_UnitTestCase {

	public function test_the_setting_strips_tags(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$setting = $manager->get_setting( 'hyperpress_site_copyright_name' );
		$this->assertNotNull( $setting );
		$this->assertSame( 'x', $setting->sanitize( '<b>x</b><script>y</script>' ), 'tags (and script contents) are stripped' );
	}

	public function test_the_footer_escapes_the_copyright_name(): void {
		set_theme_mod( 'hyperpress_site_copyright_name', '<img src=x onerror=alert(1)>' );
		$this->go_to( home_url( '/' ) );
		// WordPress core still hooks the deprecated the_block_template_skip_link() on wp_footer, which trips the test case's deprecation check.
		remove_action( 'wp_footer', 'the_block_template_skip_link' );

		ob_start();
		include get_template_directory() . '/footer.php';
		$html = ob_get_clean();

		$this->assertStringContainsString( '<h6>', $html, 'the copyright name is printed' );
		$this->assertStringNotContainsString( '<img', $html );
	}
}
