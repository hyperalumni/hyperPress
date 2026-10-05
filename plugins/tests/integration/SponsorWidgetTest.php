<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Sponsor\Widgets\DisplaySponsorsWidget;
use WP_UnitTestCase;

final class SponsorWidgetTest extends WP_UnitTestCase {

	private string $original_structure = '';

	public function set_up(): void {
		parent::set_up();
		$this->original_structure = '/%postname%/';
		remove_theme_mod( 'hyperpress_season_current' );
	}

	public function tear_down(): void {
		$this->use_permalinks( $this->original_structure );
		remove_theme_mod( 'hyperpress_season_current' );
		parent::tear_down();
	}

	/**
	 * The integration bootstrap forces pretty permalinks through pre_option_permalink_structure,
	 * so a later filter is needed for set_permalink_structure() to take effect.
	 */
	private function use_permalinks( string $structure ): void {
		remove_all_filters( 'pre_option_permalink_structure', 99 );
		add_filter( 'pre_option_permalink_structure', static fn (): string => $structure, 99 );
		$this->set_permalink_structure( $structure );
	}

	private function make_term( string $taxonomy, string $slug ) {
		$ids = wp_insert_term( $slug, $taxonomy, array( 'slug' => $slug ) );
		$this->assertIsArray( $ids );

		return get_term( $ids['term_id'], $taxonomy );
	}

	private function make_sponsor( string $title, string $tier_slug, string $season_slug ): int {
		$id = self::factory()->post->create(
			array(
				'post_type'  => 'hyper_sponsor',
				'post_title' => $title,
			)
		);
		update_post_meta( $id, '_thumbnail_id', self::factory()->attachment->create( array( 'post_mime_type' => 'image/jpeg' ) ) );
		wp_set_object_terms( $id, array( (int) get_term_by( 'slug', $tier_slug, 'hyper_tier' )->term_id ), 'hyper_tier' );
		wp_set_object_terms( $id, array( (int) get_term_by( 'slug', $season_slug, 'hyper_season' )->term_id ), 'hyper_season' );

		return $id;
	}

	private function render( array $instance = array() ): string {
		$instance += array(
			'title'    => 'Our Sponsors',
			'maxCount' => 5,
		);
		$args      = array(
			'before_widget' => '<div class="w">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2>',
			'after_title'   => '</h2>',
		);
		ob_start();
		try {
			( new DisplaySponsorsWidget() )->widget( $args, $instance );
		} finally {
			$output = (string) ob_get_clean();
		}

		return $output;
	}

	private function fixture_with_season( string $season_slug = 'season-a', int $tiers = 1, int $per_tier = 1 ): void {
		$this->make_term( 'hyper_season', $season_slug );
		for ( $t = 1; $t <= $tiers; $t++ ) {
			$this->make_term( 'hyper_tier', "tier-$t" );
			for ( $p = 1; $p <= $per_tier; $p++ ) {
				$this->make_sponsor( "Sponsor $t.$p", "tier-$t", $season_slug );
			}
		}
	}

	public function test_missing_season_term_renders_without_link_or_error(): void {
		$this->fixture_with_season( 'season-real' );
		set_theme_mod( 'hyperpress_season_current', 'season-ghost' );

		$output = $this->render();

		$this->assertStringContainsString( '<div class="w">', $output );
		$this->assertStringNotContainsString( 'See This Season', $output );
		$this->assertStringNotContainsString( 'WP_Error', $output );
	}

	public function test_see_all_link_is_escaped_and_pretty_with_pretty_permalinks(): void {
		$this->use_permalinks( '/%postname%/' );
		$this->fixture_with_season( 'season-a' );
		set_theme_mod( 'hyperpress_season_current', 'season-a' );
		$term = get_term_by( 'slug', 'season-a', 'hyper_season' );

		$output   = $this->render();
		$expected = esc_url( trailingslashit( get_term_link( $term ) ) . 'sponsors' );

		$this->assertStringContainsString( 'href="' . $expected . '"', $output );
		$this->assertStringNotContainsString( '?', $expected );
	}

	public function test_see_all_link_uses_post_type_query_arg_with_plain_permalinks(): void {
		$this->use_permalinks( '' );
		$this->fixture_with_season( 'season-a' );
		set_theme_mod( 'hyperpress_season_current', 'season-a' );
		$term = get_term_by( 'slug', 'season-a', 'hyper_season' );

		$output   = $this->render();
		$expected = esc_url( add_query_arg( 'post_type', 'hyper_sponsor', get_term_link( $term ) ) );

		$this->assertStringContainsString( 'href="' . $expected . '"', $output );
		$this->assertStringNotContainsString( 'sponsors"', $output );
	}

	public function test_number_of_sponsors_never_exceeds_max_count_across_tiers(): void {
		$this->fixture_with_season( 'season-a', 3, 2 );
		set_theme_mod( 'hyperpress_season_current', 'season-a' );

		$output = $this->render( array( 'maxCount' => 3 ) );

		$this->assertSame( 3, substr_count( $output, '<div class="cell">' ) );
	}

	public function test_global_post_is_restored_after_rendering(): void {
		$this->fixture_with_season( 'season-a' );
		set_theme_mod( 'hyperpress_season_current', 'season-a' );
		$host = self::factory()->post->create();
		$this->go_to( get_permalink( $host ) );

		$output = $this->render();

		$this->assertStringContainsString( '<div class="cell">', $output );
		$this->assertSame( $host, get_post()->ID );
	}

	public function test_update_tolerates_missing_keys_and_clamps_max_count(): void {
		$widget = new DisplaySponsorsWidget();

		$empty = $widget->update( array(), array() );
		$this->assertSame( 5, $empty['maxCount'] );

		$this->assertSame( 1, $widget->update( array( 'maxCount' => '-5' ), array() )['maxCount'] );
		$this->assertSame( 1, $widget->update( array( 'maxCount' => '0' ), array() )['maxCount'] );
		$this->assertSame( 5, $widget->update( array( 'maxCount' => 'abc' ), array() )['maxCount'] );
		$this->assertSame( 100, $widget->update( array( 'maxCount' => '1e9' ), array() )['maxCount'] );
		$this->assertSame( 7, $widget->update( array( 'maxCount' => '7' ), array() )['maxCount'] );
	}

	public function test_render_tolerates_missing_instance_keys(): void {
		$this->fixture_with_season( 'season-a' );
		set_theme_mod( 'hyperpress_season_current', 'season-a' );
		$args = array(
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		);

		ob_start();
		try {
			( new DisplaySponsorsWidget() )->widget( $args, array() );
		} finally {
			$output = (string) ob_get_clean();
		}

		$this->assertStringContainsString( '<div class="cell">', $output );
	}

	public function test_sponsor_title_cannot_break_out_of_the_title_attribute(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->make_term( 'hyper_season', 'season-a' );
		$this->make_term( 'hyper_tier', 'tier-1' );
		$this->make_sponsor( '"><script>alert(1)</script>', 'tier-1', 'season-a' );
		set_theme_mod( 'hyperpress_season_current', 'season-a' );

		$output = $this->render();

		$this->assertStringContainsString( '<div class="cell">', $output );
		$this->assertStringNotContainsString( '<script', $output );
		$this->assertStringNotContainsString( '"><script', $output );
	}

	public function test_season_label_links_to_the_season_term_not_a_category(): void {
		$season = $this->make_term( 'hyper_season', 'season-a' );
		$this->make_term( 'hyper_tier', 'tier-1' );
		$id = $this->make_sponsor( 'Sponsor', 'tier-1', 'season-a' );
		$this->go_to( get_permalink( $id ) );
		$GLOBALS['post'] = get_post( $id );

		$labels = apply_filters( 'hyperpress_labels_content', array() );
		$urls   = wp_list_pluck( $labels, 'url', 'title' );

		$this->assertArrayHasKey( 'Last Sponsored', $urls );
		$this->assertSame( get_term_link( $season ), $urls['Last Sponsored'] );
		$this->assertStringNotContainsString( '/category/', $urls['Last Sponsored'] );
	}

	public function test_update_strips_script_tags_and_their_contents(): void {
		$updated = ( new DisplaySponsorsWidget() )->update( array( 'title' => '<script>alert(1)</script>Hi' ), array() );

		$this->assertSame( 'Hi', $updated['title'] );
	}

	public function test_form_uses_the_default_title_for_an_empty_instance(): void {
		ob_start();
		try {
			( new DisplaySponsorsWidget() )->form( array() );
		} finally {
			$output = (string) ob_get_clean();
		}

		$this->assertStringContainsString( 'value="Our Sponsors"', $output );
	}
}
