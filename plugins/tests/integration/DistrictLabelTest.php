<?php
/**
 * Tests for the district label.
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
final class DistrictLabelTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Registry::reset();
		Renderer::register();
	}

	public function tear_down(): void {
		unregister_taxonomy_for_object_type( 'hyper_district', 'post' );
		remove_all_filters( 'hyperpress_labels_content' );
		Registry::reset( true );
		parent::tear_down();
	}

	/**
	 * Create a post of the given type with districts and make it the current request.
	 *
	 * @param string   $post_type Post type.
	 * @param string[] $names     District names.
	 */
	private function go_to_post_with_districts( string $post_type, array $names ): void {
		$post_id  = self::factory()->post->create( array( 'post_type' => $post_type ) );
		$term_ids = array();
		foreach ( $names as $name ) {
			$term_ids[] = self::factory()->term->create(
				array(
					'taxonomy' => 'hyper_district',
					'name'     => $name,
				)
			);
		}
		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $post_id, $term_ids, 'hyper_district' );
		}
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( is_singular( $post_type ) );
		the_post();
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function district_labels(): array {
		return array_values(
			array_filter(
				Renderer::labels(),
				static fn ( $label ) => 'District' === $label['title']
			)
		);
	}

	public function test_registration_metadata(): void {
		$definition = Registry::instance()->all()['district.district'];

		$this->assertSame( 'District', $definition['group'] );
		$this->assertSame( 'District', $definition['title'] );
		$this->assertSame( 36, $definition['priority'] );
		$this->assertSame( array( 'hyper_competition' ), $definition['default_post_types'] );
		$this->assertTrue( Resolver::default_enabled( 'hyper_competition' ) );
	}

	public function test_defaults_follow_the_taxonomys_attached_post_types(): void {
		$this->assertNotContains( 'district.district', Resolver::default_labels( 'post' ) );

		register_taxonomy_for_object_type( 'hyper_district', 'post' );
		Registry::reset();

		$this->assertContains( 'district.district', Resolver::default_labels( 'post' ) );
	}

	public function test_district_label_is_unlinked_and_secondary(): void {
		$this->go_to_post_with_districts( 'hyper_competition', array( 'Ontario' ) );

		$district = $this->district_labels();

		$this->assertCount( 1, $district );
		$this->assertSame( 'Ontario', $district[0]['label'] );
		$this->assertSame( array( 'secondary' ), $district[0]['classes'] );
		$this->assertSame( '', $district[0]['url'] );
	}

	public function test_no_district_means_no_label(): void {
		$this->go_to_post_with_districts( 'hyper_competition', array() );

		$this->assertSame( array(), $this->district_labels() );
	}

	public function test_only_one_label_when_a_post_has_two_districts(): void {
		$this->go_to_post_with_districts( 'hyper_competition', array( 'Ontario', 'Michigan' ) );

		$district = $this->district_labels();

		$this->assertCount( 1, $district );
		$this->assertSame( 'Michigan', $district[0]['label'] );
	}

	public function test_label_is_not_shown_on_a_post(): void {
		$this->go_to_post_with_districts( 'post', array( 'Ontario' ) );

		$this->assertSame( array(), $this->district_labels() );
	}
}
