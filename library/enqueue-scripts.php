<?php
/**
 * Enqueue all styles and scripts
 *
 * Learn more about enqueue_script: {@link https://codex.wordpress.org/Function_Reference/wp_enqueue_script}
 * Learn more about enqueue_style: {@link https://codex.wordpress.org/Function_Reference/wp_enqueue_style }
 *
 * @package FoundationPress
 * @since FoundationPress 1.0.0
 */


// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}
// Check to see if rev-manifest exists for CSS and JS static asset revisioning
//https://github.com/sindresorhus/gulp-rev/blob/master/integration.md

if ( ! function_exists( 'hyperpress_asset_path' ) ) :
	function hyperpress_asset_path( $filename ): string {
		$filename_split = explode( '.', $filename );
		$dir            = end( $filename_split );
		$manifest_path  = dirname( dirname( __FILE__ ) ) . '/dist/assets/' . $dir . '/rev-manifest.json';

		if ( file_exists( $manifest_path ) ) {
			$manifest = json_decode( file_get_contents( $manifest_path ), true );
		} else {
			$manifest = array();
		}

		if ( array_key_exists( $filename, $manifest ) ) {
			return $manifest[ $filename ];
		}

		return $filename;
	}
endif;

$hyperPressVersion    = '2.11.1';
$foundationVersion    = '6.9.0';
$fontAwesomeVersion   = '7.3.1';
$jqueryVersion        = '3.7.1';
$jqueryMigrateVersion = '3.6.0';

if ( ! function_exists( 'hyperpress_scripts' ) ) :
	add_action( 'wp_enqueue_scripts', 'hyperpress_scripts' );

	function hyperpress_scripts(): void {
		global $hyperPressVersion, $foundationVersion, $fontAwesomeVersion, $jqueryVersion, $jqueryMigrateVersion;

		// Deregister the jquery version bundled with WordPress.
		wp_deregister_script( 'jquery' );
		// Deregister the jquery-migrate version bundled with WordPress.
		wp_deregister_script( 'jquery-migrate' );

		// Register the Google Fonts
//		wp_register_style( 'oswald', 'https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700' );
		wp_register_style( 'oswald', 'https://fonts.googleapis.com/css2?family=Oswald:wght@200..700&display=swa' );
		wp_register_style( 'opensans', 'https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap' );


		// The official plugin is not active, so add FontAwesome from CDN.
		if ( ! defined( 'FONT_AWESOME_OFFICIAL_LOADED' ) ) {
			// Enqueue FontAwesome from CDN.
			wp_enqueue_script( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/' . $fontAwesomeVersion . '/js/all.min.js', array(), $fontAwesomeVersion, true );
			wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/' . $fontAwesomeVersion . '/css/all.min.css', array(), $fontAwesomeVersion, true ); // needs the 'true' for media, or else the icons load funny
		}

		// Enqueue the main Stylesheet.
		wp_enqueue_style( 'main-stylesheet', get_template_directory_uri() . '/dist/assets/css/' . hyperpress_asset_path( 'app.css' ), array(
			'oswald',
			'opensans',
			'fontawesome'
		), $hyperPressVersion );

		// CDN hosted jQuery placed in the header, as some plugins require that jQuery is loaded in the header.
		wp_enqueue_script( 'jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/' . $jqueryVersion . '/jquery.min.js', array(), $jqueryVersion, false );

		// CDN hosted jQuery migrate for compatibility with jQuery 4.x
		wp_register_script( 'jquery-migrate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/' . $jqueryMigrateVersion . '/jquery-migrate.min.js', array( 'jquery' ), $jqueryMigrateVersion, false );

		// Enqueue jQuery migrate. Uncomment the line below to enable.
		// wp_enqueue_script( 'jquery-migrate' );

		// Enqueue Foundation scripts
		wp_enqueue_script( 'foundation', get_template_directory_uri() . '/dist/assets/js/' . hyperpress_asset_path( 'foundation.js' ), array( 'jquery' ), $foundationVersion, true );
		wp_enqueue_script( 'main-script', get_template_directory_uri() . '/dist/assets/js/' . hyperpress_asset_path( 'app.js' ), array( 'foundation' ), $hyperPressVersion, true );

		// Add the comment-reply library on pages where it is necessary
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}
endif;

if ( ! function_exists( 'hyperpress_admin_scripts' ) ) :
	add_action( 'admin_enqueue_scripts', 'hyperpress_admin_scripts' );

	function hyperpress_admin_scripts(): void {
		global $hyperPressVersion, $jqueryVersion, $jqueryMigrateVersion;
		// Deregister the jquery version bundled with WordPress.
		wp_deregister_script( 'jquery' );
		// Deregister the jquery-migrate version bundled with WordPress.
		wp_deregister_script( 'jquery-migrate' );

		// CDN hosted jQuery placed in the header, as some plugins require that jQuery is loaded in the header.
		wp_enqueue_script( 'jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/' . $jqueryVersion . '/jquery.min.js', array(), $jqueryVersion, false );

		// CDN hosted jQuery migrate for compatibility with jQuery 3.x
		wp_register_script( 'jquery-migrate', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-migrate/' . $jqueryMigrateVersion . '/jquery-migrate.min.js', array( 'jquery' ), $jqueryMigrateVersion, false );

		// Enqueue jQuery migrate. Uncomment the line below to enable.
		// wp_enqueue_script( 'jquery-migrate' );

		wp_register_style( 'wp-admin-svg-support', get_template_directory_uri() . '/dist/assets/css/' . hyperpress_asset_path( 'svg-wp-admin.css' ), array(), $hyperPressVersion, 'screen' );
		// only load svg support on a screen to edit a post/page/custom-post-type
		if ( get_current_screen()->base == 'post' ) {
			wp_enqueue_style( 'wp-admin-svg-support' );
		}
	}
endif;
