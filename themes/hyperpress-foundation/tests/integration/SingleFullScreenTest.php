<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class SingleFullScreenTest extends WP_UnitTestCase {

	/** Times get_sidebar() ran. sidebar.php loads with require_once, so its markup only prints once per process. */
	private int $sidebar_calls = 0;

	public function set_up(): void {
		parent::set_up();
		$this->sidebar_calls = 0;
		add_action(
			'get_sidebar',
			function (): void {
				++$this->sidebar_calls;
			}
		);
	}

	public function tear_down(): void {
		remove_all_actions( 'get_sidebar' );
		remove_all_filters( 'hyperpress_single_full_screen' );
		remove_all_actions( 'hyperpress_before_main' );
		remove_all_actions( 'hyperpress_after_main' );
		parent::tear_down();
	}

	/** Render single.php for a fresh post and return the HTML. */
	private function render_single(): string {
		global $post; // The template loader includes templates in global scope.
		$post_id = self::factory()->post->create(
			array(
				'post_status'  => 'publish',
				'post_title'   => 'Single Subject',
				'post_content' => 'Single body',
			)
		);
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( is_single() );
		ob_start();
		include get_template_directory() . '/single.php';
		$html = ob_get_clean();
		$this->assertStringNotContainsString( 'Fatal error', $html );
		return $html;
	}

	public function test_default_single_is_unchanged(): void {
		$html = $this->render_single();

		$this->assertStringContainsString( '<div class="main-container">', $html );
		$this->assertStringNotContainsString( 'full-screen', $html );
		$this->assertSame( 1, $this->sidebar_calls );
	}

	public function test_filter_true_adds_full_screen_and_drops_the_sidebar(): void {
		add_filter( 'hyperpress_single_full_screen', '__return_true' );

		$html = $this->render_single();

		$this->assertStringContainsString( '<div class="main-container full-screen">', $html );
		$this->assertSame( 0, $this->sidebar_calls );
		$this->assertStringNotContainsString( '<aside class="sidebar">', $html );
	}

	public function test_filter_true_keeps_breadcrumbs_and_hooks(): void {
		add_filter( 'hyperpress_single_full_screen', '__return_true' );
		add_action(
			'hyperpress_before_main',
			static function () {
				echo '<!--before-main-->';
			}
		);
		add_action(
			'hyperpress_after_main',
			static function () {
				echo '<!--after-main-->';
			}
		);

		$html = $this->render_single();

		$this->assertStringContainsString( '<!--before-main-->', $html );
		$this->assertStringContainsString( '<!--after-main-->', $html );
		$this->assertStringContainsString( 'Single body', $html );
		$this->assertMatchesRegularExpression( '/breadcrumbs/i', $html );
	}
}
