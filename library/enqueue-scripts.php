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
defined( 'ABSPATH' ) || exit;
// Check to see if rev-manifest exists for CSS and JS static asset revisioning
// https://github.com/sindresorhus/gulp-rev/blob/master/integration.md

function hyperpress_asset_path( $filename ): string {
	$filename_split = explode( '.', $filename );
	$dir            = end( $filename_split );
	$manifest_path  = dirname( __DIR__ ) . '/dist/assets/' . $dir . '/rev-manifest.json';

	if ( file_exists( $manifest_path ) ) {
		$manifest = json_decode( file_get_contents( $manifest_path ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- theme audit
	} else {
		$manifest = array();
	}

	if ( array_key_exists( $filename, $manifest ) ) {
		return $manifest[ $filename ];
	}

	return $filename;
}

$hyper_press_version    = '2.11.1';
$foundation_version     = '6.9.0';
$font_awesome_version   = '7.3.1';
$jquery_version         = '3.7.1';
$jquery_migrate_version = '3.6.0';
$cdn_base               = 'https://cdnjs.cloudflare.com/ajax/libs/';
$google_fonts_base      = 'https://fonts.googleapis.com/css2?';

/**
 * Subresource Integrity hashes for the CDN assets, keyed by URL (without the version query).
 * When a version above changes, the matching hash must change too (CdnAssetsTest fails otherwise).
 *
 * @return array<string,string>
 */
function hyperpress_cdn_integrity(): array {
	global $cdn_base, $font_awesome_version, $jquery_version, $jquery_migrate_version;

	$hashes = array(
		'3.7.1' => array( 'jquery/3.7.1/jquery.min.js' => 'sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==' ),
		'3.6.0' => array( 'jquery-migrate/3.6.0/jquery-migrate.min.js' => 'sha512-85Bbg32a7HJvvMRk6u3alDbnbN6OmCBzjpUHTTLltd4dVtTMGod9HsvjqHZHwqEZGcRHmb1bGn8f1WcUoIBPBw==' ),
		'7.3.1' => array(
			'font-awesome/7.3.1/js/all.min.js'   => 'sha512-2+f4MxT8KwN4tUzw6/hv9kxKiix603S9kmBcix+0y0dBhd6zdaPOV1Thf1DM886pFZG+cAtmshBi8UBpo6m3JA==',
			'font-awesome/7.3.1/css/all.min.css' => 'sha512-QeR2VH+lsBE5LSAe1Q5EnTBbe7XTBubt8dG93Y7gidSgdMCr8nVqKcfKAMyN96SV8KDbZVTDXChatu5G2KQGzg==',
		),
	);

	$map = array();
	foreach ( array( $jquery_version, $jquery_migrate_version, $font_awesome_version ) as $version ) {
		foreach ( $hashes[ $version ] ?? array() as $path => $hash ) {
			$map[ $cdn_base . $path ] = $hash;
		}
	}

	return $map;
}

/**
 * Adds integrity and crossorigin to the CDN script and style tags.
 *
 * @param string $tag Rendered tag.
 * @param string $handle Asset handle.
 * @param string $src Asset URL.
 */
function hyperpress_add_integrity( $tag, $handle, $src ): string {
	$url    = strtok( (string) $src, '?' );
	$hashes = hyperpress_cdn_integrity();

	if ( ! isset( $hashes[ $url ] ) ) {
		return $tag;
	}

	return str_replace( ' src=', ' integrity="' . esc_attr( $hashes[ $url ] ) . '" crossorigin="anonymous" src=', str_replace( ' href=', ' integrity="' . esc_attr( $hashes[ $url ] ) . '" crossorigin="anonymous" href=', $tag ) );
}
add_filter( 'script_loader_tag', 'hyperpress_add_integrity', 10, 3 );
add_filter( 'style_loader_tag', 'hyperpress_add_integrity', 10, 3 );

add_action( 'wp_enqueue_scripts', 'hyperpress_scripts' );

function hyperpress_scripts(): void {
	global $cdn_base, $google_fonts_base, $hyper_press_version, $foundation_version, $font_awesome_version, $jquery_version, $jquery_migrate_version;

	// Deregister the jquery version bundled with WordPress.
	wp_deregister_script( 'jquery' );
	// Deregister the jquery-migrate version bundled with WordPress.
	wp_deregister_script( 'jquery-migrate' );

	// Register the Google Fonts. They have no version (Google updates the files behind the URL), so the version is
	// null: WordPress then adds no ?ver= to the URL. The sniff cannot tell that from a forgotten version.
	wp_register_style( 'oswald', $google_fonts_base . 'family=Oswald:wght@200..700&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- third-party URL without a version.
	wp_register_style( 'opensans', $google_fonts_base . 'family=Open+Sans:ital,wght@0,300..800;1,300..800&display=swap', array(), null ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- third-party URL without a version.

	// The official plugin is not active, so add FontAwesome from CDN.
	if ( ! defined( 'FONT_AWESOME_OFFICIAL_LOADED' ) ) {
		// Enqueue FontAwesome from CDN.
		wp_enqueue_script( 'fontawesome', $cdn_base . 'font-awesome/' . $font_awesome_version . '/js/all.min.js', array(), $font_awesome_version, true );
		wp_enqueue_style( 'fontawesome', $cdn_base . 'font-awesome/' . $font_awesome_version . '/css/all.min.css', array(), $font_awesome_version, true ); // needs the 'true' for media, or else the icons load funny
	}

	// Enqueue the main Stylesheet.
	wp_enqueue_style(
        'main-stylesheet',
        get_template_directory_uri() . '/dist/assets/css/' . hyperpress_asset_path( 'app.css' ),
        array(
			'oswald',
			'opensans',
			'fontawesome',
		),
        $hyper_press_version
        );

	// CDN hosted jQuery placed in the header, as some plugins require that jQuery is loaded in the header.
	wp_enqueue_script( 'jquery', $cdn_base . 'jquery/' . $jquery_version . '/jquery.min.js', array(), $jquery_version, false );

	// CDN hosted jQuery migrate for compatibility with jQuery 4.x
	wp_register_script( 'jquery-migrate', $cdn_base . 'jquery-migrate/' . $jquery_migrate_version . '/jquery-migrate.min.js', array( 'jquery' ), $jquery_migrate_version, false );

	// Enqueue jQuery migrate. Uncomment the line below to enable.
	// wp_enqueue_script( 'jquery-migrate' );

	// Enqueue Foundation scripts
	wp_enqueue_script( 'foundation', get_template_directory_uri() . '/dist/assets/js/' . hyperpress_asset_path( 'foundation.js' ), array( 'jquery' ), $foundation_version, true );
	wp_enqueue_script( 'main-script', get_template_directory_uri() . '/dist/assets/js/' . hyperpress_asset_path( 'app.js' ), array( 'foundation' ), $hyper_press_version, true );

	// Add the comment-reply library on pages where it is necessary
	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

add_action( 'admin_enqueue_scripts', 'hyperpress_admin_scripts' );

function hyperpress_admin_scripts(): void {
	global $hyper_press_version;

	// WordPress's own jQuery is left alone in wp-admin: replacing it drops jquery-core/jquery-migrate
	// for core and plugin scripts and would run third-party CDN code in admin sessions.

	wp_register_style( 'wp-admin-svg-support', get_template_directory_uri() . '/dist/assets/css/' . hyperpress_asset_path( 'svg-wp-admin.css' ), array(), $hyper_press_version, 'screen' );
	// only load svg support on a screen to edit a post/page/custom-post-type
	if ( get_current_screen()->base == 'post' ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- theme audit
		wp_enqueue_style( 'wp-admin-svg-support' );
	}
}
