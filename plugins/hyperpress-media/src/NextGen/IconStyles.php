<?php
namespace HyperPress\Media\NextGen;

defined( 'ABSPATH' ) || exit;

/**
 * Removes NextGEN Gallery's Font Awesome icon styles from the front end.
 *
 * The theme draws Font Awesome icons with Font Awesome's script (inline SVG) and does not enqueue Font Awesome's stylesheet, because a real stylesheet doubles the size of stacked icons (`fa-stack-2x { font-size: 2em }`). NextGEN enqueues its own icon stylesheets (`DisplayManager::enqueue_fontawesome()`) wherever it renders a display, which brings that doubling back. NextGEN's lightbox buttons are plain text glyphs, so nothing the lite plugin shows needs these styles.
 *
 * Registered unconditionally: when NextGEN is inactive the handles never exist and the callback does nothing, so no class_exists() gate is needed.
 *
 * Filter `hyperpress_media_dequeue_nextgen_icons` (bool, default true): return false to keep NextGEN's icon styles, for example when NextGEN Pro needs them.
 */
final class IconStyles {

	/** Handles enqueued by NextGEN Gallery's DisplayManager::enqueue_fontawesome(). */
	private const HANDLES = array( 'nextgen_gallery_icons', 'nextgen_gallery_icons_scope' );

	public static function register(): void {
		// After the styles NextGEN enqueued early are collected for wp_head.
		add_action( 'wp_print_styles', array( self::class, 'dequeue' ), 100 );
		// Before print_late_styles() (priority 10) prints styles enqueued while the body rendered.
		add_action( 'wp_print_footer_scripts', array( self::class, 'dequeue' ), 5 );
	}

	public static function dequeue(): void {
		if ( is_admin() ) {
			return;
		}

		/**
		 * Filters whether NextGEN Gallery's Font Awesome icon styles are removed on the front end.
		 *
		 * @param bool $dequeue Default true.
		 */
		if ( true !== apply_filters( 'hyperpress_media_dequeue_nextgen_icons', true ) ) {
			return;
		}

		foreach ( self::HANDLES as $handle ) {
			wp_dequeue_style( $handle );
		}
	}
}
