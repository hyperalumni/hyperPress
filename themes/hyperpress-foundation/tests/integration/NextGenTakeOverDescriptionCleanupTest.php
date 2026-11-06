<?php
namespace HyperPress\ThemeTests\Integration;

use stdClass;
use WP_UnitTestCase;

/**
 * Sub-album views: NextGEN prepends the description directly (no filter), so the helper
 * registers a one-time, exact-text-match cleanup filter at priority 20.
 */
final class NextGenTakeOverDescriptionCleanupTest extends WP_UnitTestCase {

	private const HOOK   = 'ngg_displayed_gallery_rendering';
	private const MARKER = 'album-info callout';

	public function tear_down(): void {
		remove_all_filters( self::HOOK );
		parent::tear_down();
	}

	private function para( string $text ): string {
		return "<p>\n\t\t" . $text . "\n\t</p>";
	}

	private function callout(): string {
		return '<div class="album-info callout primary"><h5>Title</h5><div>HYPER Photos &amp; more</div></div><div class="gallery-grid"><p>other</p></div>';
	}

	private function run_filter( string $html ) {
		return apply_filters( self::HOOK, $html, new stdClass() );
	}

	public function test_it_removes_the_prefix_paragraph_and_keeps_the_rest(): void {
		hyperpress_nextgen_take_over_description( 'HYPER Photos & more', self::MARKER );

		$this->assertSame( $this->callout(), $this->run_filter( $this->para( 'HYPER Photos &amp; more' ) . $this->callout() ) );
	}

	public function test_it_is_one_time(): void {
		hyperpress_nextgen_take_over_description( 'HYPER Photos & more', self::MARKER );
		$html = $this->para( 'HYPER Photos &amp; more' ) . $this->callout();

		$this->run_filter( $html );

		$this->assertFalse( has_filter( self::HOOK ) );
		$this->assertSame( $html, $this->run_filter( $html ) );
	}

	public function test_it_registers_at_priority_20(): void {
		hyperpress_nextgen_take_over_description( 'Text', self::MARKER );

		$this->assertTrue( has_filter( self::HOOK ) );
		$this->assertSame( 20, key( $GLOBALS['wp_filter'][ self::HOOK ]->callbacks ) );
	}

	public function test_a_paragraph_with_different_text_stays(): void {
		hyperpress_nextgen_take_over_description( 'HYPER Photos & more', self::MARKER );
		$html = $this->para( 'HYPER Photos &amp; more and more' ) . $this->para( 'HYPER' ) . $this->callout();

		$this->assertSame( $html, $this->run_filter( $html ) );
	}

	public function test_two_identical_paragraphs_before_the_marker_are_both_removed(): void {
		hyperpress_nextgen_take_over_description( 'HYPER Photos & more', self::MARKER );
		$html = $this->para( 'HYPER Photos &amp; more' ) . $this->para( 'HYPER Photos &amp; more' ) . $this->callout();

		$this->assertSame( $this->callout(), $this->run_filter( $html ) );
	}

	public function test_an_identical_paragraph_after_the_marker_stays(): void {
		hyperpress_nextgen_take_over_description( 'HYPER Photos & more', self::MARKER );
		$html = $this->para( 'HYPER Photos &amp; more' ) . $this->callout() . '<p>HYPER Photos &amp; more</p>';

		$this->assertSame( $this->callout() . '<p>HYPER Photos &amp; more</p>', $this->run_filter( $html ) );
	}

	public function test_it_returns_the_input_when_the_marker_is_absent(): void {
		hyperpress_nextgen_take_over_description( 'HYPER Photos & more', self::MARKER );
		$html = $this->para( 'HYPER Photos &amp; more' ) . '<div class="other">x</div>';

		$this->assertSame( $html, $this->run_filter( $html ) );
	}

	public function test_empty_description_or_marker_registers_no_filter(): void {
		hyperpress_nextgen_take_over_description( '', self::MARKER );
		hyperpress_nextgen_take_over_description( 'Text', '' );
		hyperpress_nextgen_take_over_description( " \n\t ", self::MARKER );
		hyperpress_nextgen_take_over_description( '<br>&nbsp;', self::MARKER );

		$this->assertFalse( has_filter( self::HOOK ) );
	}

	public function test_entity_and_whitespace_differences_are_normalised(): void {
		hyperpress_nextgen_take_over_description( "HYPER   Photos\n& <b>more</b>", self::MARKER );

		$this->assertSame(
			$this->callout(),
			$this->run_filter( "<p>\tHYPER Photos &amp;\n\n more </p>" . $this->callout() )
		);
	}

	public function test_breadcrumb_markup_before_the_paragraph_stays(): void {
		hyperpress_nextgen_take_over_description( 'HYPER Photos & more', self::MARKER );
		$crumbs = '<div class="ngg-breadcrumbs"><a href="/photos">Photos</a> &raquo; <p>HYPER Photos &amp; more crumb</p></div>';

		$this->assertSame(
			$crumbs . $this->callout(),
			$this->run_filter( $crumbs . $this->para( 'HYPER Photos &amp; more' ) . $this->callout() )
		);
	}

	public function test_non_string_input_is_returned_unchanged(): void {
		hyperpress_nextgen_take_over_description( 'Text', self::MARKER );
		$value = array( 'a' );

		$this->assertSame( $value, apply_filters( self::HOOK, $value, new stdClass() ) );
	}

	public function test_the_removal_of_the_priority_8_callback_still_works_with_arguments(): void {
		$controller = new class() {
			public function add_description_to_legacy_templates( $html ) {
				return $html;
			}
		};
		add_filter( self::HOOK, array( $controller, 'add_description_to_legacy_templates' ), 8 );

		hyperpress_nextgen_take_over_description( 'Text', self::MARKER );

		$this->assertFalse( has_filter( self::HOOK, array( $controller, 'add_description_to_legacy_templates' ) ) );
	}

	private function render( string $template, array $vars ): string {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- test helper mirroring how NextGEN passes variables to a template.
		extract( $vars );
		ob_start();
		include get_template_directory() . '/nggallery/' . $template;
		return (string) ob_get_clean();
	}

	public function test_album_compact_template_cleans_the_prepended_description(): void {
		$description = 'Album about HYPER &amp; friends';
		$output      = $this->render(
			'album-compact.php',
			array(
				'album'                    => (object) array(
					'name'      => 'Photos 2025',
					'albumdesc' => $description,
				),
				'galleries'                => array(),
				'pagination'               => '',
				'image_gen_params'         => array(
					'width'  => 240,
					'height' => 160,
				),
				'open_gallery_in_lightbox' => 0,
				'enable_descriptions'      => 1,
			)
		);

		$html = $this->run_filter( '<p>' . $description . '</p>' . $output );

		$this->assertStringNotContainsString( '<p>' . $description . '</p>', $html );
		$this->assertStringContainsString( 'album-info callout', $html );
		$this->assertSame( 1, substr_count( $html, $description ) );
	}

	public function test_album_compact_template_registers_nothing_when_the_header_is_hidden(): void {
		$this->render(
			'album-compact.php',
			array(
				'album'                    => (object) array(
					'name'      => 'Photos 2025',
					'albumdesc' => 'Hidden text',
				),
				'galleries'                => array(),
				'pagination'               => '',
				'image_gen_params'         => array(
					'width'  => 240,
					'height' => 160,
				),
				'open_gallery_in_lightbox' => 0,
				'enable_descriptions'      => 0,
			)
		);

		$this->assertFalse( has_filter( self::HOOK ) );
	}

	public function test_gallery_template_cleans_the_prepended_description(): void {
		$description = 'Gallery about HYPER &amp; friends';
		$output      = $this->render(
			'gallery.php',
			array(
				'gallery'    => (object) array(
					'anchor'         => 'ngg-gallery-1-1',
					'columns'        => 0,
					'title'          => 'Gallery',
					'description'    => $description,
					'show_slideshow' => false,
				),
				'images'     => array(),
				'pagination' => '',
			)
		);

		$html = $this->run_filter( '<p>' . $description . '</p>' . $output );

		$this->assertStringNotContainsString( '<p>' . $description . '</p>', $html );
		$this->assertStringContainsString( 'gallery-info callout', $html );
		$this->assertSame( 1, substr_count( $html, $description ) );
	}

	public function test_gallery_template_registers_nothing_without_a_description(): void {
		$this->render(
			'gallery.php',
			array(
				'gallery'    => (object) array(
					'anchor'         => 'ngg-gallery-1-1',
					'columns'        => 0,
					'title'          => 'Gallery',
					'description'    => '',
					'show_slideshow' => false,
				),
				'images'     => array(),
				'pagination' => '',
			)
		);

		$this->assertFalse( has_filter( self::HOOK ) );
	}
}
