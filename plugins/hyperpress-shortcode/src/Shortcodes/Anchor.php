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

		$attr_text = trim( (string) $atts['text'] );

		if ( '' !== $content ) {
			// Post HTML without <a> (no nested anchors); kses also drops block markup such as forms and iframes.
			$allowed = wp_kses_allowed_html( 'post' );
			unset( $allowed['a'] );
			$text = wp_kses( do_shortcode( $content ), $allowed );
		} elseif ( '' !== $attr_text ) {
			$text = esc_html( $attr_text );
		} else {
			$text = esc_html( $url );
		}

		if ( '' === $href ) {
			return '' === $content && '' === $attr_text ? '' : $text;
		}

		return '<a href="' . $href . '"' . self::target_attributes( $url ) . '>' . $text . '</a>';
	}

	/**
	 * The ` target` and ` rel` attributes for a URL. Without hyperpress-utils (a hard requirement, but a missing
	 * class must not take the whole page down) the link is left plain.
	 *
	 * @param string $url Link URL.
	 */
	private static function target_attributes( string $url ): string {
		if ( ! class_exists( Link::class ) ) {
			return '';
		}

		$target = Link::target( $url );
		$rel    = Link::rel( $target );

		return ' target="' . esc_attr( $target ) . '"' . ( '' !== $rel ? ' rel="' . esc_attr( $rel ) . '"' : '' );
	}
}
