<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\Breadcrumbs;
use WP_UnitTestCase;

/**
 * Only the paths that return before NextGEN's mappers are needed are testable here.
 */
final class NextGenBreadcrumbsTest extends WP_UnitTestCase {

	/**
	 * @var array<int,array<string,mixed>>
	 */
	private array $crumbs = array(
		array(
			'title' => 'Home',
			'url'   => '/',
		),
	);

	public function tear_down(): void {
		delete_option( 'hyperpress_media' );
		delete_option( 'ngg_options' );
		parent::tear_down();
	}

	private function save_page( int $id ): void {
		update_option( 'hyperpress_media', array( 'hyperpress_media_photos_page' => $id ) );
		update_option( 'ngg_options', array( 'router_param_slug' => 'nggallery' ) );
	}

	public function test_with_no_page_saved_the_breadcrumbs_are_untouched(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'ngg_options', array( 'router_param_slug' => 'nggallery' ) );
		$this->go_to( get_permalink( $id ) );

		$this->assertSame( $this->crumbs, Breadcrumbs::add( $this->crumbs ) );
	}

	public function test_a_non_array_option_leaves_the_breadcrumbs_untouched(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'hyperpress_media', 'garbage' );
		$this->go_to( get_permalink( $id ) );

		$this->assertSame( $this->crumbs, Breadcrumbs::add( $this->crumbs ) );
	}

	public function test_a_request_for_another_page_leaves_the_breadcrumbs_untouched(): void {
		$photos = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$other  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->save_page( $photos );
		$this->go_to( get_permalink( $other ) );

		$this->assertSame( $this->crumbs, Breadcrumbs::add( $this->crumbs ) );
	}

	public function test_a_deleted_photos_page_leaves_the_breadcrumbs_untouched(): void {
		$photos = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$other  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->save_page( $photos );
		wp_delete_post( $photos, true );
		$this->go_to( get_permalink( $other ) );

		$this->assertSame( $this->crumbs, Breadcrumbs::add( $this->crumbs ) );
	}

	public function test_on_the_photos_page_without_a_router_slug_the_breadcrumbs_are_untouched(): void {
		$photos = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'hyperpress_media', array( 'hyperpress_media_photos_page' => $photos ) );
		$this->go_to( get_permalink( $photos ) );

		$this->assertSame( $this->crumbs, Breadcrumbs::add( $this->crumbs ) );
	}

	public function test_on_the_photos_page_without_nextgen_path_segments_the_breadcrumbs_are_untouched(): void {
		$photos = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->save_page( $photos );
		$this->go_to( get_permalink( $photos ) );

		$this->assertSame( $this->crumbs, Breadcrumbs::add( $this->crumbs ) );
	}
}
