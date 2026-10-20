<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

use WP_Query;

/**
 * Swaps the theme's archive loop for the grouped sponsor loop (season and tier headings) on the sponsor
 * archive and sets its page size.
 */
final class ArchiveLoop {

	private const DEFAULT_PER_PAGE = 24;

	public static function register(): void {
		add_filter( 'hyperpress_posts_template', array( self::class, 'posts_template' ) );
		add_action( 'pre_get_posts', array( self::class, 'set_page_size' ) );
	}

	/**
	 * Path of the grouped loop template for the main query of the sponsor archive.
	 *
	 * @param mixed $template Template path chosen so far; returned unchanged when not handled.
	 *
	 * @return mixed
	 */
	public static function posts_template( mixed $template ): mixed {
		if ( is_admin() || is_feed() || ! is_main_query() || ! is_post_type_archive( Plugin::POST_TYPE ) ) {
			return $template;
		}

		return dirname( __DIR__ ) . '/templates/loop.php';
	}

	/**
	 * Page size of the main sponsor archive query.
	 *
	 * @param WP_Query $query The query.
	 */
	public static function set_page_size( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || $query->is_feed() || ! $query->is_post_type_archive( Plugin::POST_TYPE ) ) {
			return;
		}

		$query->set( 'posts_per_page', max( 1, (int) apply_filters( 'hyperpress_sponsor_archive_per_page', self::DEFAULT_PER_PAGE ) ) );
	}

	/**
	 * ID of the sponsor immediately before the first sponsor of the current page, in archive order.
	 *
	 * @param WP_Query $main The main sponsor archive query.
	 * @return int 0 on the first page or when there is none.
	 */
	public static function previous_sponsor_id( WP_Query $main ): int {
		$paged    = max( 1, (int) $main->get( 'paged' ) );
		$per_page = max( 1, (int) $main->get( 'posts_per_page' ) );
		if ( $paged < 2 ) {
			return 0;
		}

		$args = is_array( $main->query ) ? $main->query : array();
		unset( $args['paged'], $args['page'] );
		$args['post_type']           = Plugin::POST_TYPE;
		$args[ Ordering::ORDER_VAR ] = true;
		$args['fields']              = 'ids';
		$args['posts_per_page']      = 1;
		$args['offset']              = ( $paged - 1 ) * $per_page - 1;
		$args['no_found_rows']       = true;

		$ids = ( new WP_Query( $args ) )->posts;

		return $ids ? (int) $ids[0] : 0;
	}
}
