<?php
namespace HyperPress\Newsletter;

defined( 'ABSPATH' ) || exit;

/**
 * Opts newsletters into the shared hyperpress-label date, author, season and comment labels.
 */
final class Labels {

	/**
	 * Shared label IDs that newsletters display by default.
	 */
	private const SHARED_LABELS = array( 'post.date', 'post.author', 'post.comments', 'season.posted_during' );

	public static function register(): void {
		add_filter( 'hyperpress_label_default_post_types', array( self::class, 'default_post_types' ), 10, 2 );
	}

	/**
	 * Add the newsletter post type to the default post types of the shared labels.
	 *
	 * @param mixed  $types    Default post types for the label.
	 * @param string $label_id Label ID.
	 * @return array<int,string>
	 */
	public static function default_post_types( $types, $label_id ): array {
		$types = is_array( $types ) ? $types : array();
		if ( in_array( $label_id, self::SHARED_LABELS, true ) && ! in_array( Plugin::POST_TYPE, $types, true ) ) {
			$types[] = Plugin::POST_TYPE;
		}

		return $types;
	}
}
