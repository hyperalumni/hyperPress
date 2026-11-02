<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class FooterOffCanvasTest extends WP_UnitTestCase {

	private function footer_html(): string {
		$this->go_to( home_url( '/' ) );
		// WordPress core still hooks the deprecated the_block_template_skip_link() on wp_footer, which trips the test case's deprecation check.
		remove_action( 'wp_footer', 'the_block_template_skip_link' );

		ob_start();
		include get_template_directory() . '/footer.php';
		return (string) ob_get_clean();
	}

	public function test_only_the_offcanvas_layout_closes_the_off_canvas_content(): void {
		foreach ( array( 'topbar', 'drilldown' ) as $layout ) {
			set_theme_mod( 'wpt_mobile_menu_layout', $layout );
			$this->assertStringNotContainsString( 'Close off-canvas content', $this->footer_html(), $layout );
		}

		set_theme_mod( 'wpt_mobile_menu_layout', 'offcanvas' );
		$this->assertStringContainsString( 'Close off-canvas content', $this->footer_html() );
	}
}
