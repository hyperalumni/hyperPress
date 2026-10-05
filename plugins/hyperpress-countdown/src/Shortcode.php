<?php
namespace HyperPress\Countdown;

defined( 'ABSPATH' ) || exit;

final class Shortcode {

	public static function register(): void {
		add_shortcode( 'hyperpress_countdown', array( self::class, 'add_countdown_shortcode' ) );
	}

	/**
	 * Only published countdowns render; private ones only for users who can read them.
	 *
	 * @param mixed $countdown Result of get_post()/get_page_by_path().
	 */
	private static function is_renderable( $countdown ): bool {
		if ( ! $countdown instanceof \WP_Post || 'hyper_countdown' !== $countdown->post_type ) {
			return false;
		}
		if ( 'publish' === $countdown->post_status ) {
			return true;
		}
		return 'private' === $countdown->post_status && current_user_can( 'read_post', $countdown->ID );
	}

	public static function add_countdown_shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'id'             => '',
				'slug'           => '',
				'ignoreshowhide' => 'false',
			),
			$atts
		);

		$id   = absint( $atts['id'] );
		$slug = sanitize_title( $atts['slug'] );
		if ( 0 === $id && '' === $slug ) {
			return '';
		}

		if ( 0 === $id ) {
			// if we don't have an id, use the slug.
			$countdown = get_page_by_path( $slug, OBJECT, 'hyper_countdown' );
		} else {
			$countdown = get_post( $id );
		}
		if ( ! self::is_renderable( $countdown ) ) {
			return '';
		}
		$slug = $countdown->post_name;

		if ( filter_var( $atts['ignoreshowhide'], FILTER_VALIDATE_BOOLEAN ) ) {
			// if the user is choosing to ignore the show/hide dates, force display to true.
			$display_countdown = true;
		} else {
			// if the user is not choosing to ignore the show/hide dates, calculate based on the date.
			$showit = 0;
			$hideit = 1;
			// retrieve the values from the countdown cpt.
			$on  = rwmb_meta( 'hyperpress_countdown_hide_before', '', $countdown->ID );
			$off = rwmb_meta( 'hyperpress_countdown_hide_after', '', $countdown->ID );

			if ( empty( $on ) || strtotime( $on ) < current_time( 'timestamp' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- plugins audit
				// If on time isn't set, or has already passed, show the content.
				$showit = 1;
			}

			if ( empty( $off ) || strtotime( $off ) > current_time( 'timestamp' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- plugins audit
				// If off time isn't set, or is in the future, show the content.
				$hideit = 0;
			}
			$display_countdown = ( 1 === $showit && 0 === $hideit );
		}
		if ( ! $display_countdown ) {
			return '';
		}

		$event_date = rwmb_meta( 'hyperpress_countdown_event_date_time', '', $countdown->ID );
		if ( ! strtotime( $event_date ) ) {
			return '';
		}

		// only enqueue the TimeCircles js & css if we're displaying the countdown.
		wp_enqueue_script( 'hyperpress-countdown-timecircles' );
		wp_enqueue_style( 'hyperpress-countdown-timecircles' );

		$event_name      = rwmb_meta( 'hyperpress_countdown_event_name', '', $countdown->ID );
		$event_location  = rwmb_meta( 'hyperpress_countdown_event_location', '', $countdown->ID );
		$count_past_zero = filter_var( rwmb_meta( 'hyperpress_countdown_past_zero', '', $countdown->ID ), FILTER_VALIDATE_BOOLEAN );

		$output  = '<section class="grid-x countdown align-center">';
		$output .= '<div class="cell xsmall-12 large-10">';
		$output .= '<div id="' . esc_attr( $slug ) . '" class="cell" data-date="' . esc_attr( $event_date ) . '" ></div>';

		if ( ! empty( $event_name ) ) {
			$output .= '<h2 class="cell align-center">' . esc_html( $event_name ) . '</h2>';
		}

		$output .= '<h4 class="cell align-center"><time>' . esc_html( date_format( date_create( $event_date ), 'l F d, Y @ h:iA' ) ) . '</time></h4>';

		if ( ! empty( $event_location ) ) {
			$output .= '<h4 class="cell align-center">' . esc_html( $event_location ) . '</h4>';
		}

		$output .= '</div>';
		$output .= '</section>';
		$output .= '<div class="section-divider"><hr/></div>';

		$selector = wp_json_encode( '#' . $slug, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT );
		$js       = 'jQuery( function ( $ ) {
	$( ' . $selector . ' ).TimeCircles( {
		"animation": "smooth",
		"bg_width": 0.4,
		"fg_width": 0.07,
		"circle_bg_color": "#efefef",
		"count_past_zero": ' . wp_json_encode( $count_past_zero ) . ',
		"time": {
			"Days": {
				"text": "Days",
				"color": "' . get_theme_mod( 'hyperpress_countdown_days_color' ) . '",
				"show": true
			},
			"Hours": {
				"text": "Hours",
				"color": "' . get_theme_mod( 'hyperpress_countdown_hours_color' ) . '",
				"show": true
			},
			"Minutes": {
				"text": "Minutes",
				"color": "' . get_theme_mod( 'hyperpress_countdown_minutes_color' ) . '",
				"show": true
			},
			"Seconds": {
				"text": "Seconds",
				"color": "' . get_theme_mod( 'hyperpress_countdown_seconds_color' ) . '",
				"show": true
			}
		}
	} );
} );';
		wp_add_inline_script( 'hyperpress-countdown-timecircles', $js );

		return $output;
	}
}
