<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Sponsor
 * Description:         Custom Post Type and Taxonomy for Sponsors for hyperPress
 * Version:             1.0.0
 * Requires at least:   5.2
 * Requires PHP:        7.2
 * Author:              Chris McGroarty
 * Author URI:          https://idesign.co
 * Text Domain:         hyperpress
 * Requires Plugins: meta-box-aio
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// require the definitions first
require_once( __DIR__ . '/lib/custom-post-type.php' );
require_once( __DIR__ . '/lib/custom-taxonomy.php' );
// then require the ancillary
require_once( __DIR__ . '/lib/banner.php' );
require_once( __DIR__ . '/lib/breadcrumb.php' );
require_once( __DIR__ . '/lib/customize.php' );
require_once( __DIR__ . '/lib/shortcodes.php' );

if ( ! function_exists( 'hyperpress_add_sponsor_single_template' ) ):
	function hyperpress_add_sponsor_single_template( $single_template ) {
		global $post;
		if ( $post->post_type === 'sponsor' ) {
			$single_template = dirname( __FILE__ ) . '/lib/single.php';
		}

		return $single_template;
	}
	add_filter( 'single_template', 'hyperpress_add_sponsor_single_template' );
endif;

if ( ! function_exists( 'hyperpress_add_sponsor_archive_template' ) ):
	function hyperpress_add_sponsor_archive_template( $archive_template ): string {
		global $post;
		if ( $post->post_type === 'sponsor' ) {
			$archive_template = dirname( __FILE__ ) . '/lib/archive.php';
		}

		return $archive_template;
	}
	add_filter( 'archive_template', 'hyperpress_add_sponsor_archive_template' );
endif;

if ( ! function_exists( 'hyperpress_add_sponsor_taxonomy_template' ) ):
	function hyperpress_add_sponsor_taxonomy_template( $taxonomy_template ): string {
		global $post;
		if ( $post->post_type === 'sponsor' ) {
			$taxonomy_template = dirname( __FILE__ ) . '/lib/taxonomy.php';
		}

		return $taxonomy_template;
	}
	add_filter( 'taxonomy_template', 'hyperpress_add_sponsor_taxonomy_template' );
endif;