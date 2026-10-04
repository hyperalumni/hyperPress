<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

add_action( 'after_setup_theme', 'hyperpress_theme_support' );

/**
 * Register theme support for languages, menus, post-thumbnails, post-formats etc.
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */
function hyperpress_theme_support() {
	// Add language support
	load_theme_textdomain( 'hyperpress', get_template_directory() . '/languages' );

	// Switch default core markup for search form, comment form, and comments to output valid HTML5
	add_theme_support(
		'html5',
        array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
		)
	);

	add_theme_support(
        'custom-logo',
        array(
			'flex-height'          => true,
			'flex-width'           => true,
			'header-text'          => array( 'site-title', 'site-description' ),
			'unlink-homepage-logo' => false,
		)
        );
	add_theme_support( 'responsive-embeds' );

	// Add menu support
	add_theme_support( 'menus' );

	// Let WordPress manage the document title
	add_theme_support( 'title-tag' );

	// Add post thumbnail support: http://codex.wordpress.org/Post_Thumbnails
	add_theme_support( 'post-thumbnails' );

	// RSS thingy
	add_theme_support( 'automatic-feed-links' );

	// Add post formats support: http://codex.wordpress.org/Post_Formats
	add_theme_support(
        'post-formats',
        array(
			'aside',
			'gallery',
			'link',
			'image',
			'quote',
			'status',
			'video',
			'audio',
			'chat',
		)
        );

	// Additional theme support for woocommerce 3.0.+
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );

	add_theme_support( 'editor-styles' );

	add_editor_style( get_stylesheet_directory_uri() . '/dist/assets/css/editor.css' );

	// Add foundation.css as editor style https://codex.wordpress.org/Editor_Style
	// add_editor_style( 'dist/assets/css/' . hyperpress_asset_path( 'editor.css' ) );
}
