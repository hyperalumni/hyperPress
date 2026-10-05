<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Newsletter\MetaBoxes;
use WP_UnitTestCase;

/**
 * Meta Box validates in the browser only; the field's HTML5 `pattern` must agree with the server-side sanitizer.
 */
final class NewsletterCanvaFieldValidationTest extends WP_UnitTestCase {

	private function field(): array {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			foreach ( $box['fields'] ?? array() as $field ) {
				if ( 'hyperpress_newsletter_canva_url' === ( $field['id'] ?? '' ) ) {
					return $field;
				}
			}
		}
		$this->fail( 'canva field not found' );
	}

	private function pattern_matches( string $pattern, string $value ): bool {
		// HTML5 anchors the pattern to the whole value.
		return 1 === preg_match( '~^(?:' . $pattern . ')$~', $value );
	}

	public function test_the_field_has_a_pattern_and_an_explanatory_title(): void {
		$field = $this->field();
		$this->assertNotEmpty( $field['pattern'] ?? '', 'pattern attribute is set' );
		$this->assertNotEmpty( $field['title'] ?? '', 'title explains the pattern to the editor' );
		$this->assertTrue( $field['required'] );
	}

	public function test_pattern_accepts_what_the_sanitizer_keeps(): void {
		$pattern = $this->field()['pattern'];
		foreach ( array( 'https://www.canva.com/design/XYZ/view', 'https://canva.com/design/XYZ/view', 'https://my.canva.com/abc', 'https://www.canva.com' ) as $url ) {
			$this->assertTrue( $this->pattern_matches( $pattern, $url ), "pattern accepts $url" );
			$this->assertSame( $url, MetaBoxes::canva_url( $url ) );
		}
	}

	public function test_pattern_rejects_what_the_sanitizer_clears(): void {
		$pattern = $this->field()['pattern'];
		$bad     = array(
			'javascript:alert(1)',
			'https://evil.example/x',
			'https://canva.com.evil.example/x',
			'https://notcanva.com/x',
			'http://www.canva.com/x',
			'https://www.canva.com/x" onload="y',
			'hello',
		);
		foreach ( $bad as $url ) {
			$this->assertFalse( $this->pattern_matches( $pattern, $url ), "pattern rejects $url" );
			$this->assertSame( '', MetaBoxes::canva_url( $url ) );
		}
	}
}
