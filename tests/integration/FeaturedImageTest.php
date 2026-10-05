<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class FeaturedImageTest extends WP_UnitTestCase {

	private function render_part(): string {
		ob_start();
		get_template_part( 'template-parts/featured-image' );
		return (string) ob_get_clean();
	}

	public function test_part_prints_nothing_and_raises_no_warning_without_a_current_post(): void {
		unset( $GLOBALS['post'] );

		$this->assertSame( '', trim( $this->render_part() ) );
	}

	public function test_part_prints_the_hero_header_for_a_post_with_a_thumbnail(): void {
		$post_id       = self::factory()->post->create();
		$attachment_id = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.jpg', $post_id );
		set_post_thumbnail( $post_id, $attachment_id );
		$GLOBALS['post'] = get_post( $post_id );

		$html = $this->render_part();

		$this->assertStringContainsString( '<header class="featured-hero"', $html );
		$this->assertStringContainsString( 'data-interchange=', $html );
	}

	public function test_part_prints_nothing_for_a_post_without_a_thumbnail(): void {
		$GLOBALS['post'] = get_post( self::factory()->post->create() );

		$this->assertSame( '', trim( $this->render_part() ) );
	}
}
