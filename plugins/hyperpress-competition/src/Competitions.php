<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * The competition list shortcode: the competitions of a season, district, type and program, soonest first.
 */
final class Competitions {

	public const SHORTCODE = 'hyperpress_competitions';

	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'shortcode' ) );
	}

	/**
	 * Shortcode handler: [hyperpress_competitions season="" district="" type="" program=""].
	 *
	 * Each attribute is a term slug or name; an empty one is not applied (`season` defaults to the current season).
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts(
			array(
				'season'   => '',
				'district' => '',
				'type'     => '',
				'program'  => '',
			),
			$atts,
			self::SHORTCODE
		);

		return self::render( array_map( 'strval', $atts ) );
	}

	/**
	 * The list markup, or '' when no competition matches.
	 *
	 * @param array<string,string> $filters See Listing::ids().
	 */
	public static function render( array $filters ): string {
		$items = array_map( array( self::class, 'item' ), Listing::ids( $filters ) );
		if ( array() === $items ) {
			return '';
		}

		return '<div class="competition-list">' . implode( '<hr class="competition-divider">', $items ) . '</div>';
	}

	/**
	 * One competition's markup.
	 *
	 * @param int $post_id Competition post ID.
	 */
	private static function item( int $post_id ): string {
		$hyperpress_post_id = $post_id;

		ob_start();
		include dirname( __DIR__ ) . '/templates/list-item.php';

		return (string) ob_get_clean();
	}
}
