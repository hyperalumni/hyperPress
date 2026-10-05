<?php
/**
 * Helpers that wrap functions and classes from the HYPER plugins, so the theme keeps working (with less
 * data) when a plugin is deactivated or missing.
 *
 * @package hyperPress
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

/**
 * Reads a Meta Box custom field, or '' when Meta Box is not active.
 *
 * @param string   $key     Field id.
 * @param int|null $post_id Post ID; null for the current post.
 * @return mixed
 */
function hyperpress_meta( $key, $post_id = null ) {
	if ( ! function_exists( 'rwmb_meta' ) ) {
		return '';
	}

	return rwmb_meta( $key, '', $post_id );
}

/**
 * Seasons of the current post, newest first, or an empty array when hyperpress-season is not active.
 *
 * @return array
 */
function hyperpress_sorted_seasons(): array {
	if ( ! class_exists( '\HyperPress\Season\SortedSeasons' ) ) {
		return array();
	}

	return (array) \HyperPress\Season\SortedSeasons::get();
}
