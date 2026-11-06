<?php
namespace HyperPress\ThemeTests\Integration;

use HyperPress\ThemeTests\Integration\Concerns\NggalleryTemplateHelpers;
use WP_UnitTestCase;

require_once __DIR__ . '/Concerns/NggalleryTemplateHelpers.php';

final class NggalleryImageBrowserTest extends WP_UnitTestCase {

	use NggalleryTemplateHelpers;

	private function image( array $over = array() ): object {
		return (object) array_merge(
			array(
				'anchor'              => 'ngg-imagebrowser-abc123-42',
				'previous_pid'        => 41,
				'previous_image_link' => 'https://example.test/gallery/image/41',
				'next_pid'            => 43,
				'next_image_link'     => 'https://example.test/gallery/image/43',
				'number'              => 3,
				'total'               => 12,
				'alttext'             => 'Goal celebration',
				'href_link'           => '<a href="https://example.test/full-42.jpg" class="ngg-simplelightbox" data-image-id="42" rel="set-1"><img src="https://example.test/photo-42.jpg" alt="Goal celebration"></a>',
				'description'         => 'Second half <em>winner</em>',
			),
			$over
		);
	}

	private function vars( array $over = array() ): array {
		return array_merge(
			array(
				'image'             => $this->image(),
				'displayed_gallery' => new class() {
					public function id(): string {
						return 'dg-77';
					}
				},
			),
			$over
		);
	}

	/** @return array<string,array{string}> */
	public static function templates(): array {
		return array(
			'imagebrowser'         => array( 'imagebrowser.php' ),
			'imagebrowser-caption' => array( 'imagebrowser-caption.php' ),
		);
	}

	/** @dataProvider templates */
	public function test_the_wrapper_keeps_the_hooks_the_ajax_script_binds_to( string $file ): void {
		$html = $this->render( $file, $this->vars() );

		$this->assertStringContainsString( '<div class="ngg-imagebrowser" id="ngg-imagebrowser-abc123-42" data-nextgen-gallery-id="dg-77">', $html );
	}

	/** @dataProvider templates */
	public function test_the_title_is_an_h3( string $file ): void {
		$this->assertStringContainsString( '<h3>Goal celebration</h3>', $this->render( $file, $this->vars() ) );
	}

	/** @dataProvider templates */
	public function test_the_title_is_decoded_like_the_stock_template( string $file ): void {
		$html = $this->render( $file, $this->vars( array( 'image' => $this->image( array( 'alttext' => 'It&#039;s a <em>test</em>' ) ) ) ) );

		$this->assertStringContainsString( '<h3>It\'s a <em>test</em></h3>', $html );
	}

	/** @dataProvider templates */
	public function test_an_entity_encoded_script_in_the_title_is_removed( string $file ): void {
		$html = $this->render( $file, $this->vars( array( 'image' => $this->image( array( 'alttext' => '&lt;script&gt;alert(1)&lt;/script&gt;Title' ) ) ) ) );

		preg_match( '/<h3>(.*?)<\/h3>/s', $html, $m );
		$this->assertStringNotContainsString( '<script', $m[1] );
		$this->assertStringContainsString( 'Title', $m[1] );
	}

	/** @dataProvider templates */
	public function test_a_raw_script_in_the_title_is_stripped( string $file ): void {
		$html = $this->render( $file, $this->vars( array( 'image' => $this->image( array( 'alttext' => '<script>alert(1)</script>Title' ) ) ) ) );

		preg_match( '/<h3>(.*?)<\/h3>/s', $html, $m );
		$this->assertStringNotContainsString( '<script', $m[1] );
		$this->assertStringContainsString( 'Title', $m[1] );
	}

	/** @dataProvider templates */
	public function test_the_image_link_gets_thumbnail_merged_into_nextgens_own_class( string $file ): void {
		$html = $this->render( $file, $this->vars() );

		$this->assertMatchesRegularExpression( '/<div class="text-center">\s*<a [^>]*class="ngg-simplelightbox thumbnail"[^>]*>\s*<img /', $html );
		$this->assertSame( 1, preg_match_all( '/<a [^>]*href="https:\/\/example\.test\/full-42\.jpg"[^>]*>/', $html, $m ) );
		$this->assertSame( 1, substr_count( $m[0][0], 'class=' ) );
		$this->assertStringContainsString( 'data-image-id="42"', $html );
		$this->assertStringContainsString( 'rel="set-1"', $html );
		$this->assertStringContainsString( 'src="https://example.test/photo-42.jpg"', $html );
	}

	/** @dataProvider templates */
	public function test_an_image_without_an_anchor_is_printed_unchanged( string $file ): void {
		$html = $this->render( $file, $this->vars( array( 'image' => $this->image( array( 'href_link' => '<img src="https://example.test/bare.jpg" alt="Bare">' ) ) ) ) );

		$this->assertMatchesRegularExpression( '/<div class="text-center">\s*<img src="https:\/\/example\.test\/bare\.jpg" alt="Bare">\s*<\/div>/', $html );
		$this->assertStringNotContainsString( 'thumbnail', $html );
	}

	/** @dataProvider templates */
	public function test_previous_and_next_are_hollow_buttons_keeping_the_nextgen_hooks( string $file ): void {
		$html = $this->render( $file, $this->vars() );

		$this->assertStringContainsString( '<div class="button-group', $html );
		$this->assertStringContainsString( '<a class="button hollow ngg-browser-prev" id="ngg-prev-41" href="https://example.test/gallery/image/41">', $html );
		$this->assertStringContainsString( '<a class="button hollow ngg-browser-next" id="ngg-next-43" href="https://example.test/gallery/image/43">', $html );
		$this->assertStringContainsString( 'Previous', $html );
		$this->assertStringContainsString( 'Next', $html );
		$this->assertStringNotContainsString( 'disabled', $html );
	}

	/** @dataProvider templates */
	public function test_the_first_image_has_a_disabled_previous_placeholder( string $file ): void {
		$html = $this->render( $file, $this->vars( array( 'image' => $this->image( array( 'previous_pid' => 0, 'previous_image_link' => '' ) ) ) ) );

		$this->assertMatchesRegularExpression( '/<span class="button hollow disabled"[^>]*>[^<]*Previous/', $html );
		$this->assertStringNotContainsString( 'ngg-browser-prev', $html );
		$this->assertStringNotContainsString( 'ngg-prev-', $html );
		$this->assertStringContainsString( 'ngg-browser-next', $html );
	}

	/** @dataProvider templates */
	public function test_the_last_image_has_a_disabled_next_placeholder( string $file ): void {
		$html = $this->render( $file, $this->vars( array( 'image' => $this->image( array( 'next_pid' => 0, 'next_image_link' => '' ) ) ) ) );

		$this->assertMatchesRegularExpression( '/<span class="button hollow disabled"[^>]*>[^<]*Next/', $html );
		$this->assertStringNotContainsString( 'ngg-browser-next', $html );
		$this->assertStringNotContainsString( 'ngg-next-', $html );
		$this->assertStringContainsString( 'ngg-browser-prev', $html );
	}

	/** @dataProvider templates */
	public function test_the_counter_reads_image_n_of_m( string $file ): void {
		$html = $this->render( $file, $this->vars() );

		$this->assertStringContainsString( 'Image 3 of 12', $html );
	}

	/** @dataProvider templates */
	public function test_the_description_is_a_sanitized_callout( string $file ): void {
		$html = $this->render( $file, $this->vars( array( 'image' => $this->image( array( 'description' => 'Second half <em>winner</em><script></script>' ) ) ) ) );

		$this->assertMatchesRegularExpression( '/<div class="callout[^"]*">\s*Second half <em>winner<\/em>\s*<\/div>/', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	/** @dataProvider templates */
	public function test_an_empty_description_renders_no_callout( string $file ): void {
		$html = $this->render( $file, $this->vars( array( 'image' => $this->image( array( 'description' => '' ) ) ) ) );

		$this->assertStringNotContainsString( 'callout', $html );
	}

	/** @dataProvider templates */
	public function test_no_ngg_classes_beyond_the_ajax_hooks( string $file ): void {
		$html = $this->render( $file, $this->vars() );

		preg_match_all( '/ngg-[a-z-]+/', preg_replace( '/id="[^"]*"/', '', $html ), $m );
		$this->assertEqualsCanonicalizing( array( 'ngg-imagebrowser', 'ngg-browser-prev', 'ngg-browser-next', 'ngg-simplelightbox' ), array_unique( $m[0] ) );
	}

	/** @dataProvider templates */
	public function test_nothing_renders_without_an_image( string $file ): void {
		$this->assertSame( '', trim( $this->render( $file, $this->vars( array( 'image' => null ) ) ) ) );
	}

	public function test_both_templates_render_identical_markup(): void {
		$this->assertSame(
			$this->render( 'imagebrowser.php', $this->vars() ),
			$this->render( 'imagebrowser-caption.php', $this->vars() )
		);
	}
}
