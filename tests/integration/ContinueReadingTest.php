<?php
namespace HyperPress\ThemeTests\Integration;

use DOMDocument;
use DOMXPath;
use WP_UnitTestCase;

final class ContinueReadingTest extends WP_UnitTestCase {

	public function test_more_tag_renders_a_real_link_not_a_button(): void {
		$id = self::factory()->post->create( array( 'post_content' => 'Intro text<!--more-->Rest of the post' ) );
		$this->go_to( home_url( '/' ) );
		$this->assertTrue( have_posts() );
		the_post();

		ob_start();
		the_content();
		$html = ob_get_clean();

		$dom = new DOMDocument();
		$dom->loadHTML( '<?xml encoding="utf-8"?><div>' . $html . '</div>' );
		$xpath = new DOMXPath( $dom );

		$links = $xpath->query( '//a[contains(concat(" ", normalize-space(@class), " "), " more-link ")]' );
		$this->assertSame( 1, $links->length, 'exactly one Continue Reading link' );
		$link = $links->item( 0 );
		$this->assertStringStartsWith( get_permalink( $id ), $link->getAttribute( 'href' ) );
		$this->assertStringContainsString( 'button', $link->getAttribute( 'class' ) );
		$this->assertSame( 'Continue Reading', trim( $link->textContent ) );
		$this->assertSame( 0, $xpath->query( '//button[@href]' )->length, 'no <button href>' );
	}
}
