<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * The blog-related banners (generic archive, category, tag, year, month, day, author) default to the background
 * colour set on the Posts page, through the priority-95 hyperpress_banner_blog_color filter. The filter is keyed on
 * the banner type, so plugin banners are never touched, and it never overrides a colour that is already set.
 *
 * The test bootstrap simulates Meta Box through $GLOBALS['hyperpress_test_rwmb'][ $key ], which ignores the post id.
 */
final class BlogBannerColorTest extends WP_UnitTestCase {

	private const POST_TYPE = 'hp_test_competition';

	/** @var callable|null */
	private $plugin_filter = null;

	public function set_up(): void {
		parent::set_up();
		register_post_type(
			self::POST_TYPE,
			array(
				'public'      => true,
				'has_archive' => true,
				'taxonomies'  => array( 'post_tag', 'category' ),
			)
		);
	}

	public function tear_down(): void {
		if ( null !== $this->plugin_filter ) {
			remove_filter( 'hyperpress_banner_content', $this->plugin_filter, 10 );
			$this->plugin_filter = null;
		}
		unset( $GLOBALS['hyperpress_test_rwmb'] );
		unregister_post_type( self::POST_TYPE );
		parent::tear_down();
	}

	private function set_posts_page( ?string $color ): void {
		if ( null !== $color && ! function_exists( 'rwmb_meta' ) ) {
			$this->markTestSkipped( 'Needs the simulated Meta Box (not defined with HYPERPRESS_TEST_WITHOUT_PLUGINS).' );
		}
		$page_id = self::factory()->post->create(
			array(
				'post_type'  => 'page',
				'post_title' => 'Blog',
			)
		);
		update_option( 'show_on_front', 'page' );
		update_option( 'page_for_posts', $page_id );
		if ( null !== $color ) {
			$GLOBALS['hyperpress_test_rwmb']['hyperpress_banner_background_color'] = $color;
		}
	}

	private function url( string $query, ?string $post_type = null ): string {
		$tag_id    = self::factory()->tag->create( array( 'slug' => 'hp-tag' ) );
		$cat_id    = self::factory()->category->create( array( 'slug' => 'hp-cat' ) );
		$author_id = self::factory()->user->create();
		// A matching post, so a plain date or author request is not turned into a 404.
		self::factory()->post->create(
			array(
				'post_author'   => $author_id,
				'post_date'     => '2025-10-14 12:00:00',
				'tags_input'    => array( $tag_id ),
				'post_category' => array( $cat_id ),
			)
		);
		$query     = str_replace( '{author}', (string) $author_id, $query );

		return home_url( '/?' . ( null === $post_type ? '' : 'post_type=' . $post_type . '&' ) . $query );
	}

	/**
	 * @return array<string,array{0:string,1:string,2:string}> Query, conditional tag, expected banner type.
	 */
	public static function blog_archives(): array {
		return array(
			'archive'  => array( 'post_type=' . self::POST_TYPE, 'is_archive', 'archive' ),
			'category' => array( 'category_name=hp-cat', 'is_category', 'category' ),
			'tag'      => array( 'tag=hp-tag', 'is_tag', 'tag' ),
			'year'     => array( 'year=2025', 'is_year', 'year' ),
			'month'    => array( 'm=202510', 'is_month', 'month' ),
			'day'      => array( 'year=2025&monthnum=10&day=14', 'is_day', 'day' ),
			'author'   => array( 'author={author}', 'is_author', 'author' ),
		);
	}

	private function go_to_archive( string $query, string $conditional ): void {
		$this->go_to( $this->url( $query ) );
		$this->assertTrue( $conditional() );
	}

	public function test_the_blog_color_filter_runs_at_priority_95(): void {
		$this->assertSame( 95, has_filter( 'hyperpress_banner_content', 'hyperpress_banner_blog_color' ) );
	}

	/**
	 * @dataProvider blog_archives
	 */
	public function test_a_blog_archive_gets_the_posts_page_color( string $query, string $conditional, string $type ): void {
		$this->set_posts_page( '#112233' );
		$this->go_to_archive( $query, $conditional );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( $type, $banner['type'] );
		$this->assertSame( '#112233', $banner['backgroundColor'] );
	}

	/**
	 * @dataProvider blog_archives
	 */
	public function test_a_blog_archive_keeps_a_color_that_is_already_set( string $query, string $conditional, string $type ): void {
		$this->set_posts_page( '#112233' );
		$this->go_to_archive( $query, $conditional );

		$banner = apply_filters( 'hyperpress_banner_content', array( 'backgroundColor' => '#abcdef' ) );

		$this->assertSame( $type, $banner['type'] );
		$this->assertSame( '#abcdef', $banner['backgroundColor'] );
	}

	public function test_a_color_set_by_a_plugin_filter_at_priority_10_is_not_overridden(): void {
		$this->set_posts_page( '#112233' );
		$this->plugin_filter = static function ( $banner ) {
			$banner['backgroundColor'] = '#abcdef';
			return $banner;
		};
		add_filter( 'hyperpress_banner_content', $this->plugin_filter, 10 );
		$this->go_to_archive( 'tag=hp-tag', 'is_tag' );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'tag', $banner['type'] );
		$this->assertSame( '#abcdef', $banner['backgroundColor'] );
	}

	public function test_a_plugin_owned_type_is_never_touched(): void {
		$this->set_posts_page( '#112233' );
		// Like HyperPress\Utils\Banner: priority 10, claims a CPT archive that is also a tag archive.
		$this->plugin_filter = static function ( $banner ) {
			if ( is_post_type_archive( self::POST_TYPE ) ) {
				$banner['type'] = 'competition';
			}
			return $banner;
		};
		add_filter( 'hyperpress_banner_content', $this->plugin_filter, 10 );
		$this->go_to( $this->url( 'tag=hp-tag', self::POST_TYPE ) );
		$this->assertTrue( is_post_type_archive( self::POST_TYPE ) );
		$this->assertTrue( is_tag() );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'competition', $banner['type'] );
		$this->assertEmpty( $banner['backgroundColor'] ?? '' );
	}

	public function test_a_plugin_type_outside_the_list_is_not_given_the_color_directly(): void {
		$this->set_posts_page( '#112233' );

		$banner = hyperpress_banner_blog_color(
			array(
				'type'  => 'newsletter',
				'title' => 'News',
			)
		);

		$this->assertEmpty( $banner['backgroundColor'] ?? '' );
	}

	/**
	 * @dataProvider blog_archives
	 */
	public function test_no_posts_page_sets_no_color( string $query, string $conditional ): void {
		// The simulated meta would be readable, so only the missing Posts page can keep the color empty.
		$GLOBALS['hyperpress_test_rwmb']['hyperpress_banner_background_color'] = '#112233';
		$this->assertSame( 0, (int) get_option( 'page_for_posts' ) );
		$this->go_to_archive( $query, $conditional );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertEmpty( $banner['backgroundColor'] ?? '' );
	}

	/**
	 * @dataProvider blog_archives
	 */
	public function test_a_posts_page_without_a_color_leaves_the_color_empty( string $query, string $conditional ): void {
		$this->set_posts_page( null );
		$this->go_to_archive( $query, $conditional );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertEmpty( $banner['backgroundColor'] ?? '' );
	}

	public function test_search_and_404_are_untouched(): void {
		$this->set_posts_page( '#112233' );

		$this->go_to( home_url( '/?s=hello' ) );
		$this->assertTrue( is_search() );
		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertEmpty( $banner['backgroundColor'] ?? '' );

		$this->go_to( home_url( '/?p=999999' ) );
		$this->assertTrue( is_404() );
		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertEmpty( $banner['backgroundColor'] ?? '' );
	}

	public function test_the_posts_page_banner_still_uses_its_own_color(): void {
		$this->set_posts_page( '#112233' );
		$this->go_to( get_permalink( (int) get_option( 'page_for_posts' ) ) );
		$this->assertTrue( is_home() );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'blog', $banner['type'] );
		$this->assertSame( '#112233', $banner['backgroundColor'] );
	}
}
