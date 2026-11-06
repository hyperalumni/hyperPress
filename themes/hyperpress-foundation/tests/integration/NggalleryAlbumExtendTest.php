<?php
namespace HyperPress\ThemeTests\Integration;

use HyperPress\ThemeTests\Integration\Concerns\NggalleryTemplateHelpers;
use WP_UnitTestCase;

require_once __DIR__ . '/Concerns/NggalleryTemplateHelpers.php';

final class NggalleryAlbumExtendTest extends WP_UnitTestCase {

	use NggalleryTemplateHelpers;

	private const PAGINATION = "<div class='ngg-navigation'><span class='current'>1</span><a class='page-numbers' data-pageid='2' href='?nggpage=2'>2</a></div>";

	private function album_gallery( array $over = array() ): object {
		return (object) array_merge(
			array(
				'title'      => 'Match day',
				'pagelink'   => 'https://example.test/gallery/match-day',
				'previewurl' => 'https://example.test/preview.jpg',
				'galdesc'    => 'Gallery <em>notes</em>',
				'counter'    => 12,
			),
			$over
		);
	}

	private function vars( array $over = array() ): array {
		return array_merge(
			array(
				'galleries'  => array( $this->album_gallery() ),
				'pagination' => '',
			),
			$over
		);
	}

	public function test_it_renders_one_media_object_per_gallery(): void {
		$html = $this->render(
			'album-extend.php',
			$this->vars( array( 'galleries' => array( $this->album_gallery(), $this->album_gallery( array( 'title' => 'Second' ) ) ) ) )
		);

		$this->assertSame( 2, substr_count( $html, '<div class="media-object stack-for-small">' ) );
		$this->assertSame( 2, substr_count( $html, '<div class="media-object-section shrink">' ) );
		$this->assertSame( 2, substr_count( $html, '<div class="media-object-section main-section">' ) );
	}

	public function test_the_thumbnail_links_to_the_gallery_with_alt_and_lazy_loading(): void {
		$html = $this->render( 'album-extend.php', $this->vars() );

		$this->assertMatchesRegularExpression( '/<a href="https:\/\/example\.test\/gallery\/match-day">\s*<img [^>]*src="https:\/\/example\.test\/preview\.jpg"[^>]*alt="Match day"[^>]*loading="lazy"/', $html );
	}

	public function test_the_title_is_an_h4_link_to_the_gallery(): void {
		$html = $this->render( 'album-extend.php', $this->vars() );

		$this->assertStringContainsString( '<h4><a href="https://example.test/gallery/match-day">Match day</a></h4>', $html );
	}

	public function test_the_description_is_sanitized_and_the_count_is_a_label(): void {
		$html = $this->render( 'album-extend.php', $this->vars( array( 'galleries' => array( $this->album_gallery( array( 'galdesc' => '<strong>Notes</strong><script>x()</script>' ) ) ) ) ) );

		$this->assertStringContainsString( '<strong>Notes</strong>', $html );
		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertMatchesRegularExpression( '/<span class="label primary"><strong>12<\/strong>&nbsp;Photos<\/span>/', $html );
	}

	public function test_the_title_is_escaped(): void {
		$html = $this->render( 'album-extend.php', $this->vars( array( 'galleries' => array( $this->album_gallery( array( 'title' => '<script>x()</script>' ) ) ) ) ) );

		$this->assertStringNotContainsString( '<script>', $html );
		$this->assertStringContainsString( '&lt;script&gt;', $html );
	}

	/** @return array<string,array{string}> */
	public static function missing_preview(): array {
		return array(
			'empty'   => array( '' ),
			'missing' => array( '__unset__' ),
		);
	}

	/** @dataProvider missing_preview */
	public function test_a_gallery_without_a_preview_renders_no_image_and_no_thumbnail_link( string $previewurl ): void {
		$gallery = $this->album_gallery( array( 'previewurl' => $previewurl ) );
		if ( '__unset__' === $previewurl ) {
			unset( $gallery->previewurl );
		}

		$html = $this->render( 'album-extend.php', $this->vars( array( 'galleries' => array( $gallery ) ) ) );

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertSame( 1, substr_count( $html, '<a ' ), 'Only the title link remains.' );
		$this->assertStringContainsString( 'Match day', $html );
	}

	public function test_a_gallery_without_a_description_prints_no_description(): void {
		$gallery = $this->album_gallery();
		unset( $gallery->galdesc );

		$html = $this->render( 'album-extend.php', $this->vars( array( 'galleries' => array( $gallery ) ) ) );

		$this->assertStringNotContainsString( 'Gallery', $html );
		$this->assertDoesNotMatchRegularExpression( '/<p>\s*<\/p>/', $html );
		$this->assertStringContainsString( 'label primary', $html );
	}

	/** @return array<string,array{mixed}> */
	public static function no_count(): array {
		return array(
			'zero'    => array( 0 ),
			'missing' => array( null ),
		);
	}

	/** @dataProvider no_count */
	public function test_a_gallery_without_a_positive_counter_prints_no_label( $counter ): void {
		$gallery = $this->album_gallery( array( 'counter' => $counter ) );
		if ( null === $counter ) {
			unset( $gallery->counter );
		}

		$html = $this->render( 'album-extend.php', $this->vars( array( 'galleries' => array( $gallery ) ) ) );

		$this->assertStringNotContainsString( 'label', $html );
		$this->assertStringNotContainsString( 'Photos', $html );
	}

	public function test_the_pagination_follows_the_list_inside_the_wrapper(): void {
		$html = $this->render( 'album-extend.php', $this->vars( array( 'pagination' => self::PAGINATION ) ) );

		$this->assertStringContainsString( '<ul class="pagination text-center">', $html );
		$this->assertGreaterThan( strrpos( $html, 'media-object-section' ), strpos( $html, 'class="pagination' ) );
		$this->assertLessThan( strrpos( $html, '</div>' ), strpos( $html, 'class="pagination' ) );
	}

	public function test_it_renders_without_notices_when_pagination_is_not_set(): void {
		$vars = $this->vars();
		unset( $vars['pagination'] );

		$html = $this->render( 'album-extend.php', $vars );

		$this->assertStringContainsString( 'media-object', $html );
		$this->assertStringNotContainsString( 'class="pagination', $html );
	}

	public function test_it_renders_nothing_without_galleries(): void {
		$this->assertSame( '', trim( $this->render( 'album-extend.php', $this->vars( array( 'galleries' => array(), 'pagination' => self::PAGINATION ) ) ) ) );
	}

	public function test_it_drops_the_legacy_nextgen_classes(): void {
		$html = $this->render( 'album-extend.php', $this->vars( array( 'pagination' => self::PAGINATION ) ) );

		$this->assertStringNotContainsString( 'ngg-album', $html );
		$this->assertStringNotContainsString( 'class="Thumb"', $html );
		$this->assertDoesNotMatchRegularExpression( '/class="[^"]*\bngg-/', $html );
	}
}
