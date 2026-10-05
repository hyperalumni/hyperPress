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

/**
 * Map a source asset name to its revisioned filename via the gulp-rev manifest.
 *
 * The manifest is read once per request and an unreadable or non-array manifest is ignored.
 *
 * @param string $filename     Asset name, e.g. 'app.css'.
 * @param string $manifest_dir Base directory holding the per-type manifests; defaults to dist/assets.
 */
function hyperpress_asset_path( $filename, string $manifest_dir = '' ): string {
	static $manifests = array();

	$filename_split = explode( '.', $filename );
	$dir            = end( $filename_split );
	if ( '' === $manifest_dir ) {
		$manifest_dir = dirname( __DIR__ ) . '/dist/assets';
	}
	$manifest_path = $manifest_dir . '/' . $dir . '/rev-manifest.json';

	if ( ! array_key_exists( $manifest_path, $manifests ) ) {
		$manifest = array();
		if ( file_exists( $manifest_path ) ) {
			$decoded = wp_json_file_decode( $manifest_path, array( 'associative' => true ) );
			if ( is_array( $decoded ) ) {
				$manifest = $decoded;
			}
		}
		$manifests[ $manifest_path ] = $manifest;
	}

	if ( array_key_exists( $filename, $manifests[ $manifest_path ] ) ) {
		return (string) $manifests[ $manifest_path ][ $filename ];
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
 * URL of a file on cdnjs, built from the version variables above.
 *
 * @param string $library cdnjs library name, e.g. 'jquery'.
 * @param string $file    Path inside the library version, e.g. 'jquery.min.js'.
 */
function hyperpress_cdn_url( string $library, string $file ): string {
	global $cdn_base, $font_awesome_version, $jquery_version, $jquery_migrate_version;

	$versions = array(
		'font-awesome'   => $font_awesome_version,
		'jquery'         => $jquery_version,
		'jquery-migrate' => $jquery_migrate_version,
	);

	return $cdn_base . $library . '/' . $versions[ $library ] . '/' . $file;
}

/**
 * Subresource Integrity hashes for the CDN assets, keyed by URL (without the version query).
 *
 * The hashes are keyed by library and version: when a version variable above changes without a new hash here,
 * the asset is served without integrity and CdnAssetsTest fails.
 *
 * @return array<string,string>
 */
function hyperpress_cdn_integrity(): array {
	global $font_awesome_version, $jquery_version, $jquery_migrate_version;

	$hashes = array(
		'jquery'         => array(
			'3.7.1' => array( 'jquery.min.js' => 'sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==' ),
		),
		'jquery-migrate' => array(
			'3.6.0' => array( 'jquery-migrate.min.js' => 'sha512-85Bbg32a7HJvvMRk6u3alDbnbN6OmCBzjpUHTTLltd4dVtTMGod9HsvjqHZHwqEZGcRHmb1bGn8f1WcUoIBPBw==' ),
		),
		'font-awesome'   => array(
			'7.3.1' => array(
				'js/all.min.js'   => 'sha512-2+f4MxT8KwN4tUzw6/hv9kxKiix603S9kmBcix+0y0dBhd6zdaPOV1Thf1DM886pFZG+cAtmshBi8UBpo6m3JA==',
				'css/all.min.css' => 'sha512-QeR2VH+lsBE5LSAe1Q5EnTBbe7XTBubt8dG93Y7gidSgdMCr8nVqKcfKAMyN96SV8KDbZVTDXChatu5G2KQGzg==',
			),
		),
	);

	$in_use = array(
		'jquery'         => $jquery_version,
		'jquery-migrate' => $jquery_migrate_version,
		'font-awesome'   => $font_awesome_version,
	);

	$map = array();
	foreach ( $in_use as $library => $version ) {
		foreach ( $hashes[ $library ][ $version ] ?? array() as $file => $hash ) {
			$map[ hyperpress_cdn_url( $library, $file ) ] = $hash;
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

/**
 * Style handles the main stylesheet depends on.
 *
 * WordPress skips a style whose dependency is not registered, so 'fontawesome' is only listed when it is
 * (the theme does not register it when the official Font Awesome plugin is active).
 *
 * @param bool|null $fontawesome_registered Whether the 'fontawesome' style is registered; null checks WordPress.
 * @return string[]
 */
function hyperpress_main_style_deps( ?bool $fontawesome_registered = null ): array {
	$fontawesome_registered ??= wp_style_is( 'fontawesome', 'registered' );

	$deps = array( 'oswald', 'opensans' );
	if ( $fontawesome_registered ) {
		$deps[] = 'fontawesome';
	}

	return $deps;
}

function hyperpress_scripts(): void {
	global $google_fonts_base, $hyper_press_version, $foundation_version, $font_awesome_version, $jquery_version, $jquery_migrate_version;

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
		wp_enqueue_script( 'fontawesome', hyperpress_cdn_url( 'font-awesome', 'js/all.min.js' ), array(), $font_awesome_version, true );
		wp_enqueue_style( 'fontawesome', hyperpress_cdn_url( 'font-awesome', 'css/all.min.css' ), array(), $font_awesome_version, true ); // needs the 'true' for media, or else the icons load funny
	}

	// Enqueue the main Stylesheet.
	wp_enqueue_style(
        'main-stylesheet',
        get_template_directory_uri() . '/dist/assets/css/' . hyperpress_asset_path( 'app.css' ),
        hyperpress_main_style_deps(),
        $hyper_press_version
        );

	// CDN hosted jQuery placed in the header, as some plugins require that jQuery is loaded in the header.
	wp_enqueue_script( 'jquery', hyperpress_cdn_url( 'jquery', 'jquery.min.js' ), array(), $jquery_version, false );

	// CDN hosted jQuery migrate for compatibility with jQuery 4.x
	wp_register_script( 'jquery-migrate', hyperpress_cdn_url( 'jquery-migrate', 'jquery-migrate.min.js' ), array( 'jquery' ), $jquery_migrate_version, false );

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
	$screen = get_current_screen();
	if ( $screen && 'post' === $screen->base ) {
		wp_enqueue_style( 'wp-admin-svg-support' );
	}
}
