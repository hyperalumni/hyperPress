<?php
namespace HyperPress\Tests\Integration\Season;

use HyperPress\Season\Settings;
use WP_UnitTestCase;

final class SeasonArchiveImageTest extends WP_UnitTestCase {

	private bool $registered_gizmo = false;

	public function tear_down(): void {
		if ( $this->registered_gizmo ) {
			unregister_post_type( 'hyper_gizmo' );
			$this->registered_gizmo = false;
		}
		parent::tear_down();
	}

	private function image(): int {
		return (int) self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.jpg' );
	}

	private function season(): int {
		return (int) self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => 'Hero Test Season', 'slug' => 'hero-test-season' ) );
	}

	private function banner_for( int $term_id ): array {
		$this->go_to( get_term_link( $term_id, 'hyper_season' ) );
		return apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
	}

	public function test_the_term_image_is_used(): void {
		$term_id = $this->season();
		$image   = $this->image();
		update_term_meta( $term_id, Settings::BANNER_IMAGE, $image );

		$banner = $this->banner_for( $term_id );
		$this->assertSame( 'season', $banner['type'] );
		$this->assertSame( $image, $banner['image'] );
	}

	public function test_the_fallback_is_used_when_the_term_has_no_image(): void {
		$term_id  = $this->season();
		$fallback = $this->image();
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $fallback ) );

		$this->assertSame( $fallback, $this->banner_for( $term_id )['image'] );
	}

	public function test_a_deleted_term_image_falls_back(): void {
		$term_id  = $this->season();
		$gone     = $this->image();
		$fallback = $this->image();
		update_term_meta( $term_id, Settings::BANNER_IMAGE, $gone );
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $fallback ) );
		wp_delete_attachment( $gone, true );

		$this->assertSame( $fallback, $this->banner_for( $term_id )['image'] );
	}

	public function test_without_a_term_image_or_fallback_there_is_no_image_key(): void {
		$banner = $this->banner_for( $this->season() );

		$this->assertTrue( is_tax() );
		$this->assertSame( 'season', $banner['type'] );
		$this->assertArrayNotHasKey( 'image', $banner );
	}

	public function test_a_deleted_term_image_without_a_fallback_gives_no_image_key(): void {
		$term_id = $this->season();
		$gone    = $this->image();
		update_term_meta( $term_id, Settings::BANNER_IMAGE, $gone );
		wp_delete_attachment( $gone, true );

		$errors = array();
		set_error_handler(
			static function ( $no, $str ) use ( &$errors ) {
				$errors[] = $str;
				return true;
			}
		);
		try {
			$banner = $this->banner_for( $term_id );
		} finally {
			restore_error_handler();
		}

		$this->assertTrue( is_tax() );
		$this->assertSame( 'season', $banner['type'] );
		$this->assertArrayNotHasKey( 'image', $banner );
		$this->assertSame( array(), $errors );
	}

	public function test_the_term_image_beats_the_fallback(): void {
		$term_id = $this->season();
		$own     = $this->image();
		update_term_meta( $term_id, Settings::BANNER_IMAGE, $own );
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $this->image() ) );

		$this->assertSame( $own, $this->banner_for( $term_id )['image'] );
	}

	public function test_an_image_set_earlier_is_kept(): void {
		$term_id = $this->season();
		update_term_meta( $term_id, Settings::BANNER_IMAGE, $this->image() );
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $this->image() ) );
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) {
				$banner['image'] = 4242;
				return $banner;
			},
			5
		);

		$this->assertSame( 4242, $this->banner_for( $term_id )['image'] );
	}

	public function test_a_cleared_fallback_gives_no_image(): void {
		update_option( Settings::OPTION, array( Settings::CURRENT => '' ) );

		$this->assertArrayNotHasKey( 'image', $this->banner_for( $this->season() ) );
		$this->assertSame( 0, Settings::banner_image() );
	}

	public function test_a_season_scoped_post_type_archive_gets_no_season_image(): void {
		$this->season();
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $this->image() ) );
		register_post_type( 'hyper_gizmo', array( 'public' => true, 'has_archive' => 'gizmos' ) );
		$this->registered_gizmo = true;

		$this->go_to( '/?post_type=hyper_gizmo&hyper_season=hero-test-season' );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );

		$this->assertTrue( is_post_type_archive() );
		$this->assertArrayNotHasKey( 'type', $banner );
		$this->assertArrayNotHasKey( 'image', $banner );
	}

	public function test_the_meta_boxes_declare_the_fallback_and_the_term_image(): void {
		$boxes = apply_filters( 'rwmb_meta_boxes', array() );
		$by_id = array_column( $boxes, null, 'id' );

		$this->assertArrayHasKey( 'hyperpress_season_term_box', $by_id );
		$term_box = $by_id['hyperpress_season_term_box'];
		$this->assertSame( 'term', $term_box['type'] );
		$this->assertSame( array( 'hyper_season' ), $term_box['taxonomies'] );
		$this->assertSame( 'single_image', $term_box['fields'][0]['type'] );
		$this->assertSame( Settings::BANNER_IMAGE, $term_box['fields'][0]['id'] );

		$fields = array_column( $by_id['hyperpress_season_box']['fields'], null, 'id' );
		$this->assertArrayHasKey( Settings::BANNER_IMAGE, $fields );
		$this->assertSame( 'single_image', $fields[ Settings::BANNER_IMAGE ]['type'] );
	}
}
