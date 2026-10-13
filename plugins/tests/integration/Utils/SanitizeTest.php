<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Sanitize;
use WP_UnitTestCase;

final class SanitizeTest extends WP_UnitTestCase {

	public function test_url_keeps_an_https_url(): void {
		$this->assertSame( 'https://example.test/path?a=1', Sanitize::url( 'https://example.test/path?a=1' ) );
	}

	public function test_url_drops_a_dangerous_protocol(): void {
		$this->assertSame( '', Sanitize::url( 'javascript:alert(1)' ) );
	}

	public function test_url_gives_an_empty_string_for_non_strings(): void {
		$this->assertSame( '', Sanitize::url( array( 'https://example.test/' ) ) );
		$this->assertSame( '', Sanitize::url( null ) );
		$this->assertSame( '', Sanitize::url( 42 ) );
	}

	public function test_url_trims_surrounding_whitespace(): void {
		$this->assertSame( 'https://example.test/', Sanitize::url( "  https://example.test/ \n" ) );
	}

	public function test_url_keeps_a_relative_path(): void {
		$this->assertSame( '/about/', Sanitize::url( '/about/' ) );
	}
}
