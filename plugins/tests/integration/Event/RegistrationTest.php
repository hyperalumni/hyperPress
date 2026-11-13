<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Plugin;
use WP_UnitTestCase;

final class RegistrationTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
		Plugin::register();
		flush_rewrite_rules();
	}

	public function test_post_type_uses_singular_base_and_has_no_archive(): void {
		$type = get_post_type_object( Plugin::POST_TYPE );

		$this->assertNotNull( $type );
		$this->assertSame( 'event', $type->rewrite['slug'] );
		$this->assertFalse( $type->rewrite['with_front'] );
		$this->assertFalse( $type->has_archive );
		$this->assertTrue( $type->show_in_rest );
		foreach ( array( 'title', 'editor', 'excerpt', 'thumbnail' ) as $feature ) {
			$this->assertTrue( post_type_supports( Plugin::POST_TYPE, $feature ), $feature );
		}
	}

	public function test_series_taxonomy_routes_under_event_series(): void {
		$tax = get_taxonomy( Plugin::SERIES );

		$this->assertNotFalse( $tax );
		$this->assertSame( array( Plugin::POST_TYPE ), $tax->object_type );
		$this->assertSame( 'event/series', $tax->rewrite['slug'] );
		$this->assertTrue( $tax->show_in_nav_menus );
		$this->assertTrue( $tax->show_admin_column );
		$this->assertFalse( $tax->hierarchical );

		$term = self::factory()->term->create_and_get(
			array(
				'taxonomy' => Plugin::SERIES,
				'name'     => 'Bar Crawl',
				'slug'     => 'bar-crawl',
			)
		);
		$this->assertStringEndsWith( '/event/series/bar-crawl/', get_term_link( $term ) );
	}

	public function test_event_permalink(): void {
		$id = self::factory()->post->create(
			array(
				'post_type' => Plugin::POST_TYPE,
				'post_name' => 'bar-crawl-2026',
			)
		);

		$this->assertStringEndsWith( '/event/bar-crawl-2026/', get_permalink( $id ) );
	}

	public function test_series_is_a_reserved_event_slug(): void {
		$id = self::factory()->post->create(
			array(
				'post_type' => Plugin::POST_TYPE,
				'post_name' => 'series',
			)
		);

		$this->assertNotSame( 'series', get_post( $id )->post_name );
	}

	public function test_pages_under_events_still_resolve(): void {
		$parent = self::factory()->post->create(
			array(
				'post_type' => 'page',
				'post_name' => 'events',
			)
		);
		$child  = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_name'   => 'build-schedule',
				'post_parent' => $parent,
			)
		);

		foreach ( array( $parent, $child ) as $id ) {
			$this->go_to( get_permalink( $id ) );
			$this->assertTrue( is_page(), get_permalink( $id ) );
			$this->assertFalse( is_404(), get_permalink( $id ) );
		}
	}
}
