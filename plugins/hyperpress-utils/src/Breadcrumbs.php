<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Breadcrumb trails for a plugin post type's archive and singular pages.
 *
 * Filter `hyperpress_breadcrumbs_term_url` lets a plugin rewrite the link of an extra term crumb (not the
 * season crumb), for example to a season-scoped archive:
 *
 *     apply_filters( 'hyperpress_breadcrumbs_term_url', string $url, \WP_Term $term, string $post_type, ?\WP_Term $season )
 *
 * `$url` is the term's `get_term_link()` ('' when that is an error); `$season` is the season term used for the
 * season crumb, or null when the request has none. The result is used only when it is a non-empty string,
 * otherwise `$url` stays.
 */
final class Breadcrumbs {

	public const SEASON_TAXONOMY = 'hyper_season';

	/**
	 * Add a trail for each post type to the theme's breadcrumb filter.
	 *
	 * @param string[] $post_types Post type keys.
	 * @param string[] $taxonomies Extra taxonomy keys whose term is shown after the season (broad to narrow).
	 */
	public static function register( array $post_types, array $taxonomies = array() ): void {
		foreach ( $post_types as $post_type ) {
			add_filter(
				'hyperpress_breadcrumbs_content',
				static function ( $breadcrumbs ) use ( $post_type, $taxonomies ) {
					$ctx = self::context( $post_type, $taxonomies );
					return null === $ctx ? $breadcrumbs : self::build( (array) $breadcrumbs, $ctx );
				}
			);
		}
	}

	/**
	 * Pure: append the post type, season, extra term and current-item crumbs, in that order (broad to narrow).
	 *
	 * @param array $breadcrumbs Existing crumbs.
	 * @param array $ctx         post_type, label, archive_url, is_singular, season (name, slug, url) or null, title, id,
	 *                           and optionally terms (a list of name, slug, url), shown after the season.
	 */
	public static function build( array $breadcrumbs, array $ctx ): array {
		$label = (string) $ctx['label'];
		$class = sanitize_html_class( sanitize_title( $label ) );

		if ( '' !== $label ) {
			$breadcrumbs[] = array(
				'title'   => $label,
				'url'     => $ctx['archive_url'],
				'classes' => array(
					'li'    => array( 'item-archive-' . $class ),
					'bread' => array( 'bread-archive-' . $class ),
				),
			);
		}

		if ( ! empty( $ctx['season'] ) ) {
			$breadcrumbs[] = self::term_crumb( $ctx['season'], $class );
		}

		foreach ( $ctx['terms'] ?? array() as $term ) {
			$breadcrumbs[] = self::term_crumb( $term, $class );
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
	 * One term or season crumb: title, url and the item-/bread- classes built from its slug.
	 *
	 * @param array  $term       name, slug and url.
	 * @param string $class_slug Sanitized post type label slug.
	 */
	private static function term_crumb( array $term, string $class_slug ): array {
		return array(
			'title'   => $term['name'],
			'url'     => $term['url'],
			'classes' => array(
				'li'    => array( 'item-' . $term['slug'] . '-' . $class_slug ),
				'bread' => array( 'bread-' . $term['slug'] . '-' . $class_slug ),
			),
		);
	}

	/**
	 * Read the request into a context array, or null when the request is not this post type's
	 * singular or archive.
	 *
	 * @param string   $post_type Post type key.
	 * @param string[] $taxonomies Extra taxonomy keys.
	 */
	private static function context( string $post_type, array $taxonomies = array() ): ?array {
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
				usort( $terms, static fn( $a, $b ) => strnatcasecmp( $b->name, $a->name ) );
				$term = $terms[0];
			}
		} elseif ( is_tax() ) {
			// is_tax( $taxonomy ) only matches the one taxonomy WP picks as the queried object, so ask the season itself.
			$term = RequestTerm::get( self::SEASON_TAXONOMY );
		}

		$season_term = $term;
		$season      = null;
		if ( $term ) {
			$link   = get_term_link( $term );
			$season = array(
				'name' => $term->name,
				'slug' => $term->slug,
				'url'  => is_wp_error( $link ) ? '' : trailingslashit( $link ) . ( is_string( $object->has_archive ) ? $object->has_archive : '' ),
			);
		}

		return array(
			'terms'       => self::extra_terms( $taxonomies, $is_singular, $post_type, $season_term ),
			'post_type'   => $post_type,
			'label'       => (string) ( $object->labels->name ?? '' ),
			'archive_url' => (string) get_post_type_archive_link( $post_type ),
			'is_singular' => $is_singular,
			'season'      => $season,
			'title'       => $is_singular ? get_the_title() : '',
			'id'          => $is_singular ? (int) get_the_ID() : 0,
		);
	}

	/**
	 * One crumb per registered extra taxonomy that has a term for this request.
	 *
	 * @param string[]      $taxonomies  Taxonomy keys.
	 * @param bool          $is_singular Whether the request is a single post.
	 * @param string        $post_type   Post type key.
	 * @param \WP_Term|null $season_term The season term used for the season crumb, or null.
	 * @return array<int, array{name: string, slug: string, url: string}>
	 */
	private static function extra_terms( array $taxonomies, bool $is_singular, string $post_type, ?\WP_Term $season_term ): array {
		$crumbs = array();
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$term = null;
			if ( $is_singular ) {
				$terms = wp_get_object_terms( get_the_ID(), $taxonomy );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					// Natural order (Week 2 before Week 10); get_terms ordering is not a natural sort.
					usort( $terms, static fn( $a, $b ) => strnatcasecmp( $a->name, $b->name ) );
					$term = $terms[0];
				}
			} else {
				$term = RequestTerm::get( $taxonomy );
			}

			if ( ! $term ) {
				continue;
			}

			$link = get_term_link( $term );
			$url  = is_wp_error( $link ) ? '' : $link;

			/**
			 * Filters the link of an extra term crumb.
			 *
			 * @param string        $url       The term's archive link, or '' when it has none.
			 * @param \WP_Term      $term      The crumb's term.
			 * @param string        $post_type Post type key.
			 * @param \WP_Term|null $season    The season term used for the season crumb, or null.
			 */
			$filtered = apply_filters( 'hyperpress_breadcrumbs_term_url', $url, $term, $post_type, $season_term );

			$crumbs[] = array(
				'name' => $term->name,
				'slug' => $term->slug,
				'url'  => ( is_string( $filtered ) && '' !== $filtered ) ? $filtered : $url,
			);
		}
		return $crumbs;
	}
}
