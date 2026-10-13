<?php
/**
 * Tests for the season label.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Label\Registry;
use HyperPress\Label\Renderer;
use HyperPress\Label\Resolver;
use WP_Error;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Season\Labels
 */
final class SeasonLabelTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		register_taxonomy_for_object_type( 'hyper_season', 'post' );
		Registry::reset();
		Renderer::register();
	}

	public function tear_down(): void {
		delete_option( 'hyperpress_label' );
		unregister_taxonomy_for_object_type( 'hyper_season', 'post' );
		unregister_taxonomy_for_object_type( 'hyper_season', 'page' );
		remove_all_filters( 'hyperpress_labels_content' );
		remove_all_filters( 'term_link' );
		Registry::reset( true );
		parent::tear_down();
	}

	/**
	 * Create a post with the given seasons and make it the current request.
	 *
	 * @param string[] $names Season names.
	 * @return int[] Term IDs.
	 */
	private function go_to_post_with_seasons( array $names ): array {
		$post_id  = self::factory()->post->create();
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
		$this->assertTrue( is_singular( 'post' ) );
		the_post();
		return $term_ids;
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function season_labels(): array {
		return array_values(
			array_filter(
				Renderer::labels(),
				static fn ( $label ) => 'Posted During' === $label['title']
			)
		);
	}

	public function test_registration_metadata(): void {
		Registry::reset();

		$definition = Registry::instance()->all()['season.posted_during'];

		$this->assertSame( 'Season', $definition['group'] );
		$this->assertSame( 'Posted During', $definition['title'] );
		$this->assertSame( 30, $definition['priority'] );
		// Holds because set_up attaches the taxonomy to `post`.
		$this->assertSame( array( 'post' ), $definition['default_post_types'] );
	}

	public function test_defaults_follow_the_taxonomys_attached_post_types(): void {
		register_taxonomy_for_object_type( 'hyper_season', 'page' );
		Registry::reset();

		$definition = Registry::instance()->all()['season.posted_during'];

		$this->assertEqualsCanonicalizing( array( 'post', 'page' ), $definition['default_post_types'] );
		$this->assertTrue( Resolver::default_enabled( 'page' ) );
		$this->assertContains( 'season.posted_during', Resolver::default_labels( 'page' ) );
	}

	public function test_unattached_post_types_do_not_get_the_label(): void {
		Registry::reset();

		$this->assertFalse( Resolver::default_enabled( 'page' ) );
		$this->assertNotContains( 'season.posted_during', Resolver::default_labels( 'page' ) );
	}

	public function test_season_label_links_to_the_term(): void {
		list( $term_id ) = $this->go_to_post_with_seasons( array( '2026' ) );

		$season = $this->season_labels();

		$this->assertCount( 1, $season );
		$this->assertSame( '2026', $season[0]['label'] );
		$this->assertSame( array( 'warning' ), $season[0]['classes'] );
		$this->assertSame( get_term_link( get_term( $term_id, 'hyper_season' ) ), $season[0]['url'] );
	}

	public function test_no_seasons_means_no_label(): void {
		$this->go_to_post_with_seasons( array() );

		$this->assertSame( array(), $this->season_labels() );
	}

	public function test_newest_season_wins(): void {
		$this->go_to_post_with_seasons( array( '2025', '2026' ) );

		$season = $this->season_labels();

		$this->assertCount( 1, $season );
		$this->assertSame( '2026', $season[0]['label'] );
	}

	public function test_a_term_link_error_gives_an_empty_url(): void {
		add_filter( 'term_link', static fn () => new WP_Error( 'no_link', 'No link' ) );
		$this->go_to_post_with_seasons( array( '2026' ) );

		$season = $this->season_labels();

		$this->assertCount( 1, $season );
		$this->assertSame( '', $season[0]['url'] );
	}
}
