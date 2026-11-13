<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || exit;

/**
 * Menu items for an event series link to the evergreen alias (`/event/<series-slug>/`), which redirects to the current
 * event, instead of the series archive. Only the url changes: `object_id` stays the term so WordPress still marks the
 * item current on the series archive. get_term_link(), the archive and direct visits are untouched.
 */
final class Menus {

	public static function register(): void {
		add_filter( 'wp_setup_nav_menu_item', array( self::class, 'url' ) );
	}

	public static function url( object $item ): object {
		if ( ! empty( $item->_invalid ) || ( $item->type ?? '' ) !== 'taxonomy' || ( $item->object ?? '' ) !== Plugin::SERIES ) {
			return $item;
		}

		$term = get_term( (int) ( $item->object_id ?? 0 ), Plugin::SERIES );
		if ( ! $term instanceof \WP_Term ) {
			return $item;
		}

		$item->url = home_url( user_trailingslashit( Plugin::BASE . '/' . $term->slug ) );

		return $item;
	}
}
