<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class FrontPageContentTemplateTest extends WP_UnitTestCase {

	private bool $stubbed_countdown = false;

	public function set_up(): void {
		parent::set_up();
		if ( ! shortcode_exists( 'hyperpress_countdown' ) ) {
			add_shortcode( 'hyperpress_countdown', '__return_empty_string' );
			$this->stubbed_countdown = true;
		}
	}

	public function tear_down(): void {
		if ( $this->stubbed_countdown ) {
			remove_shortcode( 'hyperpress_countdown' );
			$this->stubbed_countdown = false;
		}
		remove_all_filters( 'pre_do_shortcode_tag' );
		parent::tear_down();
	}

	/** Render the front page through front-page.php and return the HTML. */
	private function render_front_page(): string {
		global $post; // The template loader includes templates in global scope, so $post is available there.
		$this->go_to( home_url( '/' ) );
		$template = get_front_page_template();
		$this->assertSame( 'front-page.php', basename( $template ) );
		ob_start();
		include $template;
		$html = ob_get_clean();
		$this->assertStringNotContainsString( 'Fatal error', $html );
		return $html;
	}

	public function test_front_page_lists_a_post_type_without_a_content_template_override(): void {
		if ( getenv( 'HYPERPRESS_TEST_WITHOUT_PLUGINS' ) ) {
			$this->markTestSkipped( 'Needs the HYPER plugins' );
		}
		update_option( 'hyperpress_frontpage', array( 'hyperpress_frontpage_post_types' => array( 'hyper_award' ) ) );
		self::factory()->post->create(
			array(
				'post_type'   => 'hyper_award',
				'post_status' => 'publish',
				'post_title'  => 'Golden Widget Award',
			)
		);

		$html = $this->render_front_page();

		$this->assertStringContainsString( 'Golden Widget Award', $html );
	}

	/** Replace every callback on the countdown filter so the real plugins' candidates cannot interfere. */
	private function countdown_filter_returns( $value ): void {
		remove_all_filters( 'hyperpress_frontpage_countdown_html' );
		add_filter( 'hyperpress_frontpage_countdown_html', static fn() => $value );
	}

	private function mark_the_countdown_block(): void {
		add_action(
			'hyperpress_page_before_entry_content',
			static function () {
				echo '<!--countdown-block-->';
			}
		);
	}

	public function test_front_page_prints_the_countdown_html_from_the_filter_in_the_entry_content(): void {
		self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$this->countdown_filter_returns( '<p class="cd-test">counting</p>' );
		$this->mark_the_countdown_block();

		$html = $this->render_front_page();

		$this->assertMatchesRegularExpression( '/<!--countdown-block-->\s*<div class="entry-content">\s*<p class="cd-test">counting<\/p>\s*<\/div>/', $html );
	}

	public function test_front_page_prints_the_countdown_once_even_with_several_posts_in_the_loop(): void {
		self::factory()->post->create_many( 3, array( 'post_status' => 'publish' ) );
		$this->countdown_filter_returns( '<p class="cd-test">counting</p>' );
		$this->mark_the_countdown_block();

		$html = $this->render_front_page();

		$this->assertSame( 1, substr_count( $html, '<!--countdown-block-->' ) );
		$this->assertSame( 1, substr_count( $html, 'class="cd-test"' ) );
	}

	public function test_front_page_prints_no_countdown_block_when_the_filter_returns_nothing(): void {
		self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$this->mark_the_countdown_block();

		$this->countdown_filter_returns( '' );
		$this->assertStringNotContainsString( '<!--countdown-block-->', $this->render_front_page() );

		$this->countdown_filter_returns( array( 'not a string' ) );
		$this->assertStringNotContainsString( '<!--countdown-block-->', $this->render_front_page() );
	}

	public function test_front_page_prints_no_literal_shortcode_when_the_countdown_plugin_is_inactive(): void {
		self::factory()->post->create( array( 'post_status' => 'publish' ) );
		update_option( 'hyperpress_frontpage', array( 'hyperpress_frontpage_countdowns' => array( '12' ) ) );
		remove_shortcode( 'hyperpress_countdown' );
		$this->stubbed_countdown = false;

		try {
			$html = $this->render_front_page();
		} finally {
			add_shortcode( 'hyperpress_countdown', '__return_empty_string' );
			$this->stubbed_countdown = true;
		}

		$this->assertStringNotContainsString( '[hyperpress_countdown', $html );
	}
}
