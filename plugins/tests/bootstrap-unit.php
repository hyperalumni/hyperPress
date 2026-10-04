<?php
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'HYPERPRESS_TESTING', true ); // Lets hyperpress-utils/src files load without WordPress.

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) { // phpcs:ignore
		return $text;
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
