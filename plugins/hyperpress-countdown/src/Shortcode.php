<?php
namespace HyperPress\Countdown;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Meta;

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
			$on  = Meta::get( 'hyperpress_countdown_hide_before', $countdown->ID );
			$off = Meta::get( 'hyperpress_countdown_hide_after', $countdown->ID );

			if ( empty( $on ) || strtotime( $on ) < current_time( 'timestamp' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- compared with user-entered site-local times, so the site-offset timestamp is intended
				// If on time isn't set, or has already passed, show the content.
				$showit = 1;
			}

			if ( empty( $off ) || strtotime( $off ) > current_time( 'timestamp' ) ) { // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- compared with user-entered site-local times, so the site-offset timestamp is intended
				// If off time isn't set, or is in the future, show the content.
				$hideit = 0;
			}
			$display_countdown = ( 1 === $showit && 0 === $hideit );
		}
		if ( ! $display_countdown ) {
			return '';
		}

		$event_date = Meta::get( 'hyperpress_countdown_event_date_time', $countdown->ID );
		if ( ! strtotime( $event_date ) ) {
			return '';
		}

		$event_name      = Meta::get( 'hyperpress_countdown_event_name', $countdown->ID );
		$event_location  = Meta::get( 'hyperpress_countdown_event_location', $countdown->ID );
		$count_past_zero = filter_var( Meta::get( 'hyperpress_countdown_past_zero', $countdown->ID ), FILTER_VALIDATE_BOOLEAN );

		// The event is fixed in the site's time zone (Settings > General). The stored value is a wall-clock time with no zone,
		// so read it in that zone; the renderer sends the resulting offset to TimeCircles.
		$when = new \DateTimeImmutable( $event_date, wp_timezone() );

		return Renderer::render(
			$when,
			array(
				'name'      => $event_name,
				'location'  => $event_location,
				'past_zero' => $count_past_zero,
				'slug'      => $slug,
			)
		);
	}
}
