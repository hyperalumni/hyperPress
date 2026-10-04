<?php
/**
 * @wordpress-plugin
 * Plugin Name:         hyperPress Sponsor
 * Plugin URI:          https://hyperalumni.org
 * GitHub Plugin URI:   https://github.com/hyperalumni/hyperPress
 * Description:         Custom Post Type and Taxonomy for Sponsors for hyperPress
 * Version:             2026.0.0
 * Requires at least:   6.9
 * Requires PHP:        8.3
 * Author:              Chris McGroarty
 * Author URI:          https://idesign.co
 * License:             MIT License
 * License URI:         http://www.opensource.org/licenses/mit-license.php
 * Text Domain:         hyperpress
 * Domain Path:         /languages
 * Update URI:          false
 * Requires Plugins:    hyperpress-season, meta-box-aio
 */

namespace HYPER_Press_Sponsor;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// require the definitions first
require_once( __DIR__ . '/lib/custom-post-type.php' );
require_once( __DIR__ . '/lib/custom-taxonomy.php' );
// then require the ancillary
require_once( __DIR__ . '/lib/banner.php' );
require_once( __DIR__ . '/lib/breadcrumbs.php' );
require_once( __DIR__ . '/lib/customize.php' );
require_once( __DIR__ . '/lib/meta-boxes.php' );
require_once( __DIR__ . '/lib/labels.php' );
require_once( __DIR__ . '/lib/shortcodes.php' );
require_once( __DIR__ . '/lib/widgets/load.php' );

if ( ! function_exists( '\HYPER_Press_Sponsor\hyperpress_add_sponsor_content_template' ) ):
	function hyperpress_add_sponsor_content_template( $content_template ): string {
		if ( get_post_type() === 'sponsor' ) {
			$custom_content_template = dirname( __FILE__ ) . '/lib/content.php';
			if ( file_exists( $custom_content_template ) ) {
				return $custom_content_template;
			}
		}

		return $content_template;
	}

	add_filter( 'content_template', '\HYPER_Press_Sponsor\hyperpress_add_sponsor_content_template' );
endif;

if ( ! function_exists( '\HYPER_Press_Sponsor\hyperpress_add_sponsor_archive_template' ) ):
	function hyperpress_add_sponsor_archive_template( $archive_template ): string {

		if ( get_post_type() === 'sponsor' ) {
			$archive_template = dirname( __FILE__ ) . '/lib/archive.php';
		}

		return $archive_template;
	}

	add_filter( 'archive_template', '\HYPER_Press_Sponsor\hyperpress_add_sponsor_archive_template' );
endif;