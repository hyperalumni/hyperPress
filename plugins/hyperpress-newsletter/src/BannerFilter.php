<?php
namespace HyperPress\Newsletter;

defined( 'ABSPATH' ) || exit;

use HyperPress\Utils\Meta;

final class BannerFilter {

	public static function register(): void {
		add_filter( 'hyperpress_banner_args', array( self::class, 'adjust' ), 10, 3 );
	}

	/**
	 * Adjust the banner arguments.
	 *
	 * @param array  $banner    Assembled banner.
	 * @param string $post_type Post type the banner is for.
	 * @param array  $ctx       Request context from HyperPress\Utils\Banner.
	 */
	public static function adjust( $banner, $post_type, $ctx ) {
		if ( Plugin::POST_TYPE !== $post_type || empty( $ctx['is_singular'] ) ) {
			return $banner;
		}

		if ( empty( $banner['title'] ) ) {
			$seasons = wp_get_object_terms(
				get_the_ID(),
				'hyper_season',
				array(
					'orderby' => 'name',
					'order'   => 'DESC',
				)
			);
			if ( ! empty( $seasons ) && ! is_wp_error( $seasons ) ) {
				$banner['title'] = $seasons[0]->name . ': ' . get_the_title();
			} else {
				$banner['title'] = __( 'Newsletter', 'hyperpress' );
			}
		}

		$banner['backgroundColor'] = Meta::get( 'hyperpress_banner_background_color' );
		$blog_page_id              = get_option( 'page_for_posts' );
		if ( empty( $banner['backgroundColor'] ) && $blog_page_id > 0 ) {
			$banner['backgroundColor'] = Meta::get( 'hyperpress_banner_background_color', $blog_page_id );
		}

		return $banner;
	}
}
