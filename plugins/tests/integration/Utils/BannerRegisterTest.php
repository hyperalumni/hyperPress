<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Banner;
use WP_UnitTestCase;

final class BannerRegisterTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		register_post_type( 'hyper_gizmo', array( 'public' => true, 'has_archive' => 'gizmos' ) );
		flush_rewrite_rules();
		set_theme_mod( 'hyperpress_gizmo_banner_subtitle', 'Sub' );
		set_theme_mod( 'hyperpress_hyper_green', '#0a0' );
		Banner::register(
			'hyper_gizmo',
			array(
				'type'          => 'gizmo',
				'subtitle_mod'  => 'hyperpress_gizmo_banner_subtitle',
				'color_mod'     => 'hyperpress_hyper_green',
				'archive_title' => 'Gizmos',
			)
		);
	}

	public function tear_down(): void {
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
