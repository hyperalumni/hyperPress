<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Newsletter
 * Description:         Custom Post Type and Taxonomy for Newsletters for hyperPress
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
require_once( __DIR__ . '/lib/metaboxes.php' );
require_once( __DIR__ . '/lib/shortcodes.php' );

if ( ! function_exists( 'hyperpress_add_newsletter_content_template' ) ):
	function hyperpress_add_newsletter_content_template( $content_template ): string {
		global $post;
		if ( $post->post_type === 'newsletter' ) {
			$custom_content_template = dirname( __FILE__ ) . '/lib/content.php';
			if ( file_exists( $custom_content_template ) ) {
				return $custom_content_template;
			}
		}

		return $content_template;
	}

	add_filter( 'content_template', 'hyperpress_add_newsletter_content_template' );
endif;

if ( ! function_exists( 'hyperpress_add_newsletter_single_template' ) ):
	function hyperpress_add_newsletter_single_template( $single_template ): string {
		global $post;
		if ( $post->post_type === 'newsletter' ) {
			$custom_single_template = dirname( __FILE__ ) . '/lib/single.php';
			if ( file_exists( $custom_single_template ) ) {
				return $custom_single_template;
			}
		}

		return $single_template;
	}

	add_filter( 'single_template', 'hyperpress_add_newsletter_single_template' );
endif;

if ( ! function_exists( 'hyperpress_add_newsletter_archive_template' ) ):
	function hyperpress_add_newsletter_archive_template( $archive_template ): string {
		global $post;
		if ( $post->post_type === 'newsletter' ) {
			$custom_archive_template = dirname( __FILE__ ) . '/lib/archive.php';
			if ( file_exists( $custom_archive_template ) ) {
				return $custom_archive_template;
			}
		}


		return $archive_template;
	}

	add_filter( 'archive_template', 'hyperpress_add_newsletter_archive_template' );
endif;

if ( ! function_exists( 'hyperpress_add_newsletter_taxonomy_template' ) ):
	function hyperpress_add_newsletter_taxonomy_template( $taxonomy_template ): string {
		global $post;
		if ( $post->post_type === 'newsletter' ) {
			$custom_taxonomy_template = dirname( __FILE__ ) . '/lib/taxonomy.php';
			if ( file_exists( $custom_taxonomy_template ) ) {
				return $custom_taxonomy_template;
			}
		}


		return $taxonomy_template;
	}

	add_filter( 'taxonomy_template', 'hyperpress_add_newsletter_taxonomy_template' );
endif;