<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class FrontPageQueryTest extends WP_UnitTestCase {

	private bool $stubbed_countdown = false;

	public function set_up(): void {
		parent::set_up();
		// WordPress core still hooks the deprecated the_block_template_skip_link() on these actions, which trips the test case's deprecation check.
		remove_action( 'wp_footer', 'the_block_template_skip_link' );
		remove_action( 'wp_body_open', 'the_block_template_skip_link' );
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

	public function test_front_page_renders_without_warnings_when_no_categories_are_configured(): void {
		self::factory()->post->create( array( 'post_title' => 'Unfiltered Post Title' ) );

		foreach ( array( null, false, '' ) as $value ) {
			if ( null === $value ) {
				remove_theme_mod( 'hyperpress_home_blog_categories' );
			} else {
				set_theme_mod( 'hyperpress_home_blog_categories', $value );
			}
			$html = $this->render_front_page();
			$this->assertStringContainsString( 'Unfiltered Post Title', $html );
		}
	}

	public function test_front_page_restores_the_global_post_after_the_loops(): void {
		self::factory()->post->create(
			array(
				'post_title' => 'Older Post',
				'post_date'  => '2020-01-01 00:00:00',
			)
		);
		$newest = self::factory()->post->create(
			array(
				'post_title' => 'Newest Post',
				'post_date'  => '2021-01-01 00:00:00',
			)
		);

		$this->render_front_page();

		// The secondary loop ends on the oldest post; the global post must be the main query's current post again.
		$this->assertInstanceOf( \WP_Post::class, $GLOBALS['post'] );
		$this->assertSame( $newest, $GLOBALS['post']->ID );
	}

	public function test_front_page_limits_the_posts_to_a_configured_category(): void {
		$category = self::factory()->category->create();
		self::factory()->post->create(
			array(
				'post_title'    => 'Inside Category Post',
				'post_category' => array( $category ),
			)
		);
		self::factory()->post->create( array( 'post_title' => 'Outside Category Post' ) );
		set_theme_mod( 'hyperpress_home_blog_categories', array( (string) $category, 'abc', '0' ) );

		// Only look at the post list: the banner above it prints the main query's current post regardless of the mod.
		$html = $this->render_front_page();
		$list = substr( $html, (int) strpos( $html, '<main' ) );

		$this->assertStringContainsString( 'Inside Category Post', $list );
		$this->assertStringNotContainsString( 'Outside Category Post', $list );
	}
}
