<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The banner that opens a section of an event page.
 */
final class Sections {

	/**
	 * Section attributes with defaults.
	 *
	 * @param mixed  $atts          Raw shortcode attributes.
	 * @param string $default_title Title when the attribute is absent.
	 * @return array{title: string, subtitle: string, color: string, anchor: string}
	 */
	public static function atts( $atts, string $default_title ): array {
		return shortcode_atts(
			array(
				'title'    => $default_title,
				'subtitle' => '',
				'color'    => '',
				'anchor'   => '',
			),
			(array) $atts
		);
	}

	/**
	 * The section banner, or '' when it has no text or the shortcode plugin is not active.
	 *
	 * @param int   $post_id Event ID.
	 * @param array $atts    Result of atts().
	 */
	public static function banner( int $post_id, array $atts ): string {
		$class = '\\HyperPress\\Shortcode\\Shortcodes\\Banner';
		if ( ! class_exists( $class ) ) {
			return '';
		}
		$title    = (string) ( $atts['title'] ?? '' );
		$subtitle = (string) ( $atts['subtitle'] ?? '' );
		if ( '' === $title && '' === $subtitle ) {
			return '';
		}

		return $class::render(
			array(
				'anchor'   => (string) ( $atts['anchor'] ?? '' ),
				'title'    => $title,
				'subtitle' => $subtitle,
				'color'    => Appearance::color( $post_id, (string) ( $atts['color'] ?? '' ) ),
			)
		);
	}
}
