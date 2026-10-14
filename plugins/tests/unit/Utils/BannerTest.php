<?php
namespace HyperPress\Tests\Unit\Utils;

use HyperPress\Utils\Banner;
use PHPUnit\Framework\TestCase;

final class BannerTest extends TestCase {

	private function args( array $overrides = array() ): array {
		return array_merge(
			array(
				'type'          => 'sponsor',
				'subtitle'      => static fn() => 'Sponsor subtitle',
				'color_mod'     => 'hyperpress_hyper_red',
				'archive_title' => 'Sponsors',
				'term_joiner'   => 'in',
			),
			$overrides
		);
	}

	private function ctx( array $overrides = array() ): array {
		return array_merge(
			array(
				'is_singular'   => false,
				'is_archive'    => false,
				'is_tax_season' => false,
				'subtitle'      => 'Thank you',
				'color'         => '#c00',
				'term_name'     => '',
			),
			$overrides
		);
	}

	public function test_untouched_when_neither_singular_nor_archive(): void {
		$this->assertSame( array( 'title' => 'T' ), Banner::apply( array( 'title' => 'T' ), $this->args(), $this->ctx() ) );
	}

	public function test_singular_sets_type_subtitle_and_color_but_keeps_title(): void {
		$banner = Banner::apply( array( 'title' => 'Acme' ), $this->args(), $this->ctx( array( 'is_singular' => true ) ) );
		$this->assertSame( 'sponsor', $banner['type'] );
		$this->assertSame( 'Thank you', $banner['subtitle'] );
		$this->assertSame( '#c00', $banner['backgroundColor'] );
		$this->assertSame( 'Acme', $banner['title'] );
	}

	public function test_archive_sets_title_only_when_empty_by_default(): void {
		$empty = Banner::apply( array( 'title' => '' ), $this->args(), $this->ctx( array( 'is_archive' => true ) ) );
		$this->assertSame( 'Sponsors', $empty['title'] );

		$set = Banner::apply( array( 'title' => 'Custom' ), $this->args(), $this->ctx( array( 'is_archive' => true ) ) );
		$this->assertSame( 'Custom', $set['title'] );
	}

	public function test_archive_title_can_always_be_overwritten(): void {
		$banner = Banner::apply(
			array( 'title' => 'Custom' ),
			$this->args( array( 'always_set_archive_title' => true ) ),
			$this->ctx( array( 'is_archive' => true ) )
		);
		$this->assertSame( 'Sponsors', $banner['title'] );
	}

	public function test_season_archive_appends_the_term_with_the_configured_joiner(): void {
		$in = Banner::apply( array( 'title' => '' ), $this->args(), $this->ctx( array( 'is_archive' => true, 'is_tax_season' => true, 'term_name' => '2026' ) ) );
		$this->assertSame( 'Thank you in 2026', $in['subtitle'] );

		$from = Banner::apply( array( 'title' => '' ), $this->args( array( 'term_joiner' => 'from' ) ), $this->ctx( array( 'is_archive' => true, 'is_tax_season' => true, 'term_name' => '2026' ) ) );
		$this->assertSame( 'Thank you from 2026', $from['subtitle'] );
	}

	public function test_no_term_suffix_without_a_subtitle(): void {
		$banner = Banner::apply( array( 'title' => '' ), $this->args(), $this->ctx( array( 'is_archive' => true, 'is_tax_season' => true, 'term_name' => '2026', 'subtitle' => '' ) ) );
		$this->assertSame( '', $banner['subtitle'] );
	}

	public function test_taxonomy_page_title_is_term_names_plus_archive_title(): void {
		$banner = Banner::apply(
			array(),
			$this->args( array( 'type' => 'competition', 'archive_title' => 'Competitions' ) ),
			$this->ctx( array( 'extra_terms' => array( 'District' ) ) )
		);
		$this->assertSame( 'District Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Thank you', $banner['subtitle'] );
		$this->assertSame( '#c00', $banner['backgroundColor'] );
	}

	public function test_taxonomy_page_joins_several_terms(): void {
		$banner = Banner::apply( array(), $this->args( array( 'archive_title' => 'Competitions' ) ), $this->ctx( array( 'extra_terms' => array( 'District', 'Week 1' ) ) ) );
		$this->assertSame( 'District Week 1 Competitions', $banner['title'] );
	}

	public function test_taxonomy_page_overwrites_an_existing_title(): void {
		$banner = Banner::apply( array( 'title' => 'Season 31' ), $this->args( array( 'archive_title' => 'Competitions' ) ), $this->ctx( array( 'extra_terms' => array( 'District' ) ) ) );
		$this->assertSame( 'District Competitions', $banner['title'] );
	}

	public function test_archive_subtitle_lists_extra_terms_then_season(): void {
		$banner = Banner::apply(
			array( 'title' => '' ),
			$this->args(),
			$this->ctx( array( 'is_archive' => true, 'is_tax_season' => true, 'term_name' => 'Season 31', 'subtitle' => 'Subtitle', 'extra_terms' => array( 'District' ) ) )
		);
		$this->assertSame( 'Subtitle in District, Season 31', $banner['subtitle'] );
	}

	public function test_archive_subtitle_with_extra_terms_and_no_season(): void {
		$banner = Banner::apply(
			array( 'title' => '' ),
			$this->args(),
			$this->ctx( array( 'is_archive' => true, 'subtitle' => 'Subtitle', 'extra_terms' => array( 'District' ) ) )
		);
		$this->assertSame( 'Subtitle in District', $banner['subtitle'] );
		$this->assertSame( 'Sponsors', $banner['title'] );
	}

	public function test_apply_without_extra_terms_is_unchanged(): void {
		$ctx    = $this->ctx( array( 'is_archive' => true, 'is_tax_season' => true, 'term_name' => '2026' ) );
		$banner = Banner::apply( array( 'title' => '' ), $this->args(), $ctx );
		$this->assertArrayNotHasKey( 'extra_terms', $ctx );
		$this->assertSame( 'Thank you in 2026', $banner['subtitle'] );
		$this->assertSame( 'Sponsors', $banner['title'] );

		$same = Banner::apply( array( 'title' => '' ), $this->args(), array_merge( $ctx, array( 'extra_terms' => array() ) ) );
		$this->assertSame( $banner, $same );
	}

	public function test_page_with_no_terms_is_unchanged(): void {
		$this->assertSame( array( 'title' => 'T' ), Banner::apply( array( 'title' => 'T' ), $this->args(), $this->ctx( array( 'extra_terms' => array() ) ) ) );
	}
}
