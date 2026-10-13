<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\Breadcrumbs;
use WP_UnitTestCase;

/**
 * Only the paths that return before NextGEN's mappers are needed are testable here.
 *
 * Each early-return test requests a NextGEN-style path and sets the router slug, so that only the photos-page guard
 * (the first check in Breadcrumbs::add()) stops it. If that guard were broken, add() would go on to the NextGEN
 * mappers, which do not exist here, and the test would fail with an Error.
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

	private mixed $request_uri = null;

	public function set_up(): void {
		parent::set_up();
		$this->request_uri = $_SERVER['REQUEST_URI'] ?? null;
	}

	public function tear_down(): void {
		if ( null === $this->request_uri ) {
			unset( $_SERVER['REQUEST_URI'] );
		} else {
			$_SERVER['REQUEST_URI'] = $this->request_uri;
		}
		delete_option( 'hyperpress_media' );
		delete_option( 'ngg_options' );
		parent::tear_down();
	}

	private function save_page( int $id ): void {
		update_option( 'hyperpress_media', array( 'hyperpress_media_photos_page' => $id ) );
		update_option( 'ngg_options', array( 'router_param_slug' => 'nggallery' ) );
	}

	/**
	 * Points the request at a path that passes the router-slug and path-segment checks.
	 *
	 * Called after go_to(), which resets REQUEST_URI.
	 */
	private function request_nextgen_path(): void {
		$_SERVER['REQUEST_URI'] = '/x/nggallery/some-album/';
	}

	public function test_with_no_page_saved_the_breadcrumbs_are_untouched(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'ngg_options', array( 'router_param_slug' => 'nggallery' ) );
		$this->go_to( get_permalink( $id ) );
		$this->request_nextgen_path();

		$this->assertSame( $this->crumbs, Breadcrumbs::add( $this->crumbs ) );
	}

	public function test_a_non_array_option_leaves_the_breadcrumbs_untouched(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'hyperpress_media', 'garbage' );
		update_option( 'ngg_options', array( 'router_param_slug' => 'nggallery' ) );
		$this->go_to( get_permalink( $id ) );
		$this->request_nextgen_path();

		$this->assertSame( $this->crumbs, Breadcrumbs::add( $this->crumbs ) );
	}

	public function test_a_request_for_another_page_leaves_the_breadcrumbs_untouched(): void {
		$photos = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$other  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->save_page( $photos );
		$this->go_to( get_permalink( $other ) );
		$this->request_nextgen_path();

		$this->assertSame( $this->crumbs, Breadcrumbs::add( $this->crumbs ) );
	}

	public function test_a_deleted_photos_page_leaves_the_breadcrumbs_untouched(): void {
		$photos = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$other  = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->save_page( $photos );
		wp_delete_post( $photos, true );
		$this->go_to( get_permalink( $other ) );
		$this->request_nextgen_path();

		$this->assertSame( $this->crumbs, Breadcrumbs::add( $this->crumbs ) );
	}

	public function test_an_unpublished_photos_page_leaves_the_breadcrumbs_untouched(): void {
		// The saved page exists but is a draft, so it is never the queried page for a visitor on another URL; is_page() is false and the guard stops add() (the router slug and the path are both valid, so nothing else could).
		$draft = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);
		$other = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->save_page( $draft );
		$this->go_to( get_permalink( $other ) );
		$this->request_nextgen_path();

		$this->assertFalse( is_page( $draft ) );
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
