<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

require_once __DIR__ . '/stubs/nextgen.php';

final class NggalleryTemplatesTest extends WP_UnitTestCase {

	private const RENDERING_HOOK = 'ngg_displayed_gallery_rendering';

	public function tear_down(): void {
		remove_all_filters( self::RENDERING_HOOK );
		parent::tear_down();
	}

	/** Registers a stand-in for NextGEN's description filter and returns its callback. */
	private function register_nextgen_description_filter(): array {
		$controller = new class() {
			public function add_description_to_legacy_templates( $html ) {
				return $html;
			}
		};
		$callback   = array( $controller, 'add_description_to_legacy_templates' );
		add_filter( self::RENDERING_HOOK, $callback, 8, 2 );
		return $callback;
	}

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
		$this->assertStringContainsString( 'class="lightbox thumbnail"', $html );
		$this->assertStringContainsString( 'data-image-id="5"', $html );
		$this->assertStringContainsString( 'loading="lazy"', $html );
		$this->assertStringContainsString( "width='240' height='160'", $html );
		$this->assertStringContainsString( 'alt="Alt text"', $html );
		$this->assertStringContainsString( 'src="https://example.test/thumb-5.jpg"', $html );
	}

	public function test_gallery_merges_the_thumbnail_class_into_the_thumbcode_class(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image( array( 'thumbcode' => 'class="ngg-simplelightbox" rel="x" data-image-id="5"' ) ) ),
				'pagination' => '',
			)
		);

		$this->assertSame( 1, preg_match( '/<div class="cell" id="ngg-image-5">\s*(<a [^>]*>)/s', $html, $match ) );
		$anchor = $match[1];

		$this->assertSame( 1, substr_count( $anchor, 'class=' ), 'The anchor must carry exactly one class attribute.' );
		$this->assertSame( 1, preg_match( '/class="([^"]*)"/', $anchor, $classes ) );
		$this->assertContains( 'ngg-simplelightbox', preg_split( '/\s+/', $classes[1] ) );
		$this->assertContains( 'thumbnail', preg_split( '/\s+/', $classes[1] ) );
		$this->assertStringContainsString( 'rel="x"', $anchor );
		$this->assertStringContainsString( 'data-image-id="5"', $anchor );
	}

	public function test_gallery_adds_the_thumbnail_class_when_the_thumbcode_has_none(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image( array( 'thumbcode' => 'rel="x" data-image-id="5"' ) ) ),
				'pagination' => '',
			)
		);

		$this->assertSame( 1, preg_match( '/<div class="cell" id="ngg-image-5">\s*(<a [^>]*>)/s', $html, $match ) );
		$this->assertSame( 1, substr_count( $match[1], 'class=' ) );
		$this->assertStringContainsString( 'class="thumbnail"', $match[1] );
		$this->assertStringContainsString( 'rel="x"', $match[1] );
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

	public function test_gallery_with_only_hidden_images_has_no_grid_and_no_callout_but_keeps_the_lightbox_anchors(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(),
				'images'     => array( $this->image( array( 'pid' => 8, 'hidden' => true ) ), $this->image( array( 'pid' => 9, 'hidden' => true ) ) ),
				'pagination' => '',
			)
		);

		$this->assertStringNotContainsString( 'grid-x', $html );
		$this->assertStringNotContainsString( 'callout secondary', $html );
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertMatchesRegularExpression( '/<div hidden>\s*<a [^>]*id="ngg-image-8".*id="ngg-image-9"/s', $html );
	}

	public function test_gallery_prints_the_description_callout_before_the_slideshow_link(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(
					array(
						'show_slideshow' => true,
						'description'    => 'Some notes',
					)
				),
				'images'     => array( $this->image() ),
				'pagination' => '',
			)
		);

		$this->assertNotFalse( strpos( $html, 'gallery-info' ) );
		$this->assertNotFalse( strpos( $html, 'Show slideshow' ) );
		$this->assertLessThan( strpos( $html, 'Show slideshow' ), strpos( $html, 'gallery-info' ) );
	}

	public function test_gallery_shows_its_title_and_description_in_a_callout_above_the_grid(): void {
		$html = $this->render(
			'gallery.php',
			array(
				'gallery'    => $this->gallery(
					array(
						'title'       => 'Spring <i>2026</i>',
						'description' => '<strong>Notes</strong><script>x()</script>',
					)
				),
				'images'     => array( $this->image() ),
				'pagination' => '',
			)
		);

		$this->assertStringContainsString( 'gallery-info callout primary', $html );
		$this->assertStringContainsString( '<h5>Spring &lt;i&gt;2026&lt;/i&gt;</h5>', $html );
		$this->assertStringContainsString( '<strong>Notes</strong>', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertLessThan( strpos( $html, 'grid-x' ), strpos( $html, 'gallery-info' ) );
	}

	public function test_gallery_has_no_callout_without_a_description(): void {
		$vars = array(
			'images'     => array( $this->image() ),
			'pagination' => '',
		);

		$without = $this->render( 'gallery.php', $vars + array( 'gallery' => $this->gallery() ) );
		$title   = $this->render(
			'gallery.php',
			$vars + array(
				'gallery' => $this->gallery(
					array(
						'title'       => 'Only a title',
						'description' => '',
					)
				),
			)
		);

		$this->assertStringNotContainsString( 'gallery-info', $without );
		$this->assertStringNotContainsString( 'gallery-info', $title );
	}

	public function test_gallery_with_a_description_takes_over_nextgens_description_filter(): void {
		$callback = $this->register_nextgen_description_filter();

		$this->render( 'gallery.php', array( 'gallery' => $this->gallery( array( 'description' => 'Some notes' ) ), 'images' => array( $this->image() ), 'pagination' => '' ) );

		$this->assertFalse( has_filter( self::RENDERING_HOOK, $callback ) );
	}

	public function test_gallery_without_a_description_leaves_nextgens_description_filter(): void {
		$callback = $this->register_nextgen_description_filter();

		$this->render( 'gallery.php', array( 'gallery' => $this->gallery( array( 'description' => '' ) ), 'images' => array( $this->image() ), 'pagination' => '' ) );

		$this->assertSame( 8, has_filter( self::RENDERING_HOOK, $callback ) );
	}

	public function test_gallery_renders_nothing_without_a_gallery(): void {
		$this->assertSame(
			'',
			trim( $this->render( 'gallery.php', array( 'gallery' => null, 'images' => array(), 'pagination' => '' ) ) )
		);
	}

	private function album( array $over = array() ): object {
		return (object) array_merge(
			array(
				'name'      => 'Spring <i>2026</i>',
				'albumdesc' => '<strong>Photos</strong><script>x()</script>',
			),
			$over
		);
	}

	private function album_gallery( array $over = array() ): object {
		return (object) array_merge(
			array(
				'title'                                => 'Match day',
				'pagelink'                             => 'https://example.test/gallery/match-day',
				'previewurl'                           => 'https://example.test/preview.jpg',
				'previewpic'                           => 77,
				'previewpic_fullsized_url'             => 'https://example.test/full-77.jpg',
				'previewpic_fullsized_display_url'     => 'https://example.test/full-77.jpg?v=2',
				'previewpic_image'                     => (object) array(
					'alttext'     => 'Cover alt',
					'description' => 'Cover caption',
				),
				'entity_type'                          => 'gallery',
				'displayed_gallery'                    => (object) array( 'effect_code' => 'class="lightbox" rel="x"' ),
				'counter'                              => 12,
				'author'                               => 0,
				'galdesc'                              => 'Gallery <em>notes</em>',
			),
			$over
		);
	}

	private function album_vars( array $over = array() ): array {
		return array_merge(
			array(
				'album'               => $this->album(),
				'galleries'           => array( $this->album_gallery() ),
				'pagination'          => '',
				'enable_descriptions' => 1,
				'image_gen_params'    => array(
					'width'  => 300,
					'height' => 200,
				),
			),
			$over
		);
	}

	public function test_album_uses_the_foundation_card_structure(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars() );

		$this->assertStringContainsString( 'grid-x grid-margin-x small-up-1 medium-up-2 large-up-3 xlarge-up-4', $html );
		$this->assertStringContainsString( '<div class="cell grid-y">', $html );
		$this->assertStringContainsString( '<div class="card flex-child-auto">', $html );
		$this->assertStringContainsString( '<div class="card-section flex-child-auto">', $html );
		foreach ( array( 'card-image', 'align-stretch', 'align-bottom', 'small-12', 'ngg-album', 'class="Thumb"' ) as $legacy ) {
			$this->assertStringNotContainsString( $legacy, $html );
		}
	}

	public function test_album_image_links_to_the_gallery_with_alt_and_lazy_loading(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars() );

		$this->assertMatchesRegularExpression( '/<a href="https:\/\/example\.test\/gallery\/match-day">\s*<img [^>]*src="https:\/\/example\.test\/preview\.jpg"[^>]*alt="Match day"[^>]*loading="lazy"/', $html );
	}

	public function test_album_opens_the_preview_in_the_lightbox_when_the_option_is_on(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'open_gallery_in_lightbox' => 1 ) ) );

		$this->assertMatchesRegularExpression( '/<a class="lightbox" rel="x"\s+href="https:\/\/example\.test\/full-77\.jpg"/', $html );
		$this->assertStringContainsString( 'data-src="https://example.test/full-77.jpg?v=2"', $html );
		$this->assertStringContainsString( 'data-title="Cover alt"', $html );
		$this->assertStringContainsString( 'data-description="Cover caption"', $html );
		$this->assertStringContainsString( 'data-image-id="77"', $html );
		$this->assertStringContainsString( '<a href="https://example.test/gallery/match-day">Match day</a>', $html );
	}

	public function test_album_links_albums_to_their_page_even_with_the_lightbox_option(): void {
		$html = $this->render(
			'album-compact.php',
			$this->album_vars(
				array(
					'open_gallery_in_lightbox' => 1,
					'galleries'                => array( $this->album_gallery( array( 'entity_type' => 'album' ) ) ),
				)
			)
		);

		$this->assertStringNotContainsString( 'data-image-id', $html );
		$this->assertMatchesRegularExpression( '/<a href="https:\/\/example\.test\/gallery\/match-day">\s*<img /', $html );
	}

	public function test_album_links_to_the_page_when_the_lightbox_option_is_off(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'open_gallery_in_lightbox' => 0 ) ) );

		$this->assertStringNotContainsString( 'data-image-id', $html );
		$this->assertMatchesRegularExpression( '/<a href="https:\/\/example\.test\/gallery\/match-day">\s*<img /', $html );
	}

	/** @return array<string,array{string}> */
	public static function missing_lightbox_data(): array {
		return array(
			'fullsized url missing'     => array( 'previewpic_fullsized_url' ),
			'displayed gallery missing' => array( 'displayed_gallery' ),
		);
	}

	/** @dataProvider missing_lightbox_data */
	public function test_album_falls_back_to_the_page_link_when_the_lightbox_data_is_missing( string $missing ): void {
		$gallery = $this->album_gallery();
		unset( $gallery->$missing );

		$html = $this->render(
			'album-compact.php',
			$this->album_vars(
				array(
					'open_gallery_in_lightbox' => 1,
					'galleries'                => array( $gallery ),
				)
			)
		);

		$this->assertStringNotContainsString( 'data-image-id', $html );
		$this->assertMatchesRegularExpression( '/<a href="https:\/\/example\.test\/gallery\/match-day">\s*<img /', $html );
	}

	public function test_album_renders_without_notices_when_the_gallery_has_no_previewpic(): void {
		$gallery = $this->album_gallery();
		unset( $gallery->previewpic );

		$html = $this->render(
			'album-compact.php',
			$this->album_vars(
				array(
					'open_gallery_in_lightbox' => 1,
					'galleries'                => array( $gallery ),
				)
			)
		);

		$this->assertStringContainsString( 'Match day', $html );
		$this->assertStringContainsString( 'data-image-id=""', $html );
		$this->assertStringContainsString( 'src="https://example.test/preview.jpg"', $html );
	}

	public function test_album_renders_without_notices_when_the_gallery_has_no_pagelink(): void {
		$gallery = $this->album_gallery();
		unset( $gallery->pagelink );

		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array( $gallery ) ) ) );

		$this->assertStringContainsString( 'Match day', $html );
		$this->assertStringContainsString( 'href=""', $html );
	}

	public function test_album_renders_without_notices_when_pagination_is_not_set(): void {
		$vars = $this->album_vars();
		unset( $vars['pagination'] );

		$html = $this->render( 'album-compact.php', $vars );

		$this->assertStringContainsString( 'gallery-grid', $html );
		$this->assertStringNotContainsString( 'class="pagination', $html );
	}

	public function test_album_prints_the_gallery_description_in_a_div_not_a_paragraph(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars() );

		$this->assertStringContainsString( '<div>Gallery <em>notes</em></div>', $html );
		$this->assertStringNotContainsString( '<p>Gallery', $html );
	}

	public function test_album_placeholder_uses_the_generated_thumbnail_ratio(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array( $this->album_gallery( array( 'previewurl' => '' ) ) ) ) ) );

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringContainsString( 'style="aspect-ratio:1.5"', $html );
	}

	public function test_album_placeholder_survives_missing_image_gen_params(): void {
		$vars = $this->album_vars( array( 'galleries' => array( $this->album_gallery( array( 'previewurl' => '' ) ) ) ) );
		unset( $vars['image_gen_params'] );

		$this->assertStringContainsString( 'style="aspect-ratio:1"', $this->render( 'album-compact.php', $vars ) );
	}

	public function test_album_escapes_and_sanitizes_text(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array( $this->album_gallery( array( 'title' => '<script>x()</script>' ) ) ) ) ) );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( 'Spring &lt;i&gt;2026&lt;/i&gt;', $html );
		$this->assertStringContainsString( '<strong>Photos</strong>', $html );
	}

	public function test_album_shows_the_photo_count_and_author_label(): void {
		$user = self::factory()->user->create( array( 'display_name' => 'Ann Author' ) );
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array( $this->album_gallery( array( 'author' => $user ) ) ) ) ) );

		$this->assertStringContainsString( '<strong>12</strong>', $html );
		$this->assertStringContainsString( 'Ann Author', $html );
	}

	public function test_album_omits_the_author_label_for_an_unknown_user(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array( $this->album_gallery( array( 'author' => 999999 ) ) ) ) ) );

		$this->assertStringNotContainsString( 'label warning', $html );
	}

	public function test_album_prints_the_pagination_after_the_grid(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'pagination' => "<div class='ngg-navigation'><span class='current'>1</span><a class='page-numbers' data-pageid='2' href='?nggpage=2'>2</a></div>" ) ) );

		$this->assertStringContainsString( '<ul class="pagination text-center">', $html );
		$this->assertGreaterThan( strrpos( $html, 'card-section' ), strpos( $html, 'class="pagination' ) );
	}

	public function test_album_renders_the_grid_without_an_album_header(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'album' => null ) ) );

		$this->assertStringNotContainsString( 'album-info', $html );
		$this->assertStringContainsString( 'gallery-grid', $html );
	}

	public function test_album_shows_a_callout_when_it_has_no_galleries(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'galleries' => array() ) ) );

		$this->assertStringContainsString( 'callout secondary', $html );
		$this->assertStringNotContainsString( 'gallery-grid', $html );
	}

	public function test_album_hides_the_header_when_descriptions_are_disabled(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'enable_descriptions' => 0 ) ) );

		$this->assertStringNotContainsString( 'album-info', $html );
		$this->assertStringNotContainsString( 'Spring &lt;i&gt;2026&lt;/i&gt;', $html );
	}

	public function test_album_hides_the_header_when_the_descriptions_setting_is_absent(): void {
		$vars = $this->album_vars();
		unset( $vars['enable_descriptions'] );

		$this->assertStringNotContainsString( 'album-info', $this->render( 'album-compact.php', $vars ) );
	}

	public function test_album_shows_the_header_when_only_the_original_settings_enable_descriptions(): void {
		$vars = $this->album_vars( array( 'original_settings' => array( 'enable_descriptions' => 1 ) ) );
		unset( $vars['enable_descriptions'] );
		$this->assertStringContainsString( 'album-info', $this->render( 'album-compact.php', $vars ) );

		$vars['enable_descriptions'] = 0;
		$this->assertStringContainsString( 'album-info', $this->render( 'album-compact.php', $vars ) );
	}

	public function test_album_hides_the_header_when_the_original_settings_disable_descriptions(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'enable_descriptions' => 0, 'original_settings' => array( 'enable_descriptions' => 0 ) ) ) );

		$this->assertStringNotContainsString( 'album-info', $html );
	}

	public function test_album_grid_and_gallery_descriptions_ignore_the_descriptions_setting(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'enable_descriptions' => 0 ) ) );

		$this->assertStringContainsString( 'gallery-grid', $html );
		$this->assertStringContainsString( '<div>Gallery <em>notes</em></div>', $html );
	}

	public function test_album_empty_callout_ignores_the_descriptions_setting(): void {
		$html = $this->render( 'album-compact.php', $this->album_vars( array( 'enable_descriptions' => 0, 'galleries' => array() ) ) );

		$this->assertStringContainsString( 'callout secondary', $html );
		$this->assertStringNotContainsString( 'album-info', $html );
	}

	public function test_album_header_takes_over_nextgens_description_filter(): void {
		$callback = $this->register_nextgen_description_filter();

		$this->render( 'album-compact.php', $this->album_vars() );

		$this->assertFalse( has_filter( self::RENDERING_HOOK, $callback ) );
	}

	public function test_album_leaves_nextgens_description_filter_when_the_header_is_hidden(): void {
		$callback = $this->register_nextgen_description_filter();

		$this->render( 'album-compact.php', $this->album_vars( array( 'enable_descriptions' => 0 ) ) );
		$this->render( 'album-compact.php', $this->album_vars( array( 'album' => null ) ) );

		$this->assertSame( 8, has_filter( self::RENDERING_HOOK, $callback ) );
	}

	public function test_album_renders_nothing_without_an_album_or_galleries(): void {
		$this->assertSame( '', trim( $this->render( 'album-compact.php', $this->album_vars( array( 'album' => null, 'galleries' => array() ) ) ) ) );
	}
}
