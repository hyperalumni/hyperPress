<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use WP_UnitTestCase;

final class BannerEscapingTest extends WP_UnitTestCase {

	public function test_anchor_cannot_break_out_of_the_id_attribute(): void {
		$output = \HyperPress\Shortcodes\Shortcodes\Banner::render(
			array(
				'anchor' => 'x"><script>alert(1)</script>',
				'title'  => 'Title',
			)
		);

		$this->assertStringNotContainsString( '<script', $output );
		$this->assertStringNotContainsString( 'id="x">', $output );
		$this->assertStringContainsString( 'id="x&quot;&gt;&lt;script&gt;', $output );
	}

	public function test_title_loses_event_handlers(): void {
		$output = \HyperPress\Shortcodes\Shortcodes\Banner::render( array( 'title' => '<img src=x onerror=alert(1)>' ) );

		$this->assertStringNotContainsString( 'onerror', $output );
	}

	public function test_subtitle_loses_scripts_and_event_handlers(): void {
		$output = \HyperPress\Shortcodes\Shortcodes\Banner::render( array( 'subtitle' => '<img src=x onerror=alert(1)><script>alert(2)</script>' ) );

		$this->assertStringNotContainsString( 'onerror', $output );
		$this->assertStringNotContainsString( '<script', $output );
	}

	public function test_normal_post_markup_survives_in_title_and_subtitle(): void {
		$output = \HyperPress\Shortcodes\Shortcodes\Banner::render(
			array(
				'title'    => '<em>ok</em>',
				'subtitle' => '<strong>fine</strong>',
			)
		);

		$this->assertStringContainsString( '<h2 class="entry-title"><em>ok</em></h2>', $output );
		$this->assertStringContainsString( '<h4 class="subtitle"><strong>fine</strong></h4>', $output );
	}

	public function test_plain_text_output_is_unchanged(): void {
		$output = \HyperPress\Shortcodes\Shortcodes\Banner::render(
			array(
				'anchor'   => 'about-us',
				'title'    => 'Hello world',
				'subtitle' => 'A plain subtitle',
			)
		);

		$this->assertSame(
			'<header role="banner" class="banner"><span class="anchor" id="about-us"></span><div class="main-container"><h2 class="entry-title">Hello world</h2><h4 class="subtitle">A plain subtitle</h4></div></header>',
			$output
		);
	}
}
