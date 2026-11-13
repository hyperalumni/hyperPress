<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Version;

/**
 * Flushes the rewrite rules once per plugin version, so the event and series routes exist after an update.
 */
final class Rewrite {

	private const VERSION_OPTION = 'hyperpress_event_rewrite_version';

	public static function register(): void {
		// After the post type and taxonomy register (priority 10) and the rules are built.
		add_action( 'init', array( self::class, 'maybe_flush' ), 99 );
	}

	public static function maybe_flush(): void {
		$version = Version::of( dirname( __DIR__ ) . '/hyperpress-event.php' );

		if ( get_option( self::VERSION_OPTION ) === $version ) {
			return;
		}

		// Store first: flush_rewrite_rules() must never run twice for one change.
		update_option( self::VERSION_OPTION, $version );
		flush_rewrite_rules( false );
	}
}
