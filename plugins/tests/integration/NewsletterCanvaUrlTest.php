<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class NewsletterCanvaUrlTest extends WP_UnitTestCase {

	private function render_with_canva_url( string $url ): string {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		update_post_meta( $post_id, 'hyperpress_newsletter_canva_url', $url );
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		ob_start();
		include dirname( __DIR__, 2 ) . '/hyperpress-newsletter/templates/content.php';
		return (string) ob_get_clean();
	}

	public function test_valid_canva_url_renders_the_embed_iframe(): void {
		$url    = 'https://www.canva.com/design/XYZ/view';
		$output = $this->render_with_canva_url( $url );

		$this->assertSame( 1, preg_match( '/<iframe[^>]*\ssrc="([^"]*)"/', $output, $matches ) );
		$this->assertStringStartsWith( $url, $matches[1] );
		$this->assertStringEndsWith( '?embed', $matches[1] );
	}

	public function test_canva_subdomain_is_accepted(): void {
		$output = $this->render_with_canva_url( 'https://design.canva.com/x/view' );

		$this->assertStringContainsString( '<iframe', $output );
	}

	public static function rejected_urls(): array {
		return array(
			'javascript scheme'    => array( 'javascript:alert(1)' ),
			'foreign host'         => array( 'https://evil.example/x' ),
			'canva as subdomain'   => array( 'https://canva.com.evil.example/x' ),
			'plain http'           => array( 'http://www.canva.com/x' ),
			'attribute breakout'   => array( 'https://www.canva.com/x" onload="y' ),
		);
	}

	/**
	 * @dataProvider rejected_urls
	 */
	public function test_other_urls_render_no_iframe_and_no_payload( string $url ): void {
		$output = $this->render_with_canva_url( $url );

		$this->assertStringNotContainsString( '<iframe', $output );
		$this->assertStringNotContainsString( 'javascript:', $output );
		$this->assertStringNotContainsString( 'evil.example', $output );
		$this->assertStringNotContainsString( 'onload', $output );
	}
}
