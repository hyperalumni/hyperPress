<?php
namespace HyperPress\Tests\Integration\Season;

use HyperPress\Season\SingularBanner;
use HyperPress\Utils\Banner;
use WP_UnitTestCase;

final class SingularBannerTest extends WP_UnitTestCase {

	private const TYPE = 'hyper_singular_test';

	/**
	 * The banner filter callbacks registered before this test, restored afterwards.
	 *
	 * @var mixed
	 */
	private $saved_filters;

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

		if ( ! post_type_exists( self::TYPE ) ) {
			register_post_type( self::TYPE, array( 'public' => true, 'taxonomies' => array( 'hyper_season' ) ) );
			$this->registered_post_types[] = self::TYPE;
		}
		set_theme_mod( 'hyperpress_hyper_orange', '#ff8800' );

		// Stand-ins for the theme's singular banner filters, which run before the plugins' (same priority, loaded
		// earlier): copies of library/banner/post.php and page.php, which only act on `post` and pages.
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) {
				if ( is_singular( 'post' ) ) {
					$banner['type']  = 'post';
					$banner['title'] = 'Blog';
				}
				if ( is_page() && ! is_front_page() && ! is_home() ) {
					$banner['type'] = 'page';
				}
				return $banner;
			}
		);
		Banner::register(
			self::TYPE,
			array(
				'type'          => 'singular-test',
				'subtitle'      => static fn() => 'Sub',
				'color_mod'     => 'hyperpress_hyper_orange',
				'archive_title' => 'Tests',
			)
		);
		SingularBanner::register( self::TYPE, 'Fallback' );
	}

	public function tear_down(): void {
		global $wp_filter;
		unset( $GLOBALS['hyperpress_test_rwmb'], $GLOBALS['hyperpress_test_rwmb_posts'] );
		SingularBanner::unregister( self::TYPE );
		foreach ( $this->registered_post_types as $post_type ) {
			unregister_post_type( $post_type );
		}
		$this->registered_post_types = array();
		remove_all_filters( 'hyperpress_banner_content' );
		if ( null !== $this->saved_filters ) {
			$wp_filter['hyperpress_banner_content'] = $this->saved_filters;
		}
		parent::tear_down();
	}

	private function banner_for( int $post_id ): array {
		$this->go_to( '/?post_type=' . self::TYPE . '&p=' . $post_id );
		return apply_filters( 'hyperpress_banner_content', array() );
	}

	private function post( string $title = 'Kickoff' ): int {
		return self::factory()->post->create( array( 'post_type' => self::TYPE, 'post_title' => $title ) );
	}

	public function test_the_title_is_prefixed_with_the_latest_season_after_the_theme_filters(): void {
		$post_id = $this->post();
		$old     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2025' ) );
		$new     = self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026' ) );
		wp_set_object_terms( $post_id, array( $old, $new ), 'hyper_season' );

		$banner = $this->banner_for( $post_id );
		$this->assertSame( 'singular-test', $banner['type'] );
		$this->assertSame( '2026: Kickoff', $banner['title'] );
		$this->assertSame( 'Sub', $banner['subtitle'] );
	}

	public function test_a_title_set_earlier_is_kept(): void {
		$post_id = $this->post();
		wp_set_object_terms( $post_id, array( self::factory()->term->create( array( 'taxonomy' => 'hyper_season', 'name' => '2026' ) ) ), 'hyper_season' );
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) {
				$banner['title'] = 'Custom';
				return $banner;
			},
			5
		);

		$this->assertSame( 'Custom', $this->banner_for( $post_id )['title'] );
	}

	public function test_without_a_season_the_title_is_the_fallback(): void {
		$this->assertSame( 'Fallback', $this->banner_for( $this->post() )['title'] );
	}

	public function test_the_colour_comes_from_the_per_post_meta_box(): void {
		$GLOBALS['hyperpress_test_rwmb']['hyperpress_banner_background_color'] = '#123456';
		$this->assertSame( '#123456', $this->banner_for( $this->post() )['backgroundColor'] );
	}

	public function test_the_theme_colour_stays_when_the_post_has_none_even_if_the_posts_page_has_one(): void {
		$posts_page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( 'page_for_posts', $posts_page );
		$GLOBALS['hyperpress_test_rwmb_posts'][ $posts_page ]['hyperpress_banner_background_color'] = '#F15623';
		$this->assertSame( '#ff8800', $this->banner_for( $this->post() )['backgroundColor'] );
	}

	public function test_other_post_types_and_non_singular_requests_are_untouched(): void {
		$this->assertSame( array( 'title' => 'x' ), SingularBanner::adjust( array( 'title' => 'x' ), 'hyper_other', array( 'is_singular' => true ) ) );
		$this->assertSame( array( 'title' => '' ), SingularBanner::adjust( array( 'title' => '' ), self::TYPE, array( 'is_singular' => false ) ) );
	}

	public function test_the_theme_post_filter_only_fills_the_title_for_posts(): void {
		// Evidence for the empty-title guard: library/banner/post.php fills the title only for `post`, so on this
		// post type the title is still empty when the shared filter runs.
		$post_id = self::factory()->post->create( array( 'post_type' => 'post', 'post_title' => 'News' ) );
		$this->go_to( get_permalink( $post_id ) );
		$this->assertSame( 'Blog', apply_filters( 'hyperpress_banner_content', array() )['title'] );

		$custom = $this->post( 'Plain' );
		$this->assertSame( 'Fallback', $this->banner_for( $custom )['title'] );
	}
}
