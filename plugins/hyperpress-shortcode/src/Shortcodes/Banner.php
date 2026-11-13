<?php
namespace HyperPress\Shortcode\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class Banner {

	public static function register(): void {
		add_shortcode( 'hyperpress_banner', array( self::class, 'render' ) );
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress always passes $content to shortcode callbacks; the signature matches the other shortcodes
	public static function render( $atts = array(), $content = null ): string {
		$atts = shortcode_atts(
			array(
				'anchor'   => '',
				'title'    => '',
				'subtitle' => '',
				'color'    => '',
			),
			$atts
		);

		$anchor   = $atts['anchor'];
		$title    = $atts['title'];
		$subtitle = $atts['subtitle'];
		if ( ! empty( $title ) || ! empty( $subtitle ) ) {
			$color = sanitize_hex_color( (string) $atts['color'] );
			if ( empty( $color ) ) {
				/**
				 * Filters the banner colour used when the shortcode has no valid `color` attribute.
				 *
				 * @param string $color Hex colour, empty by default.
				 * @param array  $atts  The shortcode attributes.
				 */
				$color = sanitize_hex_color( (string) apply_filters( 'hyperpress_banner_shortcode_default_color', '', $atts ) );
			}

			$style  = empty( $color ) ? '' : ' style="background-color: ' . esc_attr( $color ) . '"';
			$output = '<header role="banner" class="banner"' . $style . '>';
			if ( ! empty( $anchor ) ) {
				$output .= '<span class="anchor" id="' . esc_attr( $anchor ) . '"></span>';
			}
			$output .= '<div class="main-container">';

			if ( ! empty( $title ) ) {
				$output .= '<h2 class="entry-title">' . wp_kses_post( $title ) . '</h2>';
			}
			if ( ! empty( $subtitle ) ) {
				$output .= '<h4 class="subtitle">' . wp_kses_post( do_shortcode( $subtitle ) ) . '</h4>';
			}
			$output .= '</div>';
			$output .= '</header>';

			return $output;
		}

		return '';
	}
}
