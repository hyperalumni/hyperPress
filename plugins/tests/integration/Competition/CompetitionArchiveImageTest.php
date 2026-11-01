<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Settings;
use WP_UnitTestCase;

final class CompetitionArchiveImageTest extends WP_UnitTestCase {

	private const POST_TYPE = 'hyper_competition';

	public function tear_down(): void {
		delete_option( Settings::OPTION );
		parent::tear_down();
	}

	private function image(): int {
		return (int) self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.jpg' );
	}

	private function banner_at( string $url ): array {
		$this->go_to( $url );
		return apply_filters( 'hyperpress_banner_content', array() );
	}

	private function archive_url(): string {
		return (string) get_post_type_archive_link( self::POST_TYPE );
	}

	public function test_the_archive_banner_carries_the_saved_image(): void {
		$id = $this->image();
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $id ) );

		$this->assertSame( $id, $this->banner_at( $this->archive_url() )['image'] );
	}

	public function test_a_paged_archive_carries_the_image(): void {
		$id = $this->image();
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $id ) );
		// Page 2 only exists (not a 404) when the archive has more than one page.
		update_option( 'posts_per_page', 1 );
		self::factory()->post->create_many( 2, array( 'post_type' => self::POST_TYPE ) );

		$this->assertSame( $id, $this->banner_at( add_query_arg( 'paged', 2, $this->archive_url() ) )['image'] );
	}

	public function test_a_season_scoped_archive_carries_the_plugin_image(): void {
		$id = $this->image();
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $id ) );
		self::factory()->term->create(
			array(
				'taxonomy' => 'hyper_season',
				'slug'     => 'image-season',
			)
		);

		$banner = $this->banner_at( add_query_arg( 'hyper_season', 'image-season', $this->archive_url() ) );

		$this->assertSame( $id, $banner['image'] );
	}

	public function test_a_single_post_has_no_image(): void {
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $this->image() ) );
		$post = self::factory()->post->create( array( 'post_type' => self::POST_TYPE ) );

		$this->assertArrayNotHasKey( 'image', $this->banner_at( get_permalink( $post ) ) );
	}

	public function test_a_season_taxonomy_page_has_no_image(): void {
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $this->image() ) );
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
		update_option( Settings::OPTION, array() );

		$this->assertArrayNotHasKey( 'image', $this->banner_at( $this->archive_url() ) );
	}

	public function test_a_deleted_attachment_gives_no_image(): void {
		$id = $this->image();
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $id ) );
		wp_delete_attachment( $id, true );

		$this->assertArrayNotHasKey( 'image', $this->banner_at( $this->archive_url() ) );
	}

	public function test_a_non_image_attachment_gives_no_image(): void {
		$id = self::factory()->attachment->create( array( 'post_mime_type' => 'text/plain' ) );
		update_option( Settings::OPTION, array( Settings::BANNER_IMAGE => $id ) );

		$this->assertArrayNotHasKey( 'image', $this->banner_at( $this->archive_url() ) );
	}

	public function test_the_settings_box_has_the_image_field_after_the_subtitle(): void {
		$ids = array();
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			if ( ( $box['settings_pages'] ?? '' ) !== \HyperPress\Competition\SettingsPage::ID ) {
				continue;
			}
			foreach ( $box['fields'] as $field ) {
				$ids[ $field['id'] ] = $field['type'];
			}
		}

		$this->assertSame( 'single_image', $ids[ Settings::BANNER_IMAGE ] ?? null );
		$keys = array_keys( $ids );
		$this->assertSame( array_search( Settings::BANNER_SUBTITLE, $keys, true ) + 1, array_search( Settings::BANNER_IMAGE, $keys, true ) );
	}
}
