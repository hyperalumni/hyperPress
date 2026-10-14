<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

/**
 * The generic theme banner fallbacks run at priority 90, after the plugin banners (priority 10), so a plugin
 * claims its own archive before the theme's "Archive" fallback does.
 */
final class ArchiveBannerPriorityTest extends WP_UnitTestCase {

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
			)
		);
	}

	public function tear_down(): void {
		if ( null !== $this->plugin_filter ) {
			remove_filter( 'hyperpress_banner_content', $this->plugin_filter, 10 );
			$this->plugin_filter = null;
		}
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
}
