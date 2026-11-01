<?php
namespace HyperPress\Tests\Support;

use WP_UnitTestCase;

/**
 * The archive hero image contract shared by the post-type plugins (sponsor, award, robot, newsletter,
 * competition): the saved image shows on the post type's archive requests only, and only when it is a
 * valid image attachment. A concrete test class supplies the plugin's identifiers.
 */
abstract class ArchiveImageTestCase extends WP_UnitTestCase {

	/** The plugin's post type key. */
	abstract protected function post_type(): string;

	/** The plugin's settings option name. */
	abstract protected function option(): string;

	/** The option key of the archive hero image. */
	abstract protected function image_key(): string;

	/** The option key of the banner subtitle. */
	abstract protected function subtitle_key(): string;

	/** The id of the plugin's settings page. */
	abstract protected function settings_page_id(): string;

	/** Make the post type's archive paginate at one post per page; override where the plugin sets its own page size. */
	protected function make_archive_paginate(): void {
		update_option( 'posts_per_page', 1 );
	}

	public function tear_down(): void {
		delete_option( $this->option() );
		parent::tear_down();
	}

	private function image(): int {
		return (int) self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.jpg' );
	}

	private function save_image( int $id ): void {
		update_option( $this->option(), array( $this->image_key() => $id ) );
	}

	private function banner_at( string $url ): array {
		$this->go_to( $url );
		return apply_filters( 'hyperpress_banner_content', array() );
	}

	private function archive_url(): string {
		return (string) get_post_type_archive_link( $this->post_type() );
	}

	public function test_the_archive_banner_carries_the_saved_image(): void {
		$id = $this->image();
		$this->save_image( $id );

		$this->assertSame( $id, $this->banner_at( $this->archive_url() )['image'] );
	}

	public function test_a_paged_archive_carries_the_image(): void {
		$id = $this->image();
		$this->save_image( $id );
		// Page 2 only exists (not a 404) when the archive has more than one page.
		$this->make_archive_paginate();
		self::factory()->post->create_many( 2, array( 'post_type' => $this->post_type() ) );

		$banner = $this->banner_at( add_query_arg( 'paged', 2, $this->archive_url() ) );

		$this->assertSame( 2, (int) get_query_var( 'paged' ) );
		$this->assertSame( $id, $banner['image'] );
	}

	public function test_a_season_scoped_archive_carries_the_plugin_image(): void {
		$id = $this->image();
		$this->save_image( $id );
		$term = self::factory()->term->create(
			array(
				'taxonomy' => 'hyper_season',
				'slug'     => 'image-season',
			)
		);
		$this->assertIsInt( $term );

		$banner = $this->banner_at( add_query_arg( 'hyper_season', 'image-season', $this->archive_url() ) );

		$this->assertSame( 'image-season', get_query_var( 'hyper_season' ) );
		$this->assertSame( $id, $banner['image'] );
	}

	public function test_a_single_post_has_no_image(): void {
		$this->save_image( $this->image() );
		$post = self::factory()->post->create( array( 'post_type' => $this->post_type() ) );

		$this->assertArrayNotHasKey( 'image', $this->banner_at( get_permalink( $post ) ) );
	}

	public function test_a_season_taxonomy_page_has_no_image(): void {
		$this->save_image( $this->image() );
		self::factory()->term->create(
			array(
				'taxonomy' => 'hyper_season',
				'slug'     => 'tax-only-season',
			)
		);

		$this->assertArrayNotHasKey( 'image', $this->banner_at( home_url( '/?hyper_season=tax-only-season' ) ) );
	}

	public function test_a_cleared_field_gives_no_image(): void {
		// Meta Box drops the key of an empty field but keeps the option row.
		update_option( $this->option(), array() );

		$this->assertArrayNotHasKey( 'image', $this->banner_at( $this->archive_url() ) );
	}

	public function test_a_deleted_attachment_gives_no_image(): void {
		$id = $this->image();
		$this->save_image( $id );
		wp_delete_attachment( $id, true );

		$this->assertArrayNotHasKey( 'image', $this->banner_at( $this->archive_url() ) );
	}

	public function test_a_non_image_attachment_gives_no_image(): void {
		$this->save_image( self::factory()->attachment->create( array( 'post_mime_type' => 'text/plain' ) ) );

		$this->assertArrayNotHasKey( 'image', $this->banner_at( $this->archive_url() ) );
	}

	public function test_the_settings_box_has_the_image_field_after_the_subtitle(): void {
		$ids = array();
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			if ( ( $box['settings_pages'] ?? '' ) !== $this->settings_page_id() ) {
				continue;
			}
			foreach ( $box['fields'] as $field ) {
				$ids[ $field['id'] ] = $field['type'];
			}
		}

		$this->assertSame( 'single_image', $ids[ $this->image_key() ] ?? null );
		$keys = array_keys( $ids );
		$this->assertSame( array_search( $this->subtitle_key(), $keys, true ) + 1, array_search( $this->image_key(), $keys, true ) );
	}
}
