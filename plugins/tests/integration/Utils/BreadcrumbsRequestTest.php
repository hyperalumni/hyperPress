<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Breadcrumbs;
use WP_UnitTestCase;

final class BreadcrumbsRequestTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		if ( ! taxonomy_exists( 'hyper_season' ) ) {
			register_taxonomy( 'hyper_season', array(), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
		}
		register_post_type( 'hyper_gizmo', array( 'public' => true, 'has_archive' => 'gizmos', 'labels' => array( 'name' => 'Gizmos' ) ) );
		register_taxonomy_for_object_type( 'hyper_season', 'hyper_gizmo' );
		flush_rewrite_rules();
		Breadcrumbs::register( array( 'hyper_gizmo' ) );
	}

	public function tear_down(): void {
		unregister_post_type( 'hyper_gizmo' );
		parent::tear_down();
	}

	public function test_other_requests_are_untouched(): void {
		$this->go_to( home_url( '/' ) );
		$this->assertSame( array( array( 'title' => 'Home' ) ), apply_filters( 'hyperpress_breadcrumbs_content', array( array( 'title' => 'Home' ) ) ) );
	}

	public function test_post_type_archive_trail(): void {
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos' ), array_column( $trail, 'title' ) );
	}

	public function test_singular_uses_the_newest_season_and_the_title(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_gizmo', 'post_title' => 'Widget One' ) );
		$old     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2025' ) );
		$new     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026' ) );
		wp_set_object_terms( $post_id, array( $old, $new ), 'hyper_season' );

		$this->go_to( get_permalink( $post_id ) );
		$trail = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( array( 'Gizmos', '2026', 'Widget One' ), array_column( $trail, 'title' ) );
		$this->assertStringContainsString( '/gizmos', $trail[1]['url'] );
	}

	public function test_debug_output_is_gone(): void {
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		ob_start();
		apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$this->assertSame( '', ob_get_clean(), 'Breadcrumb building must not print anything' );
	}
}
