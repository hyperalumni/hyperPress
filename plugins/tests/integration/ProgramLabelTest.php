<?php
/**
 * Tests for the program label.
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
 * @covers \HyperPress\Program\Labels
 */
final class ProgramLabelTest extends WP_UnitTestCase {

	/**
	 * Merge values into the saved label settings option.
	 *
	 * @param string $key   Option array key.
	 * @param mixed  $value Value.
	 */
	private function save( string $key, $value ): void {
		update_option( 'hyperpress_label', array_merge( (array) get_option( 'hyperpress_label', array() ), array( $key => $value ) ) );
	}

	public function set_up(): void {
		parent::set_up();
		register_taxonomy_for_object_type( 'hyper_program', 'post' );
		Registry::reset();
		Renderer::register();
	}

	public function tear_down(): void {
		delete_option( 'hyperpress_label' );
		foreach ( array( 'hyper_program', 'hyper_season' ) as $taxonomy ) {
			unregister_taxonomy_for_object_type( $taxonomy, 'post' );
			unregister_taxonomy_for_object_type( $taxonomy, 'page' );
		}
		// Put the settings-driven attachments back so later tests see the registered defaults.
		\HyperPress\Program\Plugin::register();
		\HyperPress\Season\Plugin::register();
		remove_all_filters( 'hyperpress_labels_content' );
		remove_all_filters( 'term_link' );
		Registry::reset( true );
		parent::tear_down();
	}

	/**
	 * Create a post with the given programs and make it the current request.
	 *
	 * @param string[] $names    Program names.
	 * @param string[] $seasons  Season names.
	 */
	private function go_to_post_with_programs( array $names, array $seasons = array() ): void {
		$post_id  = self::factory()->post->create();
		$term_ids = array();
		foreach ( $names as $name ) {
			$term_ids[] = self::factory()->term->create(
				array(
					'taxonomy' => 'hyper_program',
					'name'     => $name,
				)
			);
		}
		if ( ! empty( $term_ids ) ) {
			wp_set_object_terms( $post_id, $term_ids, 'hyper_program' );
		}
		if ( ! empty( $seasons ) ) {
			wp_set_object_terms( $post_id, $seasons, 'hyper_season' );
		}
		$this->go_to( get_permalink( $post_id ) );
		$this->assertTrue( is_singular( 'post' ) );
		the_post();
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	private function program_labels(): array {
		return array_values(
			array_filter(
				Renderer::labels(),
				static fn ( $label ) => 'Program' === $label['title']
			)
		);
	}

	public function test_registration_metadata(): void {
		Registry::reset();

		$definition = Registry::instance()->all()['program.programs'];

		$this->assertSame( 'Program', $definition['group'] );
		$this->assertSame( 'Program', $definition['title'] );
		$this->assertSame( 35, $definition['priority'] );
		// Holds because set_up attaches the taxonomy to `post`.
		$this->assertSame( array( 'post' ), $definition['default_post_types'] );
	}

	public function test_defaults_follow_the_taxonomys_attached_post_types(): void {
		Registry::reset();

		$this->assertContains( 'program.programs', Resolver::default_labels( 'post' ) );
		$this->assertNotContains( 'program.programs', Resolver::default_labels( 'page' ) );

		register_taxonomy_for_object_type( 'hyper_program', 'page' );
		Registry::reset();

		$this->assertContains( 'program.programs', Resolver::default_labels( 'page' ) );
	}

	public function test_one_label_per_program_sorted_by_name(): void {
		$this->go_to_post_with_programs( array( 'FTC', 'FLL 10', 'FLL 2', 'fll 5' ) );

		$programs = $this->program_labels();

		$this->assertCount( 4, $programs );
		$this->assertSame( array( 'FLL 2', 'fll 5', 'FLL 10', 'FTC' ), array_column( $programs, 'label' ) );
		foreach ( $programs as $program ) {
			$this->assertSame( array( 'alert' ), $program['classes'] );
			$this->assertSame( 'Program', $program['title'] );
		}
		$ftc = get_term_by( 'name', 'FTC', 'hyper_program' );
		$this->assertSame( get_term_link( $ftc ), $programs[3]['url'] );
	}

	public function test_no_programs_means_no_label(): void {
		$this->go_to_post_with_programs( array() );

		$this->assertSame( array(), $this->program_labels() );
	}

	public function test_a_term_link_error_gives_an_empty_url(): void {
		add_filter( 'term_link', static fn () => new WP_Error( 'no_link', 'No link' ) );
		$this->go_to_post_with_programs( array( 'FTC' ) );

		$programs = $this->program_labels();

		$this->assertCount( 1, $programs );
		$this->assertSame( '', $programs[0]['url'] );
	}

	public function test_label_is_hidden_when_labels_are_disabled_for_the_post_type(): void {
		$this->save( Resolver::enabled_key( 'post' ), false );
		$this->go_to_post_with_programs( array( 'FTC' ) );

		$this->assertSame( array(), $this->program_labels() );
	}

	public function test_season_program_and_category_labels_are_in_priority_order(): void {
		register_taxonomy_for_object_type( 'hyper_season', 'post' );
		Registry::reset();
		$this->go_to_post_with_programs( array( 'FTC' ), array( '2026' ) );

		$titles = array_values(
			array_intersect(
				array_column( Renderer::labels(), 'title' ),
				array( 'Posted During', 'Program', 'Posted In' )
			)
		);

		$this->assertSame( array( 'Posted During', 'Program', 'Posted In' ), $titles );
	}
}
