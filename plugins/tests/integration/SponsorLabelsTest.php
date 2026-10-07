<?php
/**
 * Tests for the sponsor labels.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Label\Registry;
use HyperPress\Label\Renderer;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Sponsor\Labels
 */
final class SponsorLabelsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		unset( $GLOBALS['hyperpress_test_rwmb'] );
		Registry::reset();
		Renderer::register();
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb'] );
		remove_all_filters( 'hyperpress_labels_content' );
		Registry::reset( true );
		parent::tear_down();
	}

	/**
	 * Create a post of the given type with seasons and make it the current request.
	 *
	 * @param string   $post_type Post type.
	 * @param string[] $names     Season names.
	 */
	private function go_to_post_with_seasons( string $post_type, array $names ): void {
		$post_id  = self::factory()->post->create( array( 'post_type' => $post_type ) );
		$term_ids = array();
		foreach ( $names as $name ) {
			$term_ids[] = self::factory()->term->create(
				array(
					'taxonomy' => 'hyper_season',
					'name'     => $name,
				)
			);
		}
		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $post_id, $term_ids, 'hyper_season' );
		}
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( is_singular( $post_type ) );
		the_post();
	}

	private function set_tier( string $name, string $description = '' ): void {
		$GLOBALS['hyperpress_test_rwmb']['hyperpress_sponsor_tier'] = (object) array(
			'name'        => $name,
			'description' => $description,
		);
	}

	public function test_labels_are_registered_with_the_registry(): void {
		$registry = Registry::instance();

		$this->assertSame(
			array( 'sponsor.last_sponsored', 'sponsor.tier', 'sponsor.season_count' ),
			array_values( array_filter( $registry->ids(), static fn ( $id ) => 0 === strpos( $id, 'sponsor.' ) ) )
		);
		$this->assertSame( array( 'hyper_sponsor' ), $registry->default_post_types( 'sponsor.tier' ) );
		$this->assertFalse( has_filter( 'hyperpress_labels_content', array( \HyperPress\Sponsor\Labels::class, 'labels_sponsor' ) ) );
	}

	public function test_three_labels_in_order(): void {
		$this->set_tier( 'Gold' );
		$this->go_to_post_with_seasons( 'hyper_sponsor', array( '2025', '2026' ) );

		$labels = Renderer::labels();

		$this->assertCount( 3, $labels );
		$this->assertSame( '2026', $labels[0]['label'] );
		$this->assertSame( 'Last Sponsored', $labels[0]['title'] );
		$this->assertSame( array( 'warning' ), $labels[0]['classes'] );
		$this->assertSame( 'Gold', $labels[1]['label'] );
		$this->assertSame( array( 'info' ), $labels[1]['classes'] );
		$this->assertSame( '2 Seasons', $labels[2]['label'] );
		$this->assertSame( 'Sponsored For', $labels[2]['title'] );
		$this->assertSame( array( 'primary' ), $labels[2]['classes'] );
	}

	public function test_season_count_singular(): void {
		$this->go_to_post_with_seasons( 'hyper_sponsor', array( '2026' ) );

		$labels = Renderer::labels();

		$this->assertSame( '1 Season', end( $labels )['label'] );
	}

	public function test_no_seasons_and_no_tier_means_no_labels(): void {
		$this->go_to_post_with_seasons( 'hyper_sponsor', array() );

		$this->assertSame( array(), Renderer::labels() );
	}

	public function test_tier_title_includes_the_description(): void {
		$this->set_tier( 'Gold', 'Top level' );
		$this->go_to_post_with_seasons( 'hyper_sponsor', array() );

		$labels = Renderer::labels();

		$this->assertCount( 1, $labels );
		$this->assertSame( 'Sponsorship Tier: Top level', $labels[0]['title'] );
	}

	public function test_sponsor_labels_do_not_show_on_posts(): void {
		$this->set_tier( 'Gold' );
		$this->go_to_post_with_seasons( 'post', array( '2026' ) );

		$titles = array_column( Renderer::labels(), 'title' );

		$this->assertNotContains( 'Last Sponsored', $titles );
		$this->assertNotContains( 'Sponsored For', $titles );
		$this->assertNotContains( 'Sponsorship Tier', $titles );
	}
}
