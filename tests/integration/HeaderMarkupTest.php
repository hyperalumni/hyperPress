<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class HeaderMarkupTest extends WP_UnitTestCase {

	private function render_header(): string {
		$this->go_to( home_url( '/' ) );
		ob_start();
		include get_template_directory() . '/header.php'; // get_header() would require_once, so only the first render in a process would output.
		return ob_get_clean();
	}

	public function test_wp_body_open_fires_right_after_the_body_tag(): void {
		add_action(
			'wp_body_open',
			static function () {
				echo '<!--body-open-marker-->';
			}
		);
		$before = did_action( 'wp_body_open' );

		$html = $this->render_header();

		$this->assertSame( $before + 1, did_action( 'wp_body_open' ) );
		$this->assertMatchesRegularExpression( '/<body[^>]*>\s*<!--body-open-marker-->/', $html );
	}
}
