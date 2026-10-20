<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SponsorArchiveTest extends WP_UnitTestCase {

	private const DEFAULT = '/theme/archive.php';

	private function template(): string {
		return (string) apply_filters( 'archive_template', self::DEFAULT );
	}

	public function test_sponsor_archive_is_not_overridden_by_the_plugin(): void {
		flush_rewrite_rules();
		$this->go_to( home_url( '/?post_type=hyper_sponsor' ) );
		$this->assertTrue( is_post_type_archive( 'hyper_sponsor' ) );
		$this->assertSame( 'x.php', apply_filters( 'archive_template', 'x.php' ) );
	}

	public function test_sponsor_archive_pretty_url_is_not_overridden_by_the_plugin(): void {
		flush_rewrite_rules();
		$this->go_to( home_url( '/sponsors/' ) );
		$this->assertTrue( is_post_type_archive( 'hyper_sponsor' ) );
		$this->assertSame( self::DEFAULT, $this->template() );
	}

	public function test_season_sponsor_archive_is_not_overridden_by_the_plugin(): void {
		self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026', 'slug' => '2026' ) );
		flush_rewrite_rules();
		$this->go_to( home_url( '/seasons/2026/sponsors/' ) );
		$this->assertTrue( is_tax( 'hyper_season' ) );
		$this->assertTrue( is_post_type_archive( 'hyper_sponsor' ) );
		$this->assertSame( self::DEFAULT, $this->template() );
	}

	public function test_single_sponsor_page_keeps_the_default_template(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_sponsor' ) );
		$this->go_to( get_permalink( $post_id ) );
		$this->assertFalse( is_post_type_archive() );
		$this->assertSame( self::DEFAULT, $this->template() );
	}

	public function test_normal_post_keeps_the_default_template(): void {
		$post_id = self::factory()->post->create();
		$this->go_to( get_permalink( $post_id ) );
		$this->assertSame( self::DEFAULT, $this->template() );
	}

	public function test_sponsor_in_the_loop_of_another_page_keeps_the_default_template(): void {
		$sponsor = self::factory()->post->create( array( 'post_type' => 'hyper_sponsor' ) );
		$this->go_to( home_url( '/' ) );
		$GLOBALS['post'] = get_post( $sponsor ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- simulates a sponsor leaking into the global loop.
		setup_postdata( $GLOBALS['post'] );
		$this->assertSame( self::DEFAULT, $this->template() );
	}
}
