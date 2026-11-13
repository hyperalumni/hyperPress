<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Menus;
use HyperPress\Event\Plugin;
use HyperPress\Utils\RequestTerm;
use stdClass;
use WP_UnitTestCase;

final class MenusTest extends WP_UnitTestCase {

	private function item( array $props ): stdClass {
		$item      = new stdClass();
		$item->url = 'http://example.org/original/';
		foreach ( $props as $key => $value ) {
			$item->$key = $value;
		}

		return $item;
	}

	private function series( string $slug = 'camp' ): int {
		return self::factory()->term->create(
			array(
				'taxonomy' => Plugin::SERIES,
				'name'     => 'Camp',
				'slug'     => $slug,
			)
		);
	}

	public function tear_down(): void {
		remove_all_filters( 'pre_option_permalink_structure' );
		parent::tear_down();
	}

	public function test_registered_at_priority_10(): void {
		$this->assertSame( 10, has_filter( 'wp_setup_nav_menu_item', array( Menus::class, 'url' ) ) );
	}

	public function test_series_item_links_to_the_alias(): void {
		$term = $this->series();
		$item = $this->item(
			array(
				'type'      => 'taxonomy',
				'object'    => Plugin::SERIES,
				'object_id' => $term,
			)
		);

		$result = Menus::url( $item );

		$this->assertSame( home_url( user_trailingslashit( 'event/camp' ) ), $result->url );
		$this->assertSame( $term, $result->object_id );
		$this->assertSame( 'taxonomy', $result->type );
	}

	public function test_other_items_and_invalid_items_are_untouched(): void {
		$term  = $this->series();
		$cases = array(
			'page'           => array(
				'type'      => 'post_type',
				'object'    => 'page',
				'object_id' => 5,
			),
			'custom'         => array(
				'type'      => 'custom',
				'object'    => 'custom',
				'object_id' => 6,
			),
			'other taxonomy' => array(
				'type'      => 'taxonomy',
				'object'    => 'category',
				'object_id' => 1,
			),
			'invalid'        => array(
				'type'      => 'taxonomy',
				'object'    => Plugin::SERIES,
				'object_id' => $term,
				'_invalid'  => true,
			),
			'deleted term'   => array(
				'type'      => 'taxonomy',
				'object'    => Plugin::SERIES,
				'object_id' => 999999,
			),
		);

		foreach ( $cases as $name => $props ) {
			$item   = $this->item( $props );
			$before = clone $item;
			$this->assertEquals( $before, Menus::url( $item ), $name );
		}
	}

	public function test_term_link_and_archive_are_unchanged(): void {
		$term = $this->series();

		$this->assertSame( home_url( user_trailingslashit( 'event/series/camp' ) ), get_term_link( $term, Plugin::SERIES ) );
	}

	public function test_series_item_is_current_on_the_archive(): void {
		$term = $this->series();
		$menu = wp_create_nav_menu( 'Event menu' );
		wp_update_nav_menu_item(
			$menu,
			0,
			array(
				'menu-item-type'      => 'taxonomy',
				'menu-item-object'    => Plugin::SERIES,
				'menu-item-object-id' => $term,
				'menu-item-status'    => 'publish',
			)
		);

		$this->go_to( '/?' . Plugin::SERIES . '=camp' );
		$this->assertTrue( is_tax( Plugin::SERIES ) );
		$this->assertSame( $term, RequestTerm::get( Plugin::SERIES )?->term_id );

		$html = wp_nav_menu(
			array(
				'menu' => $menu,
				'echo' => false,
			)
		);

		$this->assertMatchesRegularExpression( '/<li[^>]*class="[^"]*\bcurrent-menu-item\b[^"]*"/', $html );
		$this->assertStringContainsString( 'href="' . home_url( user_trailingslashit( 'event/camp' ) ) . '"', $html );
		$this->assertStringNotContainsString( 'event/series/camp', $html );
	}

	public function test_plain_permalinks_keep_the_term_archive_link(): void {
		$term = $this->series();
		// Plain permalinks 404 the alias path, so the item keeps the archive link.
		add_filter( 'pre_option_permalink_structure', static fn(): string => '', 99 );
		$this->assertSame( '', get_option( 'permalink_structure' ) );

		$original = (string) get_term_link( $term, Plugin::SERIES );
		$item     = $this->item(
			array(
				'type'      => 'taxonomy',
				'object'    => Plugin::SERIES,
				'object_id' => $term,
				'url'       => $original,
			)
		);

		$this->assertSame( $original, Menus::url( $item )->url );
	}
}
