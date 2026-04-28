<?php
function hyperpress_root_colors() {
	echo '<style type="text/css" id="hyper-colors-css">
					:root {
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
}

add_action( 'wp_head', 'hyperpress_root_colors' );
?>
