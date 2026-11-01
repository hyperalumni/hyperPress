<?php
namespace HyperPress\Shortcode\Shortcodes;

use HyperPress\Utils\Link;

defined( 'ABSPATH' ) || exit;

final class Anchor {

	public static function register(): void {
		add_shortcode( 'hyperpress_link', array( self::class, 'render' ) );
	}

	public static function render( $atts = array(), $content = '' ): string {
		$atts = shortcode_atts(
			array(
				'url'  => '',
				'text' => '',
			),
			$atts,
			'hyperpress_link'
		);

		$url     = trim( (string) $atts['url'] );
		$content = trim( (string) $content );
		$href    = esc_url( $url );

		if ( '' !== $content ) {
			$text = wp_kses_post( do_shortcode( $content ) );
		} elseif ( '' !== trim( (string) $atts['text'] ) ) {
			$text = esc_html( trim( (string) $atts['text'] ) );
		} else {
			$text = esc_html( $url );
		}

		if ( '' === $href ) {
			return $text;
		}

		$target = Link::target( $url );
		$rel    = Link::rel( $target );

		return '<a href="' . $href . '" target="' . esc_attr( $target ) . '"'
			. ( '' !== $rel ? ' rel="' . esc_attr( $rel ) . '"' : '' )
			. '>' . $text . '</a>';
	}
}
