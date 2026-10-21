<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the shared countdown from a competition's start date.
 */
final class Countdown {

	public const SHORTCODE = 'hyperpress_competition_countdown';

	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'shortcode' ) );
	}

	/**
	 * Shortcode handler: [hyperpress_competition_countdown id="123"].
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts( array( 'id' => '' ), $atts, self::SHORTCODE );

		return self::render( absint( $atts['id'] ) );
	}

	/**
	 * The countdown markup, or '' unless the competition is readable and has a usable start date.
	 *
	 * @param int  $post_id       Competition post ID.
	 * @param bool $show_location Whether to show the venue name under the countdown.
	 * @param bool $show_date     Whether to show the formatted start date under the circles.
	 */
	public static function render( int $post_id, bool $show_location = true, bool $show_date = false ): string {
		if ( ! class_exists( '\HyperPress\Countdown\Renderer' ) || ! self::is_renderable( get_post( $post_id ) ) ) {
			return '';
		}

		$when = StartDate::instant( $post_id );
		if ( null === $when ) {
			return '';
		}

		return \HyperPress\Countdown\Renderer::render(
			$when,
			array(
				'location'  => $show_location ? Details::for_post( $post_id )['venue'] : '',
				'past_zero' => Settings::past_zero(),
				'slug'      => 'competition-' . $post_id,
				'show_date' => $show_date,
			)
		);
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
