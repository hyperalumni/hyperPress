<?php
// Minimal stand-ins for the NextGEN classes the legacy templates call.
// NextGEN is not installed in the theme's test environment.

namespace Imagely\NGG\Util {
	if ( ! class_exists( Router::class, false ) ) {
		class Router {
			public static function esc_url( $url ) {
				return esc_url( (string) $url );
			}
		}
	}
}

namespace Imagely\NGG\Display {
	if ( ! class_exists( I18N::class, false ) ) {
		class I18N {
			public static function ngg_plain_text_alt_title_attributes( $text ) {
				return wp_strip_all_tags( (string) $text );
			}
		}
	}

	if ( ! class_exists( GalleryImage::class, false ) ) {
		class GalleryImage {
			public static function attributes( $extra = array() ) {
				$attributes = apply_filters( 'ngg_gallery_image_attributes', array_merge( array( 'class' => 'ngg-gallery-thumbnail-img' ), $extra ) );
				$html       = '';
				foreach ( $attributes as $name => $value ) {
					if ( false === $value || null === $value ) {
						continue;
					}
					$html .= true === $value ? ' ' . esc_attr( $name ) : ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
				}
				return $html;
			}
		}
	}
}
