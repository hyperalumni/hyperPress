<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Breadcrumbs;
use HyperPress\Event\Plugin;
use HyperPress\Event\Settings;
use WP_UnitTestCase;

final class BreadcrumbsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		delete_option( Settings::OPTION );
	}

	public function tear_down(): void {
		delete_option( Settings::OPTION );
		parent::tear_down();
	}

	private function hub(): int {
		$page = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		update_option( Settings::OPTION, array( Settings::HUB_PAGE => (string) $page ) );

		return $page;
	}

	private function event( array $series = array() ): int {
		$id = self::factory()->post->create(
			array(
				'post_type'   => Plugin::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => 'Spring Open',
			)
		);
		if ( $series ) {
			wp_set_object_terms( $id, $series, Plugin::SERIES );
		}

		return $id;
	}

	private function titles( array $items ): array {
		return array_column( $items, 'title' );
	}

	public function test_registered_at_priority_10(): void {
		$this->assertSame( 10, has_filter( 'hyperpress_breadcrumbs_content', array( Breadcrumbs::class, 'filter' ) ) );
	}

	public function test_single_event_crumbs(): void {
		$hub    = $this->hub();
		$series = self::factory()->term->create(
			array(
				'taxonomy' => Plugin::SERIES,
				'name'     => 'Camp',
			)
		);
		$id     = $this->event( array( $series ) );
		$this->go_to( get_permalink( $id ) );

		$items = Breadcrumbs::filter( array( array( 'title' => 'Home' ) ) );

		$this->assertSame( array( 'Home', 'Events', 'Camp', 'Spring Open' ), $this->titles( $items ) );
		$this->assertSame( get_permalink( $hub ), $items[1]['url'] );
		$this->assertSame( get_term_link( $series, Plugin::SERIES ), $items[2]['url'] );
		$this->assertArrayNotHasKey( 'url', $items[3] );
		foreach ( array_slice( $items, 1 ) as $item ) {
			$this->assertArrayHasKey( 'li', $item['classes'] );
			$this->assertArrayHasKey( 'bread', $item['classes'] );
		}
	}

	public function test_first_series_by_term_id(): void {
		$this->hub();
		$first  = self::factory()->term->create(
			array(
				'taxonomy' => Plugin::SERIES,
				'name'     => 'Zeta',
			)
		);
		$second = self::factory()->term->create(
			array(
				'taxonomy' => Plugin::SERIES,
				'name'     => 'Alpha',
			)
		);
		$id     = $this->event( array( $second, $first ) );
		$this->go_to( get_permalink( $id ) );

		$items = Breadcrumbs::filter( array() );

		$this->assertSame( array( 'Events', 'Zeta', 'Spring Open' ), $this->titles( $items ) );
	}

	public function test_hub_crumb_omitted_without_a_hub_page(): void {
		$series = self::factory()->term->create(
			array(
				'taxonomy' => Plugin::SERIES,
				'name'     => 'Camp',
			)
		);
		$id     = $this->event( array( $series ) );
		$this->go_to( get_permalink( $id ) );

		$this->assertSame( array( 'Camp', 'Spring Open' ), $this->titles( Breadcrumbs::filter( array() ) ) );
	}

	public function test_event_without_a_series_skips_that_crumb(): void {
		$this->hub();
		$id = $this->event();
		$this->go_to( get_permalink( $id ) );

		$this->assertSame( array( 'Events', 'Spring Open' ), $this->titles( Breadcrumbs::filter( array() ) ) );
	}

	public function test_series_archive_crumbs(): void {
		$hub    = $this->hub();
		$series = self::factory()->term->create(
			array(
				'taxonomy' => Plugin::SERIES,
				'name'     => 'Camp',
				'slug'     => 'camp',
			)
		);
		$this->go_to( '/?taxonomy=' . Plugin::SERIES . '&term=camp' );
		$this->assertTrue( is_tax( Plugin::SERIES ) );

		$items = Breadcrumbs::filter( array( array( 'title' => 'Home' ) ) );

		$this->assertSame( array( 'Home', 'Events', 'Camp' ), $this->titles( $items ) );
		$this->assertSame( get_permalink( $hub ), $items[1]['url'] );
		$this->assertArrayNotHasKey( 'url', $items[2] );
	}

	public function test_other_contexts_untouched(): void {
		$input = array( array( 'title' => 'Home' ) );

		$this->go_to( '/' );
		$this->assertSame( $input, Breadcrumbs::filter( $input ) );

		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_status' => 'publish' ) ) ) );
		$this->assertSame( $input, Breadcrumbs::filter( $input ) );

		$this->go_to( get_permalink( self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) ) ) );
		$this->assertSame( $input, Breadcrumbs::filter( $input ) );

		$cat = self::factory()->category->create();
		$this->go_to( get_category_link( $cat ) );
		$this->assertSame( $input, Breadcrumbs::filter( $input ) );
	}
}
