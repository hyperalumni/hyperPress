<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Link;
use WP_UnitTestCase;

final class LinkTest extends WP_UnitTestCase {

	/** @return array<string, array{0: string, 1: bool}> */
	public static function urls(): array {
		return array(
			'relative path'                  => array( '/agenda.pdf', true ),
			'protocol-relative other host'   => array( '//other.example/x', false ),
			'different host'                 => array( 'https://other.example/x', false ),
			'site url only in query string'  => array( 'https://other.example/?r=' . rawurlencode( 'http://' . WP_TESTS_DOMAIN ), false ),
			'empty'                          => array( '', false ),
			'unparseable'                    => array( 'http:///x', false ),
			'bare word'                      => array( 'agenda', false ),
		);
	}

	/** @dataProvider urls */
	public function test_is_internal( string $url, bool $expected ): void {
		$this->assertSame( $expected, Link::is_internal( $url ), $url );
	}

	public function test_site_url_and_same_host_in_any_case_are_internal(): void {
		$this->assertTrue( Link::is_internal( home_url( '/agenda.pdf' ) ) );

		$host = (string) wp_parse_url( get_site_url(), PHP_URL_HOST );
		$this->assertTrue( Link::is_internal( 'https://' . strtoupper( $host ) . '/x' ) );
	}

	public function test_target_and_rel(): void {
		$this->assertSame( '_self', Link::target( '/agenda' ) );
		$this->assertSame( '_blank', Link::target( 'https://other.example/x' ) );
		$this->assertSame( '', Link::rel( '_self' ) );
		$this->assertSame( 'noopener noreferrer', Link::rel( '_blank' ) );
	}

	/** @return array<string, array{0: string, 1: string}> */
	public static function targets(): array {
		return array(
			'internal relative pdf'        => array( '/wp-content/uploads/2026/agenda.pdf', '_blank' ),
			'internal absolute pdf'        => array( home_url( '/x/Agenda.PDF' ), '_blank' ),
			'internal pdf with query'      => array( '/files/agenda.pdf?v=2', '_blank' ),
			'internal pdf with fragment'   => array( home_url( '/files/agenda.pdf#page=2' ), '_blank' ),
			'internal page'                => array( home_url( '/schedule/' ), '_self' ),
			'internal pdf only in query'   => array( '/page/?file=a.pdf', '_self' ),
			'internal pdf only in fragment' => array( '/page/#a.pdf', '_self' ),
			'external page'                => array( 'https://other.example/page', '_blank' ),
			'external pdf'                 => array( 'https://other.example/a.pdf', '_blank' ),
		);
	}

	/** @dataProvider targets */
	public function test_target_opens_external_and_pdf_links_in_a_new_tab( string $url, string $expected ): void {
		$this->assertSame( $expected, Link::target( $url ), $url );
	}
}
