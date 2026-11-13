<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Renders the shared countdown for an event.
 */
final class Countdown {

	public const SHORTCODE = 'hyperpress_event_countdown';

	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'shortcode' ) );
	}

	/**
	 * Shortcode handler: [hyperpress_event_countdown id="123"]. Without an id it uses the current event.
	 *
	 * Prints nothing outside an event, or for an event that is not readable or has no start.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts( array( 'id' => '' ), is_array( $atts ) ? $atts : array(), self::SHORTCODE );
		$id   = absint( $atts['id'] );
		if ( 0 === $id ) {
			$post = get_post();
			$id   = $post instanceof \WP_Post && Plugin::POST_TYPE === $post->post_type ? $post->ID : 0;
		}

		return 0 === $id ? '' : self::render( $id );
	}

	/**
	 * The countdown markup, or '' unless the event is readable, has a start and is not over: a past event never
	 * counts down.
	 *
	 * @param int $id Event post ID.
	 */
	public static function render( int $id ): string {
		if ( ! class_exists( '\HyperPress\Countdown\Renderer' ) || ! self::is_renderable( get_post( $id ) ) ) {
			return '';
		}

		$start = Dates::start( $id );
		if ( null === $start || Dates::is_over( $id ) ) {
			return '';
		}

		$venue = get_post_meta( $id, Fields::VENUE_NAME, true );

		return \HyperPress\Countdown\Renderer::render(
			$start,
			array(
				'name'      => trim( wp_specialchars_decode( wp_strip_all_tags( get_the_title( $id ) ), ENT_QUOTES ) ),
				'location'  => is_string( $venue ) ? $venue : '',
				'past_zero' => Settings::past_zero(),
				'slug'      => 'event-' . $id,
				'show_date' => true,
			)
		);
	}

	/**
	 * Only published events, or private ones the current user can read.
	 *
	 * @param \WP_Post|null $post The post.
	 */
	private static function is_renderable( $post ): bool {
		if ( ! $post instanceof \WP_Post || Plugin::POST_TYPE !== $post->post_type ) {
			return false;
		}
		if ( 'publish' === $post->post_status ) {
			return true;
		}

		return 'private' === $post->post_status && current_user_can( 'read_post', $post->ID );
	}
}
