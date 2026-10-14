<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * The theme's archive banner fallbacks run after the plugin banners (priority 10): the specific ones (tag,
 * category, author, year, month, day) at priority 80 and the generic "Archive" one at priority 90. A plugin
 * claims its own archive first, even when the request is also a tag, category, author or date archive.
 */
final class ArchiveBannerPriorityTest extends WP_UnitTestCase {

	private const POST_TYPE = 'hp_test_competition';

	/** @var callable|null */
	private $plugin_filter = null;

	/** @var array<string,mixed>|null Banner as the plugin filter received it. */
	private $incoming = null;

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
		$this->incoming = null;
		unregister_post_type( self::POST_TYPE );
		parent::tear_down();
	}

	private function go_to_archive(): void {
		$this->go_to( home_url( '/?post_type=' . self::POST_TYPE ) );
		$this->assertTrue( is_archive() );
	}

	public function test_the_archive_fallback_runs_after_priority_10(): void {
		$this->assertSame( 90, has_filter( 'hyperpress_banner_content', 'hyperpress_banner_archive' ) );
	}

	public function test_a_plugin_banner_at_priority_10_is_not_overwritten_by_the_archive_fallback(): void {
		// Like HyperPress\Utils\Banner::register(): a plain filter at the default priority, added after the theme's.
		// It sets the type and keeps a title that is already set.
		$this->plugin_filter = static function ( $banner ) {
			if ( is_post_type_archive( self::POST_TYPE ) ) {
				$banner['type'] = 'competition';
				if ( empty( $banner['title'] ) ) {
					$banner['title'] = 'Competitions';
				}
			}
			return $banner;
		};
		add_filter( 'hyperpress_banner_content', $this->plugin_filter, 10 );

		$this->go_to_archive();
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Competitions', $banner['title'] );
	}

	public function test_a_plain_archive_without_a_plugin_banner_still_gets_the_archive_banner(): void {
		$this->go_to_archive();
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'archive', $banner['type'] );
		$this->assertSame( 'Archive', $banner['title'] );
	}

	public function test_a_specific_archive_banner_still_wins_over_the_archive_fallback(): void {
		$this->go_to( get_term_link( self::factory()->category->create(), 'category' ) );
		$this->assertTrue( is_category() );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'category', $banner['type'] );
	}

	/**
	 * Specific fallbacks: [ query args, conditional tag, theme banner type ].
	 *
	 * @return array<string,array{0:string,1:string,2:string}>
	 */
	public static function specific_archives(): array {
		return array(
			'tag'      => array( 'tag=hp-tag', 'is_tag', 'tag' ),
			'category' => array( 'category_name=hp-cat', 'is_category', 'category' ),
			'author'   => array( 'author={author}', 'is_author', 'author' ),
			'year'     => array( 'year=2025', 'is_year', 'year' ),
			'month'    => array( 'm=202510', 'is_month', 'month' ),
			'day'      => array( 'year=2025&monthnum=10&day=14', 'is_day', 'day' ),
		);
	}

	private function specific_url( string $query, ?string $post_type ): string {
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

	/** Like HyperPress\Utils\Banner: priority 10, unconditional type, keeps a title that is already set. */
	private function add_plugin_filter(): void {
		$this->plugin_filter = function ( $banner ) {
			$this->incoming = $banner;
			if ( is_post_type_archive( self::POST_TYPE ) ) {
				$banner['type'] = 'competition';
				if ( empty( $banner['title'] ) ) {
					$banner['title'] = 'Competitions';
				}
			}
			return $banner;
		};
		add_filter( 'hyperpress_banner_content', $this->plugin_filter, 10 );
	}

	/**
	 * @dataProvider specific_archives
	 */
	public function test_the_specific_fallbacks_run_at_priority_80( string $query, string $conditional, string $type ): void {
		$this->assertSame( 80, has_filter( 'hyperpress_banner_content', 'hyperpress_banner_' . $type ) );
	}

	/**
	 * @dataProvider specific_archives
	 */
	public function test_a_plugin_banner_at_priority_10_wins_on_a_plugin_archive_that_is_also_a_specific_archive( string $query, string $conditional, string $type ): void {
		$this->add_plugin_filter();
		$this->go_to( $this->specific_url( $query, self::POST_TYPE ) );
		$this->assertTrue( is_post_type_archive( self::POST_TYPE ) );
		$this->assertTrue( $conditional() );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		// The theme has not claimed the banner before the plugin runs.
		$this->assertEmpty( $this->incoming['type'] ?? '' );
		$this->assertEmpty( $this->incoming['title'] ?? '' );
		$this->assertSame( 'competition', $banner['type'] );
		$this->assertSame( 'Competitions', $banner['title'] );
	}

	/**
	 * Plain tag, category and date archives keep the generic "Archive" title (the specific fallbacks only set the
	 * type and the subtitle), so the banner template never falls back to the first post's title.
	 *
	 * @dataProvider specific_archives
	 */
	public function test_a_plain_specific_archive_gets_the_archive_title_and_keeps_its_subtitle( string $query, string $conditional, string $type ): void {
		if ( 'author' === $type ) {
			$this->markTestSkipped( 'The author banner sets its own title; see AuthorBannerTest.' );
		}
		$this->go_to( $this->specific_url( $query, null ) );
		$this->assertTrue( $conditional() );

		$banner = apply_filters(
			'hyperpress_banner_content',
			array(
				'type'     => '',
				'title'    => '',
				'subtitle' => '',
			)
		);

		$this->assertSame( $type, $banner['type'] );
		$this->assertSame( 'Archive', $banner['title'] );
		$this->assertStringStartsWith( ucfirst( $type ) . ': <span class="emphasis">', $banner['subtitle'] );
	}

	public function test_a_category_archive_heading_is_not_the_first_post_title(): void {
		$cat_id = self::factory()->category->create( array( 'slug' => 'hp-cat' ) );
		self::factory()->post->create(
			array(
				'post_title'    => 'First Post Title Zed',
				'post_date'     => '2025-10-14 12:00:00',
				'post_category' => array( $cat_id ),
			)
		);
		self::factory()->post->create(
			array(
				'post_title'    => 'Second Post Title Zed',
				'post_date'     => '2025-10-13 12:00:00',
				'post_category' => array( $cat_id ),
			)
		);
		remove_action( 'wp_body_open', 'the_block_template_skip_link' );
		$this->go_to( home_url( '/?category_name=hp-cat' ) );
		$this->assertTrue( is_category() );
		// The Loop is positioned on the first post, as it is when the template renders the banner.
		$GLOBALS['wp_query']->the_post();

		ob_start();
		include get_template_directory() . '/template-parts/banner.php';
		$html = ob_get_clean();
		wp_reset_postdata();

		$this->assertMatchesRegularExpression( '#<h2 class="entry-title">\s*Archive\s*</h2>#', $html );
		$this->assertStringNotContainsString( 'Post Title Zed', $html );
		$this->assertStringContainsString( 'Category: <span class="emphasis">', $html );
	}

	/**
	 * @dataProvider specific_archives
	 */
	public function test_a_plain_specific_archive_still_gets_its_theme_banner( string $query, string $conditional, string $type ): void {
		$this->add_plugin_filter();
		$this->go_to( $this->specific_url( $query, null ) );
		$this->assertTrue( $conditional() );
		$this->assertFalse( is_post_type_archive( self::POST_TYPE ) );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( $type, $banner['type'] );
	}
}
