<?php
namespace HyperPress\Shortcodes\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class Banner {

	public static function register(): void {
		add_shortcode( 'hyper_banner', array( self::class, 'render' ) );
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WordPress always passes $content to shortcode callbacks; the signature matches the other shortcodes
	public static function render( $atts = array(), $content = null ): string {
		$atts = shortcode_atts(
			array(
				'anchor'   => '',
				'title'    => '',
				'subtitle' => '',
			),
			$atts
		);

		$anchor   = $atts['anchor'];
		$title    = $atts['title'];
		$subtitle = $atts['subtitle'];
		if ( ! empty( $title ) || ! empty( $subtitle ) ) {
			$output = '<header role="banner" class="banner">';
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
