<?php
/**
 * Tests for the week label.
 *
 * @package HyperPress\Tests
 */

namespace HyperPress\Tests\Integration;

use HyperPress\Label\Registry;
use HyperPress\Label\Renderer;
use HyperPress\Label\Resolver;
use WP_UnitTestCase;

/**
 * @covers \HyperPress\Competition\Labels
 */
final class WeekLabelTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Registry::reset();
		Renderer::register();
	}

	public function tear_down(): void {
		unregister_taxonomy_for_object_type( 'hyper_week', 'post' );
		remove_all_filters( 'hyperpress_labels_content' );
		Registry::reset( true );
		parent::tear_down();
	}

	/**
	 * Create a post of the given type with weeks and districts and make it the current request.
	 *
	 * @param string   $post_type Post type.
	 * @param string[] $weeks     Week names.
	 * @param string[] $districts District names.
	 */
	private function go_to_post_with_terms( string $post_type, array $weeks, array $districts = array() ): void {
		$post_id = self::factory()->post->create( array( 'post_type' => $post_type ) );
		foreach ( array(
			'hyper_week'     => $weeks,
			'hyper_district' => $districts,
		) as $taxonomy => $names ) {
			$term_ids = array();
			foreach ( $names as $name ) {
				$term_ids[] = self::factory()->term->create(
					array(
						'taxonomy' => $taxonomy,
						'name'     => $name,
					)
				);
			}
			if ( ! empty( $term_ids ) ) {
				wp_set_object_terms( $post_id, $term_ids, $taxonomy );
			}
		}
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( is_singular( $post_type ) );
		the_post();
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function week_labels(): array {
		return array_values(
			array_filter(
				Renderer::labels(),
				static fn ( $label ) => 'Week' === $label['title']
			)
		);
	}

	public function test_registration_metadata(): void {
		$definition = Registry::instance()->all()['week.week'];

		$this->assertSame( 'Week', $definition['group'] );
		$this->assertSame( 'Week', $definition['title'] );
		$this->assertSame( 37, $definition['priority'] );
		$this->assertSame( array( 'hyper_competition' ), $definition['default_post_types'] );
		$this->assertTrue( Resolver::default_enabled( 'hyper_competition' ) );
	}

	public function test_defaults_follow_the_taxonomys_attached_post_types(): void {
		$this->assertNotContains( 'week.week', Resolver::default_labels( 'post' ) );

		register_taxonomy_for_object_type( 'hyper_week', 'post' );
		Registry::reset();

		$this->assertContains( 'week.week', Resolver::default_labels( 'post' ) );
	}

	public function test_week_label_is_unlinked_and_alert(): void {
		$this->go_to_post_with_terms( 'hyper_competition', array( 'Week 1' ) );

		$week = $this->week_labels();

		$this->assertCount( 1, $week );
		$this->assertSame( 'Week 1', $week[0]['label'] );
		$this->assertSame( 'Week', $week[0]['title'] );
		$this->assertSame( array( 'alert' ), $week[0]['classes'] );
		$this->assertSame( '', $week[0]['url'] );
		$this->assertSame( '', $week[0]['rel'] );
	}

	public function test_no_week_means_no_label(): void {
		$this->go_to_post_with_terms( 'hyper_competition', array() );

		$this->assertSame( array(), $this->week_labels() );
	}

	public function test_only_one_label_when_a_post_has_two_weeks(): void {
		$this->go_to_post_with_terms( 'hyper_competition', array( 'Week 2', 'Week 1' ) );

		$week = $this->week_labels();

		$this->assertCount( 1, $week );
		// First = alphabetical by name (get_the_terms order).
		$this->assertSame( 'Week 1', $week[0]['label'] );
	}

	public function test_label_is_not_shown_on_a_post(): void {
		Registry::reset();
		$this->assertArrayHasKey( 'week.week', Registry::instance()->all() );
		$this->assertNotContains( 'week.week', Resolver::default_labels( 'post' ) );

		$this->go_to_post_with_terms( 'post', array( 'Week 1' ) );

		$this->assertNotContains( 'Week', array_column( Renderer::labels(), 'title' ) );
		$this->assertSame( array(), $this->week_labels() );
	}

	public function test_week_follows_district_in_the_label_order(): void {
		$this->go_to_post_with_terms( 'hyper_competition', array( 'Week 1' ), array( 'Ontario' ) );

		$titles = array_values(
			array_intersect(
				array_column( Renderer::labels(), 'title' ),
				array( 'District', 'Week' )
			)
		);

		$this->assertSame( array( 'District', 'Week' ), $titles );
	}
}
