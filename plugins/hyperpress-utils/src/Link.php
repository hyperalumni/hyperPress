<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Helpers for printing links: whether a URL stays on this site decides how it opens.
 */
final class Link {

	/**
	 * True when the URL points at this site: a relative URL, a fragment-only or query-only URL (`#top`, `?page=2`:
	 * the current page), or one whose host equals the site's host. Surrounding whitespace is ignored.
	 * Comparing hosts (not substrings) stops `https://other.example/?r=<site url>` passing as internal.
	 *
	 * @param string $url Link URL.
	 */
	public static function is_internal( string $url ): bool {
		$url = trim( $url );

		if ( 0 === strpos( $url, '#' ) || 0 === strpos( $url, '?' ) ) {
			return true;
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( null === $host || false === $host ) {
			// No host: a relative URL (or an unparseable one, which esc_url() will reject).
			return 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' );
		}

		return strtolower( $host ) === strtolower( (string) wp_parse_url( get_site_url(), PHP_URL_HOST ) );
	}

	/**
	 * The link `target`: `_self` for `mailto:`, `tel:` and `sms:` URLs; `_blank` for an external URL or one whose path
	 * ends in `.pdf` (query string and fragment ignored); else `_self`. Surrounding whitespace is ignored.
	 *
	 * @param string $url Link URL.
	 */
	public static function target( string $url ): string {
		$url = trim( $url );

		// A regex, not wp_parse_url(): PHP reads a digits-only `tel:5551234` as host `tel`, port 5551234.
		if ( 1 === preg_match( '/^(mailto|tel|sms):/i', $url ) ) {
			return '_self';
		}

		if ( ! self::is_internal( $url ) ) {
			return '_blank';
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );

		return is_string( $path ) && 1 === preg_match( '/\.pdf$/i', $path ) ? '_blank' : '_self';
	}

	/**
	 * The link `rel` for a target: `noopener noreferrer` for `_blank`, else ''.
	 *
	 * @param string $target Link target, as returned by {@see self::target()}.
	 */
	public static function rel( string $target ): string {
		return '_blank' === $target ? 'noopener noreferrer' : '';
	}
}
