<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The event list shortcode: the events of a series (or all), upcoming first.
 */
final class ListShortcode {

	public const SHORTCODE = 'hyperpress_event_list';

	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'shortcode' ) );
	}

	/**
	 * Shortcode handler: [hyperpress_event_list series="" show="all" limit="0"].
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'series' => '',
				'show'   => 'all',
				'limit'  => '0',
			),
			$atts,
			self::SHORTCODE
		);

		return Listing::html( Listing::ids( (string) $atts['series'], (string) $atts['show'], (int) $atts['limit'] ) );
	}
}
