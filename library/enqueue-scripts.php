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


// Check to see if rev-manifest exists for CSS and JS static asset revisioning
//https://github.com/sindresorhus/gulp-rev/blob/master/integration.md

if ( ! function_exists( 'foundationpress_asset_path' ) ) :
	function foundationpress_asset_path( $filename ) {
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

$hyperPressVersion  = '2.11.1';
$foundationVersion  = '6.9.0';
$fontAwesomeVersion = '7.0.1';
$timeCirclesVersion = '1.5.3';

if ( ! function_exists( 'foundationpress_scripts' ) ) :
	function foundationpress_scripts() {
		global $hyperPressVersion, $foundationVersion, $fontAwesomeVersion, $timeCirclesVersion;

		// Deregister the jquery version bundled with WordPress.
		wp_deregister_script( 'jquery' );
		// Deregister the jquery-migrate version bundled with WordPress.
		wp_deregister_script( 'jquery-migrate' );


		// Enqueue the main Stylesheet.
		wp_enqueue_style( 'main-stylesheet', get_template_directory_uri() . '/dist/assets/css/' . foundationpress_asset_path( 'app.css' ), array(
			'oswald',
			'opensans',
			'fontawesome'
		), $hyperPressVersion, 'all' );


		// Register the Google Fonts
		wp_register_style( 'oswald', 'https://fonts.googleapis.com/css?family=Oswald:300,400,500,600,700', array(), 'all' );
		wp_register_style( 'opensans', 'https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,500italic,600italic,700italic,800italic,300,400,500,600,700,800', array(), 'all' );


		// CDN hosted jQuery placed in the header, as some plugins require that jQuery is loaded in the header.
		wp_enqueue_script( 'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js', array(), '3.7.1', false );


		// CDN hosted jQuery migrate for compatibility with jQuery 3.x
		wp_register_script( 'jquery-migrate', 'https://code.jquery.com/jquery-migrate-3.6.0.min.js', array( 'jquery' ), '3.6.0', false );

		// Enqueue jQuery migrate. Uncomment the line below to enable.
		// wp_enqueue_script( 'jquery-migrate' );

		// Enqueue Foundation scripts
		wp_enqueue_script( 'foundation', get_template_directory_uri() . '/dist/assets/js/' . foundationpress_asset_path( 'foundation.js' ), array( 'jquery' ), $foundationVersion, true );
		wp_enqueue_script( 'main-script', get_template_directory_uri() . '/dist/assets/js/' . foundationpress_asset_path( 'app.js' ), array( 'foundation' ), $hyperPressVersion, true );

		// Enqueue FontAwesome from CDN. Uncomment the line below if you need FontAwesome.
		wp_enqueue_script( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/js/all.min.js', array(), $fontAwesomeVersion, true );
		wp_enqueue_style( 'fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css', array(), $fontAwesomeVersion, true );

		// Add the comment-reply library on pages where it is necessary
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}
	}

	add_action( 'wp_enqueue_scripts', 'foundationpress_scripts' );
endif;

if ( ! function_exists( 'foundationpress_admin_scripts' ) ) :
	function foundationpress_admin_scripts() {
		global $hyperPressVersion;
		// Deregister the jquery version bundled with WordPress.
		wp_deregister_script( 'jquery' );
		// Deregister the jquery-migrate version bundled with WordPress.
		wp_deregister_script( 'jquery-migrate' );

		// CDN hosted jQuery placed in the header, as some plugins require that jQuery is loaded in the header.
		wp_enqueue_script( 'jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js', array(), '3.7.1', false );

		// CDN hosted jQuery migrate for compatibility with jQuery 3.x
		wp_register_script( 'jquery-migrate', 'https://code.jquery.com/jquery-migrate-3.6.0.min.js', array( 'jquery' ), '3.6.0', false );



	}

	add_action( 'admin_enqueue_scripts', 'foundationpress_admin_scripts' );
endif;
