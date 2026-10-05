<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class GalleryCaptionEscapingTest extends WP_UnitTestCase {

	private const PAYLOAD = '<pre>" onfocus="x" autofocus x="';

	private function attachment(): int {
		$id = self::factory()->attachment->create_object(
			array(
				'post_mime_type' => 'image/jpeg',
				'post_title'     => 'pic',
				'post_excerpt'   => self::PAYLOAD,
				'post_status'    => 'inherit',
			)
		);
		update_post_meta( $id, '_wp_attached_file', 'pic.jpg' );
		return (int) $id;
	}

	/** @return \DOMElement[] every element in the output carrying an attribute named like an event handler or autofocus */
	private function dangerous_attributes( string $html ): array {
		$dom = new \DOMDocument();
		@$dom->loadHTML( '<?xml encoding="utf-8"?><div>' . $html . '</div>' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		$found = array();
		foreach ( ( new \DOMXPath( $dom ) )->query( '//*[@onfocus or @autofocus or @x]' ) as $node ) {
			$found[] = $node;
		}
		return $found;
	}

	public static function links(): array {
		return array(
			'link to file (lightbox)' => array( 'file' ),
			'link to attachment page' => array( '' ),
		);
	}

	/** @dataProvider links */
	public function test_caption_cannot_break_out_of_the_attribute( string $link ): void {
		$id    = $this->attachment();
		$attrs = '' === $link ? '' : ' link="' . $link . '"';
		$html  = do_shortcode( '[gallery ids="' . $id . '"' . $attrs . ']' );

		$this->assertNotSame( '', $html, 'gallery rendered' );
		$this->assertSame( array(), $this->dangerous_attributes( $html ), 'no injected attributes' );
	}

	public function test_title_attribute_holds_the_escaped_caption(): void {
		$html = do_shortcode( '[gallery ids="' . $this->attachment() . '" link="file"]' );
		$this->assertStringContainsString( 'title="' . esc_attr( self::PAYLOAD ) . '"', $html );
		$this->assertStringContainsString( 'data-title="' . esc_attr( self::PAYLOAD ) . '"', $html );
	}

	public function test_lightbox_link_works_without_a_global_post(): void {
		$GLOBALS['post'] = null;
		$html            = do_shortcode( '[gallery ids="' . $this->attachment() . '" link="file"]' );
		$this->assertStringContainsString( 'data-gall="fp-gallery-0"', $html );
	}
}
