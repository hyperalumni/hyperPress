<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the shared countdown from a competition's schedule.
 */
final class Countdown {

	public const SHORTCODE = 'hyperpress_competition_countdown';

	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'shortcode' ) );
	}

	/**
	 * Shortcode handler: [hyperpress_competition_countdown id="123"].
	 *
	 * The heading is the competition's title followed by the current day's label ("UNH District Event 2026: Day 1"),
	 * because the shortcode appears outside the competition's own page.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts( array( 'id' => '' ), $atts, self::SHORTCODE );

		return self::render( absint( $atts['id'] ), true, false, true );
	}

	/**
	 * The countdown markup, or '' unless the competition is readable and has a counted day in its schedule.
	 *
	 * @param int  $post_id       Competition post ID.
	 * @param bool $show_location Whether to show the venue name under the countdown.
	 * @param bool $show_date     Whether to show the formatted start date under the circles.
	 * @param bool $with_title    Whether the heading is "Title: Day label" instead of the day label alone (the competition's own page already shows the title in its banner).
	 */
	public static function render( int $post_id, bool $show_location = true, bool $show_date = false, bool $with_title = false ): string {
		if ( ! class_exists( '\HyperPress\Countdown\Renderer' ) || ! self::is_renderable( get_post( $post_id ) ) ) {
			return '';
		}

		$day = Schedule::current( $post_id );
		if ( null === $day ) {
			return '';
		}

		return \HyperPress\Countdown\Renderer::render(
			$day['start'],
			array(
				'name'      => $with_title ? self::heading( $post_id, $day['label'] ) : $day['label'],
				'location'  => $show_location ? Details::for_post( $post_id )['venue'] : '',
				'past_zero' => Settings::past_zero(),
				'slug'      => 'competition-' . $post_id,
				'show_date' => $show_date,
			)
		);
	}

	/**
	 * "Title: label", or whichever of the two is not empty. Plain text: the Renderer escapes it.
	 *
	 * @param int    $post_id Competition post ID.
	 * @param string $label   The day's label.
	 */
	private static function heading( int $post_id, string $label ): string {
		$title = trim( wp_specialchars_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES ) );

		return '' !== $title && '' !== $label ? $title . ': ' . $label : $title . $label;
	}

	/**
	 * Only published competitions, or private ones the current user can read.
	 *
	 * @param \WP_Post|null $post The post.
	 */
	private static function is_renderable( $post ): bool {
		if ( ! $post instanceof \WP_Post || 'hyper_competition' !== $post->post_type ) {
			return false;
		}
		if ( 'publish' === $post->post_status ) {
			return true;
		}
		return 'private' === $post->post_status && current_user_can( 'read_post', $post->ID );
	}
}
