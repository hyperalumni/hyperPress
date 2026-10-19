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
		unset( $GLOBALS['hyperpress_test_rwmb'], $GLOBALS['hyperpress_test_rwmb_posts'] );
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

	public function test_a_single_competition_banner_shows_its_own_title_without_the_season(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_title' => 'Granite State' ) );
		wp_set_object_terms( $post_id, array( $this->season()->term_id ), 'hyper_season' );

		$banner = $this->singular_banner_for( $post_id );

		$this->assertTrue( is_singular( Plugin::POST_TYPE ) );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Granite State', $banner['title'] );
		$this->assertSame( '#0f0', $banner['backgroundColor'] );
	}

	public function test_a_single_competition_without_a_season_shows_its_own_title(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_title' => 'Granite State' ) );

		$banner = $this->singular_banner_for( $post_id );

		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Granite State', $banner['title'] );
	}

	public function test_a_single_competition_without_a_title_falls_back_to_competition(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_title' => '' ) );

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

	public function test_a_single_competition_keeps_its_type_colour_when_the_posts_page_has_a_colour(): void {
		// Reported: an orange banner on a single competition. The Posts page colour leaked onto plugin singulars.
		$posts_page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'page_for_posts', $posts_page );
		$GLOBALS['hyperpress_test_rwmb_posts'][ $posts_page ]['hyperpress_banner_background_color'] = '#F15623';
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_title' => 'UNH District Event 2026' ) );
		wp_set_object_terms( $post_id, array( $this->season()->term_id ), 'hyper_season' );

		$banner = $this->singular_banner_for( $post_id );

		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( '#0f0', $banner['backgroundColor'] );
	}

	public function test_a_single_competition_with_its_own_colour_beats_the_type_colour_and_the_posts_page(): void {
		$posts_page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'page_for_posts', $posts_page );
		$post_id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE ) );
		$GLOBALS['hyperpress_test_rwmb_posts'][ $posts_page ]['hyperpress_banner_background_color'] = '#F15623';
		$GLOBALS['hyperpress_test_rwmb_posts'][ $post_id ]['hyperpress_banner_background_color']    = '#123456';

		$this->assertSame( '#123456', $this->singular_banner_for( $post_id )['backgroundColor'] );
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
