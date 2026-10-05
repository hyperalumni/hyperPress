<?php
namespace HyperPress\ThemeTests\Integration;

use WP_Error;
use WP_UnitTestCase;

final class LabelsBreadcrumbsGuardTest extends WP_UnitTestCase {

	public function test_post_without_categories_renders_labels_without_a_category_label(): void {
		$id = self::factory()->post->create( array( 'post_author' => self::factory()->user->create() ) );
		add_filter( 'get_the_categories', '__return_empty_array' );
		$this->go_to( get_permalink( $id ) );
		$this->assertTrue( is_singular( 'post' ) );
		the_post();

		$labels = apply_filters( 'hyperpress_labels_content', array() );

		$titles = wp_list_pluck( $labels, 'title' );
		$this->assertContains( 'Posted At', $titles );
		$this->assertNotContains( 'Posted In', $titles );
		foreach ( $labels as $label ) {
			$this->assertNotSame( '', (string) $label['label'] );
		}
	}

	public function test_post_with_a_category_still_gets_the_category_label(): void {
		$cat_id = self::factory()->category->create( array( 'name' => 'Guard Cat' ) );
		$id     = self::factory()->post->create( array( 'post_category' => array( $cat_id ) ) );
		$this->go_to( get_permalink( $id ) );

		$labels = apply_filters( 'hyperpress_labels_content', array() );

		$titles = wp_list_pluck( $labels, 'title' );
		$this->assertContains( 'Posted In', $titles );
		$this->assertContains( 'Guard Cat', wp_list_pluck( $labels, 'label' ) );
	}

	public function test_tag_archive_for_a_missing_tag_yields_no_crumbs(): void {
		$tag_id = self::factory()->tag->create();
		$this->go_to( get_tag_link( $tag_id ) );
		set_query_var( 'tag_id', 987654 );
		$this->assertTrue( is_tag() );

		$this->assertSame( array(), apply_filters( 'hyperpress_breadcrumbs_content', array() ) );
	}

	public function test_category_archive_for_a_missing_category_yields_no_crumbs(): void {
		$cat_id = self::factory()->category->create();
		$this->go_to( get_category_link( $cat_id ) );
		set_query_var( 'cat', 987654 );
		$this->assertTrue( is_category() );

		$this->assertSame( array(), apply_filters( 'hyperpress_breadcrumbs_content', array() ) );
	}

	public function test_category_archive_where_get_category_returns_wp_error_yields_no_crumbs(): void {
		$cat_id = self::factory()->category->create();
		$this->go_to( get_category_link( $cat_id ) );
		$this->assertTrue( is_category() );
		add_filter(
			'get_category',
			static function () {
				return new WP_Error( 'forced', 'forced' );
			}
		);

		$this->assertSame( array(), apply_filters( 'hyperpress_breadcrumbs_content', array() ) );
	}

	public function test_category_archive_yields_a_category_crumb(): void {
		$cat_id = self::factory()->category->create( array( 'name' => 'Crumb Cat' ) );
		$this->go_to( get_category_link( $cat_id ) );

		$crumbs = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertContains( 'Crumb Cat', wp_list_pluck( $crumbs, 'title' ) );
	}
}
