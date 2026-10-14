<?php
namespace HyperPress\Competition;

use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\RequestTerm;

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumb trails for the competition taxonomy archives.
 *
 * Type (`/competition-types/district/`), district (`/districts/<district>/`) and week (`/weeks/<week>/`) all get
 * `Competitions > <taxonomy singular label> > <term>`, e.g. `Competitions > Type > District`.
 *
 * The middle crumb is read from the taxonomy object at filter time (the label registry may translate it) and has no url.
 * Its visible `title` is the taxonomy's `breadcrumb_label` label when it has one (the type taxonomy's short "Type"), else its
 * `singular_name`. Its `title_attr` (the theme's title attribute, native hover text) is always the `singular_name`, e.g.
 * "Competition Type" for the "Type" crumb; district and week have no `breadcrumb_label`, so their two are equal.
 * The last crumb is the term's own name only, with no ancestors. The Home crumb comes from the theme.
 *
 * A request carrying more than one of these taxonomies gets the trail for the first of type, district, week only.
 * A request that also carries a season is the season page, whose trail the Season plugin owns, and the post-type
 * archive trails (including the combined season + type archive) belong to `Utils\Breadcrumbs`.
 */
final class TaxonomyBreadcrumbs {

	/**
	 * Taxonomies in priority order.
	 */
	private const TAXONOMIES = array(
		Plugin::TYPE,
		Plugin::DISTRICT,
		Plugin::WEEK,
	);

	public static function register(): void {
		add_filter( 'hyperpress_breadcrumbs_content', array( self::class, 'filter' ) );
	}

	public static function filter( $breadcrumbs ) {
		// is_tax( $taxonomy ) only matches the taxonomy WP picks as the queried object, so ask the term itself.
		// A request that also carries a season is the season page, whose trail the Season plugin owns.
		if ( ! is_tax() || is_post_type_archive() || RequestTerm::get( Breadcrumbs::SEASON_TAXONOMY ) ) {
			return $breadcrumbs;
		}

		$term     = null;
		$taxonomy = '';
		foreach ( self::TAXONOMIES as $candidate ) {
			$term = RequestTerm::get( $candidate );
			if ( $term ) {
				$taxonomy = $candidate;
				break;
			}
		}
		if ( ! $term ) {
			return $breadcrumbs;
		}

		$post_type = get_post_type_object( Plugin::POST_TYPE );
		if ( ! $post_type ) {
			return $breadcrumbs;
		}

		$label = $post_type->labels->name;
		$class = sanitize_html_class( sanitize_title( $label ) );
		$url   = get_post_type_archive_link( Plugin::POST_TYPE );

		$breadcrumbs   = (array) $breadcrumbs;
		$breadcrumbs[] = array(
			'title'   => $label,
			'url'     => $url ? $url : '',
			'classes' => array(
				'li'    => array( 'item-archive-' . $class ),
				'bread' => array( 'bread-archive-' . $class ),
			),
		);

		$taxonomy_object = get_taxonomy( $taxonomy );
		if ( $taxonomy_object ) {
			$singular      = $taxonomy_object->labels->singular_name;
			$short         = isset( $taxonomy_object->labels->breadcrumb_label ) ? $taxonomy_object->labels->breadcrumb_label : '';
			$breadcrumbs[] = array(
				'title'      => is_string( $short ) && '' !== $short ? $short : $singular,
				'title_attr' => $singular,
				'classes'    => array(
					'li'    => array(),
					'bread' => array(),
				),
			);
		}

		$breadcrumbs[] = array(
			'title'   => $term->name,
			'classes' => array(
				'li'    => array(),
				'bread' => array(),
			),
		);

		return $breadcrumbs;
	}
}
