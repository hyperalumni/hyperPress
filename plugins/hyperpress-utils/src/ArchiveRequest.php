<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The request context the banners share: which post type's request this is and which terms it carries.
 *
 * It reads the taxonomies' own query vars through RequestTerm, because is_tax( $taxonomy ) only matches the one
 * taxonomy WP picks as the queried object.
 */
final class ArchiveRequest {

	/**
	 * Request context for one post type.
	 *
	 * `extra_terms` holds the names of the requested terms of `$taxonomies`, and only for this post type's own
	 * requests: its archive, or a taxonomy archive that is not any post type's archive and does not carry a season (that
	 * is the season's page). Never for another post type's archive or for a singular request.
	 *
	 * @param string   $post_type  Post type key.
	 * @param string[] $taxonomies Taxonomy keys whose requested terms are named in the banner.
	 * @return array{is_singular: bool, is_archive: bool, is_tax_season: bool, season: ?\WP_Term, extra_terms: string[]}
	 */
	public static function for_post_type( string $post_type, array $taxonomies = array() ): array {
		// is_tax( $taxonomy ) only matches the one taxonomy WP picks as the queried object, so ask the season itself.
		$season        = is_tax() ? RequestTerm::get( Banner::SEASON_TAXONOMY ) : null;
		$is_tax_season = null !== $season;
		$is_singular   = is_singular( $post_type );
		$is_archive    = is_post_type_archive( $post_type );
		$extra_terms   = array();

		// Only this post type's requests: its archive, or a taxonomy archive that is not any post type's archive.
		// Another post type's archive or singular (or a page carrying a taxonomy query var) must not take the taxonomy branch.
		// A taxonomy archive that also carries a season is the season's page: the Season plugin's banner stands there.
		$own_request = ! is_singular() && ( $is_archive || ( is_tax() && ! is_post_type_archive() && ! $is_tax_season ) );
		if ( $own_request ) {
			foreach ( $taxonomies as $taxonomy ) {
				// Not is_tax( $taxonomy ): it only matches the one taxonomy WP picked as the queried object.
				$term = RequestTerm::get( (string) $taxonomy );
				if ( $term instanceof \WP_Term ) {
					$extra_terms[] = $term->name;
				}
			}
		}

		return array(
			'is_singular'   => $is_singular,
			'is_archive'    => $is_archive,
			'is_tax_season' => $is_tax_season,
			'season'        => $season,
			'extra_terms'   => $extra_terms,
		);
	}

	/**
	 * The taxonomy's term on a taxonomy page that is not a post-type archive.
	 *
	 * @param string $taxonomy Taxonomy key.
	 */
	public static function taxonomy_term( string $taxonomy ): ?\WP_Term {
		return is_tax() && ! is_post_type_archive() ? RequestTerm::get( $taxonomy ) : null;
	}
}
