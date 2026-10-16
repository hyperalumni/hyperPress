<?php
namespace HyperPress\Example;

defined( 'ABSPATH' ) || exit;

final class Shortcode {

	public const TAG = 'hp_uikit_card';

	public static function register(): void {
		add_shortcode( self::TAG, array( self::class, 'render' ) );
	}

	/**
	 * Render the card.
	 *
	 * @param array<string,string>|string $atts    Shortcode attributes (an empty string when there are none).
	 * @param string|null                 $content Enclosed content.
	 */
	public static function render( array|string $atts, ?string $content = null ): string {
		$atts = shortcode_atts(
			array( 'title' => '' ),
			is_array( $atts ) ? $atts : array(),
			self::TAG
		);

		wp_enqueue_style( Plugin::STYLE_HANDLE );

		$title   = trim( (string) $atts['title'] );
		$heading = '' === $title ? '' : '<h3 class="uk-card-title">' . esc_html( $title ) . '</h3>';

		return '<div class="uk-card uk-card-default uk-card-body hp-uikit-card">'
			. $heading
			. wp_kses_post( do_shortcode( (string) $content ) )
			. '</div>';
	}
}
