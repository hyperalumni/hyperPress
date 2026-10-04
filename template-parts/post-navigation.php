<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

the_post_navigation( [
	'prev_text' => __( '&laquo; %title' ),
	'next_text' => __( '%title &raquo;' )
] );
