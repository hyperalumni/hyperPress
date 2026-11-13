<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || exit;

/**
 * Slugs an event may not take: they are the series taxonomy's path segment under /event/.
 */
final class Slugs {

	public const RESERVED = 'series';

	public static function register(): void {
		add_filter( 'wp_unique_post_slug_is_bad_flat_slug', array( self::class, 'is_bad_slug' ), 10, 3 );
	}

	/**
	 * Filter: rejects the reserved slug for events so WordPress picks another.
	 *
	 * @param mixed $is_bad    Whether the slug is already bad.
	 * @param mixed $slug      The slug.
	 * @param mixed $post_type Post type key.
	 * @return mixed True for the reserved slug on an event, else $is_bad.
	 */
	public static function is_bad_slug( $is_bad, $slug, $post_type ) {
		if ( Plugin::POST_TYPE === $post_type && self::RESERVED === $slug ) {
			return true;
		}

		return $is_bad;
	}
}
