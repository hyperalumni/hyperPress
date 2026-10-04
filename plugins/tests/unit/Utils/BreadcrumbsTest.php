<?php
namespace HyperPress\Tests\Unit\Utils;

use HyperPress\Utils\Breadcrumbs;
use PHPUnit\Framework\TestCase;

final class BreadcrumbsTest extends TestCase {

	private function ctx( array $overrides = array() ): array {
		return array_merge(
			array(
				'post_type'   => 'hyper_sponsor',
				'label'       => 'Sponsors',
				'archive_url' => 'https://example.org/sponsors/',
				'is_singular' => false,
				'season'      => null,
				'title'       => '',
				'id'          => 0,
			),
			$overrides
		);
	}

	private function season(): array {
		return array(
			'name' => '2026',
			'slug' => '2026',
			'url'  => 'https://example.org/seasons/2026/sponsors/',
		);
	}

	public function test_archive_adds_only_the_post_type_crumb(): void {
		$trail = Breadcrumbs::build( array(), $this->ctx() );
		$this->assertCount( 1, $trail );
		$this->assertSame( 'Sponsors', $trail[0]['title'] );
		$this->assertSame( 'https://example.org/sponsors/', $trail[0]['url'] );
		$this->assertSame( array( 'item-archive-Sponsors' ), $trail[0]['classes']['li'] );
		$this->assertSame( array( 'bread-archive-Sponsors' ), $trail[0]['classes']['bread'] );
	}

	public function test_season_archive_adds_the_season_crumb(): void {
		$trail = Breadcrumbs::build( array(), $this->ctx( array( 'season' => $this->season() ) ) );
		$this->assertCount( 2, $trail );
		$this->assertSame( '2026', $trail[1]['title'] );
		$this->assertSame( 'https://example.org/seasons/2026/sponsors/', $trail[1]['url'] );
		$this->assertSame( array( 'item-2026-Sponsors' ), $trail[1]['classes']['li'] );
		$this->assertSame( array( 'bread-2026-Sponsors' ), $trail[1]['classes']['bread'] );
	}

	public function test_singular_adds_the_current_title_last_without_a_url(): void {
		$trail = Breadcrumbs::build(
			array(),
			$this->ctx( array( 'is_singular' => true, 'season' => $this->season(), 'title' => 'Acme', 'id' => 42 ) )
		);
		$this->assertCount( 3, $trail );
		$this->assertSame( 'Acme', $trail[2]['title'] );
		$this->assertArrayNotHasKey( 'url', $trail[2] );
		$this->assertSame( array( 'item-hyper_sponsor', 'item-hyper_sponsor42' ), $trail[2]['classes']['li'] );
		$this->assertSame( array( 'bread-hyper_sponsor', 'bread-hyper_sponsor42' ), $trail[2]['classes']['bread'] );
	}

	public function test_singular_without_a_season_skips_the_season_crumb(): void {
		$trail = Breadcrumbs::build( array(), $this->ctx( array( 'is_singular' => true, 'title' => 'Acme', 'id' => 7 ) ) );
		$this->assertSame( array( 'Sponsors', 'Acme' ), array_column( $trail, 'title' ) );
	}

	public function test_existing_crumbs_are_kept_in_front(): void {
		$trail = Breadcrumbs::build( array( array( 'title' => 'Home' ) ), $this->ctx() );
		$this->assertSame( array( 'Home', 'Sponsors' ), array_column( $trail, 'title' ) );
	}

	public function test_empty_label_skips_the_post_type_crumb(): void {
		$trail = Breadcrumbs::build( array(), $this->ctx( array( 'label' => '' ) ) );
		$this->assertSame( array(), $trail );
	}

	public function test_season_taxonomy_constant_is_the_new_key(): void {
		$this->assertSame( 'hyper_season', Breadcrumbs::SEASON_TAXONOMY );
	}
}
