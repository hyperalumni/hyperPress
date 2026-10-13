<?php
namespace HyperPress\Tests\Integration\Season;

use HyperPress\Season\Sanitizers;
use WP_UnitTestCase;

final class SeasonSettingsPageTest extends WP_UnitTestCase {

	public function tear_down(): void {
		unregister_post_type( 'hp_private_ui' );
		unregister_post_type( 'hp_no_ui' );
		if ( ! taxonomy_exists( 'hyper_season' ) ) {
			\HyperPress\Season\Plugin::register();
		}
		parent::tear_down();
	}

	/**
	 * The Season boxes from the Meta Box filter.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function boxes(): array {
		return array_values(
			array_filter(
				apply_filters( 'rwmb_meta_boxes', array() ),
				static fn( $box ) => 'hyperpress_season_box' === ( $box['id'] ?? '' )
			)
		);
	}

	/**
	 * Fields of the Season box keyed by id, in order.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	private function fields(): array {
		$fields = array();
		foreach ( $this->boxes()[0]['fields'] as $field ) {
			$fields[ $field['id'] ] = $field;
		}
		return $fields;
	}

	public function test_the_settings_page_is_registered_under_the_hyperpress_menu(): void {
		$pages = array_values(
			array_filter(
				apply_filters( 'mb_settings_pages', array() ),
				static fn( $page ) => 'hyperpress_season' === ( $page['id'] ?? '' )
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_season', $pages[0]['option_name'] );
		$this->assertSame( 'Season', $pages[0]['menu_title'] );
		$this->assertSame( 'Season', $pages[0]['page_title'] );
		$this->assertSame( 'hyperpress', $pages[0]['parent'] );
		$this->assertSame( 'manage_options', $pages[0]['capability'] );
		$this->assertSame( 'no-boxes', $pages[0]['style'] );
	}

	public function test_one_box_is_attached_to_the_settings_page(): void {
		$boxes = $this->boxes();

		$this->assertCount( 1, $boxes );
		$this->assertSame( 'hyperpress_season', $boxes[0]['settings_pages'] );
	}

	public function test_the_box_has_the_three_fields_in_order_with_their_types(): void {
		$this->assertSame(
			array(
				'hyperpress_season_current'         => 'select_advanced',
				'hyperpress_season_banner_subtitle' => 'textarea',
				'hyperpress_season_post_types'      => 'select_advanced',
			),
			array_map( static fn( $field ) => $field['type'], $this->fields() )
		);
	}

	public function test_multiple_flags_raw_and_sanitize_callbacks(): void {
		$fields = $this->fields();

		$this->assertSame( array( Sanitizers::class, 'current' ), $fields['hyperpress_season_current']['sanitize_callback'] );
		$this->assertArrayNotHasKey( 'multiple', $fields['hyperpress_season_current'] );
		$this->assertTrue( $fields['hyperpress_season_banner_subtitle']['raw'] );
		$this->assertSame( array( Sanitizers::class, 'banner_subtitle' ), $fields['hyperpress_season_banner_subtitle']['sanitize_callback'] );
		$this->assertTrue( $fields['hyperpress_season_post_types']['multiple'] );
		$this->assertSame( array( 'post' ), $fields['hyperpress_season_post_types']['std'] );
		$this->assertSame( array( Sanitizers::class, 'post_types' ), $fields['hyperpress_season_post_types']['sanitize_callback'] );
	}

	public function test_the_current_season_options_list_seasons_as_slug_to_name(): void {
		self::factory()->term->create(
			array(
				'taxonomy' => 'hyper_season',
				'name'     => 'Season 2026',
				'slug'     => 'season-2026',
			)
		);

		$options = $this->fields()['hyperpress_season_current']['options'];

		$this->assertSame( 'None', $options[''] );
		$this->assertSame( 'Season 2026', $options['season-2026'] );
	}

	public function test_the_current_season_options_list_seasons_newest_first_after_none(): void {
		foreach ( array( 'Season 2', 'Season 10', 'Season 1' ) as $name ) {
			self::factory()->term->create(
				array(
					'taxonomy' => 'hyper_season',
					'name'     => $name,
					'slug'     => sanitize_title( $name ),
				)
			);
		}

		$keys = array_keys( $this->fields()['hyperpress_season_current']['options'] );

		$this->assertSame( '', $keys[0] );
		$this->assertSame(
			array( 'season-10', 'season-2', 'season-1' ),
			array_values( array_intersect( $keys, array( 'season-10', 'season-2', 'season-1' ) ) )
		);
	}

	public function test_the_current_season_options_are_only_none_before_any_season_exists(): void {
		$terms = get_terms(
			array(
				'taxonomy'   => 'hyper_season',
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);
		foreach ( $terms as $term_id ) {
			wp_delete_term( $term_id, 'hyper_season' );
		}

		$this->assertSame( array( '' => 'None' ), $this->fields()['hyperpress_season_current']['options'] );
	}

	public function test_the_current_season_options_are_only_none_when_the_taxonomy_is_unregistered(): void {
		unregister_taxonomy( 'hyper_season' );

		$this->assertSame( array( '' => 'None' ), $this->fields()['hyperpress_season_current']['options'] );
	}

	public function test_the_post_type_options_list_types_with_a_ui(): void {
		register_post_type(
			'hp_private_ui',
			array(
				'public'  => false,
				'show_ui' => true,
				'label'   => 'Private UI',
			)
		);
		register_post_type(
			'hp_no_ui',
			array(
				'public'  => false,
				'show_ui' => false,
			)
		);

		$options = $this->fields()['hyperpress_season_post_types']['options'];

		$this->assertArrayHasKey( 'post', $options );
		$this->assertSame( 'Private UI', $options['hp_private_ui'] );
		$this->assertArrayNotHasKey( 'hp_no_ui', $options );
	}
}
