<?php
/**
 * Clean up WordPress defaults
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'hyperpress_start_cleanup' );

function hyperpress_start_cleanup(): void {
	// Launching operation cleanup.
	add_action( 'init', 'hyperpress_cleanup_head' );

	// Remove WP version from RSS.
	add_filter( 'the_generator', 'hyperpress_remove_rss_version' );

	// Clean up comment styles in the head.
	add_action( 'wp_head', 'hyperpress_remove_recent_comments_style', 1 );
}
/**
 * Clean up the head.
 *
 * Only output nobody needs is removed. The canonical link and the feed autodiscovery links are deliberately kept:
 * the canonical link protects against duplicate URLs (?replytocom, tracking parameters) unless an SEO plugin takes
 * over, and the feed links let readers find the RSS feeds. Upstream FoundationPress removed them without a recorded
 * reason.
 */
function hyperpress_cleanup_head(): void {

	// EditURI link.
	remove_action( 'wp_head', 'rsd_link' );

	// Windows Live Writer.
	remove_action( 'wp_head', 'wlwmanifest_link' );

	// Shortlink.
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );

	// Links for adjacent posts.
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );

	// WP version.
	remove_action( 'wp_head', 'wp_generator' );

	// Emoji detection script.
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );

	// Emoji styles. Since WordPress 6.4 they are enqueued by wp_enqueue_emoji_styles, and print_emoji_styles stays
	// hooked for backwards compatibility (core only unhooks it from inside the enqueue function), so both go.
	remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}

// Remove WP version from RSS.
function hyperpress_remove_rss_version(): string {
	return '';
}

// Remove injected CSS from recent comments widget.
function hyperpress_remove_recent_comments_style(): void {
	global $wp_widget_factory;
	if ( isset( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'] ) ) {
		remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );
	}
}
