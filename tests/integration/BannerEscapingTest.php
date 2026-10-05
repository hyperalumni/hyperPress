<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class BannerEscapingTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		// Core still hooks the deprecated the_block_template_skip_link() on wp_body_open.
		remove_action( 'wp_body_open', 'the_block_template_skip_link' );
	}

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_banner_content' );
		parent::tear_down();
	}

	private function render_banner( array $banner ): string {
		add_filter(
			'hyperpress_banner_content',
			static function () use ( $banner ) {
				return $banner;
			}
		);
		$this->go_to( home_url( '/' ) );
		ob_start();
		include get_template_directory() . '/template-parts/banner.php';
		return ob_get_clean();
	}

	public function test_hostile_banner_values_cannot_break_out_of_attributes_or_inject_markup(): void {
		$html = $this->render_banner(
			array(
				'type'            => 'x" onmouseover="1',
				'title'           => '<script>1</script><em>ok</em>',
				'subtitle'        => '',
				'backgroundColor' => 'red;background:url(x)',
				'buttonText'      => '',
				'buttonLink'      => '',
			)
		);

		// The hostile text may remain as escaped attribute content; what matters is that it never becomes an attribute.
		$dom = new \DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8"?>' . $html, LIBXML_NOERROR );
		$header = $dom->getElementsByTagName( 'header' )->item( 0 );
		$this->assertNotNull( $header );
		$this->assertFalse( $header->hasAttribute( 'onmouseover' ), 'the type must not break out of the class attribute' );
		$this->assertFalse( $header->hasAttribute( 'style' ), 'an invalid colour prints no style attribute' );
		$this->assertStringNotContainsString( 'url(x)', $html );
		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringContainsString( '<em>ok</em>', $html, 'harmless markup in the title is kept' );
	}

	public function test_front_banner_button_never_links_to_a_javascript_url(): void {
		$html = $this->render_banner(
			array(
				'type'            => 'front',
				'title'           => 'Hi',
				'subtitle'        => '',
				'backgroundColor' => '',
				'buttonText'      => 'Go <b>now</b>',
				'buttonLink'      => 'javascript:alert(1)',
			)
		);

		$this->assertStringNotContainsString( 'javascript:', $html );
		$this->assertStringNotContainsString( '<b>', $html );
	}

	public function test_valid_values_render_as_before(): void {
		$html = $this->render_banner(
			array(
				'type'            => 'front',
				'title'           => 'Welcome',
				'subtitle'        => '',
				'backgroundColor' => '#cc0000',
				'buttonText'      => 'Join',
				'buttonLink'      => 'https://example.org/join?a=1&b=2',
			)
		);

		$this->assertStringContainsString( 'class="banner front"', $html );
		$this->assertStringContainsString( 'style="background-color: #cc0000;"', $html );
		$this->assertStringContainsString( '<h2 class="entry-title">Welcome</h2>', $html );
		$this->assertStringContainsString( 'href="https://example.org/join?a=1&#038;b=2"', $html );
		$this->assertStringContainsString( '<h4>Join</h4>', $html );
	}
}
