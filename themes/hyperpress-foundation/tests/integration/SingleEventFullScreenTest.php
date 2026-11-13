<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * The theme's single.php rendering a hyperpress-event (the theme suite loads every plugin in /plugins).
 */
final class SingleEventFullScreenTest extends WP_UnitTestCase {

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
		parent::tear_down();
	}

	public function test_theme_renders_the_event_full_screen(): void {
		if ( ! post_type_exists( 'hyper_event' ) ) {
			$this->markTestSkipped( 'hyperpress-event is not loaded.' );
		}
		global $post; // The template loader includes templates in global scope.
		$post_id = self::factory()->post->create(
			array(
				'post_type'    => 'hyper_event',
				'post_status'  => 'publish',
				'post_title'   => 'Spring Open',
				'post_content' => 'Event body',
			)
		);
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( is_singular( 'hyper_event' ) );
		ob_start();
		include get_template_directory() . '/single.php';
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'main-container full-screen', $html );
		$this->assertMatchesRegularExpression( '/breadcrumbs/i', $html );
		$this->assertStringContainsString( 'Event body', $html );
		$this->assertSame( 0, $this->sidebar_calls );
	}
}
