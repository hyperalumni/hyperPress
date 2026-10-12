<?php
namespace HyperPress\Shortcode\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class Raw {

	public static function register(): void {
		add_shortcode( 'hyperpress_raw', array( self::class, 'render' ) );
	}

	public static function render( $atts = null, $content = '' ): array|string|null {
		$content = (string) $content;

		$content = do_shortcode( shortcode_unautop( $content ) );

		return preg_replace( '#^<\/p>|^<br \/>|<p>$#', '', $content );
	}
}
