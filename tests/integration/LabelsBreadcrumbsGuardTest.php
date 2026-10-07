<?php
namespace HyperPress\ThemeTests\Integration;

use WP_Error;
use WP_UnitTestCase;

final class LabelsBreadcrumbsGuardTest extends WP_UnitTestCase {

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

	public function test_post_season_crumb_links_to_the_season_term_not_a_category(): void {
		$term_id = self::factory()->term->create(
			array(
				'taxonomy' => 'hyper_season',
				'name'     => 'Crumb Season',
			)
		);
		$post_id = self::factory()->post->create();
		wp_set_object_terms( $post_id, array( $term_id ), 'hyper_season' );
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( is_singular( 'post' ) );

		$term   = get_term( $term_id, 'hyper_season' );
		$crumbs = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$season = null;
		foreach ( $crumbs as $crumb ) {
			if ( 'Crumb Season' === $crumb['title'] ) {
				$season = $crumb;
			}
		}

		$this->assertNotNull( $season, 'The season crumb is missing' );
		$this->assertSame( get_term_link( $term ), $season['url'] );
		$this->assertStringNotContainsString( '/category/', $season['url'] );
	}

	public function test_post_season_crumb_has_an_empty_url_when_the_term_link_errors(): void {
		$term_id = self::factory()->term->create(
			array(
				'taxonomy' => 'hyper_season',
				'name'     => 'Linkless Season',
			)
		);
		$post_id = self::factory()->post->create();
		wp_set_object_terms( $post_id, array( $term_id ), 'hyper_season' );
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( is_singular( 'post' ) );
		add_filter(
			'term_link',
			static function () {
				return new WP_Error( 'forced', 'forced' );
			}
		);
		$this->assertInstanceOf( WP_Error::class, get_term_link( get_term( $term_id, 'hyper_season' ) ) );

		$crumbs = apply_filters( 'hyperpress_breadcrumbs_content', array() );
		$season = null;
		foreach ( $crumbs as $crumb ) {
			if ( 'Linkless Season' === $crumb['title'] ) {
				$season = $crumb;
			}
		}

		$this->assertNotNull( $season, 'The season crumb is missing' );
		$this->assertSame( '', $season['url'] );
	}

	public function test_category_archive_yields_a_category_crumb(): void {
		$cat_id = self::factory()->category->create( array( 'name' => 'Crumb Cat' ) );
		$this->go_to( get_category_link( $cat_id ) );

		$crumbs = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$this->assertContains( 'Crumb Cat', wp_list_pluck( $crumbs, 'title' ) );
	}
}
