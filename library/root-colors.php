<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'hyperpress_root_colors' );

function hyperpress_root_colors() {
	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- values are sanitized with sanitize_hex_color() when saved
	echo '<style id="hyper-colors-css">' .
		':root {
							--hyper-gear-blue: ' . get_theme_mod( 'hyperpress_gear_blue' ) . ';
							--hyper-gear-orange: ' . get_theme_mod( 'hyperpress_gear_orange' ) . ';
							--hyper-gear-grey: ' . get_theme_mod( 'hyperpress_gear_grey' ) . ';
							--hyper-logo-red: ' . get_theme_mod( 'hyperpress_hyper_red' ) . ';
							--hyper-logo-orange: ' . get_theme_mod( 'hyperpress_hyper_orange' ) . ';
							--hyper-logo-green: ' . get_theme_mod( 'hyperpress_hyper_green' ) . ';
							--hyper-logo-blue: ' . get_theme_mod( 'hyperpress_hyper_blue' ) . ';
							--hyper-logo-purple: ' . get_theme_mod( 'hyperpress_hyper_purple' ) . ';
					}' .
		'</style>';
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
}
