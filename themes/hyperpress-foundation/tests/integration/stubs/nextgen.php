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

			public static function ngg_decode_sanitized_html_content( $content ) {
				return htmlspecialchars_decode( wp_kses_post( (string) $content ), ENT_QUOTES );
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

namespace Imagely\NGG\DataStorage {
	if ( ! class_exists( Manager::class, false ) ) {
		class Manager {
			public static function get_instance() {
				return new self();
			}

			public function get_image_url( $image, $size = 'full', $check_existence = false ) {
				return 'https://example.test/' . $size . '-' . $image->pid . '.jpg';
			}

			public function get_cache_busted_image_url( $image, $type = 'thumb' ) {
				return 'https://example.test/' . $type . '-' . $image->pid . '.jpg?v=1';
			}
		}
	}
}

namespace Imagely\NGG\DisplayType {
	if ( ! class_exists( Controller::class, false ) ) {
		class Controller {
			/** Raw attribute HTML the stub returns; tests set it. */
			public static string $effect_code = 'class="ngg-simplelightbox" rel="widget-1"';

			public function get_effect_code( $displayed_gallery ) {
				return self::$effect_code;
			}
		}
	}
}
