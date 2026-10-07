<?php
/**
 * Tests for the post labels.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Label\Registry;
use HyperPress\Label\Renderer;
use HyperPress\Label\Resolver;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Label\PostLabels
 */
final class PostLabelsTest extends WP_UnitTestCase {

	/**
	 * Provider of comment counts and their labels.
	 *
	 * @return array<string,array{int,string}>
	 */
	public static function comment_counts(): array {
		return array(
			'none' => array( 0, '0 comments' ),
			'one'  => array( 1, '1 comment' ),
			'two'  => array( 2, '2 comments' ),
		);
	}

	public function set_up(): void {
		parent::set_up();
		Registry::reset();
		Renderer::register();
	}

	public function tear_down(): void {
		remove_theme_mod( Resolver::enabled_key( 'page' ) );
		remove_all_filters( 'hyperpress_labels_content' );
		remove_filter( 'get_the_categories', '__return_empty_array' );
		Registry::reset( true );
		parent::tear_down();
	}

	public function test_post_without_categories_has_no_category_label(): void {
		$id = self::factory()->post->create( array( 'post_author' => self::factory()->user->create() ) );
		add_filter( 'get_the_categories', '__return_empty_array' );
		$this->go_to( get_permalink( $id ) );
		$this->assertTrue( is_singular( 'post' ) );
		the_post();

		$labels = Renderer::labels();

		$titles = wp_list_pluck( $labels, 'title' );
		$this->assertContains( 'Posted At', $titles );
		$this->assertNotContains( 'Posted In', $titles );
		foreach ( $labels as $label ) {
			$this->assertNotSame( '', (string) $label['label'] );
		}
	}

	public function test_post_with_a_category_gets_it(): void {
		$cat_id = self::factory()->category->create( array( 'name' => 'Guard Cat' ) );
		$id     = self::factory()->post->create( array( 'post_category' => array( $cat_id ) ) );
		$this->go_to( get_permalink( $id ) );

		$labels = Renderer::labels();

		$this->assertContains( 'Posted In', wp_list_pluck( $labels, 'title' ) );
		$this->assertContains( 'Guard Cat', wp_list_pluck( $labels, 'label' ) );
	}

	/**
	 * @dataProvider comment_counts
	 *
	 * @param int    $count    Approved comments.
	 * @param string $expected Expected label text.
	 */
	public function test_comment_count_label( int $count, string $expected ): void {
		$post_id = self::factory()->post->create();
		self::factory()->comment->create_many(
			$count,
			array(
				'comment_post_ID'  => $post_id,
				'comment_approved' => 1,
			)
		);
		$this->go_to( get_permalink( $post_id ) );

		$this->assertContains( $expected, array_column( Renderer::labels(), 'label' ) );
	}

	public function test_order_is_date_author_category_comments(): void {
		$cat_id = self::factory()->category->create( array( 'name' => 'Order Cat' ) );
		$id     = self::factory()->post->create( array( 'post_category' => array( $cat_id ) ) );
		$this->go_to( get_permalink( $id ) );

		$titles = array_values(
			array_filter(
				wp_list_pluck( Renderer::labels(), 'title' ),
				static fn ( $title ): bool => in_array( $title, array( 'Posted At', 'Posted By', 'Posted In', '' ), true )
			)
		);

		$this->assertSame( array( 'Posted At', 'Posted By', 'Posted In', '' ), $titles );
	}

	public function test_labels_do_not_appear_on_a_page(): void {
		$id = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$this->go_to( get_permalink( $id ) );

		$this->assertSame( array(), Renderer::labels() );
	}
}
