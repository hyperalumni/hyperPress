<?php
namespace HyperPress\Shortcode\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * [hyperpress_noautop]: keeps wpautop and wptexturize out of the enclosed content.
 *
 * `stash()` runs on `the_content` at priority 9, ahead of core's wpautop and wptexturize (priority 10), and swaps
 * each block for a placeholder. `restore()` runs at priority 10 but registered after core's filters, so it comes
 * after wpautop, and puts the original block back. `do_shortcode` (priority 11) then renders it.
 */
final class NoAutop {

	private const TAG = 'hyperpress_noautop';

	/**
	 * Original blocks keyed by placeholder.
	 *
	 * @var array<string, string>
	 */
	private static array $store = array();

	private static string $prefix = '';

	public static function register(): void {
		add_shortcode( self::TAG, array( self::class, 'render' ) );
		add_filter( 'the_content', array( self::class, 'stash' ), 9 );
		add_filter( 'the_content', array( self::class, 'restore' ), 10 );
	}

	public static function stash( string $content ): string {
		if ( false === strpos( $content, '[' . self::TAG ) ) {
			return $content;
		}

		$result = preg_replace_callback(
			'/' . get_shortcode_regex( array( self::TAG ) ) . '/',
			static function ( array $found ): string {
				// An escaped tag, [[tag]...[/tag]], is literal text and stays for do_shortcode() to unescape.
				if ( '[' === $found[1] && ']' === $found[6] ) {
					return $found[0];
				}

				if ( '' === self::$prefix ) {
					self::$prefix = 'hpnoautop' . bin2hex( random_bytes( 4 ) );
				}

				// The trailing letter keeps index 1 from matching inside index 10.
				$placeholder                 = self::$prefix . count( self::$store ) . 'x';
				self::$store[ $placeholder ] = $found[0];

				return $placeholder;
			},
			$content
		);

		return is_string( $result ) ? $result : $content;
	}

	public static function restore( string $content ): string {
		if ( array() === self::$store ) {
			return $content;
		}

		$store       = self::$store;
		self::$store = array();

		$content = (string) preg_replace_callback(
			'#(?:<p>\s*)?(' . preg_quote( self::$prefix, '#' ) . '\d+x)(?:\s*</p>)?#',
			static function ( array $found ) use ( $store ): string {
				return $store[ $found[1] ] ?? $found[0];
			},
			$content
		);

		return $content;
	}

	public static function render( $atts = null, $content = '' ): string {
		return do_shortcode( (string) $content );
	}
}
