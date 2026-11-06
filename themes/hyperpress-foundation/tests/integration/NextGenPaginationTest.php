<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class NextGenPaginationTest extends WP_UnitTestCase {

	private const SAMPLE = "<div class='ngg-navigation'><a class='next' data-pageid='1' href='?nggpage=1'>&#9668;</a><a class='page-numbers' data-pageid='1' href='?nggpage=1'>1</a><span class='current'>2</span><a class='page-numbers' data-pageid='3' href='?nggpage=3'>3</a><span class='ellipsis'>...</span><a class='page-numbers' data-pageid='9' href='?nggpage=9'>9</a><a class='prev' data-pageid='3' href='?nggpage=3'>&#9658;</a></div>";

	public function test_it_builds_foundation_pagination_markup(): void {
		$html = hyperpress_nextgen_pagination( self::SAMPLE );

		$this->assertStringStartsWith( '<nav aria-label="Pagination"><ul class="pagination text-center">', $html );
		$this->assertStringEndsWith( '</ul></nav>', $html );
		$this->assertStringContainsString( '<li class="current" aria-current="page"><span class="show-for-sr">You are on page</span> 2</li>', $html );
		$this->assertStringContainsString( '<li class="ellipsis" aria-hidden="true"></li>', $html );
	}

	public function test_it_keeps_the_classes_and_attributes_nextgens_ajax_script_binds_to(): void {
		$html = hyperpress_nextgen_pagination( self::SAMPLE );

		$this->assertStringContainsString( "<li><a class='page-numbers' data-pageid='3' href='?nggpage=3'>3</a></li>", $html );
		$this->assertStringContainsString( "class='next'", $html );
		$this->assertStringContainsString( "class='prev'", $html );
	}

	public function test_it_labels_previous_and_next_by_page_number_not_by_nextgens_swapped_classes(): void {
		$html = hyperpress_nextgen_pagination( self::SAMPLE );

		$this->assertStringContainsString( "<a class='next' data-pageid='1' href='?nggpage=1' aria-label=\"Previous page\">", $html );
		$this->assertStringContainsString( "<a class='prev' data-pageid='3' href='?nggpage=3' aria-label=\"Next page\">", $html );
	}

	public function test_it_has_no_previous_link_on_the_first_page(): void {
		$html = hyperpress_nextgen_pagination( "<div class='ngg-navigation'><span class='current'>1</span><a class='page-numbers' data-pageid='2' href='?nggpage=2'>2</a><span class='ellipsis'>...</span><a class='page-numbers' data-pageid='9' href='?nggpage=9'>9</a><a class='prev' data-pageid='2' href='?nggpage=2'>&#9658;</a></div>" );

		$this->assertStringNotContainsString( 'Previous page', $html );
		$this->assertStringContainsString( "<a class='prev' data-pageid='2' href='?nggpage=2' aria-label=\"Next page\">", $html );
		$this->assertStringContainsString( '<li class="current" aria-current="page"><span class="show-for-sr">You are on page</span> 1</li>', $html );
	}

	public function test_it_has_no_next_link_on_the_last_page(): void {
		$html = hyperpress_nextgen_pagination( "<div class='ngg-navigation'><a class='next' data-pageid='8' href='?nggpage=8'>&#9668;</a><a class='page-numbers' data-pageid='1' href='?nggpage=1'>1</a><span class='ellipsis'>...</span><a class='page-numbers' data-pageid='8' href='?nggpage=8'>8</a><span class='current'>9</span></div>" );

		$this->assertStringNotContainsString( 'Next page', $html );
		$this->assertStringContainsString( "<a class='next' data-pageid='8' href='?nggpage=8' aria-label=\"Previous page\">", $html );
		$this->assertStringContainsString( '<li class="current" aria-current="page"><span class="show-for-sr">You are on page</span> 9</li>', $html );
	}

	public function test_it_labels_links_by_page_number_for_multi_digit_pages(): void {
		$html = hyperpress_nextgen_pagination( "<div class='ngg-navigation'><a class='next' data-pageid='9' href='?nggpage=9'>&#9668;</a><a class='page-numbers' data-pageid='9' href='?nggpage=9'>9</a><span class='current'>10</span><a class='page-numbers' data-pageid='11' href='?nggpage=11'>11</a><a class='prev' data-pageid='11' href='?nggpage=11'>&#9658;</a></div>" );

		$this->assertStringContainsString( "<a class='next' data-pageid='9' href='?nggpage=9' aria-label=\"Previous page\">", $html );
		$this->assertStringContainsString( "<a class='prev' data-pageid='11' href='?nggpage=11' aria-label=\"Next page\">", $html );
		$this->assertStringContainsString( 'You are on page</span> 10</li>', $html );
	}

	public function test_it_leaves_unrecognised_markup_unchanged(): void {
		$inputs = array(
			'',
			'<p>nothing</p>',
			"<div class='ngg-navigation'><a class='page-numbers' data-pageid='2' href='?p=2'>2</a></div>",
			"<div class='ngg-navigation'><span class='current'>1</span><span class='mystery'>x</span></div>",
		);

		foreach ( $inputs as $input ) {
			$this->assertSame( $input, hyperpress_nextgen_pagination( $input ) );
		}
	}
}
