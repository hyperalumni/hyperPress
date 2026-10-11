<?php
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'HYPERPRESS_TESTING', true ); // Lets hyperpress-utils/src files load without WordPress.

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore
		return $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { // phpcs:ignore
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'sanitize_title' ) ) {
	// Minimal stand-in for core: lowercase, non [a-z0-9_-] become dashes, dashes collapse, ends trimmed.
	function sanitize_title( $title ) { // phpcs:ignore
		return trim( (string) preg_replace( '/-+/', '-', (string) preg_replace( '/[^a-z0-9_-]/', '-', strtolower( (string) $title ) ) ), '-' );
	}
}

if ( ! function_exists( 'sanitize_html_class' ) ) {
	// Minimal stand-in for core: keeps only [A-Za-z0-9_-].
	function sanitize_html_class( $classname, $fallback = '' ) { // phpcs:ignore
		$sanitized = (string) preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $classname );
		return '' !== $sanitized ? $sanitized : $fallback;
	}
}

$hyperpress_utils_autoload = dirname( __DIR__ ) . '/hyperpress-utils/src/';
spl_autoload_register(
	static function ( string $class_name ) use ( $hyperpress_utils_autoload ): void {
		$prefix = 'HyperPress\\Utils\\';
		if ( 0 === strpos( $class_name, $prefix ) ) {
			$file = $hyperpress_utils_autoload . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
			if ( is_file( $file ) ) {
				require_once $file;
			}
		}
	}
);

$hyperpress_season_autoload = dirname( __DIR__ ) . '/hyperpress-season/src/';
spl_autoload_register(
	static function ( string $class_name ) use ( $hyperpress_season_autoload ): void {
		$prefix = 'HyperPress\\Season\\';
		if ( 0 === strpos( $class_name, $prefix ) ) {
			$file = $hyperpress_season_autoload . str_replace( '\\', '/', substr( $class_name, strlen( $prefix ) ) ) . '.php';
			if ( is_file( $file ) ) {
				require_once $file;
			}
		}
	}
);
