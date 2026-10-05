<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class BannerSubtitleTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		remove_action( 'wp_body_open', 'the_block_template_skip_link' );
	}

	private function banner_html( string $url ): string {
		$this->go_to( $url );
		ob_start();
		include get_template_directory() . '/template-parts/banner.php';
		return (string) ob_get_clean();
	}

	private function subtitle_html( string $banner_html ): string {
		return preg_match( '~<h4 class="subtitle">(.*?)</h4>~s', $banner_html, $m ) ? $m[1] : '';
	}

	public static function archives(): array {
		return array(
			'category' => array( 'Category', 'News' ),
			'tag'      => array( 'Tag', 'robots' ),
		);
	}

	/** @dataProvider archives */
	public function test_term_archive_subtitles_emphasize_the_term( string $label, string $name ): void {
		$taxonomy = 'Category' === $label ? 'category' : 'post_tag';
		$term_id  = self::factory()->term->create( array( 'taxonomy' => $taxonomy, 'name' => $name ) );
		$post_id  = self::factory()->post->create();
		wp_set_object_terms( $post_id, array( $term_id ), $taxonomy );

		$subtitle = $this->subtitle_html( $this->banner_html( get_term_link( $term_id, $taxonomy ) ) );

		$this->assertSame( $label . ': <span class="emphasis">' . $name . '</span>', $subtitle );
	}

	public function test_the_search_subtitle_keeps_its_emphasis_and_still_neutralizes_shortcodes(): void {
		add_shortcode( 'hp_test_sc', static fn() => 'EXECUTED' );
		$subtitle = $this->subtitle_html( $this->banner_html( home_url( '/?s=' . rawurlencode( '[hp_test_sc] <b>x</b>' ) ) ) );

		$this->assertStringContainsString( '<span class="emphasis">', $subtitle );
		$this->assertStringNotContainsString( 'EXECUTED', $subtitle );
		$this->assertStringNotContainsString( '<b>', $subtitle, 'markup typed into the search box stays escaped' );
	}

	public function test_a_date_archive_subtitle_is_emphasized(): void {
		self::factory()->post->create( array( 'post_date' => '2024-03-15 10:00:00' ) );

		$subtitle = $this->subtitle_html( $this->banner_html( get_year_link( 2024 ) ) );

		$this->assertSame( 'Year: <span class="emphasis">2024</span>', $subtitle );
	}

	public function test_a_configured_subtitle_may_use_the_span_or_the_shortcode(): void {
		set_theme_mod( 'hyperpress_homepage_customize_banner_subtitle', 'About <span class="emphasis">us</span> and [hyper_emphasis]you[/hyper_emphasis]' );
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$subtitle = $this->subtitle_html( $this->banner_html( get_permalink( $page_id ) ) );

		$this->assertStringContainsString( '<span class="emphasis">us</span>', $subtitle );
		if ( shortcode_exists( 'hyper_emphasis' ) ) {
			$this->assertStringContainsString( '<span class="emphasis">you</span>', $subtitle );
		}
	}

	public function test_hostile_markup_in_a_subtitle_is_removed(): void {
		set_theme_mod( 'hyperpress_homepage_customize_banner_subtitle', 'Hi <span class="emphasis" onclick="x()">there</span><script>alert(1)</script><a href="javascript:alert(2)">l</a><img src=x onerror=alert(3)>' );
		$page_id = self::factory()->post->create( array( 'post_type' => 'page' ) );

		$subtitle = $this->subtitle_html( $this->banner_html( get_permalink( $page_id ) ) );

		$this->assertStringContainsString( '<span class="emphasis">there</span>', $subtitle );
		$this->assertStringNotContainsString( 'onclick', $subtitle );
		$this->assertStringNotContainsString( '<script', $subtitle );
		$this->assertStringNotContainsString( 'onerror', $subtitle );
		$this->assertStringNotContainsString( 'javascript:', $subtitle );
	}
}
