<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SponsorContentTemplateTest extends WP_UnitTestCase {
	public function test_single_sponsor_supplies_a_readable_content_template(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_sponsor' ) );
		$this->go_to( get_permalink( $post_id ) );
		$path = apply_filters( 'content_template', '' );
		$this->assertNotSame( '', $path );
		$this->assertFileIsReadable( $path );
	}

	public static function website_links(): array {
		return array(
			'external'     => array( 'https://external.example/sponsor/', 'target="_blank" rel="noopener noreferrer"' ),
			'internal'     => array( home_url( '/x/' ), 'target="_self">' ),
			'internal pdf' => array( home_url( '/files/info.pdf' ), 'target="_blank" rel="noopener noreferrer"' ),
		);
	}

	/**
	 * @dataProvider website_links
	 */
	public function test_website_link_opens_according_to_its_url( string $url, string $expected ): void {
		$post_id = self::factory()->post->create(
			array(
				'post_type'  => 'hyper_sponsor',
				'meta_input' => array( 'hyperpress_sponsor_website' => $url ),
			)
		);
		$this->go_to( get_permalink( $post_id ) );
		the_post();

		ob_start();
		include dirname( __DIR__, 2 ) . '/hyperpress-sponsor/templates/content.php';
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( $expected, $html );
		if ( str_contains( $expected, '_self' ) ) {
			$this->assertStringNotContainsString( 'rel="noopener', $html );
		}
	}
}
