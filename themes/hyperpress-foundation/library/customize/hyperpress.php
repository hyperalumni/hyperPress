<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', 'hyperpress_customize_section' );

function hyperpress_customize_section( $wp_customize ): void {
	$wp_customize->add_section(
        'hyperpress',
        array(
			'title'    => 'hyperPress',
			'priority' => 105, // Before Widgets.
		)
        );
}
