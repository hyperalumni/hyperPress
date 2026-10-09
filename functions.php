<?php
/**
 * Author: Ole Fredrik Lie
 * URL: http://olefredrik.com
 *
 * FoundationPress functions and definitions
 *
 * Set up the theme and provides some helper functions, which are used in the
 * theme as custom template tags. Others are attached to action and filter
 * hooks in WordPress to change core functionality.
 *
 * @link https://codex.wordpress.org/Theme_Development
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */

// Don't load directly.
defined( 'ABSPATH' ) || exit;
/** Various clean up functions */
require_once get_template_directory() . '/library/cleanup.php';

/** Required for Foundation to work properly */
require_once get_template_directory() . '/library/foundation.php';

/** Format comments */
require_once get_template_directory() . '/library/class-hyperpress-comments.php';

/** Register all navigation menus */
require_once get_template_directory() . '/library/navigation.php';

/** Wrappers for plugin functions (Meta Box, seasons) */
require_once get_template_directory() . '/library/helpers.php';

/** Add Breadcrumbs */
require_once get_template_directory() . '/library/breadcrumbs.php';

/** Add Banner */
require_once get_template_directory() . '/library/banner.php';

/** Add menu walkers for top-bar and off-canvas */
require_once get_template_directory() . '/library/class-hyperpress-top-bar-walker.php';
require_once get_template_directory() . '/library/class-hyperpress-mobile-walker.php';

/** Create widget areas in sidebar and footer */
require_once get_template_directory() . '/library/widget-areas.php';

/** Enqueue scripts */
require_once get_template_directory() . '/library/enqueue-scripts.php';

/** Add theme support */
require_once get_template_directory() . '/library/theme-support.php';

/** Seed default logo and site icon */
require_once get_template_directory() . '/library/default-branding.php';

/** Add Nav Options to Customer */
require_once get_template_directory() . '/library/custom-nav.php';

/** Change WP's sticky post class */
require_once get_template_directory() . '/library/sticky-posts.php';

/** Configure responsive image sizes */
require_once get_template_directory() . '/library/responsive-images.php';

/** Gutenberg editor support */
require_once get_template_directory() . '/library/gutenberg.php';

/** TGMPA plugins */
require_once get_template_directory() . '/library/plugins.php';

/** WP Customize Options */
require_once get_template_directory() . '/library/customize.php';

/** HYPER Colors in wp-head */
require_once get_template_directory() . '/library/root-colors.php';

/** Add theme metaboxes */
require_once get_template_directory() . '/library/metaboxes.php';

/** If your site requires protocol relative url's for theme assets, uncomment the line below */
// phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- theme audit
// require_once( 'library/class-hyperpress-protocol-relative-theme-assets.php' );
