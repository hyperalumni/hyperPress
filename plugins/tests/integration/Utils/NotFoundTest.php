<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Competition\Plugin;
use HyperPress\Utils\NotFound;
use WP_Query;
use WP_UnitTestCase;

final class NotFoundTest extends WP_UnitTestCase {

	/**
	 * Taxonomies this test registered itself, removed again in tear_down().
	 *
	 * @var string[]
	 */
	private array $registered_taxonomies = array();

	public function set_up(): void {
		parent::set_up();
		register_taxonomy( 'rt_nf_tax', array( 'post' ), array( 'public' => true ) );
		register_taxonomy( 'rt_nf_noqv', array( 'post' ), array( 'public' => true, 'query_var' => false ) );
		$this->registered_taxonomies = array( 'rt_nf_tax', 'rt_nf_noqv' );
	}

	public function tear_down(): void {
		foreach ( $this->registered_taxonomies as $taxonomy ) {
			unregister_taxonomy( $taxonomy );
		}
		$this->registered_taxonomies = array();
		parent::tear_down();
	}

	/**
	 * Find or create a term. The plugins seed their terms outside the test transaction.
	 */
	private function term( string $name, string $slug, string $taxonomy ): \WP_Term {
		$term = get_term_by( 'slug', $slug, $taxonomy );
		if ( ! $term ) {
			$term = self::factory()->term->create_and_get( array( 'taxonomy' => $taxonomy, 'name' => $name, 'slug' => $slug ) );
		}
		return $term;
	}

	/**
	 * The callback register() last added to pre_handle_404, so it can be called directly.
	 */
	private function registered_callback( array $taxonomies ): callable {
		NotFound::register( $taxonomies );
		$callbacks = $GLOBALS['wp_filter']['pre_handle_404']->callbacks[10];
		return end( $callbacks )['function'];
	}

	public function test_register_hooks_pre_handle_404_with_two_args(): void {
		NotFound::register( array( 'rt_nf_tax' ) );

		$this->assertNotFalse( has_filter( 'pre_handle_404' ) );
		$callbacks = $GLOBALS['wp_filter']['pre_handle_404']->callbacks[10];
		$this->assertSame( 2, end( $callbacks )['accepted_args'] );
	}

	public function test_unknown_term_in_a_taxonomy_query_var_is_a_404(): void {
		NotFound::register( array( 'rt_nf_tax' ) );

		$this->go_to( '/?rt_nf_tax=rt-nope' );

		$this->assertTrue( is_404() );
	}

	public function test_known_term_is_not_a_404(): void {
		$this->term( 'Known', 'rt-known', 'rt_nf_tax' );
		NotFound::register( array( 'rt_nf_tax' ) );

		$this->go_to( '/?rt_nf_tax=rt-known' );

		$this->assertFalse( is_404() );
	}

	public function test_request_without_the_query_var_is_untouched(): void {
		NotFound::register( array( 'rt_nf_tax' ) );

		$this->go_to( '/' );

		$this->assertFalse( is_404() );
		$this->assertFalse( NotFound::has_unknown_term( array( 'rt_nf_tax' ) ) );
	}

	public function test_unknown_competition_type_query_is_a_404(): void {
		$this->go_to( '/?' . Plugin::TYPE . '=rt-nope' );

		$this->assertTrue( is_404() );
	}

	public function test_unknown_type_on_the_competition_archive_is_a_404(): void {
		$season = $this->term( 'Season 31', 'season-31', 'hyper_season' );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=rt-nope' );

		$this->assertTrue( is_404() );
	}

	public function test_unknown_district_and_week_are_a_404(): void {
		$this->go_to( '/?' . Plugin::DISTRICT . '=rt-nope' );
		$this->assertTrue( is_404() );

		$this->go_to( '/?' . Plugin::WEEK . '=rt-nope' );
		$this->assertTrue( is_404() );
	}

	public function test_unknown_season_on_the_competition_archive_is_a_404(): void {
		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=rt-no-season' );

		$this->assertTrue( is_404() );
	}

	public function test_valid_season_and_type_is_not_a_404(): void {
		$season = $this->term( 'Season 31', 'season-31', 'hyper_season' );
		$type   = $this->term( 'District', 'district', Plugin::TYPE );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&hyper_season=' . $season->slug . '&' . Plugin::TYPE . '=' . $type->slug );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertFalse( is_404() );
	}

	public function test_a_list_with_one_known_slug_is_not_unknown(): void {
		$type    = $this->term( 'District', 'district', Plugin::TYPE );
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE ) );
		wp_set_object_terms( $post_id, array( $type->term_id ), Plugin::TYPE );

		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&' . Plugin::TYPE . '=rt-nope,' . $type->slug );

		$this->assertFalse( NotFound::has_unknown_term( array( Plugin::TYPE ) ) );
		$this->assertFalse( is_404() );
	}

	public function test_array_valued_query_var_is_not_unknown_and_raises_no_warning(): void {
		$this->term( 'District', 'district', Plugin::TYPE );

		// Core itself cannot parse an array in the URL, so set it on the query.
		$this->go_to( '/' );
		set_query_var( Plugin::TYPE, array( 'rt-nope' ) );

		$this->assertFalse( NotFound::has_unknown_term( array( Plugin::TYPE ) ) );

		set_query_var( 'rt_nf_tax', array( 'rt-nope' ) );
		$this->assertFalse( NotFound::has_unknown_term( array( 'rt_nf_tax' ) ) );
	}

	public function test_unregistered_taxonomy_and_taxonomy_without_query_var_are_ignored(): void {
		$this->go_to( '/' );
		set_query_var( 'rt_nf_missing', 'rt-nope' );
		set_query_var( 'rt_nf_noqv', 'rt-nope' );

		$this->assertFalse( NotFound::has_unknown_term( array( 'rt_nf_missing', 'rt_nf_noqv' ) ) );
		$this->assertFalse( NotFound::has_unknown_term( array( 'rt_nf_missing', 'rt_nf_noqv', 'rt_nf_tax' ) ) );

		set_query_var( 'rt_nf_tax', 'rt-nope' );
		$this->assertTrue( NotFound::has_unknown_term( array( 'rt_nf_missing', 'rt_nf_noqv', 'rt_nf_tax' ) ) );
	}

	public function test_callback_ignores_a_non_main_query(): void {
		$callback = $this->registered_callback( array( 'rt_nf_tax' ) );
		$this->go_to( '/' );
		set_query_var( 'rt_nf_tax', 'rt-nope' );

		$secondary = new WP_Query();
		$this->assertFalse( $callback( false, $secondary ) );
		$this->assertFalse( $secondary->is_404() );

		$this->assertTrue( $callback( false, $GLOBALS['wp_query'] ) );
		$this->assertTrue( $GLOBALS['wp_query']->is_404() );
	}

	public function test_callback_keeps_an_already_preempted_result(): void {
		$callback = $this->registered_callback( array( 'rt_nf_tax' ) );
		$this->go_to( '/' );

		$this->assertTrue( $callback( true, $GLOBALS['wp_query'] ) );
		$this->assertFalse( $GLOBALS['wp_query']->is_404() );
		$this->assertFalse( $callback( false, $GLOBALS['wp_query'] ) );
	}
}
