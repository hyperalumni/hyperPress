<?php
namespace HyperPress\Shortcode\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class DateDistance {

	public static function register(): void {
		add_shortcode( 'hyperpress_date_distance', array( self::class, 'render' ) );
	}

	/**
	 * Whether ordinal suffixes ("1st", "2nd") can be produced; they need ext-intl.
	 *
	 * The filter lets tests (and sites that want plain numbers) force this off.
	 */
	public static function supports_ordinals(): bool {
		return (bool) apply_filters( 'hyperpress_shortcode_ordinals', class_exists( '\NumberFormatter' ) );
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress always passes $content to shortcode callbacks; the signature matches the other shortcodes
	public static function render( $atts = array(), $content = null ): string {
		$args = shortcode_atts(
			array(
				'date'      => '',
				'format'    => '%y',
				'inclusive' => false,
				'suffix'    => false,
				'locale'    => 'en-US',
			),
			$atts
		);

		$date      = $args['date'];
		$format    = $args['format'];
		$inclusive = filter_var( $args['inclusive'], FILTER_VALIDATE_BOOLEAN );
		$suffix    = filter_var( $args['suffix'], FILTER_VALIDATE_BOOLEAN );
		$locale    = str_replace( '_', '-', (string) $args['locale'] );

		$target = date_create( $date, wp_timezone() );
		if ( false === $target ) {
			return '';
		}

		$diff = ( new \DateTimeImmutable( 'today', wp_timezone() ) )->diff( $target )->format( $format );

		if ( $inclusive && is_numeric( $diff ) ) {
			++$diff;
		}

		// An explicit empty locale keeps the default-locale formatter; anything else must be well formed, because PHP 8.3 accepts junk that 8.4+ rejects.
		if ( $suffix && is_numeric( $diff ) && self::supports_ordinals() && ( '' === $locale || self::is_valid_locale( $locale ) ) ) {
			try {
				$nf        = new \NumberFormatter( $locale, \NumberFormatter::ORDINAL );
				$formatted = $nf->format( (int) $diff );
			} catch ( \ValueError | \IntlException ) {
				$formatted = false;
			}
			if ( false !== $formatted ) {
				$diff = $formatted;
			}
		}

		return esc_html( (string) $diff );
	}

	/**
	 * Whether an underscore-normalised locale has a BCP 47 shape such as "en", "en-US" or "zh-Hant-TW".
	 *
	 * @param string $locale Locale with underscores already replaced by hyphens.
	 */
	private static function is_valid_locale( string $locale ): bool {
		return 1 === preg_match( '/^[a-z]{2,3}(?:-[a-z0-9]{2,8})*$/i', $locale );
	}
}
