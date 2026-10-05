<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Newsletter\MetaBoxes;
use WP_UnitTestCase;

final class NewsletterCanvaSanitizerTest extends WP_UnitTestCase {

	public static function accepted(): array {
		return array(
			'plain design url'    => array( 'https://www.canva.com/design/XYZ/view', 'https://www.canva.com/design/XYZ/view' ),
			'bare domain'         => array( 'https://canva.com/design/XYZ/view', 'https://canva.com/design/XYZ/view' ),
			'short subdomain'     => array( 'https://my.canva.com/abc', 'https://my.canva.com/abc' ),
			'trailing embed'      => array( 'https://www.canva.com/design/XYZ/view?embed', 'https://www.canva.com/design/XYZ/view' ),
			'surrounding spaces'  => array( "  https://www.canva.com/design/XYZ/view \n", 'https://www.canva.com/design/XYZ/view' ),
		);
	}

	/** @dataProvider accepted */
	public function test_valid_canva_urls_are_kept( string $input, string $expected ): void {
		$this->assertSame( $expected, MetaBoxes::canva_url( $input ) );
	}

	public static function rejected(): array {
		return array(
			'javascript'       => array( 'javascript:alert(1)' ),
			'other host'       => array( 'https://evil.example/x' ),
			'lookalike host'   => array( 'https://canva.com.evil.example/x' ),
			'suffix lookalike' => array( 'https://notcanva.com/x' ),
			'plain http'       => array( 'http://www.canva.com/x' ),
			'attribute break'  => array( 'https://www.canva.com/x" onload="y' ),
			'embed mid string' => array( 'https://evil.example/?embed=https://www.canva.com/x' ),
			'empty'            => array( '' ),
			'not a url'        => array( 'hello' ),
		);
	}

	/** @dataProvider rejected */
	public function test_everything_else_is_cleared( string $input ): void {
		$this->assertSame( '', MetaBoxes::canva_url( $input ) );
	}

	public function test_non_string_input_does_not_error(): void {
		$this->assertSame( '', MetaBoxes::canva_url( null ) );
		$this->assertSame( '', MetaBoxes::canva_url( array( 'https://www.canva.com/x' ) ) );
	}

	public function test_the_field_uses_the_sanitizer(): void {
		foreach ( apply_filters( 'rwmb_meta_boxes', array() ) as $box ) {
			foreach ( $box['fields'] ?? array() as $field ) {
				if ( 'hyperpress_newsletter_canva_url' === ( $field['id'] ?? '' ) ) {
					$this->assertSame( '', call_user_func( $field['sanitize_callback'], 'https://evil.example/x' ) );
					return;
				}
			}
		}
		$this->fail( 'canva field not found' );
	}
}
