<?php
namespace HyperPress\Tests\Integration;

use DOMDocument;
use DOMXPath;
use HyperPress\Socials\Widgets\DisplaySocialsWidget;
use HyperPress\Sponsor\Widgets\DisplaySponsorsWidget;
use WP_UnitTestCase;

/**
 * Characterization: the admin forms print field ids and names through esc_attr(), which must not change them.
 */
final class WidgetFormEscapingTest extends WP_UnitTestCase {

	public static function widgets(): array {
		return array(
			'socials' => array( DisplaySocialsWidget::class, array( 'title', 'layout' ) ),
			'sponsor' => array( DisplaySponsorsWidget::class, array( 'title', 'maxCount', 'becomeASponsorPage' ) ),
		);
	}

	/**
	 * @dataProvider widgets
	 */
	public function test_form_ids_and_names_are_exact_and_the_markup_parses_cleanly( string $class, array $fields ): void {
		// wp_dropdown_pages() prints nothing when there are no pages.
		self::factory()->post->create( array( 'post_type' => 'page' ) );
		$w = new $class();
		$w->_set( 7 );
		ob_start();
		$w->form( array() );
		$html = ob_get_clean();

		$previous = libxml_use_internal_errors( true );
		libxml_clear_errors();
		$dom = new DOMDocument();
		$dom->loadHTML( '<!DOCTYPE html><html><body>' . $html . '</body></html>' );
		$errors = libxml_get_errors();
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		$this->assertSame( array(), $errors, 'The form must be well-formed HTML.' );

		$xpath = new DOMXPath( $dom );
		$ids   = array();
		$names = array();
		foreach ( $xpath->query( '//*[@id]' ) as $node ) {
			$ids[] = $node->getAttribute( 'id' );
		}
		foreach ( $xpath->query( '//*[@name]' ) as $node ) {
			$names[] = $node->getAttribute( 'name' );
		}
		$labels = array();
		foreach ( $xpath->query( '//label[@for]' ) as $node ) {
			$labels[] = $node->getAttribute( 'for' );
		}

		$expected_ids   = array();
		$expected_names = array();
		foreach ( $fields as $field ) {
			$expected_ids[]   = 'widget-' . $w->id_base . '-7-' . $field;
			$expected_names[] = 'widget-' . $w->id_base . '[7][' . $field . ']';
		}

		$this->assertSame( $expected_ids, $ids );
		$this->assertSame( $expected_names, $names );
		$this->assertSame( $expected_ids, $labels );
	}
}
