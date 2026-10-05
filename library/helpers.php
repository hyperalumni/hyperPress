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

/**
 * Width / height ratio for the NextGen thumbnail placeholder, or 1.0 when either value is missing,
 * non-numeric or not positive (avoids a DivisionByZeroError / TypeError on bad NextGen settings).
 *
 * @param mixed $width  Thumbnail width.
 * @param mixed $height Thumbnail height.
 */
function hyperpress_nextgen_ratio( $width, $height ): float {
	if ( ! is_numeric( $width ) || ! is_numeric( $height ) || (float) $width <= 0 || (float) $height <= 0 ) {
		return 1.0;
	}

	return (float) $width / (float) $height;
}

/**
 * Social links configured in the Customizer, ready to print, or an empty array when hyperpress-socials is not active.
 *
 * Each item has `key`, `icon`, `title`, `url`, `target` and `rel`; the caller must escape them.
 *
 * @return array<int, array{key: string, icon: string, title: string, url: string, target: string, rel: string}>
 */
function hyperpress_social_links(): array {
	if ( ! class_exists( '\HyperPress\Socials\Links' ) ) {
		return array();
	}

	return \HyperPress\Socials\Links::all();
}
