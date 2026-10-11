<?php
// Don't load directly.
defined( 'ABSPATH' ) || exit;

/**
 * URL of the logo shown on the login page: the site logo, else the bundled SVG.
 */
function hyperpress_login_logo_url(): string {
	$url = wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' );

	if ( is_string( $url ) && '' !== $url ) {
		return $url;
	}

	return get_template_directory_uri() . '/library/branding-assets/logo.svg';
}

/**
 * Inline CSS that swaps the WordPress logo on the login page for the given image.
 *
 * @param string $url Logo image URL.
 */
function hyperpress_login_logo_css( string $url ): string {
	return sprintf(
		'.login h1 a { background-image: url( "%s" ); background-size: contain; background-position: center; width: 100%%; height: 84px; }',
		esc_url_raw( $url )
	);
}

/**
 * Add the logo CSS to the login page.
 */
function hyperpress_login_enqueue_styles(): void {
	wp_add_inline_style( 'login', hyperpress_login_logo_css( hyperpress_login_logo_url() ) );
}

/**
 * Point the login logo link at the site instead of wordpress.org.
 */
function hyperpress_login_header_url(): string {
	return home_url( '/' );
}

/**
 * Use the site name as the login logo link text.
 */
function hyperpress_login_header_text(): string {
	return get_bloginfo( 'name' );
}

add_action( 'login_enqueue_scripts', 'hyperpress_login_enqueue_styles' );
add_filter( 'login_headerurl', 'hyperpress_login_header_url' );
add_filter( 'login_headertext', 'hyperpress_login_header_text' );
