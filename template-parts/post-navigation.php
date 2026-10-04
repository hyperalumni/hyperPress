<?php
// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

the_post_navigation(
    array(
		'prev_text' => __( '&laquo; %title' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- theme audit
		'next_text' => __( '%title &raquo;' ), // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- theme audit
	)
    );
