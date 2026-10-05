<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SeasonBannerTitleTest extends WP_UnitTestCase {

	public function tear_down(): void {
		remove_all_filters( 'gettext' );
		parent::tear_down();
	}

	public function test_banner_title_is_the_term_name_and_is_not_translated(): void {
		$term_id = self::factory()->term->create(
			array(
				'taxonomy' => 'hyper_season',
				'name'     => 'Open Sans',
			)
		);
		$this->go_to( get_term_link( $term_id, 'hyper_season' ) );
		add_filter( 'gettext', static fn (): string => 'TRANSLATED' );

		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );

		$this->assertSame( 'season', $banner['type'] );
		$this->assertSame( 'Open Sans', $banner['title'] );
	}
}
