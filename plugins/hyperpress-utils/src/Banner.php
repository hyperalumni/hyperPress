<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Banner {

	public const SEASON_TAXONOMY = 'hyper_season';

	/**
	 * Hook the theme's banner filter for one post type.
	 *
	 * @param string $post_type Post type key.
	 * @param array  $args      type, subtitle_mod, color_mod, archive_title (all required);
	 *                          term_joiner ('in' default), always_set_archive_title (false default).
	 */
	public static function register( string $post_type, array $args ): void {
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) use ( $post_type, $args ) {
				$is_tax_season = is_tax( self::SEASON_TAXONOMY );
				$queried       = $is_tax_season ? get_queried_object() : null;
				$ctx           = array(
					'is_singular'   => is_singular( $post_type ),
					'is_archive'    => is_post_type_archive( $post_type ),
					'is_tax_season' => $is_tax_season,
					'subtitle'      => get_theme_mod( $args['subtitle_mod'] ),
					'color'         => get_theme_mod( $args['color_mod'] ),
					'term_name'     => $queried instanceof \WP_Term ? $queried->name : '',
				);
				if ( ! $ctx['is_singular'] && ! $ctx['is_archive'] ) {
					return $banner;
				}
				$banner = self::apply( (array) $banner, $args, $ctx );

				/**
				 * Lets a plugin adjust the assembled banner for its own post type.
				 *
				 * @param array  $banner    Banner data.
				 * @param string $post_type Post type key.
				 * @param array  $ctx       Request context (is_singular, is_archive, ...).
				 */
				return apply_filters( 'hyperpress_banner_args', $banner, $post_type, $ctx );
			}
		);
	}

	/**
	 * Pure: assemble banner data from the request context.
	 *
	 * @param array $banner Incoming banner.
	 * @param array $args   See register().
	 * @param array $ctx    is_singular, is_archive, is_tax_season, subtitle, color, term_name.
	 */
	public static function apply( array $banner, array $args, array $ctx ): array {
		if ( ! $ctx['is_singular'] && ! $ctx['is_archive'] ) {
			return $banner;
		}

		$banner['type']            = $args['type'];
		$banner['subtitle']        = $ctx['subtitle'];
		$banner['backgroundColor'] = $ctx['color'];

		if ( $ctx['is_archive'] && ( ! empty( $args['always_set_archive_title'] ) || empty( $banner['title'] ) ) ) {
			$banner['title'] = $args['archive_title'];
			if ( $ctx['is_tax_season'] && ! empty( $ctx['subtitle'] ) ) {
				$banner['subtitle'] = $ctx['subtitle'] . ' ' . ( $args['term_joiner'] ?? 'in' ) . ' ' . $ctx['term_name'];
			}
		}

		return $banner;
	}
}
