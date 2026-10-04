<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_action( 'widgets_init', 'hyperpress_sidebar_widgets' );

/**
 * Register widget areas
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */
function hyperpress_sidebar_widgets() {
	register_sidebar(
		array(
			'id'            => 'sidebar-widgets',
			'name'          => __( 'Sidebar widgets', 'hyperpress' ),
			'description'   => __( 'Drag widgets to this sidebar container.', 'hyperpress' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section><hr />',
			'before_title'  => '<h4>',
			'after_title'   => '</h4>',
		)
	);

	register_sidebar(
		array(
			'id'            => 'footer-widgets',
			'name'          => __( 'Footer widgets', 'hyperpress' ),
			'description'   => __( 'Drag widgets to this footer container', 'hyperpress' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h6>',
			'after_title'   => '</h6>',
		)
	);
}
