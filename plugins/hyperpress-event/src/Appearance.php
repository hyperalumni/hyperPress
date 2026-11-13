<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

use HyperPress\Utils\Banner;

/**
 * Banner colour and subtitle of an event: the page's own banner and the section banners.
 */
final class Appearance {

	/** Post meta the theme's page banner reads its colour from. */
	private const PAGE_BANNER_COLOR = 'hyperpress_banner_background_color';

	public static function register(): void {
		add_action( 'init', array( self::class, 'register_banner' ) );
		add_filter( 'hyperpress_banner_args', array( self::class, 'adjust' ), 10, 3 );
		add_filter( 'hyperpress_banner_shortcode_default_color', array( self::class, 'default_color' ), 10, 2 );
	}

	public static function register_banner(): void {
		Banner::register(
			Plugin::POST_TYPE,
			array(
				'type'          => 'event',
				'subtitle'      => static fn(): string => Banner::sanitize_subtitle( self::subtitle( (int) get_queried_object_id() ) ),
				// Utils\Banner only reads a theme mod for the colour; the per-post colour is set in adjust().
				'color_mod'     => 'hyperpress_event_banner_color',
				'archive_title' => __( 'Events', 'hyperpress' ),
			)
		);
	}

	/**
	 * The colour of an event: the attribute, then the event's own, then the settings default, then the Events hub
	 * page's banner colour; valid hex only, else '' (the theme's default banner colour then applies).
	 *
	 * @param int    $post_id Event ID.
	 * @param string $attr    Colour given by the caller (for example a shortcode attribute).
	 */
	public static function color( int $post_id, string $attr = '' ): string {
		// Lazy: each level is read only when the ones before it gave nothing, so the hub page lookup (get_post,
		// ancestors, meta) runs last.
		$levels = array(
			static fn(): mixed => $attr,
			static fn(): mixed => get_post_meta( $post_id, Fields::BANNER_COLOR, true ),
			static fn(): mixed => Settings::default_banner_color(),
			static fn(): mixed => self::hub_color(),
		);
		foreach ( $levels as $level ) {
			$candidate = $level();
			$color     = is_string( $candidate ) ? sanitize_hex_color( trim( $candidate ) ) : null;
			if ( is_string( $color ) && '' !== $color ) {
				return $color;
			}
		}

		return '';
	}

	/**
	 * The banner colour of the series archive: the settings default, then the hub page's; '' when neither is set.
	 */
	public static function archive_color(): string {
		$default = Settings::default_banner_color();

		return '' !== $default ? $default : self::hub_color();
	}

	/**
	 * The banner colour of the Events hub page, as the theme's page banner resolves it: the page's own colour, else
	 * that of its top-level ancestor. Read from post meta, since a plugin must not call theme functions. '' when no
	 * hub page is set or neither has a valid hex colour.
	 */
	private static function hub_color(): string {
		$hub = Settings::hub_page_id();
		if ( $hub <= 0 ) {
			return '';
		}
		$ids = array( $hub );
		$top = array_reverse( get_post_ancestors( $hub ) );
		if ( array() !== $top ) {
			$ids[] = (int) $top[0];
		}
		foreach ( $ids as $id ) {
			$value = get_post_meta( $id, self::PAGE_BANNER_COLOR, true );
			$color = is_string( $value ) ? sanitize_hex_color( trim( $value ) ) : null;
			if ( is_string( $color ) && '' !== $color ) {
				return $color;
			}
		}

		return '';
	}

	/**
	 * The subtitle of an event: its own, then the settings fallback, else ''.
	 *
	 * @param int $post_id Event ID.
	 */
	public static function subtitle( int $post_id ): string {
		$own = get_post_meta( $post_id, Fields::SUBTITLE, true );
		if ( is_string( $own ) && '' !== trim( $own ) ) {
			return trim( $own );
		}

		return trim( Settings::fallback_subtitle() );
	}

	/**
	 * Per-event title, colour and featured image (hero) of the page banner. Hooked on `hyperpress_banner_args` because Utils\Banner has
	 * already assembled the banner there (its colour comes from a theme mod only), as the Season plugin's
	 * SingularBanner does; `hyperpress_banner_content` would run before Banner::apply() overwrites the colour.
	 *
	 * @param mixed  $banner    Assembled banner.
	 * @param string $post_type Post type the banner is for.
	 * @param mixed  $ctx       Request context from HyperPress\Utils\Banner.
	 * @return mixed
	 */
	public static function adjust( $banner, $post_type = '', $ctx = array() ) {
		if ( Plugin::POST_TYPE !== $post_type || ! is_array( $banner ) || empty( $ctx['is_singular'] ) ) {
			return $banner;
		}

		$id = (int) get_queried_object_id();
		if ( empty( $banner['title'] ) ) {
			$banner['title'] = get_the_title( $id );
		}
		$color = self::color( $id );
		if ( '' !== $color ) {
			$banner['backgroundColor'] = $color;
		}
		// The theme renders the hero right under the banner header; single.php has no featured-image part of its own.
		$thumbnail = (int) get_post_thumbnail_id( $id );
		if ( $thumbnail > 0 ) {
			$banner['image'] = $thumbnail;
		}

		return $banner;
	}

	/**
	 * Default colour of `[hyperpress_banner]`: the current event's colour inside an event, the input anywhere else.
	 *
	 * @param mixed $color Colour so far.
	 * @param mixed $atts  Shortcode attributes.
	 * @return mixed
	 */
	public static function default_color( $color, $atts = array() ) {
		unset( $atts );
		$post = get_post();
		if ( ! $post instanceof \WP_Post || Plugin::POST_TYPE !== $post->post_type ) {
			return $color;
		}
		$own = self::color( $post->ID );

		return '' !== $own ? $own : $color;
	}
}
