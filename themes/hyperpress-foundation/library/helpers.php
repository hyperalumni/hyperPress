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
 * Prints the default content of the "Footer socials" widget area: the Customizer socials as an inline list, or nothing
 * when hyperpress-social is not active.
 */
function hyperpress_default_footer_socials(): void {
	if ( ! class_exists( '\HyperPress\Social\Widgets\DisplaySocialWidget' ) ) {
		return;
	}

	the_widget(
		// Widget factory keys have no leading backslash (register_widget( Class::class )).
		'HyperPress\Social\Widgets\DisplaySocialWidget',
		\HyperPress\Social\Widgets\DisplaySocialWidget::default_instance(),
		array(
			'before_widget' => '',
			'after_widget'  => '',
			'before_title'  => '',
			'after_title'   => '',
		)
	);
}
