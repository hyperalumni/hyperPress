<?php
namespace HyperPress\Utils;

use InvalidArgumentException;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Banner {

	public const SEASON_TAXONOMY = 'hyper_season';

	/**
	 * Hook the theme's banner filter for one post type.
	 *
	 * @param string $post_type Post type key.
	 * @param array  $args      Required: type, subtitle (callable returning the subtitle string),
	 *                          color_mod, archive_title.
	 *                          Optional: term_joiner ('in' default), always_set_archive_title (false default).
	 *
	 * @throws InvalidArgumentException When subtitle is missing or not callable.
	 */
	public static function register( string $post_type, array $args ): void {
		if ( ! is_callable( $args['subtitle'] ?? null ) ) {
			throw new InvalidArgumentException( esc_html( "Banner for '$post_type' needs a callable 'subtitle'." ) );
		}
		add_filter(
			'hyperpress_banner_content',
			static function ( $banner ) use ( $post_type, $args ) {
				// is_tax( $taxonomy ) only matches the one taxonomy WP picks as the queried object, so ask the season itself.
				$queried       = is_tax() ? RequestTerm::get( self::SEASON_TAXONOMY ) : null;
				$is_tax_season = null !== $queried;
				$subtitle      = call_user_func( $args['subtitle'] );
				$ctx           = array(
					'is_singular'   => is_singular( $post_type ),
					'is_archive'    => is_post_type_archive( $post_type ),
					'is_tax_season' => $is_tax_season,
					'subtitle'      => is_scalar( $subtitle ) ? (string) $subtitle : '',
					'color'         => get_theme_mod( $args['color_mod'] ),
					'term_name'     => $queried ? $queried->name : '',
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
	 * Sanitize a banner subtitle: post-allowed markup for scalars, '' for anything else.
	 *
	 * @param mixed $value Raw value.
	 */
	public static function sanitize_subtitle( $value ): string {
		return is_scalar( $value ) ? wp_kses_post( (string) $value ) : '';
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
