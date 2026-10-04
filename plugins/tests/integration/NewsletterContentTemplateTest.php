<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class NewsletterContentTemplateTest extends WP_UnitTestCase {
	public function test_single_newsletter_supplies_a_readable_content_template(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$this->go_to( get_permalink( $post_id ) );
		$path = apply_filters( 'content_template', '' );
		$this->assertNotSame( '', $path );
		$this->assertFileIsReadable( $path );
	}
}
