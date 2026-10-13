<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class NewsletterBannerTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb'] );
		delete_option( 'hyperpress_newsletter' );
		parent::tear_down();
	}

	private function banner_for( int $post_id ): array {
		$this->go_to( get_permalink( $post_id ) );
		return apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
	}

	public function test_singular_title_is_prefixed_with_the_latest_season(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter', 'post_title' => 'Kickoff' ) );
		$old     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2025' ) );
		$new     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026' ) );
		wp_set_object_terms( $post_id, array( $old, $new ), 'hyper_season' );

		$banner = $this->banner_for( $post_id );
		$this->assertSame( 'newsletter', $banner['type'] );
		$this->assertSame( '2026: Kickoff', $banner['title'] );
	}

	public function test_singular_without_a_season_falls_back_to_newsletter(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter', 'post_title' => 'Kickoff' ) );
		$this->assertSame( 'Newsletter', $this->banner_for( $post_id )['title'] );
	}

	public function test_singular_color_comes_from_the_per_post_meta_box(): void {
		$GLOBALS['hyperpress_test_rwmb']['hyperpress_banner_background_color'] = '#123456';
		set_theme_mod( 'hyperpress_hyper_orange', '#ff8800' );
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$this->assertSame( '#123456', $this->banner_for( $post_id )['backgroundColor'] );
	}

	public function test_singular_keeps_the_theme_color_when_the_post_has_none(): void {
		set_theme_mod( 'hyperpress_hyper_orange', '#ff8800' );
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$this->assertSame( '#ff8800', $this->banner_for( $post_id )['backgroundColor'] );
	}

	public function test_singular_keeps_the_theme_color_when_neither_post_nor_posts_page_has_one(): void {
		set_theme_mod( 'hyperpress_hyper_orange', '#ff8800' );
		update_option( 'page_for_posts', self::factory()->post->create( array( 'post_type' => 'page' ) ) );
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$this->assertSame( '#ff8800', $this->banner_for( $post_id )['backgroundColor'] );
	}

	public function test_archive_title_is_always_set_and_season_uses_from(): void {
		update_option( 'hyperpress_newsletter', array( 'hyperpress_newsletter_banner_subtitle' => 'News' ) );
		$this->go_to( get_post_type_archive_link( 'hyper_newsletter' ) );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => 'Preset' ) );
		$this->assertSame( 'Newsletters', $banner['title'] );
		$this->assertSame( 'News', $banner['subtitle'] );
	}
}
