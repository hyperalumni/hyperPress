<?php
namespace HyperPress\Example;

defined( 'ABSPATH' ) || exit;

final class Plugin {

	public const STYLE_HANDLE = 'hp-example-uikit';

	private const MAIN_FILE = 'hyperpress-example.php';
	private const STYLE     = 'dist/uikit.css';

	public static function boot(): void {
		Shortcode::register();
		add_action( 'wp_enqueue_scripts', array( self::class, 'register_style' ) );
	}

	/**
	 * Register only; the shortcode enqueues the stylesheet when it renders.
	 */
	public static function register_style(): void {
		$dir     = dirname( __DIR__ );
		$version = is_file( $dir . '/' . self::STYLE ) ? (string) filemtime( $dir . '/' . self::STYLE ) : '2026.0.0';

		wp_register_style(
			self::STYLE_HANDLE,
			plugins_url( self::STYLE, $dir . '/' . self::MAIN_FILE ),
			array(),
			$version
		);
	}
}
