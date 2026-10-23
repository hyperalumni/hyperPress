<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

/**
 * One-time conversion of the legacy plain-URL sponsor website into the Meta Box link array.
 */
final class WebsiteMigration {

	public const OPTION  = 'hyperpress_sponsor_website_link_version';
	public const VERSION = '1';
	public const META    = 'hyperpress_sponsor_website';

	public static function register(): void {
		add_action( 'init', array( self::class, 'run' ), 20 );
	}

	/**
	 * Converts each legacy string value to array( url, title, target, post_id ), then records the version.
	 * Values that are already arrays, empty, or have no safe URL are left untouched.
	 */
	public static function run(): void {
		if ( self::VERSION === get_option( self::OPTION ) ) {
			return;
		}

		$ids = get_posts(
			array(
				'post_type'              => Plugin::POST_TYPE,
				'post_status'            => array_values( get_post_stati() ),
				'meta_key'               => self::META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'orderby'                => 'ID',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'suppress_filters'       => true,
			)
		);

		foreach ( $ids as $post_id ) {
			self::convert( (int) $post_id );
		}

		update_option( self::OPTION, self::VERSION );
	}

	private static function convert( int $post_id ): void {
		$value = get_post_meta( $post_id, self::META, true );
		if ( ! is_string( $value ) ) {
			return;
		}

		$url = esc_url_raw( trim( $value ) );
		if ( '' === $url ) {
			return;
		}

		update_post_meta(
			$post_id,
			self::META,
			wp_slash(
				array(
					'url'     => $url,
					'title'   => '',
					'target'  => '',
					'post_id' => 0,
				)
			)
		);
	}
}
