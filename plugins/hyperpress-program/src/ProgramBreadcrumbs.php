<?php
namespace HyperPress\Program;

use HyperPress\Utils\RequestTerm;

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumb trail for the program archives: `Programs > FIRST > FTC`.
 *
 * The first crumb is the taxonomy's plural label (read at filter time) with no url, the ancestors link to their own
 * archives from the top down, and the last crumb is the program itself. The Home crumb comes from the theme.
 *
 * Any trail another filter already added wins: the theme passes in only the Home crumb, so a longer array means a
 * season, competition or other trail owns the request.
 */
final class ProgramBreadcrumbs {

	public static function register(): void {
		// Priority 20: the season, competition and theme trails run at 10 and win mixed requests.
		add_filter( 'hyperpress_breadcrumbs_content', array( self::class, 'filter' ), 20 );
	}

	public static function filter( $breadcrumbs ) {
		if ( ! is_tax() || is_post_type_archive() || 1 !== count( (array) $breadcrumbs ) ) {
			return $breadcrumbs;
		}

		// is_tax( $taxonomy ) only matches the one taxonomy WP picks as the queried object, so ask the program itself.
		$term = RequestTerm::get( Plugin::TAXONOMY );
		if ( ! $term ) {
			return $breadcrumbs;
		}

		$taxonomy = get_taxonomy( Plugin::TAXONOMY );
		$label    = $taxonomy && ! empty( $taxonomy->labels->name ) ? $taxonomy->labels->name : __( 'Programs', 'hyperpress' );

		$breadcrumbs   = (array) $breadcrumbs;
		$breadcrumbs[] = self::crumb( $label );

		foreach ( array_reverse( get_ancestors( $term->term_id, Plugin::TAXONOMY, 'taxonomy' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, Plugin::TAXONOMY );
			if ( ! $ancestor instanceof \WP_Term ) {
				continue;
			}
			$link          = get_term_link( $ancestor );
			$breadcrumbs[] = self::crumb( $ancestor->name, is_wp_error( $link ) ? '' : $link );
		}

		$breadcrumbs[] = self::crumb( $term->name );

		return $breadcrumbs;
	}

	private static function crumb( string $title, ?string $url = null ): array {
		$crumb = array( 'title' => $title );
		if ( null !== $url ) {
			$crumb['url'] = $url;
		}
		$crumb['classes'] = array(
			'li'    => array(),
			'bread' => array(),
		);

		return $crumb;
	}
}
