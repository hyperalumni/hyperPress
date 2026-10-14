<?php
namespace HyperPress\Tests\Integration\Sponsor;

use HyperPress\Sponsor\Plugin;
use WP_UnitTestCase;

final class SponsorBannerTitleTest extends WP_UnitTestCase {

	/**
	 * The filter callbacks registered before this test, restored afterwards.
	 *
	 * @var mixed
	 */
	private $saved_filters;

	public function set_up(): void {
		parent::set_up();
		global $wp_filter;
		$this->saved_filters = $wp_filter['hyperpress_banner_content'] ?? null;
	}

	public function tear_down(): void {
		global $wp_filter;
		remove_all_filters( 'hyperpress_banner_content' );
		if ( null !== $this->saved_filters ) {
			$wp_filter['hyperpress_banner_content'] = $this->saved_filters;
		}
		parent::tear_down();
	}

	public function test_the_archive_keeps_the_sponsor_title_over_the_theme_archive_title(): void {
		// Stand-in for the theme's `hyperpress_banner_archive`, which runs after the plugin's banner filter, at
		// priority 90 (themes/hyperpress-foundation/library/banner/archive.php).
		remove_all_filters( 'hyperpress_banner_content' );
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

		$this->go_to( get_post_type_archive_link( Plugin::POST_TYPE ) );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'Sponsors', $banner['title'] );
		$this->assertSame( 'sponsor', $banner['type'] );
	}
}
