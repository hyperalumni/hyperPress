<?php
namespace HyperPress\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Inline SVG icons for socials that Font Awesome has no suitable glyph for (the real FIRST and Blue Alliance logos).
 *
 * To add another custom icon: drop a single-colour `assets/icons/<name>.svg` (lowercase letters, digits, `-` and `_`;
 * a `viewBox`, no fixed width/height, every fill `currentColor`), then set `'svg' => '<name>'` (and no `icon`) on the
 * social in {@see Links::all()}. Font Awesome 7 gives every icon a fixed width (`var(--fa-width, 1.25em)`), so a wide logo
 * is squeezed into it: add an optional `'svg_width' => '<n>em'` (e.g. `1.45em`) on the social to widen it, which is
 * printed as `--fa-width` on the `<svg>`. Only a minimal set of SVG elements and attributes survives sanitizing, see ALLOWED_HTML.
 */
final class Icons {

	/**
	 * The only SVG elements and attributes kept; anything else (scripts, handlers, foreign content) is stripped.
	 */
	private const ALLOWED_HTML = array(
		'svg'  => array(
			'xmlns'       => true,
			'viewbox'     => true,
			'class'       => true,
			'aria-hidden' => true,
			'focusable'   => true,
		),
		'g'    => array(
			'transform' => true,
		),
		'path' => array(
			'd'         => true,
			'fill'      => true,
			'transform' => true,
			'fill-rule' => true,
		),
		'rect' => array(
			'x'         => true,
			'y'         => true,
			'width'     => true,
			'height'    => true,
			'fill'      => true,
			'transform' => true,
		),
	);

	/**
	 * Sanitized file contents by icon name, '' for an icon that could not be loaded.
	 *
	 * @var array<string, string>
	 */
	private static array $cache = array();

	/**
	 * The sanitized inline markup of `assets/icons/<name>.svg`, decorative (`aria-hidden`, not focusable).
	 * Empty when the name is not lowercase letters, digits, `-` or `_`, or the file is missing, unreadable or not an SVG.
	 * The result is safe to print as is.
	 *
	 * @param string $name    Icon file name without extension.
	 * @param string $classes Space separated CSS classes for the root `<svg>`.
	 * @param string $width   Optional icon width as a number of em (`1.45em`), set as `--fa-width`; anything else is ignored.
	 */
	public static function svg( string $name, string $classes = '', string $width = '' ): string {
		if ( 1 !== preg_match( '/^[a-z0-9_-]+\z/', $name ) ) {
			return '';
		}

		if ( ! isset( self::$cache[ $name ] ) ) {
			self::$cache[ $name ] = self::load( $name );
		}

		$svg = self::$cache[ $name ];

		if ( '' === $svg ) {
			return '';
		}

		$attributes = '' === $classes ? '' : ' class="' . esc_attr( $classes ) . '"';

		// Added after sanitizing: only digits, an optional decimal part and `em` pass, so the value cannot carry anything else.
		if ( 1 === preg_match( '/^\d+(\.\d+)?em\z/', $width ) ) {
			$attributes .= ' style="--fa-width:' . esc_attr( $width ) . '"';
		}

		$attributes .= ' aria-hidden="true" focusable="false"';

		// load() guarantees the markup starts with `<svg`; a plain string splice keeps `$1` or `\0` in the classes literal.
		return substr_replace( $svg, '<svg' . $attributes, 0, 4 );
	}

	/**
	 * Read and sanitize one icon file, '' when it is missing, unreadable or does not sanitize to an `<svg>` element.
	 *
	 * @param string $name Icon file name without extension, already validated.
	 */
	private static function load( string $name ): string {
		$file = dirname( __DIR__ ) . '/assets/icons/' . $name . '.svg';

		if ( ! is_file( $file ) || ! is_readable( $file ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reads a local plugin asset, not a remote URL
		$contents = file_get_contents( $file );

		if ( false === $contents ) {
			return '';
		}

		// kses drops the script and style tags but keeps their text, so remove the whole elements first.
		$contents = (string) preg_replace( '#<(script|style)\b.*?</\1\s*>#is', '', $contents );
		$svg      = trim( wp_kses( $contents, self::ALLOWED_HTML ) );

		if ( 1 !== preg_match( '#^<svg\b.*</svg>$#s', $svg ) ) {
			return '';
		}

		return $svg;
	}
}
