<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

the_post_navigation(
    array(
		'prev_text' => __( '&laquo; %title', 'hyperpress' ),
		'next_text' => __( '%title &raquo;', 'hyperpress' ),
	)
    );
