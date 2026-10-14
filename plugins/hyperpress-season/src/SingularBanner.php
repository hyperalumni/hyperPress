<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Meta;

/**
 * The banner of a single post of a season-based post type: "<latest season>: <post title>", plus the post's
 * banner colour when it has one (else the post type's own colour stands, never the Posts page's).
 *
 * Hooked on `hyperpress_banner_args`, which HyperPress\Utils\Banner applies only to the post type it was
 * registered for. The theme's own singular banner filters (library/banner/post.php, page.php) only act on
 * `post` and pages, so they leave the title of these post types empty and the guard below fills it.
 */
final class SingularBanner {

	/**
	 * Fallback title (no season on the post) per post type.
	 *
	 * @var array<string,string>
	 */
	private static array $fallbacks = array();

	/**
	 * Give a post type's single pages the season-prefixed banner.
	 *
	 * @param string $post_type      Post type key.
	 * @param string $fallback_title Title when the post has no season (translated by the caller).
	 */
	public static function register( string $post_type, string $fallback_title ): void {
		self::$fallbacks[ $post_type ] = $fallback_title;
		add_filter( 'hyperpress_banner_args', array( self::class, 'adjust' ), 10, 3 );
	}

	/**
	 * Forget a post type again (used by tests; the shared hook stays, it ignores unknown post types).
	 *
	 * @param string $post_type Post type key.
	 */
	public static function unregister( string $post_type ): void {
		unset( self::$fallbacks[ $post_type ] );
	}

	/**
	 * Adjust the banner arguments.
	 *
	 * @param array  $banner    Assembled banner.
	 * @param string $post_type Post type the banner is for.
	 * @param array  $ctx       Request context from HyperPress\Utils\Banner.
	 */
	public static function adjust( $banner, $post_type, $ctx ) {
		if ( ! isset( self::$fallbacks[ $post_type ] ) || empty( $ctx['is_singular'] ) ) {
			return $banner;
		}

		if ( empty( $banner['title'] ) ) {
			$seasons = SortedSeasons::get();
			if ( ! empty( $seasons ) ) {
				$banner['title'] = $seasons[0]->name . ': ' . get_the_title();
			} else {
				$banner['title'] = self::$fallbacks[ $post_type ];
			}
		}

		// Keep the type colour Banner::apply() set unless the post has its own. The Posts page colour is for the
		// blog's own banners (the theme's library/banner/blog-color.php) and must not leak onto a plugin type.
		$color = Meta::get( 'hyperpress_banner_background_color' );
		if ( ! empty( $color ) ) {
			$banner['backgroundColor'] = $color;
		}

		return $banner;
	}
}
