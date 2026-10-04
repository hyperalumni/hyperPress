<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Newsletter
 * Description:         Custom Post Type and Taxonomy for Newsletters for hyperPress
 * Version:             2026.0.0
 * Requires at least:   6.9
 * Requires PHP:        8.3
 * Author:              Chris McGroarty
 * Author URI:          https://idesign.co
 * Text Domain:         hyperpress
 * Requires Plugins:    hyperpress-season, meta-box-aio
 */

namespace HYPER_Press_Newsletter;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
// require the definitions first
require_once( __DIR__ . '/lib/custom-post-type.php' );
// then require the ancillary
require_once( __DIR__ . '/lib/banner.php' );
require_once( __DIR__ . '/lib/breadcrumbs.php' );
require_once( __DIR__ . '/lib/customize.php' );
require_once( __DIR__ . '/lib/labels.php' );
require_once( __DIR__ . '/lib/meta-boxes.php' );
require_once( __DIR__ . '/lib/shortcodes.php' );

if ( ! function_exists( '\HYPER_Press_Newsletter\hyperpress_add_newsletter_content_template' ) ):
	function hyperpress_add_newsletter_content_template( $content_template ): string {
		if ( get_post_type() === 'newsletter' ) {
			$custom_content_template = dirname( __FILE__ ) . '/lib/content.php';
			if ( file_exists( $custom_content_template ) ) {
				return $custom_content_template;
			}
		}

		return $content_template;
	}

	add_filter( 'content_template', '\HYPER_Press_Newsletter\hyperpress_add_newsletter_content_template' );
endif;