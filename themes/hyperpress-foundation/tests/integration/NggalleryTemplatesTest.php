<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

require_once __DIR__ . '/stubs/nextgen.php';

final class NggalleryTemplatesTest extends WP_UnitTestCase {

	private function render( string $file, array $vars ): string {
		$path = dirname( __DIR__, 2 ) . '/nggallery/' . $file;
		ob_start();
		( static function ( string $template_file, array $template_vars ): void {
			extract( $template_vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $template_file;
		} )( $path, $vars );
		return (string) ob_get_clean();
	}

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
				'description'  => 'A caption',
				'alttext'      => 'Alt <b>text</b>',
				'thumbcode'    => 'class="lightbox" data-image-id="5"',
				'size'         => "width='240' height='160'",
				'style'        => 'style="width:25%;"',
				'hidden'       => false,
			),
			$over
		);
	}

	/** @return array<string,array{int,string}> */
	public static function columns(): array {
		return array(
			'four'     => array( 4, 'small-up-2 medium-up-3 large-up-4' ),
			'one'      => array( 1, 'small-up-1 medium-up-1 large-up-1' ),
			'two'      => array( 2, 'small-up-2 medium-up-2 large-up-2' ),
			'zero'     => array( 0, 'small-up-2 medium-up-3 large-up-4' ),
			'negative' => array( -3, 'small-up-2 medium-up-3 large-up-4' ),
			'too many' => array( 12, 'small-up-2 medium-up-3 large-up-8' ),
		);
	}

	/** @dataProvider columns */
	public function test_gallery_grid_follows_columns( int $columns, string $expected ): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery( array( 'columns' => $columns ) ),
				'images'     => array( $this->image() ),
				'pagination' => '',
			)
		);

		$this->assertStringContainsString( 'grid-x grid-margin-x ' . $expected, $html );
	}

	public function test_gallery_renders_a_visible_image_as_a_foundation_thumbnail(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image() ),
				'pagination' => '',
			)
		);

		$this->assertStringContainsString( '<div class="cell" id="ngg-image-5">', $html );
		$this->assertStringContainsString( 'class="thumbnail"', $html );
		$this->assertStringContainsString( 'data-image-id="5"', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
		$this->assertStringContainsString( "width='240' height='160'", $html );
		$this->assertStringContainsString( 'alt="Alt text"', $html );
		$this->assertStringContainsString( 'src="https://example.test/thumb-5.jpg"', $html );
	}

	public function test_gallery_image_attributes_go_through_the_nextgen_filter(): void {
		$add = static fn( array $attributes ): array => $attributes + array( 'data-no-lazy' => '1' );
		add_filter( 'ngg_gallery_image_attributes', $add );

		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image() ),
				'pagination' => '',
			)
		);

		remove_filter( 'ngg_gallery_image_attributes', $add );

		$this->assertMatchesRegularExpression( '/<img [^>]*class="ngg-gallery-thumbnail-img"[^>]*data-no-lazy="1"/', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
	}

	public function test_gallery_drops_legacy_nextgen_markup(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image(), $this->image( array( 'pid' => 6 ) ) ),
				'pagination' => '',
			)
		);

		$this->assertStringNotContainsString( 'ngg-gallery-thumbnail-box', $html );
		$this->assertStringNotContainsString( 'class="ngg-gallery-thumbnail"', $html );
		$this->assertStringContainsString( '<div class="ngg-galleryoverview" id="ngg-gallery-1-1">', $html );
		$this->assertStringNotContainsString( 'grid-x grid-margin-x ngg-', $html );
		$this->assertStringNotContainsString( 'style=', $html );
		$this->assertStringNotContainsString( '<br', $html );
	}

	public function test_gallery_puts_hidden_images_outside_the_grid_without_an_img(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image(), $this->image( array( 'pid' => 9, 'hidden' => true ) ) ),
				'pagination' => '',
			)
		);

		$this->assertSame( 1, substr_count( $html, '<img' ) );
		$this->assertMatchesRegularExpression( '/<div hidden>\s*<a [^>]*id="ngg-image-9"[^>]*data-image-id="5"/s', $html );
		$this->assertLessThan( strpos( $html, '<div hidden>' ), strrpos( $html, 'id="ngg-image-5"' ) );
	}

	public function test_gallery_escapes_plain_text_values(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(
					array(
						'anchor'              => '"><script>alert(1)</script>',
						'show_slideshow'      => true,
						'slideshow_link_text' => '<img src=x onerror=alert(1)>',
					)
				),
				'images'     => array( $this->image( array( 'description' => '" onmouseover="x' ) ) ),
				'pagination' => '',
			)
		);

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringNotContainsString( '<img src=x', $html );
		$this->assertStringNotContainsString( '" onmouseover="x', $html );
		$this->assertStringContainsString( '&lt;img src=x onerror=alert(1)&gt;', $html );
	}

	public function test_gallery_shows_the_slideshow_link_only_when_enabled(): void {
		$vars = array(
			'images'     => array( $this->image() ),
			'pagination' => '',
		);

		$off = $this->render( 'gallery.php', $vars + array( 'gallery' => $this->gallery() ) );
		$on  = $this->render( 'gallery.php', $vars + array( 'gallery' => $this->gallery( array( 'show_slideshow' => true ) ) ) );

		$this->assertStringNotContainsString( 'Show slideshow', $off );
		$this->assertStringContainsString( 'class="button small hollow" href="https://example.test/?show=slide"', $on );
		$this->assertStringContainsString( '[Show slideshow]', $on );
	}

	public function test_gallery_prints_foundation_pagination_inside_the_wrapper(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image() ),
				'pagination' => "<div class='ngg-navigation'><span class='current'>1</span><a class='page-numbers' data-pageid='2' href='?nggpage=2'>2</a></div>",
			)
		);

		$this->assertStringContainsString( '<ul class="pagination text-center">', $html );
		$this->assertMatchesRegularExpression( '/class=["\']page-numbers["\'] data-pageid=["\']2["\']/', $html );
		$this->assertLessThan( strrpos( $html, '</div>' ), strpos( $html, 'class="pagination' ) );
	}

	public function test_gallery_shows_a_callout_when_it_has_no_images(): void {
		$html = $this->render( 'gallery.php', array( 'gallery' => $this->gallery(), 'images' => array(), 'pagination' => '' ) );

		$this->assertStringContainsString( 'callout secondary', $html );
		$this->assertStringNotContainsString( 'grid-x', $html );
	}

	public function test_gallery_renders_nothing_without_a_gallery(): void {
		$this->assertSame(
			'',
			trim( $this->render( 'gallery.php', array( 'gallery' => null, 'images' => array(), 'pagination' => '' ) ) )
		);
	}
}
