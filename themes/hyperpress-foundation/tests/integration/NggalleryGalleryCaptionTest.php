<?php
namespace HyperPress\ThemeTests\Integration;

use HyperPress\ThemeTests\Integration\Concerns\NggalleryTemplateHelpers;
use WP_UnitTestCase;

require_once __DIR__ . '/Concerns/NggalleryTemplateHelpers.php';

final class NggalleryGalleryCaptionTest extends WP_UnitTestCase {

	use NggalleryTemplateHelpers;

	private function gallery( array $over = array() ): object {
		return (object) array_merge(
			array(
				'anchor'              => 'ngg-gallery-1-1',
				'columns'             => 4,
				'show_slideshow'      => false,
				'slideshow_link'      => 'https://example.test/?show=slide',
				'slideshow_link_text' => '[Show slideshow]',
			),
			$over
		);
	}

	private function image( array $over = array() ): object {
		return (object) array_merge(
			array(
				'pid'          => 5,
				'imageURL'     => 'https://example.test/full-5.jpg',
				'thumbnailURL' => 'https://example.test/thumb-5.jpg',
				'description'  => 'A description',
				'caption'      => 'A <em>caption</em>',
				'alttext'      => 'Alt <b>text</b>',
				'thumbcode'    => 'class="lightbox" data-image-id="5"',
				'size'         => "width='240' height='160'",
				'hidden'       => false,
			),
			$over
		);
	}

	private function vars( array $over = array() ): array {
		return array_merge(
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image() ),
				'pagination' => '',
			),
			$over
		);
	}

	private function render_caption( array $over = array() ): string {
		return $this->render( 'gallery-caption.php', $this->vars( $over ) );
	}

	public function test_each_visible_image_is_a_figure_with_a_thumbnail_link_and_a_caption(): void {
		$html = $this->render_caption();

		$this->assertMatchesRegularExpression(
			'/<div class="cell" id="ngg-image-5">\s*<figure>\s*<a [^>]*class="[^"]*\bthumbnail\b[^"]*"[^>]*>\s*<img [^>]*>\s*<\/a>\s*<figcaption class="text-center">A <em>caption<\/em><\/figcaption>\s*<\/figure>\s*<\/div>/s',
			$html
		);
	}

	public function test_the_thumbnail_anchor_has_a_single_merged_class_attribute(): void {
		$html = $this->render_caption();

		$this->assertSame( 1, preg_match( '/<figure>\s*(<a [^>]*>)/s', $html, $match ) );
		$this->assertSame( 1, substr_count( $match[1], 'class=' ) );
		$this->assertStringContainsString( 'class="lightbox thumbnail"', $match[1] );
		$this->assertStringContainsString( 'data-image-id="5"', $match[1] );
	}

	public function test_the_caption_is_sanitised_with_wp_kses_post(): void {
		$html = $this->render_caption( array( 'images' => array( $this->image( array( 'caption' => '<strong>Bold</strong><script>x()</script>' ) ) ) ) );

		$this->assertStringContainsString( '<figcaption class="text-center"><strong>Bold</strong>', $html );
		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_a_stored_caption_is_decoded_the_way_the_stock_template_does(): void {
		$html = $this->render_caption( array( 'images' => array( $this->image( array( 'caption' => 'It&#039;s <strong>great</strong>' ) ) ) ) );

		$this->assertStringContainsString( '<figcaption class="text-center">It\'s <strong>great</strong></figcaption>', $html );
		$this->assertStringNotContainsString( '&#039;', $html );
	}

	public function test_an_entity_encoded_script_in_a_caption_is_removed_after_decoding(): void {
		$html = $this->render_caption( array( 'images' => array( $this->image( array( 'caption' => '&lt;script&gt;alert(1)&lt;/script&gt;ok' ) ) ) ) );

		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringContainsString( '<figcaption class="text-center">', $html );
	}

	public function test_an_empty_or_missing_caption_renders_no_figcaption(): void {
		$missing = $this->image();
		unset( $missing->caption );

		foreach ( array( $this->image( array( 'caption' => '' ) ), $this->image( array( 'caption' => null ) ), $missing ) as $image ) {
			$html = $this->render_caption( array( 'images' => array( $image ) ) );

			$this->assertStringNotContainsString( 'figcaption', $html );
			$this->assertStringContainsString( '<figure>', $html );
		}
	}

	public function test_the_wrapper_keeps_nextgens_ajax_hooks_without_template_classes(): void {
		$html = $this->render_caption();

		$this->assertStringContainsString( '<div class="ngg-galleryoverview" id="ngg-gallery-1-1">', $html );
		$this->assertStringNotContainsString( 'ngg-template-caption', $html );
		$this->assertStringNotContainsString( 'grid-x grid-margin-x ngg-', $html );
		$this->assertStringNotContainsString( 'ngg-gallery-thumbnail-box', $html );
	}

	/** @return array<string,array{int,string}> */
	public static function columns(): array {
		return array(
			'four' => array( 4, 'small-up-2 medium-up-3 large-up-4' ),
			'two'  => array( 2, 'small-up-2 medium-up-2 large-up-2' ),
			'zero' => array( 0, 'small-up-2 medium-up-3 large-up-4 xxlarge-up-5' ),
		);
	}

	/** @dataProvider columns */
	public function test_the_grid_follows_columns( int $columns, string $expected ): void {
		$html = $this->render_caption( array( 'gallery' => $this->gallery( array( 'columns' => $columns ) ) ) );

		$this->assertStringContainsString( 'grid-x grid-margin-x ' . $expected, $html );
	}

	public function test_explicit_columns_have_no_xxlarge_class(): void {
		$this->assertStringNotContainsString( 'xxlarge-up', $this->render_caption() );
	}

	public function test_hidden_images_are_anchors_after_the_grid_without_a_figure(): void {
		$html = $this->render_caption( array( 'images' => array( $this->image(), $this->image( array( 'pid' => 9, 'hidden' => true ) ) ) ) );

		$this->assertSame( 1, substr_count( $html, '<figure>' ) );
		$this->assertSame( 1, substr_count( $html, '<img' ) );
		$this->assertSame( 1, substr_count( $html, 'figcaption class' ) );
		$this->assertMatchesRegularExpression( '/<div hidden>\s*<a [^>]*id="ngg-image-9"/s', $html );
		$this->assertLessThan( strpos( $html, '<div hidden>' ), strrpos( $html, '</figure>' ) );
	}

	public function test_the_description_callout_and_slideshow_link_come_before_the_grid(): void {
		$html = $this->render_caption(
			array(
				'gallery' => $this->gallery(
					array(
						'show_slideshow' => true,
						'title'          => 'Spring',
						'description'    => '<strong>Notes</strong><script>x()</script>',
					)
				),
			)
		);

		$this->assertStringContainsString( 'gallery-info callout primary', $html );
		$this->assertStringContainsString( '<strong>Notes</strong>', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertLessThan( strpos( $html, 'Show slideshow' ), strpos( $html, 'gallery-info' ) );
		$this->assertLessThan( strpos( $html, 'grid-x' ), strpos( $html, 'Show slideshow' ) );
	}

	public function test_an_empty_gallery_shows_a_callout_and_no_grid(): void {
		$html = $this->render_caption( array( 'images' => array() ) );

		$this->assertStringContainsString( 'callout secondary', $html );
		$this->assertStringNotContainsString( 'grid-x', $html );
		$this->assertStringNotContainsString( '<figure>', $html );
	}

	public function test_pagination_is_foundation_markup_inside_the_wrapper(): void {
		$html = $this->render_caption( array( 'pagination' => "<div class='ngg-navigation'><span class='current'>1</span><a class='page-numbers' data-pageid='2' href='?nggpage=2'>2</a></div>" ) );

		$this->assertStringContainsString( '<ul class="pagination text-center">', $html );
		$this->assertLessThan( strrpos( $html, '</div>' ), strpos( $html, 'class="pagination' ) );
	}

	public function test_it_renders_nothing_without_a_gallery(): void {
		$this->assertSame( '', trim( $this->render_caption( array( 'gallery' => null, 'images' => array() ) ) ) );
	}

	public function test_plain_gallery_output_has_no_figure_or_figcaption(): void {
		$html = $this->render( 'gallery.php', $this->vars() );

		$this->assertStringNotContainsString( '<figure', $html );
		$this->assertStringNotContainsString( 'figcaption', $html );
		$this->assertStringContainsString( '<div class="cell" id="ngg-image-5">', $html );
	}
}
