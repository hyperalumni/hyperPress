<?php
namespace HyperPress\Shortcodes\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class Banner {

	public static function register(): void {
		add_shortcode( 'hyper_banner', array( self::class, 'render' ) );
	}

	// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- plugins audit
	public static function render( $atts = array(), $content = null ): string {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- plugins audit
		extract(
			shortcode_atts(
				array(
					'anchor'   => '',
					'title'    => '',
					'subtitle' => '',
				),
				$atts
			)
		);
		if ( ! empty( $title ) || ! empty( $subtitle ) ) {
			$output = '<header role="banner" class="banner">';
			if ( ! empty( $anchor ) ) {
				$output .= '<span class="anchor" id="' . $anchor . '"></span>';
			}
			$output .= '<div class="main-container">';

			if ( ! empty( $title ) ) {
				$output .= '<h2 class="entry-title">' . $title . '</h2>';
			}
			if ( ! empty( $subtitle ) ) {
				$output .= '<h4 class="subtitle">' . do_shortcode( $subtitle ) . '</h4>';
			}
			$output .= '</div>';
			$output .= '</header>';

			return $output;
		}

		return '';
	}
}
