<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use HyperPress\Utils\RequestTerm;
use WP_Query;

/**
 * The series archive: its events in listing order, the plugin's list template and a banner of its own.
 */
final class SeriesArchive {

	public static function register(): void {
		add_action( 'pre_get_posts', array( self::class, 'order_query' ) );
		add_filter( 'hyperpress_posts_template', array( self::class, 'posts_template' ) );
		// Priority 10: claims the archive before the theme's fallbacks at 80 and 90.
		add_filter( 'hyperpress_banner_content', array( self::class, 'banner' ) );
	}

	/**
	 * Limits the series main query to the listed events, in listing order (paging keeps it).
	 *
	 * @param WP_Query $query The query.
	 */
	public static function order_query( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || $query->is_feed() || ! $query->is_tax() ) {
			return;
		}
		$term = RequestTerm::get( Plugin::SERIES );
		if ( null === $term ) {
			return;
		}
		$ids = Listing::ids( $term->slug );

		$query->set( 'post__in', array() === $ids ? array( 0 ) : $ids );
		$query->set( 'orderby', 'post__in' );
	}

	/**
	 * Path of the series list template for the main query of a series archive.
	 *
	 * @param mixed $template Template path chosen so far; returned unchanged when not handled.
	 * @return mixed
	 */
	public static function posts_template( mixed $template ): mixed {
		if ( is_admin() || is_feed() || ! is_main_query() || ! is_tax() || null === RequestTerm::get( Plugin::SERIES ) ) {
			return $template;
		}

		return dirname( __DIR__ ) . '/templates/series.php';
	}

	/**
	 * Banner of a series archive: the term name, the fallback subtitle and the colour (settings default, then the hub page's).
	 *
	 * @param mixed $banner Banner so far.
	 * @return mixed
	 */
	public static function banner( $banner ) {
		if ( ! is_array( $banner ) || ! is_tax() ) {
			return $banner;
		}
		$term = RequestTerm::get( Plugin::SERIES );
		if ( null === $term ) {
			return $banner;
		}

		if ( empty( $banner['type'] ) ) {
			$banner['type'] = 'event';
		}
		if ( empty( $banner['title'] ) ) {
			$banner['title'] = $term->name;
		}
		if ( empty( $banner['backgroundColor'] ) ) {
			$color = Appearance::archive_color();
			if ( '' !== $color ) {
				$banner['backgroundColor'] = $color;
			}
		}
		if ( empty( $banner['subtitle'] ) ) {
			$subtitle = Settings::fallback_subtitle();
			if ( '' !== $subtitle ) {
				$banner['subtitle'] = $subtitle;
			}
		}

		return $banner;
	}
}
