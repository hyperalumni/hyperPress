<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class FrontpageSettingTest extends WP_UnitTestCase {

	public function test_it_returns_the_stored_value(): void {
		update_option( 'hyperpress_frontpage', array( 'hyperpress_frontpage_banner_title' => 'Welcome' ) );

		$this->assertSame( 'Welcome', hyperpress_frontpage_setting( 'hyperpress_frontpage_banner_title', 'fallback' ) );
	}

	public function test_it_returns_the_default_for_a_missing_key(): void {
		update_option( 'hyperpress_frontpage', array( 'hyperpress_frontpage_banner_title' => 'Welcome' ) );

		$this->assertSame( 'fallback', hyperpress_frontpage_setting( 'hyperpress_frontpage_banner_subtitle', 'fallback' ) );
		$this->assertSame( array(), hyperpress_frontpage_setting( 'hyperpress_frontpage_categories', array() ) );
		$this->assertSame( '', hyperpress_frontpage_setting( 'hyperpress_frontpage_banner_subtitle' ) );
	}

	public function test_it_returns_the_default_for_a_non_array_option(): void {
		update_option( 'hyperpress_frontpage', 'not an array' );

		$this->assertSame( 'fallback', hyperpress_frontpage_setting( 'hyperpress_frontpage_banner_title', 'fallback' ) );
	}

	public function test_it_returns_the_default_for_no_option(): void {
		delete_option( 'hyperpress_frontpage' );
		// With the plugin active, the option carries plugin defaults; ask for a key the plugin never defaults.
		$this->assertSame( 'fallback', hyperpress_frontpage_setting( 'hyperpress_frontpage_not_a_field', 'fallback' ) );
	}

	public function test_the_front_page_banner_reads_the_option(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_title' => 'Apply' ) );
		update_option(
			'hyperpress_frontpage',
			array(
				'hyperpress_frontpage_banner_title'            => 'Big Title',
				'hyperpress_frontpage_banner_subtitle'         => 'Sub',
				'hyperpress_frontpage_banner_background_color' => '#112233',
				'hyperpress_frontpage_banner_button_text'      => 'Join now',
				'hyperpress_frontpage_banner_button_page'      => $page,
			)
		);
		$this->go_to( home_url( '/' ) );
		$this->assertTrue( is_front_page() );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'front', $banner['type'] );
		$this->assertSame( 'Big Title', $banner['title'] );
		$this->assertSame( 'Sub', $banner['subtitle'] );
		$this->assertSame( '#112233', $banner['backgroundColor'] );
		$this->assertSame( 'Join now', $banner['buttonText'] );
		$this->assertSame( get_post_permalink( $page ), $banner['buttonLink'] );
	}
}
