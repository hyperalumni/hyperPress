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

	/**
	 * Page 3 of 12 exactly as NextGEN 4.5.5 builds it (Controller.php): the arrows carry an UNQUOTED data-pageid,
	 * the page links a quoted one, the class names of the arrows are swapped and the items are joined with "\n".
	 */
	private function real_nextgen_markup(): string {
		return "<div class='ngg-navigation'>"
			. "<a class='next' href='/photos/2025/nggpage--2' data-pageid=2>&#9668;</a>\n"
			. "<a class='page-numbers' data-pageid='1' href='/photos/2025/nggpage--1'>1</a>\n"
			. "<span class='ellipsis'>...</span>\n"
			. "<a class='page-numbers' data-pageid='2' href='/photos/2025/nggpage--2'>2</a>\n"
			. "<span class='current'>3</span>\n"
			. "<a class='page-numbers' data-pageid='4' href='/photos/2025/nggpage--4'>4</a>\n"
			. "<a class='page-numbers' data-pageid='5' href='/photos/2025/nggpage--5'>5</a>\n"
			. "<span class='ellipsis'>...</span>\n"
			. "<a class='page-numbers' data-pageid='12' href='/photos/2025/nggpage--12'>12</a>\n"
			. "<a class='prev' href='/photos/2025/nggpage--4' data-pageid=4>&#9658;</a>"
			. '</div>';
	}

	public function test_it_labels_the_arrows_of_real_nextgen_markup_with_unquoted_page_ids(): void {
		$html = hyperpress_nextgen_pagination( $this->real_nextgen_markup() );

		$this->assertStringStartsWith( '<nav aria-label="Pagination"><ul class="pagination text-center">', $html );
		$this->assertStringContainsString( "<a class='next' href='/photos/2025/nggpage--2' data-pageid=2 aria-label=\"Previous page\">&#9668;</a>", $html );
		$this->assertStringContainsString( "<a class='prev' href='/photos/2025/nggpage--4' data-pageid=4 aria-label=\"Next page\">&#9658;</a>", $html );
		$this->assertSame( 1, substr_count( $html, 'Previous page' ) );
		$this->assertSame( 1, substr_count( $html, 'Next page' ) );
	}

	public function test_it_leaves_page_number_links_of_real_nextgen_markup_unlabelled_and_verbatim(): void {
		$html = hyperpress_nextgen_pagination( $this->real_nextgen_markup() );

		foreach ( array( 1, 2, 4, 5, 12 ) as $page ) {
			$this->assertStringContainsString( "<li><a class='page-numbers' data-pageid='{$page}' href='/photos/2025/nggpage--{$page}'>{$page}</a></li>", $html );
		}
		$this->assertSame( 2, substr_count( $html, 'aria-label="Previous page"' ) + substr_count( $html, 'aria-label="Next page"' ) );
		$this->assertSame( 2, substr_count( $html, 'aria-label=' ) - 1, 'only the two arrows are labelled besides the nav' );
	}

	public function test_it_labels_an_unquoted_next_arrow_on_the_first_page(): void {
		$html = hyperpress_nextgen_pagination( "<div class='ngg-navigation'><span class='current'>1</span>\n<a class='page-numbers' data-pageid='2' href='/p/nggpage--2'>2</a>\n<a class='prev' href='/p/nggpage--2' data-pageid=2>&#9658;</a></div>" );

		$this->assertStringNotContainsString( 'Previous page', $html );
		$this->assertStringContainsString( "<a class='prev' href='/p/nggpage--2' data-pageid=2 aria-label=\"Next page\">", $html );
	}

	public function test_it_labels_an_unquoted_previous_arrow_on_the_last_page(): void {
		$html = hyperpress_nextgen_pagination( "<div class='ngg-navigation'><a class='next' href='/p/nggpage--11' data-pageid=11>&#9668;</a>\n<a class='page-numbers' data-pageid='11' href='/p/nggpage--11'>11</a>\n<span class='current'>12</span></div>" );

		$this->assertStringNotContainsString( 'Next page', $html );
		$this->assertStringContainsString( "<a class='next' href='/p/nggpage--11' data-pageid=11 aria-label=\"Previous page\">", $html );
	}

	public function test_it_accepts_a_double_quoted_page_id(): void {
		$html = hyperpress_nextgen_pagination( "<div class='ngg-navigation'><span class='current'>6</span>\n<a class='prev' href='?nggpage=7' data-pageid=\"7\">&#9658;</a></div>" );

		$this->assertStringContainsString( "<a class='prev' href='?nggpage=7' data-pageid=\"7\" aria-label=\"Next page\">", $html );
	}

	public function test_it_compares_unquoted_multi_digit_page_ids_numerically(): void {
		$next = hyperpress_nextgen_pagination( "<div class='ngg-navigation'><span class='current'>9</span>\n<a class='prev' href='?nggpage=10' data-pageid=10>&#9658;</a></div>" );
		$prev = hyperpress_nextgen_pagination( "<div class='ngg-navigation'><a class='next' href='?nggpage=10' data-pageid=10>&#9668;</a>\n<span class='current'>11</span></div>" );

		$this->assertStringContainsString( 'data-pageid=10 aria-label="Next page">', $next );
		$this->assertStringContainsString( 'data-pageid=10 aria-label="Previous page">', $prev );
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
