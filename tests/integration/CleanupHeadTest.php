<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class CleanupHeadTest extends WP_UnitTestCase {

	private function head( string $url ): string {
		$this->go_to( $url );
		ob_start();
		wp_head();
		return (string) ob_get_clean();
	}

	public function test_the_canonical_link_is_printed_on_a_single_post(): void {
		$id   = self::factory()->post->create( array( 'post_title' => 'Canonical me' ) );
		$head = $this->head( get_permalink( $id ) );

		$this->assertMatchesRegularExpression( '~<link rel=[\'"]canonical[\'"] href=[\'"]' . preg_quote( get_permalink( $id ), '~' ) . '[\'"]~', $head );
	}

	public function test_feed_autodiscovery_links_are_printed(): void {
		$head = $this->head( home_url( '/' ) );

		$this->assertMatchesRegularExpression( '~<link rel=[\'"]alternate[\'"] type=[\'"]application/rss\+xml[\'"][^>]*href=[\'"]http://example\.org/feed/[\'"]~', $head );
	}

	public function test_the_category_feed_link_is_printed_on_a_category_archive(): void {
		$cat  = self::factory()->category->create( array( 'name' => 'News' ) );
		$head = $this->head( get_category_link( $cat ) );

		$this->assertMatchesRegularExpression( '~<link rel=[\'"]alternate[\'"] type=[\'"]application/rss\+xml[\'"] title=[\'"][^>]*News[^>]*href=[\'"][^\'"]*feed[^>]*>~', $head );
	}

	/** @dataProvider removed_head_actions */
	public function test_the_remaining_head_clutter_stays_removed( string $hook, string $callback ): void {
		$this->assertFalse( has_action( $hook, $callback ), "$callback must stay unhooked from $hook" );
	}

	public static function removed_head_actions(): array {
		return array(
			'EditURI'          => array( 'wp_head', 'rsd_link' ),
			'Windows Live'     => array( 'wp_head', 'wlwmanifest_link' ),
			'shortlink'        => array( 'wp_head', 'wp_shortlink_wp_head' ),
			'adjacent posts'   => array( 'wp_head', 'adjacent_posts_rel_link_wp_head' ),
			'generator'        => array( 'wp_head', 'wp_generator' ),
			'emoji script'     => array( 'wp_head', 'print_emoji_detection_script' ),
			'emoji styles'     => array( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' ),
			'emoji styles, old hook' => array( 'wp_print_styles', 'print_emoji_styles' ),
		);
	}

	public function test_the_generator_tag_and_emoji_assets_are_not_printed(): void {
		$head = $this->head( home_url( '/' ) );

		$this->assertStringNotContainsString( 'name="generator"', $head );
		$this->assertStringNotContainsString( 'wp-emoji', $head );
		$this->assertStringNotContainsString( 'img.wp-smiley', $head, 'the emoji inline style' );
		$this->assertStringNotContainsString( 'rel=\'shortlink\'', $head );
	}

	public function test_dead_cleanup_code_is_gone(): void {
		$this->assertFalse( function_exists( 'hyperpress_remove_wp_widget_recent_comments_style' ) );
		$this->assertFalse( has_filter( 'wp_head', 'hyperpress_remove_wp_widget_recent_comments_style' ) );
	}

	public function test_cleanup_does_not_try_to_unhook_functions_removed_from_core(): void {
		$source = (string) file_get_contents( get_template_directory() . '/library/cleanup.php' );

		foreach ( array( 'index_rel_link', 'parent_post_rel_link', 'start_post_rel_link' ) as $dead ) {
			$this->assertStringNotContainsString( "'$dead'", $source, "$dead is a no-op on current WordPress" );
		}
	}
}
