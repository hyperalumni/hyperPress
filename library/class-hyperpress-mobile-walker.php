<?php
/**
 * Customize the output of menus for Foundation mobile walker
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
/**
 * Big thanks to Brett Mason (https://github.com/brettsmason) for the awesome walker
 */

if ( ! class_exists( 'HyperPress_Theme_Mobile_Walker' ) ) :
	class HyperPress_Theme_Mobile_Walker extends Walker_Nav_Menu {
		public function start_lvl( &$output, $depth = 0, $args = array() ) {
			$indent  = str_repeat( "\t", $depth );
			$output .= "\n$indent<ul class=\"vertical nested menu\">\n";
		}
	}
endif;
