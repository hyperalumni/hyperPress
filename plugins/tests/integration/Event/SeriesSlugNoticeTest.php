<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Plugin;
use HyperPress\Event\Slugs;
use WP_UnitTestCase;

final class SeriesSlugNoticeTest extends WP_UnitTestCase {

	private function series( string $slug = 'camp' ): \WP_Term {
		return self::factory()->term->create_and_get( array( 'taxonomy' => Plugin::SERIES, 'name' => 'Camp', 'slug' => $slug ) );
	}

	private function screen( \WP_Term $term, string $taxonomy = Plugin::SERIES ): string {
		ob_start();
		do_action( $taxonomy . '_edit_form', $term, $taxonomy );

		return (string) ob_get_clean();
	}

	public function test_registered_on_the_series_edit_form_only(): void {
		$this->assertNotFalse( has_action( 'hyper_event_series_edit_form', array( Slugs::class, 'conflict_notice' ) ) );
		$this->assertFalse( has_action( 'category_edit_form', array( Slugs::class, 'conflict_notice' ) ) );
	}

	public function test_notice_when_an_event_has_the_series_slug(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_status' => 'draft', 'post_name' => 'camp', 'post_title' => 'The Camp' ) );

		$html = $this->screen( $this->series() );

		$this->assertStringContainsString( 'notice notice-warning inline', $html );
		$this->assertStringContainsString( '/event/camp/', $html );
		$this->assertStringContainsString( 'The Camp', $html );
		$this->assertStringContainsString( 'href="' . esc_url( (string) get_edit_post_link( $id, 'raw' ) ) . '"', $html );
	}

	public function test_nothing_without_a_same_slug_event(): void {
		self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_status' => 'publish', 'post_name' => 'other' ) );
		self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'camp' ) );

		$this->assertSame( '', $this->screen( $this->series() ) );
	}

	public function test_nothing_for_a_trashed_same_slug_event(): void {
		$id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_status' => 'publish', 'post_name' => 'camp' ) );
		wp_trash_post( $id );

		$this->assertSame( '', $this->screen( $this->series() ) );
	}

	public function test_output_is_escaped(): void {
		self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_status' => 'publish', 'post_name' => 'camp', 'post_title' => '<script>alert(1)</script>' ) );

		$html = $this->screen( $this->series() );

		$this->assertStringContainsString( 'notice-warning', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_nothing_for_other_taxonomies(): void {
		self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_status' => 'publish', 'post_name' => 'camp' ) );
		$term = self::factory()->term->create_and_get( array( 'taxonomy' => 'category', 'slug' => 'camp' ) );

		$this->assertSame( '', $this->screen( $term, 'category' ) );
	}
}
