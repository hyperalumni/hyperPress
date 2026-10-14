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
	 *                          Optional: term_joiner ('in' default),
	 *                          taxonomies (list of taxonomy keys whose requested terms are named in the banner:
	 *                          appended to the archive subtitle, and the title on a taxonomy page). The terms are
	 *                          only read for non-singular requests that are this post type's archive or a
	 *                          taxonomy-only archive (is_tax() with no post-type archive), never for another
	 *                          post type's request. A taxonomy-only archive that also carries a season is the
	 *                          season's page: no terms are read there, so the Season plugin's banner stands (the
	 *                          combined view is the post-type archive).
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
					'extra_terms'   => array(),
				);
				// Only this post type's requests: its archive, or a taxonomy archive that is not any post type's archive.
				// Another post type's archive or singular (or a page carrying a taxonomy query var) must not take the taxonomy branch.
				// A taxonomy archive that also carries a season is the season's page: the Season plugin's banner stands there.
				$own_request = ! is_singular() && ( $ctx['is_archive'] || ( is_tax() && ! is_post_type_archive() && ! $is_tax_season ) );
				if ( $own_request ) {
					foreach ( (array) ( $args['taxonomies'] ?? array() ) as $taxonomy ) {
						// Not is_tax( $taxonomy ): it only matches the one taxonomy WP picked as the queried object.
						$term = RequestTerm::get( (string) $taxonomy );
						if ( $term instanceof \WP_Term ) {
							$ctx['extra_terms'][] = $term->name;
						}
					}
				}
				if ( ! $ctx['is_singular'] && ! $ctx['is_archive'] && empty( $ctx['extra_terms'] ) ) {
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
	 * @param array $ctx    is_singular, is_archive, is_tax_season, subtitle, color, term_name,
	 *                      extra_terms (optional list<string> of requested term names from args['taxonomies']).
	 */
	public static function apply( array $banner, array $args, array $ctx ): array {
		$extra_terms = $ctx['extra_terms'] ?? array();
		if ( ! $ctx['is_singular'] && ! $ctx['is_archive'] && empty( $extra_terms ) ) {
			return $banner;
		}

		$banner['type']            = $args['type'];
		$banner['subtitle']        = $ctx['subtitle'];
		$banner['backgroundColor'] = $ctx['color'];

		if ( $ctx['is_archive'] && empty( $banner['title'] ) ) {
			$banner['title'] = $args['archive_title'];
			$names           = $extra_terms;
			if ( $ctx['is_tax_season'] ) {
				$names[] = $ctx['term_name'];
			}
			if ( ! empty( $names ) && ! empty( $ctx['subtitle'] ) ) {
				$banner['subtitle'] = $ctx['subtitle'] . ' ' . ( $args['term_joiner'] ?? 'in' ) . ' ' . implode( ', ', $names );
			}
		} elseif ( ! $ctx['is_singular'] && ! $ctx['is_archive'] ) {
			// Taxonomy page: always name the requested terms, even over a title another banner set (e.g. the season's).
			$banner['title'] = implode( ' ', $extra_terms ) . ' ' . $args['archive_title'];
		}

		return $banner;
	}
}
