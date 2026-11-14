<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use HyperPress\Shortcode\Shortcodes\NoAutop;
use WP_UnitTestCase;

/**
 * [hyperpress_noautop] keeps wpautop and wptexturize away from its content, while the rest of the post is still
 * formatted as usual.
 */
final class NoAutopTest extends WP_UnitTestCase {

	public function test_block_content_is_not_autop_d(): void {
		$inner  = "<ul>\n\t<li>\n\t\tindented text\n\t</li>\n</ul>";
		$output = apply_filters( 'the_content', '[hyperpress_noautop]' . $inner . '[/hyperpress_noautop]' );

		$this->assertStringContainsString( $inner, $output );
		$this->assertStringNotContainsString( '<p>', $output );
		$this->assertStringNotContainsString( '<br />', $output );
	}

	public function test_text_outside_the_block_is_still_autop_d(): void {
		$output = apply_filters( 'the_content', "Before.\n\n[hyperpress_noautop]<div>\n x\n</div>[/hyperpress_noautop]\n\nAfter." );

		$this->assertStringContainsString( '<p>Before.</p>', $output );
		$this->assertStringContainsString( '<p>After.</p>', $output );
		$this->assertStringContainsString( "<div>\n x\n</div>", $output );
	}

	public function test_nested_shortcodes_still_render(): void {
		$output = apply_filters( 'the_content', '[hyperpress_noautop]<div>[hyperpress_emphasis]in block[/hyperpress_emphasis]</div>[/hyperpress_noautop]' );

		$this->assertStringContainsString( '<span class="emphasis">in block</span>', $output );
		$this->assertStringNotContainsString( '[hyperpress_emphasis]', $output );
	}

	public function test_the_invest_page_shape_has_no_stray_paragraphs(): void {
		$content = <<<'HTML'
[hyperpress_noautop]
<section class="invest">
	[hyperpress_banner title="T" subtitle="S" anchor="a" color="#0B67B2" /]
	<ul class="accordion" data-accordion>
		<li class="accordion-item" data-accordion-item>
			<a href="#" class="accordion-title">Why invest</a>
			<div class="accordion-content" data-tab-content>
				First line of text
				second line of text
			</div>
		</li>
	</ul>
	Since [hyperpress_date_distance date="1995-09-01" suffix="false" inclusive="true" /] years.
</section>
[/hyperpress_noautop]
HTML;
		$output  = apply_filters( 'the_content', $content );

		$this->assertStringNotContainsString( '<p></p>', $output );
		$this->assertStringNotContainsString( '<br />', $output );
		$this->assertDoesNotMatchRegularExpression( '#<ul class="accordion".*<p>.*</ul>#s', $output );
		$this->assertStringContainsString( 'First line of text', $output );
	}

	public function test_two_blocks_both_restore(): void {
		$content = '';
		for ( $i = 1; $i <= 11; $i++ ) {
			$content .= "[hyperpress_noautop]n{$i}[/hyperpress_noautop]\n\n";
		}
		$output = apply_filters( 'the_content', $content );

		for ( $i = 1; $i <= 11; $i++ ) {
			$this->assertSame( 1, preg_match_all( '/n' . $i . '(?!\d)/', $output ), "n{$i}" );
		}
		$this->assertStringNotContainsString( 'hyperpress_noautop', $output );
		$this->assertDoesNotMatchRegularExpression( '/hpnoautop/', $output );
	}

	public function test_escaped_tag_is_left_as_literal_text(): void {
		$output = apply_filters( 'the_content', '[[hyperpress_noautop]x[/hyperpress_noautop]]' );

		$this->assertStringContainsString( '[hyperpress_noautop]x[/hyperpress_noautop]', $output );
	}

	public function test_content_without_a_block_is_unchanged_by_the_filters(): void {
		$this->assertSame( 'plain', NoAutop::stash( 'plain' ) );
		$this->assertSame( 'plain', NoAutop::restore( 'plain' ) );
	}

	public function test_empty_and_null_bodies_match(): void {
		$this->assertSame( NoAutop::render( array(), '' ), NoAutop::render( array(), null ) );
	}
}
