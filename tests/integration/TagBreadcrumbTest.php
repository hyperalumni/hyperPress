<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class TagBreadcrumbTest extends WP_UnitTestCase {

	public function test_tag_archive_yields_a_tag_crumb_without_deprecations(): void {
		$tag_id = self::factory()->tag->create( array( 'name' => 'Baseline Tag' ) );
		self::factory()->post->create( array( 'tags_input' => array( $tag_id ) ) ); // get_terms() hides empty terms.
		$this->go_to( get_tag_link( $tag_id ) );
		$this->assertTrue( is_tag() );

		// WP_UnitTestCase fails the test on any unexpected _deprecated_* call.
		$crumbs = apply_filters( 'hyperpress_breadcrumbs_content', array() );

		$titles = wp_list_pluck( $crumbs, 'title' );
		$this->assertContains( 'Baseline Tag', $titles );
		$this->assertContains( 'item-tag-' . $tag_id, $crumbs[ array_search( 'Baseline Tag', $titles, true ) ]['classes']['li'] );
	}

	public function test_unknown_tag_adds_no_crumbs_and_no_errors(): void {
		$tag_id = self::factory()->tag->create();
		$this->go_to( get_tag_link( $tag_id ) );
		set_query_var( 'tag_id', 999999 );

		$this->assertSame( array(), apply_filters( 'hyperpress_breadcrumbs_content', array() ) );
	}
}
