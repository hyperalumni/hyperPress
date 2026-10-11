<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Season\AdminFilter;
use HyperPress\Season\Plugin;
use WP_Query;
use WP_UnitTestCase;

final class SeasonAdminFilterTest extends WP_UnitTestCase {

	private function render( string $post_type ): string {
		ob_start();
		AdminFilter::render( $post_type );
		return (string) ob_get_clean();
	}

	private function attach_countdown(): void {
		set_theme_mod( 'hyperpress_season_post_types', array( 'post', 'hyper_countdown' ) );
		Plugin::register();
	}

	public function tear_down(): void {
		remove_theme_mod( 'hyperpress_season_post_types' );
		Plugin::register();
		parent::tear_down();
	}

	public function test_the_taxonomy_shows_an_admin_column(): void {
		$this->assertTrue( get_taxonomy( Plugin::TAXONOMY )->show_admin_column );
	}

	public function test_it_is_registered_on_restrict_manage_posts(): void {
		$this->assertNotFalse( has_action( 'restrict_manage_posts', array( AdminFilter::class, 'render' ) ) );
	}

	public function test_nothing_is_printed_when_there_are_no_terms(): void {
		$this->assertSame( '', $this->render( 'post' ) );
	}

	public function test_the_dropdown_lists_seasons_and_marks_the_selected_one(): void {
		self::factory()->term->create( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'Season 2026', 'slug' => 'season-2026' ) );
		self::factory()->term->create( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'Season 2025', 'slug' => 'season-2025' ) );
		$this->attach_countdown();
		$_GET['hyper_season'] = 'season-2025';

		$html = $this->render( 'post' );
		unset( $_GET['hyper_season'] );

		$this->assertStringContainsString( 'name=\'hyper_season\'', $html );
		$this->assertStringContainsString( 'All seasons', $html );
		$this->assertStringContainsString( 'value="season-2026"', $html );
		$this->assertMatchesRegularExpression( '/value="season-2025"\s+selected="selected"/', $html );
	}

	public function test_nothing_is_printed_for_post_types_without_the_taxonomy(): void {
		$this->attach_countdown();
		self::factory()->term->create( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'Season 2026' ) );

		$this->assertSame( '', $this->render( 'page' ) );
		$this->assertSame( '', $this->render( 'attachment' ) );
	}

	public function test_a_non_public_countdown_gets_the_dropdown_and_the_native_query_filter(): void {
		$this->attach_countdown();
		$term = self::factory()->term->create_and_get( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'Season 2026', 'slug' => 'season-2026' ) );
		$in   = self::factory()->post->create( array( 'post_type' => 'hyper_countdown', 'post_status' => 'publish' ) );
		$out  = self::factory()->post->create( array( 'post_type' => 'hyper_countdown', 'post_status' => 'publish' ) );
		wp_set_object_terms( $in, array( $term->term_id ), Plugin::TAXONOMY );

		$this->assertStringContainsString( 'value="season-2026"', $this->render( 'hyper_countdown' ) );

		$query = new WP_Query(
			array(
				'post_type'   => 'hyper_countdown',
				'hyper_season' => 'season-2026',
				'fields'      => 'ids',
			)
		);
		$this->assertSame( array( $in ), $query->posts );
		$this->assertNotContains( $out, $query->posts );
	}

	public function test_registration_does_not_depend_on_the_post_type_being_registered_first(): void {
		unregister_post_type( 'hyper_countdown' );
		set_theme_mod( 'hyperpress_season_post_types', array( 'post', 'hyper_countdown' ) );
		Plugin::register();
		$this->assertFalse( post_type_exists( 'hyper_countdown' ) );

		register_post_type( 'hyper_countdown', array( 'show_ui' => true ) );

		$this->assertTrue( is_object_in_taxonomy( 'hyper_countdown', Plugin::TAXONOMY ) );
	}
}
