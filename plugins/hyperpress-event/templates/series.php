<?php
/**
 * Series archive loop: the events of the current page in listing order.
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;

global $wp_query;

$hyperpress_ids = array_map( 'intval', wp_list_pluck( $wp_query->posts, 'ID' ) );
if ( array() !== $hyperpress_ids ) {
	echo \HyperPress\Event\Listing::html( $hyperpress_ids ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Listing::html() returns escaped markup.
} else {
	get_template_part( 'template-parts/content', 'none' );
}
