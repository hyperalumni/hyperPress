<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Banner;
use InvalidArgumentException;
use WP_UnitTestCase;

final class BannerRegisterTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		register_post_type( 'hyper_gizmo', array( 'public' => true, 'has_archive' => 'gizmos' ) );
		flush_rewrite_rules();
		set_theme_mod( 'hyperpress_hyper_green', '#0a0' );
		Banner::register(
			'hyper_gizmo',
			array(
				'type'          => 'gizmo',
				'subtitle'      => static fn() => 'Sub',
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => 'Gizmos',
			)
		);
	}

	public function tear_down(): void {
		unregister_post_type( 'hyper_widget' );
		unregister_post_type( 'hyper_gizmo' );
		parent::tear_down();
	}

	public function test_other_requests_are_untouched(): void {
		$this->go_to( home_url( '/' ) );
		$this->assertSame( array( 'title' => 'Blog' ), apply_filters( 'hyperpress_banner_content', array( 'title' => 'Blog' ) ) );
	}

	public function test_archive_request_gets_the_banner(): void {
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
		$this->assertSame( 'gizmo', $banner['type'] );
		$this->assertSame( 'Gizmos', $banner['title'] );
		$this->assertSame( 'Sub', $banner['subtitle'] );
		$this->assertSame( '#0a0', $banner['backgroundColor'] );
	}

	public function test_register_without_a_subtitle_throws_and_hooks_nothing(): void {
		$before = count( $GLOBALS['wp_filter']['hyperpress_banner_content']->callbacks[10] ?? array() );
		try {
			Banner::register(
				'hyper_widget',
				array(
					'type'          => 'widget',
					'color_mod'     => 'hyperpress_hyper_green',
					'archive_title' => 'Widgets',
				)
			);
			$this->fail( 'Expected InvalidArgumentException.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertStringContainsString( 'hyper_widget', $e->getMessage() );
		}
		$this->assertSame( $before, count( $GLOBALS['wp_filter']['hyperpress_banner_content']->callbacks[10] ?? array() ) );
	}

	public function test_register_with_a_non_callable_subtitle_throws_and_hooks_nothing(): void {
		$before = count( $GLOBALS['wp_filter']['hyperpress_banner_content']->callbacks[10] ?? array() );
		try {
			Banner::register(
				'hyper_widget',
				array(
					'type'          => 'widget',
					'subtitle'      => 'nope',
					'color_mod'     => 'hyperpress_hyper_green',
					'archive_title' => 'Widgets',
				)
			);
			$this->fail( 'Expected InvalidArgumentException.' );
		} catch ( InvalidArgumentException $e ) {
			$this->assertStringContainsString( 'hyper_widget', $e->getMessage() );
		}
		$this->assertSame( $before, count( $GLOBALS['wp_filter']['hyperpress_banner_content']->callbacks[10] ?? array() ) );
	}

	public function test_subtitle_callable_fills_the_subtitle(): void {
		register_post_type( 'hyper_widget', array( 'public' => true, 'has_archive' => 'widgets' ) );
		flush_rewrite_rules();
		Banner::register(
			'hyper_widget',
			array(
				'type'          => 'widget',
				'subtitle'      => static fn() => 'From callable',
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => 'Widgets',
			)
		);
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_widget' ) );
		$this->go_to( get_permalink( $post_id ) );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => 'Widget' ) );
		$this->assertSame( 'widget', $banner['type'] );
		$this->assertSame( 'From callable', $banner['subtitle'] );
	}

	public function test_a_callable_returning_an_array_gives_an_empty_subtitle(): void {
		register_post_type( 'hyper_widget', array( 'public' => true, 'has_archive' => 'widgets' ) );
		flush_rewrite_rules();
		Banner::register(
			'hyper_widget',
			array(
				'type'          => 'widget',
				'subtitle'      => static fn() => array( 'not', 'a string' ),
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => 'Widgets',
			)
		);
		$this->go_to( get_post_type_archive_link( 'hyper_widget' ) );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
		$this->assertSame( '', $banner['subtitle'] );
	}

	public function test_hyperpress_banner_args_filter_can_adjust_the_banner(): void {
		$seen = array();
		add_filter(
			'hyperpress_banner_args',
			static function ( $banner, $post_type, $ctx ) use ( &$seen ) {
				$seen             = array( $post_type, $ctx['is_archive'] );
				$banner['title'] = 'Adjusted';
				return $banner;
			},
			10,
			3
		);
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
		$this->assertSame( 'Adjusted', $banner['title'] );
		$this->assertSame( array( 'hyper_gizmo', true ), $seen );
	}
}
