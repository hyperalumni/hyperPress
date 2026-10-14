<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

/**
 * The "Season: " title prefix in the newsletter loop template is hidden on a season archive, shown elsewhere.
 */
final class NewsletterSeasonPrefixTest extends WP_UnitTestCase {

	private const OTHER_TAXONOMY = 'hyper_test_other';

	private bool $registered_other = false;

	public function set_up(): void {
		parent::set_up();
		if ( ! taxonomy_exists( self::OTHER_TAXONOMY ) ) {
			register_taxonomy(
				self::OTHER_TAXONOMY,
				'hyper_newsletter',
				array(
					'public'    => true,
					'query_var' => true,
					'rewrite'   => false,
				)
			);
			$this->registered_other = true;
		}
	}

	public function tear_down(): void {
		if ( isset( $GLOBALS['wp_query'] ) ) {
			$GLOBALS['wp_query']->queried_object    = null;
			$GLOBALS['wp_query']->queried_object_id = 0;
		}
		if ( $this->registered_other ) {
			unregister_taxonomy( self::OTHER_TAXONOMY );
			$this->registered_other = false;
		}
		parent::tear_down();
	}

	/**
	 * Create a newsletter in a season (and optionally a second taxonomy's term).
	 *
	 * @return array{0:int,1:\WP_Term,2:?\WP_Term}
	 */
	private function newsletter( bool $with_other ): array {
		$post_id = self::factory()->post->create( array( 'post_type' => 'hyper_newsletter' ) );
		$season  = self::factory()->term->create_and_get(
			array(
				'taxonomy' => 'hyper_season',
				'name'     => 'Season 99',
				'slug'     => 'season-99',
			)
		);
		wp_set_object_terms( $post_id, array( $season->term_id ), 'hyper_season' );
		$other = null;
		if ( $with_other ) {
			$other = self::factory()->term->create_and_get(
				array(
					'taxonomy' => self::OTHER_TAXONOMY,
					'name'     => 'Other',
					'slug'     => 'other',
				)
			);
			wp_set_object_terms( $post_id, array( $other->term_id ), self::OTHER_TAXONOMY );
		}

		return array( $post_id, $season, $other );
	}

	private function render(): string {
		$this->assertTrue( have_posts() );
		the_post();
		ob_start();
		include dirname( __DIR__, 2 ) . '/hyperpress-newsletter/templates/content.php';

		return (string) ob_get_clean();
	}

	public function test_prefix_is_hidden_on_a_season_archive(): void {
		list( , $season ) = $this->newsletter( false );

		$this->go_to( add_query_arg( array( 'post_type' => 'hyper_newsletter', 'hyper_season' => $season->slug ), home_url( '/' ) ) );
		$html = $this->render();

		$this->assertStringNotContainsString( 'Season 99: ', $html );
	}

	public function test_prefix_is_shown_on_the_newsletter_post_type_archive(): void {
		$this->newsletter( false );

		$this->go_to( get_post_type_archive_link( 'hyper_newsletter' ) );
		$html = $this->render();

		$this->assertStringContainsString( 'Season 99: ', $html );
	}

	public function test_prefix_is_hidden_when_another_taxonomys_term_is_the_queried_object(): void {
		list( , $season, $other ) = $this->newsletter( true );

		$this->go_to( add_query_arg( array( 'post_type' => 'hyper_newsletter', 'hyper_season' => $season->slug, self::OTHER_TAXONOMY => $other->slug ), home_url( '/' ) ) );
		// With two taxonomies in the request, core may hand back either term.
		$GLOBALS['wp_query']->queried_object    = $other;
		$GLOBALS['wp_query']->queried_object_id = $other->term_id;
		$html                                   = $this->render();

		$this->assertStringNotContainsString( 'Season 99: ', $html );
	}
}
