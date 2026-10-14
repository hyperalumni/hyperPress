<?php
namespace HyperPress\Competition;

use HyperPress\Utils\Breadcrumbs;
use HyperPress\Utils\RequestTerm;

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumb trail for the competition type archive (`/competition-types/district/`).
 *
 * The combined season + type competitions archive is handled by `Utils\Breadcrumbs`.
 */
final class TypeBreadcrumbs {

	public static function register(): void {
		add_filter( 'hyperpress_breadcrumbs_content', array( self::class, 'filter' ) );
	}

	public static function filter( $breadcrumbs ) {
		// is_tax( $taxonomy ) only matches the taxonomy WP picks as the queried object, so ask the term itself.
		// A request that also carries a season is the season page, whose trail the Season plugin owns.
		$type_only = is_tax() && ! is_post_type_archive() && ! RequestTerm::get( Breadcrumbs::SEASON_TAXONOMY );
		$term      = $type_only ? RequestTerm::get( Plugin::TYPE ) : null;
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
