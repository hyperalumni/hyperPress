<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Plugin;
use HyperPress\Utils\RequestTerm;
use WP_Term;
use WP_UnitTestCase;

/**
 * Which rewrite rule matches first, not only that the rules exist: the CPT's attachment rule
 * `event/[^/]+/([^/]+)/?$` would otherwise swallow `/event/series/<term>/`.
 */
final class SeriesRoutingTest extends WP_UnitTestCase {

	private WP_Term $term;

	public function set_up(): void {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
		Plugin::register();
		flush_rewrite_rules();
		$this->term = self::factory()->term->create_and_get(
			array(
				'taxonomy' => Plugin::SERIES,
				'name'     => 'Bar Crawl',
				'slug'     => 'bar-crawl',
			)
		);
		// One event per page so that /page/2/ has something to show.
		foreach ( self::factory()->post->create_many( 3, array( 'post_type' => Plugin::POST_TYPE ) ) as $id ) {
			wp_set_object_terms( $id, 'bar-crawl', Plugin::SERIES );
		}
		update_option( 'posts_per_page', 2 );
	}

	public function test_series_archive_resolves_instead_of_the_attachment_rule(): void {
		$this->go_to( home_url( '/event/series/bar-crawl/' ) );

		$this->assertTrue( is_tax(), 'is_tax' );
		$this->assertFalse( is_404(), 'is_404' );
		$this->assertSame( $this->term->term_id, RequestTerm::get( Plugin::SERIES )?->term_id );
		$this->assertSame( '', (string) get_query_var( 'attachment' ) );
	}

	public function test_series_archive_page_two_resolves(): void {
		$this->go_to( home_url( '/event/series/bar-crawl/page/2/' ) );

		$this->assertTrue( is_tax(), 'is_tax' );
		$this->assertFalse( is_404(), 'is_404' );
		$this->assertSame( 2, (int) get_query_var( 'paged' ) );
		$this->assertSame( $this->term->term_id, RequestTerm::get( Plugin::SERIES )?->term_id );
		$this->assertSame( '', (string) get_query_var( 'attachment' ) );
	}

	public function test_a_single_event_still_resolves(): void {
		self::factory()->post->create(
			array(
				'post_type' => Plugin::POST_TYPE,
				'post_name' => 'bar-crawl-2026',
			)
		);

		$this->go_to( home_url( '/event/bar-crawl-2026/' ) );

		$this->assertTrue( is_singular( Plugin::POST_TYPE ), 'is_singular' );
		$this->assertFalse( is_404(), 'is_404' );
		$this->assertFalse( is_tax(), 'is_tax' );
	}
}
