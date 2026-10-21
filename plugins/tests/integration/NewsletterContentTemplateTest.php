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

	public function test_canva_link_opens_in_a_new_tab_with_noopener_noreferrer(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_type'  => 'hyper_newsletter',
				'meta_input' => array( 'hyperpress_newsletter_canva_url' => 'https://www.canva.com/design/abc/view' ),
			)
		);
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		ob_start();
		include dirname( __DIR__, 2 ) . '/hyperpress-newsletter/templates/content.php';
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'href="https://www.canva.com/design/abc/view" target="_blank" rel="noopener noreferrer">View on Canva</a>', $html );
	}
}
