<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * Admin styling fixes for the competition edit screen.
 *
 * The Meta Box `osm` field's address autocomplete menu gets jQuery UI's `ui-front` class, and
 * `.ui-front { z-index: 100 }` (WordPress and Meta Box core CSS) has the same specificity as, and loads
 * after, Meta Box's `.ui-autocomplete { z-index: 999999 }`. The menu then sits under the Leaflet map
 * (panes and controls use z-index 200-1000). A two-class selector wins whatever the load order.
 */
final class AdminAssets {

	private const STYLE_HANDLE = 'rwmb-osm';
	private const CSS          = '.ui-autocomplete.ui-front{z-index:999999}';

	public static function register(): void {
		// Meta Box enqueues its field styles on admin_enqueue_scripts at priority 10; a later priority
		// guarantees the handle exists when the inline style is attached.
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ), 20 );
	}

	/**
	 * Attaches the override to Meta Box's osm stylesheet on the competition add/edit screens only.
	 *
	 * @param string $hook_suffix Current admin page.
	 */
	public static function enqueue( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( null === $screen || Plugin::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_add_inline_style( self::STYLE_HANDLE, self::CSS );
	}
}
