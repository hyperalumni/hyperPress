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
}
