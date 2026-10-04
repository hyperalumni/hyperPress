<?php
namespace HyperPress\Countdown;

defined( 'ABSPATH' ) || exit;

final class Shortcode {

	public static function register(): void {
		add_shortcode( 'hyperpress_countdown', array( self::class, 'add_countdown_shortcode' ) );
	}

	public static function add_countdown_shortcode( $atts ): string {
		extract( shortcode_atts( [ 'id' => '', 'slug' => '', 'ignoreshowhide' => 'false' ], $atts ) );
		if ( ! empty( $id ) || ! empty( $slug ) ) {
			if ( empty( $id ) ) {
				// if we don't have an id, use the slug
				$countdown = get_page_by_path( $slug, OBJECT, 'hyper_countdown' );
			} else {
				$countdown = get_post( $id );
				$slug      = $countdown->post_name;
			}

			if ( ! empty( $countdown ) ) {
				$result           = json_encode( $countdown );
				$displayCountdown = false;
				if ( filter_var( $ignoreshowhide, FILTER_VALIDATE_BOOLEAN ) ) {
					// if the user is choosing to ignore the show/hide dates, force display to true
					$displayCountdown = true;
				} else {
					// if the user is not choosing to ignore the show/hide dates, calculate based on the date
					$showit = 0;
					$hideit = 1;
					// retrieve the values from the countdown cpt
					$on  = rwmb_meta( 'hyperpress_countdown_hide_before', '', $countdown->ID );
					$off = rwmb_meta( 'hyperpress_countdown_hide_after', '', $countdown->ID );


					if ( empty( $on ) || strtotime( $on ) < current_time( 'timestamp' ) ) {
						// If on time isn't set, or has already passed, show the content.
						$showit = 1;
					}

					if ( empty( $off ) || strtotime( $off ) > current_time( 'timestamp' ) ) {
						// If off time isn't set, or is in the future, show the content.
						$hideit = 0;
					}
					$displayCountdown = ( 1 === $showit && 0 === $hideit );
				}

				if ( $displayCountdown ) {
					$eventDate = rwmb_meta( 'hyperpress_countdown_event_date_time', '', $countdown->ID );
					if ( strtotime( $eventDate ) ) {
						// only enqueue the TimeCircles js & css if we're displaying the countdown
						wp_enqueue_script( 'hyperpress-countdown-timecircles' );
						wp_enqueue_style( 'hyperpress-countdown-timecircles' );

						$eventName     = rwmb_meta( 'hyperpress_countdown_event_name', '', $countdown->ID );
						$eventLocation = rwmb_meta( 'hyperpress_countdown_event_location', '', $countdown->ID );

						$countPastZero = rwmb_meta( 'hyperpress_countdown_past_zero', '', $countdown->ID );

						$output = '<section class="grid-x countdown align-center">';
						$output .= '<div class="cell xsmall-12 large-10">';
						$output .= '<div id="' . $slug . '" class="cell" data-date="' . $eventDate . '" ></div>';

						if ( ! empty( $eventName ) ) {
							$output .= '<h2 class="cell align-center">' . $eventName . '</h2>';
						}

						$output .= '<h4 class="cell align-center"><time>' . date_format( date_create( $eventDate ), "l F d, Y @ h:iA" ) . '</time></h4>';

						if ( ! empty( $eventLocation ) ) {
							$output .= '<h4 class="cell align-center">' . $eventLocation . '</h4>';
						}

						$output .= '</div>';
						$output .= '</section>';
						$output .= '<div class="section-divider"><hr/></div>';

						$output .= '<script>
													$(document).ready(function () {
														$("#' . $slug . '").TimeCircles({
															"animation": "smooth",
															"bg_width": 0.4,
															"fg_width": 0.07,
															"circle_bg_color": "#efefef",
															"count_past_zero": ' . $countPastZero . ',
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
														})
													});
												</script>';

						return $output;
					}
				}
			}
		}

		return '';
	}
}
