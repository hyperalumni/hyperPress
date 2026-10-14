<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Plugin;
use WP_UnitTestCase;

final class SingularBannerTest extends WP_UnitTestCase {

	/**
	 * The filter callbacks registered before this test, restored afterwards.
	 *
	 * @var mixed
	 */
	private $saved_filters;

	/**
	 * Taxonomies set_up() registered itself, removed again in tear_down().
	 *
	 * @var string[]
	 */
	private array $registered_taxonomies = array();

	/**
	 * Post types set_up() registered itself, removed again in tear_down().
	 *
	 * @var string[]
	 */
	private array $registered_post_types = array();

	public function set_up(): void {
		parent::set_up();
		global $wp_filter;
		$this->saved_filters = $wp_filter['hyperpress_banner_content'] ?? null;
		remove_all_filters( 'hyperpress_banner_content' );

		if ( ! taxonomy_exists( 'hyper_season' ) ) {
			register_taxonomy( 'hyper_season', array( Plugin::POST_TYPE ), array( 'public' => true, 'rewrite' => array( 'slug' => 'seasons' ) ) );
			$this->registered_taxonomies[] = 'hyper_season';
		}
		if ( ! post_type_exists( Plugin::POST_TYPE ) ) {
			register_post_type( Plugin::POST_TYPE, array( 'public' => true, 'has_archive' => true ) );
			$this->registered_post_types[] = Plugin::POST_TYPE;
		}
		set_theme_mod( 'hyperpress_hyper_green', '#0f0' );

		// Stand-in for the theme's `hyperpress_banner_archive`, which runs after the plugin's banner filter, at
		// priority 90 (themes/hyperpress-foundation/library/banner/archive.php).
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) {
				if ( empty( $banner['type'] ) && is_archive() ) {
					$banner['type']  = 'archive';
					$banner['title'] = 'Archive';
				}
				return $banner;
			},
			90
		);
		Plugin::register_banner();
	}

	public function tear_down(): void {
		global $wp_filter;
		foreach ( $this->registered_post_types as $post_type ) {
			unregister_post_type( $post_type );
		}
		foreach ( $this->registered_taxonomies as $taxonomy ) {
			unregister_taxonomy( $taxonomy );
		}
		$this->registered_post_types = array();
		$this->registered_taxonomies = array();
		remove_all_filters( 'hyperpress_banner_content' );
		if ( null !== $this->saved_filters ) {
			$wp_filter['hyperpress_banner_content'] = $this->saved_filters;
		}
		unset( $GLOBALS['hyperpress_test_rwmb'] );
		parent::tear_down();
	}

	/**
	 * Find or create a term. The season plugin seeds its terms outside the test transaction.
	 */
	private function season(): \WP_Term {
		$term = get_term_by( 'slug', 'season-31', 'hyper_season' );
		if ( ! $term ) {
			$term = self::factory()->term->create_and_get( array( 'taxonomy' => 'hyper_season', 'name' => 'Season 31', 'slug' => 'season-31' ) );
		}
		return $term;
	}

	/**
	 * Stand-in for the theme's singular banner filters (library/banner/post.php and page.php), which run before the
	 * plugin's banner filter and only act on `post` and pages: they leave the title of a competition empty.
	 */
	private function singular_banner_for( int $post_id ): array {
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) {
				if ( is_singular( 'post' ) ) {
					$banner['type']  = 'post';
					$banner['title'] = 'Blog';
				}
				if ( is_page() ) {
					$banner['type'] = 'page';
				}
				return $banner;
			},
			5
		);
		$this->go_to( '/?post_type=' . Plugin::POST_TYPE . '&p=' . $post_id );
		return apply_filters( 'hyperpress_banner_content', array() );
	}

	public function test_a_single_competition_banner_is_prefixed_with_its_season_after_the_theme_filters(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_title' => 'Granite State' ) );
		wp_set_object_terms( $post_id, array( $this->season()->term_id ), 'hyper_season' );

		$banner = $this->singular_banner_for( $post_id );

		$this->assertTrue( is_singular( Plugin::POST_TYPE ) );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Season 31: Granite State', $banner['title'] );
		$this->assertSame( '#0f0', $banner['backgroundColor'] );
	}

	public function test_a_single_competition_without_a_season_falls_back_to_competition(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_title' => 'Granite State' ) );

		$banner = $this->singular_banner_for( $post_id );

		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Competition', $banner['title'] );
	}

	public function test_a_single_competition_banner_uses_the_post_colour_when_it_has_one(): void {
		$GLOBALS['hyperpress_test_rwmb']['hyperpress_banner_background_color'] = '#123456';
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE ) );

		$banner = $this->singular_banner_for( $post_id );

		$this->assertSame( '#123456', $banner['backgroundColor'] );
	}

	public function test_the_competition_archive_banner_is_unchanged(): void {
		$this->go_to( '/?post_type=' . Plugin::POST_TYPE );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertTrue( is_post_type_archive( Plugin::POST_TYPE ) );
		$this->assertSame( 'Competitions', $banner['title'] );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( '#0f0', $banner['backgroundColor'] );
	}
}
