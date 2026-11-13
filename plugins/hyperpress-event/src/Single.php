<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || exit;

use WP_Post;

/**
 * The single event page: full-screen layout, the plugin's content template and the scaffold for a new event.
 */
final class Single {

	public static function register(): void {
		add_filter( 'hyperpress_single_full_screen', array( self::class, 'full_screen' ) );
		add_filter( 'content_template', array( self::class, 'content_template' ) );
		add_filter( 'default_content', array( self::class, 'default_content' ), 10, 2 );
	}

	/**
	 * Whether the single template drops the sidebar: only for a singular event.
	 *
	 * @param mixed $full_screen Value so far.
	 */
	public static function full_screen( mixed $full_screen ): mixed {
		return is_singular( Plugin::POST_TYPE ) ? true : $full_screen;
	}

	/**
	 * Supplies the content template path for the singular event being viewed; passes any other value through, so an
	 * event listed in a loop (search results) keeps the theme's own template.
	 *
	 * @param mixed $template Template path chosen so far.
	 */
	public static function content_template( mixed $template ): mixed {
		// Only the event being viewed: the theme applies this filter to every post of a loop (search results, lists).
		if ( is_singular( Plugin::POST_TYPE ) && get_the_ID() === get_queried_object_id() ) {
			$path = dirname( __DIR__ ) . '/templates/content.php';
			if ( is_readable( $path ) ) {
				return $path;
			}
		}

		return $template;
	}

	/**
	 * The starting content of a new, empty event.
	 *
	 * @param mixed $content Default editor content.
	 * @param mixed $post    The new post.
	 */
	public static function default_content( mixed $content, mixed $post ): mixed {
		if ( ! $post instanceof WP_Post || Plugin::POST_TYPE !== $post->post_type || ! is_string( $content ) || '' !== trim( $content ) ) {
			return $content;
		}

		return self::scaffold();
	}

	/**
	 * The scaffold: countdown, info section with placeholder rows, tickets and photos.
	 */
	public static function scaffold(): string {
		$rows = array(
			__( 'Date', 'hyperpress' )  => __( 'Add the date', 'hyperpress' ),
			__( 'Time', 'hyperpress' )  => __( 'Add the time', 'hyperpress' ),
			__( 'Where', 'hyperpress' ) => __( 'Add the location', 'hyperpress' ),
		);
		$html = "[hyperpress_event_countdown]\n\n";
		$html .= "[hyperpress_banner title=\"Event Info\" anchor=\"info\"]\n\n";
		$html .= '<div class="grid-x grid-margin-x">';
		foreach ( $rows as $label => $placeholder ) {
			$html .= '<div class="cell small-12 medium-4"><strong>' . esc_html( $label ) . '</strong><br>' . esc_html( $placeholder ) . '</div>';
		}
		$html .= "</div>\n\n";
		$html .= "[hyperpress_event_tickets]\n\n";
		$html .= "[hyperpress_event_photos]\n";

		return $html;
	}
}
