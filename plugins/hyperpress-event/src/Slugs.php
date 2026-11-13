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
		add_action( Plugin::SERIES . '_edit_form', array( self::class, 'conflict_notice' ) );
	}

	/**
	 * Series edit screen: warns when an event has the series slug, because that event then answers /event/<slug>/
	 * and the evergreen alias stops working.
	 *
	 * Any event counts whatever its status: a draft takes over the URL as soon as it is published. A trashed event
	 * does not (WordPress renames its slug), and get_page_by_path() reflects that.
	 *
	 * @param mixed $term The series term being edited.
	 */
	public static function conflict_notice( $term ): void {
		if ( ! $term instanceof \WP_Term || Plugin::SERIES !== $term->taxonomy ) {
			return;
		}
		$event = get_page_by_path( $term->slug, OBJECT, Plugin::POST_TYPE );
		if ( ! $event instanceof \WP_Post || in_array( $event->post_status, array( 'trash', 'auto-draft' ), true ) ) {
			return;
		}

		$title = get_the_title( $event );
		$link  = get_edit_post_link( $event, 'raw' );
		$name  = '' !== $link && null !== $link
			? '<a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a>'
			: esc_html( $title );
		$path  = '/' . Plugin::BASE . '/' . $term->slug . '/';

		echo '<div class="notice notice-warning inline"><p>';
		printf(
			/* translators: 1: URL path such as /event/camp/, 2: the event's title, linked to its edit screen when allowed. */
			esc_html__( 'The event %2$s has the same slug as this series, so it takes over %1$s and the series alias stops working. Change the event\'s slug or this series\' slug.', 'hyperpress' ),
			'<code>' . esc_html( $path ) . '</code>',
			$name // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from esc_html() and esc_url() above.
		);
		echo '</p></div>';
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
