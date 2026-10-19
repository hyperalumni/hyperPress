<?php
namespace HyperPress\Countdown;

defined( 'ABSPATH' ) || exit;

final class Renderer {

	/**
	 * JSON-encoded color for one TimeCircles unit: the Customizer value, or TimeCircles' own default when unset or invalid.
	 *
	 * @param string $unit days|hours|minutes|seconds.
	 */
	private static function color( string $unit ): string {
		$defaults = array(
			'days'    => '#FC6',
			'hours'   => '#9CF',
			'minutes' => '#BFB',
			'seconds' => '#F99',
		);
		$color    = sanitize_hex_color( (string) get_theme_mod( 'hyperpress_countdown_' . $unit . '_color' ) );

		return wp_json_encode( empty( $color ) ? $defaults[ $unit ] : $color, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
	}

	/**
	 * Render a TimeCircles countdown to one instant and enqueue its assets.
	 *
	 * @param \DateTimeImmutable  $when The instant to count down to; its offset is sent to TimeCircles.
	 * @param array<string,mixed> $args Optional: name, location, past_zero, slug, show_date.
	 */
	public static function render( \DateTimeImmutable $when, array $args = array() ): string {
		$args = array_merge(
			array(
				'name'      => '',
				'location'  => '',
				'past_zero' => false,
				'slug'      => 'countdown',
				'show_date' => true,
			),
			$args
		);

		$event_name      = (string) $args['name'];
		$event_location  = (string) $args['location'];
		$count_past_zero = (bool) $args['past_zero'];

		// only enqueue the TimeCircles js & css if we're displaying the countdown.
		wp_enqueue_script( 'hyperpress-countdown-timecircles' );
		wp_enqueue_style( 'hyperpress-countdown-timecircles' );

		// The ISO 8601 form with its offset is handed to Date.parse() by TimeCircles, which honors the offset,
		// so every visitor counts down to the same instant whatever their own time zone is.
		$data_date  = $when->format( 'c' );
		$element_id = $args['slug'] . '-' . wp_unique_id();

		$output  = '<section class="grid-x countdown align-center">';
		$output .= '<div class="cell xsmall-12 large-10">';
		$output .= '<div id="' . esc_attr( $element_id ) . '" class="cell" data-date="' . esc_attr( $data_date ) . '" ></div>';

		if ( ! empty( $event_name ) ) {
			$output .= '<h2 class="cell align-center">' . esc_html( $event_name ) . '</h2>';
		}

		if ( $args['show_date'] ) {
			$output .= '<h4 class="cell align-center"><time>' . esc_html( $when->format( 'l F d, Y @ h:iA' ) ) . '</time></h4>';
		}

		if ( ! empty( $event_location ) ) {
			$output .= '<h4 class="cell align-center">' . esc_html( $event_location ) . '</h4>';
		}

		$output .= '</div>';
		$output .= '</section>';
		$output .= '<div class="section-divider"><hr/></div>';

		$selector = wp_json_encode( '#' . $element_id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		$js       = 'jQuery( function ( $ ) {
	var options = {
		"animation": "smooth",
		"bg_width": 0.4,
		"fg_width": 0.07,
		"circle_bg_color": "#efefef",
		"count_past_zero": ' . wp_json_encode( $count_past_zero ) . ',
		"time": {
			"Days": {
				"text": "Days",
				"color": ' . self::color( 'days' ) . ',
				"show": true
			},
			"Hours": {
				"text": "Hours",
				"color": ' . self::color( 'hours' ) . ',
				"show": true
			},
			"Minutes": {
				"text": "Minutes",
				"color": ' . self::color( 'minutes' ) . ',
				"show": true
			},
			"Seconds": {
				"text": "Seconds",
				"color": ' . self::color( 'seconds' ) . ',
				"show": true
			}
		}
	};
	var $el = $( ' . $selector . ' );
	var lastWidth = $el.width();
	var resizeTimer;
	$el.TimeCircles( options );
	$( window ).on( "resize", function () {
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( function () {
			var width = $el.width();
			if ( width === lastWidth ) {
				return;
			}
			lastWidth = width;
			$el.TimeCircles().rebuild();
		}, 200 );
	} );
} );';
		wp_add_inline_script( 'hyperpress-countdown-timecircles', $js );

		return $output;
	}
}
