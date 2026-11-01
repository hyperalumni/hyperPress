<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use WP_UnitTestCase;

final class AnchorShortcodeTest extends WP_UnitTestCase {

	public function test_the_shortcode_is_registered(): void {
		$this->assertTrue( shortcode_exists( 'hyperpress_link' ) );
	}

	public function test_an_external_url_opens_in_a_new_tab_with_rel(): void {
		$this->assertSame(
			'<a href="https://other.example/x" target="_blank" rel="noopener noreferrer">Text</a>',
			do_shortcode( '[hyperpress_link url="https://other.example/x"]Text[/hyperpress_link]' )
		);
	}

	public function test_an_internal_url_stays_in_the_tab_without_rel(): void {
		$this->assertSame(
			'<a href="/agenda" target="_self">Agenda</a>',
			do_shortcode( '[hyperpress_link url="/agenda"]Agenda[/hyperpress_link]' )
		);
	}

	public function test_an_internal_pdf_opens_in_a_new_tab(): void {
		$out = do_shortcode( '[hyperpress_link url="/files/a.pdf"]PDF[/hyperpress_link]' );

		$this->assertStringContainsString( 'target="_blank"', $out );
		$this->assertStringContainsString( 'rel="noopener noreferrer"', $out );
	}

	/** @return array<string, array{0: string}> */
	public static function same_tab_urls(): array {
		return array(
			'mailto' => array( 'mailto:a@b.example' ),
			'tel'    => array( 'tel:5551234' ),
			'anchor' => array( '#top' ),
		);
	}

	/** @dataProvider same_tab_urls */
	public function test_mailto_tel_and_anchor_urls_stay_in_the_tab( string $url ): void {
		$out = do_shortcode( '[hyperpress_link url="' . $url . '"]Go[/hyperpress_link]' );

		$this->assertStringContainsString( 'target="_self"', $out );
		$this->assertStringNotContainsString( 'rel=', $out );
	}

	public function test_the_text_attribute_is_used_without_enclosed_content(): void {
		$this->assertSame(
			'<a href="/a" target="_self">Go</a>',
			do_shortcode( '[hyperpress_link url="/a" text="Go"]' )
		);
	}

	public function test_enclosed_content_wins_over_the_text_attribute(): void {
		$this->assertSame(
			'<a href="/a" target="_self">Body</a>',
			do_shortcode( '[hyperpress_link url="/a" text="Attr"]Body[/hyperpress_link]' )
		);
	}

	public function test_the_text_attribute_is_escaped(): void {
		$this->assertStringContainsString( '>Tom &amp; Jerry</a>', do_shortcode( '[hyperpress_link url="/a" text="Tom & Jerry"]' ) );
	}

	public function test_enclosed_inline_markup_is_kept_and_scripts_are_stripped(): void {
		$this->assertStringContainsString( '<em>Hi</em>', do_shortcode( '[hyperpress_link url="/a"]<em>Hi</em>[/hyperpress_link]' ) );
		$this->assertStringNotContainsString( '<script', do_shortcode( '[hyperpress_link url="/a"]<script>alert(1)</script>Hi[/hyperpress_link]' ) );
	}

	public function test_a_nested_shortcode_is_rendered(): void {
		$out = do_shortcode( '[hyperpress_link url="/a"][hyperpress_emphasis]Hi[/hyperpress_emphasis][/hyperpress_link]' );

		$this->assertSame( '<a href="/a" target="_self"><span class="emphasis">Hi</span></a>', $out );
	}

	public function test_an_unsafe_url_renders_the_text_without_a_link(): void {
		$this->assertSame( 'Hi', do_shortcode( '[hyperpress_link url="javascript:alert(1)"]Hi[/hyperpress_link]' ) );
	}

	public function test_an_empty_url_renders_the_text_without_a_link(): void {
		$this->assertSame( 'Hi', do_shortcode( '[hyperpress_link url=""]Hi[/hyperpress_link]' ) );
	}

	public function test_empty_text_falls_back_to_the_url(): void {
		$this->assertSame(
			'<a href="/a" target="_self">/a</a>',
			do_shortcode( '[hyperpress_link url="/a"]' )
		);
	}
}
