<?php
namespace HyperPress\ThemeTests\Integration;

use HyperPress\ThemeTests\Integration\Concerns\NggalleryTemplateHelpers;
use WP_UnitTestCase;

require_once __DIR__ . '/Concerns/NggalleryTemplateHelpers.php';

final class NggalleryCarouselTest extends WP_UnitTestCase {

	use NggalleryTemplateHelpers;

	private const PAGINATION = "<div class='ngg-navigation'><span class='current'>1</span><a class='page-numbers' data-pageid='2' href='?nggpage=2'>2</a></div>";

	private function image( int $pid, array $over = array() ): object {
		return (object) array_merge(
			array(
				'pid'          => $pid,
				'pidlink'      => 'https://example.test/gallery/?pid=photo-' . $pid,
				'description'  => 'Description ' . $pid,
				'alttext'      => 'Alt ' . $pid,
				'thumbnailURL' => 'https://example.test/thumb-' . $pid . '.jpg',
				'size'         => 'width="240" height="160"',
				'hidden'       => false,
			),
			$over
		);
	}

	private function current_image( int $pid, array $over = array() ): object {
		return (object) array_merge(
			array(
				'pid'     => $pid,
				'alttext' => 'Alt ' . $pid,
				'url'     => 'https://example.test/full-' . $pid . '.jpg',
			),
			$over
		);
	}

	/** Three visible images, the second one current. */
	private function vars( array $over = array() ): array {
		$images = array( $this->image( 1 ), $this->image( 2 ), $this->image( 3 ) );
		return array_merge(
			array(
				'gallery'    => (object) array( 'anchor' => 'ngg-gallery-1' ),
				'images'     => $images,
				'current'    => $this->current_image( 2 ),
				'pagination' => '',
				'prev'       => false,
				'next'       => false,
			),
			$over
		);
	}

	/** The markup between the opening wrapper and the thumbnail strip: the large image and the buttons. */
	private function head( string $html ): string {
		return substr( $html, 0, (int) strpos( $html, 'grid-x' ) );
	}

	public function test_the_wrapper_keeps_the_class_but_has_no_id(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars() );

		$this->assertStringContainsString( '<div class="ngg-galleryoverview">', $html );
		$this->assertStringNotContainsString( 'id="', substr( $html, 0, (int) strpos( $html, '>' ) + 1 ) );
		// NextGEN's AJAX pagination looks the gallery up by id, so no element may carry the gallery anchor.
		$this->assertStringNotContainsString( 'ngg-gallery-1', $html );
	}

	public function test_the_current_image_is_large_with_plain_text_alt_and_title(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars( array( 'current' => $this->current_image( 2, array( 'alttext' => '<b>Goal</b> kick' ) ) ) ) );

		$head = $this->head( $html );
		$this->assertMatchesRegularExpression( '/<img [^>]*src="https:\/\/example\.test\/full-2\.jpg"/', $head );
		$this->assertStringContainsString( 'alt="Goal kick"', $head );
		$this->assertStringContainsString( 'title="Goal kick"', $head );
		$this->assertMatchesRegularExpression( '/<img [^>]*class="(?:[^"]* )?thumbnail[ "]/', $head );
		$this->assertStringNotContainsString( 'loading="lazy"', $head );
	}

	public function test_a_script_in_the_alt_text_is_not_printed(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars( array( 'current' => $this->current_image( 2, array( 'alttext' => '"><script>x()</script>' ) ) ) ) );

		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_a_middle_image_links_to_both_neighbours(): void {
		$head = $this->head( $this->render( 'gallery-carousel.php', $this->vars() ) );

		$this->assertMatchesRegularExpression( '/<a class="button hollow" href="https:\/\/example\.test\/gallery\/\?pid=photo-1">[^<]*Previous image<\/a>/', $head );
		$this->assertMatchesRegularExpression( '/<a class="button hollow" href="https:\/\/example\.test\/gallery\/\?pid=photo-3">Next image[^<]*<\/a>/', $head );
		$this->assertStringNotContainsString( 'disabled', $head );
	}

	public function test_the_first_image_has_a_disabled_previous_button(): void {
		$head = $this->head( $this->render( 'gallery-carousel.php', $this->vars( array( 'current' => $this->current_image( 1 ) ) ) ) );

		$this->assertMatchesRegularExpression( '/<span class="button hollow disabled" aria-disabled="true">[^<]*Previous image<\/span>/', $head );
		$this->assertStringContainsString( 'href="https://example.test/gallery/?pid=photo-2"', $head );
	}

	public function test_the_last_image_has_a_disabled_next_button(): void {
		$head = $this->head( $this->render( 'gallery-carousel.php', $this->vars( array( 'current' => $this->current_image( 3 ) ) ) ) );

		$this->assertMatchesRegularExpression( '/<span class="button hollow disabled" aria-disabled="true">Next image[^<]*<\/span>/', $head );
		$this->assertStringContainsString( 'href="https://example.test/gallery/?pid=photo-2"', $head );
	}

	public function test_hidden_images_are_skipped_when_finding_neighbours(): void {
		$images = array( $this->image( 1 ), $this->image( 2 ), $this->image( 3, array( 'hidden' => true ) ), $this->image( 4 ) );

		$html = $this->render( 'gallery-carousel.php', $this->vars( array( 'images' => $images ) ) );

		$this->assertStringContainsString( 'href="https://example.test/gallery/?pid=photo-4"', $this->head( $html ) );
		$this->assertStringNotContainsString( 'photo-3', $html );
		$this->assertStringNotContainsString( 'thumb-3.jpg', $html );
	}

	public function test_a_current_image_that_is_not_on_the_page_degrades_without_notices(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars( array( 'current' => $this->current_image( 99 ) ) ) );

		$head = $this->head( $html );
		$this->assertStringContainsString( 'full-99.jpg', $head );
		$this->assertSame( 2, substr_count( $head, 'class="button hollow disabled"' ) );
		$this->assertStringNotContainsString( 'aria-current', $html );
	}

	public function test_my_own_buttons_do_not_use_the_classes_nextgens_pagination_script_binds(): void {
		$head = $this->head( $this->render( 'gallery-carousel.php', $this->vars() ) );

		$this->assertDoesNotMatchRegularExpression( '/class="[^"]*\b(prev|next|page-numbers)\b/', $head );
	}

	public function test_the_strip_has_a_column_count_for_the_small_and_medium_breakpoints(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars() );

		$this->assertStringContainsString( '<div class="grid-x grid-margin-x small-up-4 medium-up-6">', $html );
	}

	public function test_the_strip_is_a_direct_child_of_the_wrapper(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars() );

		$this->assertMatchesRegularExpression( '/<\/div>\s*<div class="grid-x /', $html );
		$this->assertSame( 1, substr_count( $html, '<div class="ngg-galleryoverview">' ) );
	}

	public function test_the_strip_has_one_cell_per_visible_image_with_the_thumbnail(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars( array( 'images' => array( $this->image( 1 ), $this->image( 2, array( 'hidden' => true ) ), $this->image( 3 ) ) ) ) );

		$this->assertSame( 2, substr_count( $html, '<div class="cell">' ) );
		$this->assertMatchesRegularExpression( '/<a [^>]*href="https:\/\/example\.test\/gallery\/\?pid=photo-1"[^>]*title="Description 1"[^>]*>\s*<img [^>]*loading="lazy"[^>]*src="https:\/\/example\.test\/thumb-1\.jpg"[^>]*alt="Alt 1"[^>]*width="240" height="160"/', $html );
	}

	public function test_thumbnails_use_the_raw_size_attributes_and_lazy_loading(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars() );

		$this->assertSame( 3, substr_count( $html, 'width="240" height="160"' ) );
		$this->assertSame( 3, substr_count( $html, 'loading="lazy"' ) );
	}

	public function test_only_the_current_thumbnail_is_marked(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars() );

		$this->assertSame( 1, substr_count( $html, 'is-current' ) );
		$this->assertSame( 1, substr_count( $html, 'aria-current="true"' ) );
		$this->assertMatchesRegularExpression( '/<a class="thumbnail is-current" href="[^"]*photo-2"[^>]*aria-current="true"/', $html );
		$this->assertMatchesRegularExpression( '/<a class="thumbnail" href="[^"]*photo-1"(?![^>]*aria-current)/', $html );
		$this->assertMatchesRegularExpression( '/<a class="thumbnail" href="[^"]*photo-3"(?![^>]*aria-current)/', $html );
	}

	public function test_no_per_image_ids_are_printed(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars() );

		$this->assertStringNotContainsString( 'ngg-image-', $html );
		$this->assertStringNotContainsString( ' id="', $html );
	}

	public function test_the_pagination_follows_the_strip_inside_the_wrapper(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars( array( 'pagination' => self::PAGINATION ) ) );

		$this->assertStringContainsString( '<ul class="pagination text-center">', $html );
		$this->assertGreaterThan( strrpos( $html, 'thumb-3.jpg' ), strpos( $html, 'class="pagination' ) );
		$this->assertLessThan( strrpos( $html, '</div>' ), strpos( $html, 'class="pagination' ) );
	}

	public function test_it_renders_without_notices_when_pagination_is_empty_or_not_set(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars() );
		$this->assertStringNotContainsString( 'class="pagination', $html );

		$vars = $this->vars();
		unset( $vars['pagination'] );
		$this->assertStringNotContainsString( 'class="pagination', $this->render( 'gallery-carousel.php', $vars ) );
	}

	public function test_prev_and_next_are_never_printed(): void {
		$html = $this->render(
			'gallery-carousel.php',
			$this->vars(
				array(
					'prev' => 'https://example.test/gallery/?nggpage=1-PREV',
					'next' => 'https://example.test/gallery/?nggpage=3-NEXT',
				)
			)
		);

		$this->assertStringNotContainsString( 'PREV', $html );
		$this->assertStringNotContainsString( 'NEXT', $html );
		$this->assertStringNotContainsString( 'nggpage', $html );
	}

	public function test_it_renders_without_notices_when_prev_and_next_are_not_set(): void {
		$vars = $this->vars();
		unset( $vars['prev'], $vars['next'] );

		$this->assertStringContainsString( 'full-2.jpg', $this->render( 'gallery-carousel.php', $vars ) );
	}

	public function test_it_renders_nothing_for_an_empty_gallery(): void {
		$this->assertSame( '', trim( $this->render( 'gallery-carousel.php', $this->vars( array( 'images' => array(), 'current' => null, 'pagination' => self::PAGINATION ) ) ) ) );
		$this->assertSame( '', trim( $this->render( 'gallery-carousel.php', array() ) ) );
	}

	public function test_without_visible_images_it_still_shows_the_current_image_and_no_strip(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars( array( 'images' => array( $this->image( 1, array( 'hidden' => true ) ) ) ) ) );

		$this->assertStringContainsString( 'full-2.jpg', $html );
		$this->assertStringNotContainsString( 'grid-x', $html );
		$this->assertStringNotContainsString( 'button', $html );
	}

	public function test_hidden_images_never_appear(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars( array( 'images' => array( $this->image( 1 ), $this->image( 2 ), $this->image( 3, array( 'hidden' => true, 'description' => 'Secret' ) ) ) ) ) );

		$this->assertStringNotContainsString( 'Secret', $html );
		$this->assertStringNotContainsString( 'thumb-3.jpg', $html );
	}

	public function test_titles_and_descriptions_are_escaped(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars( array( 'images' => array( $this->image( 1, array( 'description' => '"><script>x()</script>', 'alttext' => '<script>y()</script>' ) ), $this->image( 2 ) ) ) ) );

		$this->assertStringNotContainsString( '<script>', $html );
	}

	public function test_it_drops_the_legacy_nextgen_markup(): void {
		$html = $this->render( 'gallery-carousel.php', $this->vars( array( 'pagination' => self::PAGINATION ) ) );

		$this->assertStringNotContainsString( 'ngg-gallery-list', $html );
		$this->assertStringNotContainsString( 'ngg-thumbnail-list', $html );
		$this->assertStringNotContainsString( 'class="pic"', $html );
	}
}
