<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

final class Breadcrumbs {

	public const SEASON_TAXONOMY = 'hyper_season';

	/**
	 * Add a trail for each post type to the theme's breadcrumb filter.
	 *
	 * @param string[] $post_types Post type keys.
	 */
	public static function register( array $post_types ): void {
		foreach ( $post_types as $post_type ) {
			add_filter(
				'hyperpress_breadcrumbs_content',
				static function ( $breadcrumbs ) use ( $post_type ) {
					$ctx = self::context( $post_type );
					return null === $ctx ? $breadcrumbs : self::build( (array) $breadcrumbs, $ctx );
				}
			);
		}
	}

	/**
	 * Pure: append the post type, season and current-item crumbs.
	 *
	 * @param array $breadcrumbs Existing crumbs.
	 * @param array $ctx         post_type, label, archive_url, is_singular, season (name, slug, url) or null, title, id.
	 */
	public static function build( array $breadcrumbs, array $ctx ): array {
		$label = (string) $ctx['label'];

		if ( '' !== $label ) {
			$breadcrumbs[] = array(
				'title'   => $label,
				'url'     => $ctx['archive_url'],
				'classes' => array(
					'li'    => array( 'item-archive-' . $label ),
					'bread' => array( 'bread-archive-' . $label ),
				),
			);
		}

		if ( ! empty( $ctx['season'] ) ) {
			$season        = $ctx['season'];
			$breadcrumbs[] = array(
				'title'   => $season['name'],
				'url'     => $season['url'],
				'classes' => array(
					'li'    => array( 'item-' . $season['slug'] . '-' . $label ),
					'bread' => array( 'bread-' . $season['slug'] . '-' . $label ),
				),
			);
		}

		if ( ! empty( $ctx['is_singular'] ) ) {
			$name          = (string) $ctx['post_type'];
			$breadcrumbs[] = array(
				'title'   => $ctx['title'],
				'classes' => array(
					'li'    => array( 'item-' . $name, 'item-' . $name . $ctx['id'] ),
					'bread' => array( 'bread-' . $name, 'bread-' . $name . $ctx['id'] ),
				),
			);
		}

		return $breadcrumbs;
	}

	/**
	 * Read the request into a context array, or null when the request is not this post type's
	 * singular or archive.
	 */
	private static function context( string $post_type ): ?array {
		$is_singular = is_singular( $post_type );
		if ( ! $is_singular && ! is_post_type_archive( $post_type ) ) {
			return null;
		}

		$object = get_post_type_object( $post_type );
		if ( ! $object ) {
			return null;
		}

		$term = null;
		if ( $is_singular ) {
			$terms = wp_get_object_terms(
				get_the_ID(),
				self::SEASON_TAXONOMY,
				array(
					'orderby' => 'name',
					'order'   => 'DESC',
				)
			);
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				// get_terms ordering is inconsistent even with orderby, so sort again by name.
				usort( $terms, static fn( $a, $b ) => $b->name <=> $a->name );
				$term = $terms[0];
			}
		} elseif ( is_tax( self::SEASON_TAXONOMY ) ) {
			$queried = get_queried_object();
			$term    = $queried instanceof \WP_Term ? $queried : null;
		}

		$season = null;
		if ( $term ) {
			$link   = get_term_link( $term );
			$season = array(
				'name' => $term->name,
				'slug' => $term->slug,
				'url'  => is_wp_error( $link ) ? '' : trailingslashit( $link ) . ( is_string( $object->has_archive ) ? $object->has_archive : '' ),
			);
		}

		return array(
			'post_type'   => $post_type,
			'label'       => (string) ( $object->labels->name ?? '' ),
			'archive_url' => (string) get_post_type_archive_link( $post_type ),
			'is_singular' => $is_singular,
			'season'      => $season,
			'title'       => $is_singular ? get_the_title() : '',
			'id'          => $is_singular ? (int) get_the_ID() : 0,
		);
	}
}
