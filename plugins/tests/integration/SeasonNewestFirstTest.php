<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Season\AdminFilter;
use HyperPress\Season\Plugin;
use WP_UnitTestCase;

final class SeasonNewestFirstTest extends WP_UnitTestCase {

	/** @var int[] Term ids of Season 1..12, created in ascending order. */
	private array $ids = array();

	public function set_up(): void {
		parent::set_up();
		foreach ( range( 1, 12 ) as $n ) {
			$this->ids[ $n ] = self::factory()->term->create(
				array(
					'taxonomy' => Plugin::TAXONOMY,
					'name'     => "Season $n",
					'slug'     => "season-$n",
				)
			);
		}
	}

	private function newest_first(): array {
		return array_map( fn( $n ) => "Season $n", range( 12, 1 ) );
	}

	public function test_get_terms_defaults_to_newest_first(): void {
		$names = wp_list_pluck( get_terms( array( 'taxonomy' => Plugin::TAXONOMY, 'hide_empty' => false ) ), 'name' );
		$this->assertSame( $this->newest_first(), $names );
	}

	public function test_a_name_without_a_number_sorts_after_the_numbered_ones(): void {
		self::factory()->term->create( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'Legacy' ) );
		$names = wp_list_pluck( get_terms( array( 'taxonomy' => Plugin::TAXONOMY, 'hide_empty' => false ) ), 'name' );
		$this->assertSame( array_merge( $this->newest_first(), array( 'Legacy' ) ), $names );
	}

	public function test_an_explicit_orderby_is_respected(): void {
		$terms = get_terms(
			array(
				'taxonomy'   => Plugin::TAXONOMY,
				'hide_empty' => false,
				'orderby'    => 'term_id',
				'order'      => 'ASC',
			)
		);
		$this->assertSame( array_values( $this->ids ), wp_list_pluck( $terms, 'term_id' ) );
	}

	public function test_the_admin_filter_dropdown_lists_newest_first(): void {
		set_theme_mod( 'hyperpress_season_post_types', array( 'post' ) );
		Plugin::register();

		ob_start();
		AdminFilter::render( 'post' );
		$html = (string) ob_get_clean();
		remove_theme_mod( 'hyperpress_season_post_types' );
		Plugin::register();

		preg_match_all( '/value="(season-\d+)"/', $html, $m );
		$this->assertSame( array_map( fn( $n ) => "season-$n", range( 12, 1 ) ), $m[1] );
	}

	public function test_the_edit_screen_checklist_is_newest_first(): void {
		require_once ABSPATH . 'wp-admin/includes/template.php';

		ob_start();
		wp_terms_checklist( 0, array( 'taxonomy' => Plugin::TAXONOMY ) );
		$html = (string) ob_get_clean();

		preg_match_all( '/<label class="selectit">.*?> (Season \d+)<\/label>/', $html, $m );
		$this->assertSame( $this->newest_first(), $m[1] );
	}

	public function test_the_newsletter_banner_picks_the_highest_numbered_season(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter', 'post_title' => 'Kickoff' ) );
		$nine    = self::factory()->term->create( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'Season 9', 'slug' => 'season-9-x' ) );
		$thirty  = self::factory()->term->create( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'Season 32', 'slug' => 'season-32' ) );
		wp_set_object_terms( $post_id, array( $nine, $thirty ), Plugin::TAXONOMY );

		$this->go_to( get_permalink( $post_id ) );
		$banner = apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );

		$this->assertSame( 'Season 32: Kickoff', $banner['title'] );
	}
}
