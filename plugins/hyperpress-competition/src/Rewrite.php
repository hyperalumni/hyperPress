<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * Routes the season competition archive filtered by one more dimension.
 * Season\Rewrite owns the unfiltered /seasons/<season>/competitions/ rule.
 *
 * - /seasons/<season>/competitions/<type>/ (competition type; may be hierarchical)
 * - /seasons/<season>/competitions/<districts>/<district>/ (district; may be hierarchical)
 * - /seasons/<season>/competitions/<weeks>/<week>/ (week; may be hierarchical)
 *
 * The third path segment is ambiguous between type, district and week, so the
 * district and week archives are prefixed with their taxonomy rewrite slugs.
 * Rule order matters: with the 'top' position the rule added first is matched
 * first (core appends to extra_rules_top), so the prefixed rules are added
 * before the type rule, otherwise the type rule would swallow
 * "districts/<district>" as a type path.
 *
 * Each rule also has a paged variant (".../page/<n>/") registered right before
 * its unpaged counterpart, otherwise the unpaged "(.+?)" would read
 * "<term>/page/<n>" as a term path.
 */
final class Rewrite {

	private const HASH_OPTION = 'hyperpress_competition_rewrite_hash';

	/** Bump when the shape of the added rules changes. 2: paged archives. */
	private const RULES_VERSION = 2;

	public static function register(): void {
		// After the post type and taxonomies register (priority 10) and after Season\Rewrite (20).
		add_action( 'init', array( self::class, 'add_rules' ), 21 );
		// After the rules exist, so a changed rule set is persisted.
		add_action( 'init', array( self::class, 'maybe_flush' ), 99 );
	}

	/**
	 * The prefixed filter taxonomies: query var => taxonomy.
	 */
	private const PREFIXED = array( 'hyper_district', 'hyper_week' );

	/**
	 * The rewrite slug of a taxonomy, or null when it is missing or has none.
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return string|null
	 */
	private static function taxonomy_slug( string $taxonomy ): ?string {
		$object = get_taxonomy( $taxonomy );
		$slug   = ( $object && is_array( $object->rewrite ) ) ? ( $object->rewrite['slug'] ?? null ) : null;

		return ( is_string( $slug ) && '' !== $slug ) ? $slug : null;
	}

	/**
	 * The slugs that determine the rule text, or null when no rule applies.
	 *
	 * @return array{season: string, archive: string, prefixes: array<string, string>}|null Season rewrite slug,
	 *         competition archive slug and the district and week prefix slugs (only the taxonomies that have one).
	 */
	private static function slugs(): ?array {
		$type = get_post_type_object( Plugin::POST_TYPE );

		if ( ! $type || ! taxonomy_exists( Plugin::TYPE ) ) {
			return null;
		}

		$season_slug = self::taxonomy_slug( 'hyper_season' );
		$archive     = $type->has_archive;

		if ( null === $season_slug || ! is_string( $archive ) || '' === $archive ) {
			return null;
		}

		$prefixes = array();
		foreach ( self::PREFIXED as $taxonomy ) {
			$slug = self::taxonomy_slug( $taxonomy );
			if ( null !== $slug ) {
				$prefixes[ $taxonomy ] = $slug;
			}
		}

		return array(
			'season'   => $season_slug,
			'archive'  => $archive,
			'prefixes' => $prefixes,
		);
	}

	/**
	 * The season-scoped archive URL of a type, district or week term: the URL the rules above route.
	 *
	 * Built from the same slugs() as the rules. A hierarchical term's path is its ancestors' slugs, top
	 * down, then its own. Without pretty permalinks it is the equivalent query-string URL.
	 *
	 * @param \WP_Term $season The season term (hyper_season).
	 * @param \WP_Term $term   A competition type, district or week term.
	 * @return string The URL, or '' for any other taxonomy or when a slug or query var is unavailable.
	 */
	public static function archive_url( \WP_Term $season, \WP_Term $term ): string {
		$slugs = self::slugs();
		if ( null === $slugs || 'hyper_season' !== $season->taxonomy || '' === $season->slug ) {
			return '';
		}

		$prefix = '';
		if ( in_array( $term->taxonomy, self::PREFIXED, true ) ) {
			if ( ! isset( $slugs['prefixes'][ $term->taxonomy ] ) ) {
				return '';
			}
			$prefix = $slugs['prefixes'][ $term->taxonomy ];
		} elseif ( Plugin::TYPE !== $term->taxonomy ) {
			return '';
		}

		$taxonomy  = get_taxonomy( $term->taxonomy );
		$query_var = $taxonomy ? $taxonomy->query_var : false;
		if ( ! is_string( $query_var ) || '' === $query_var ) {
			return '';
		}

		if ( '' === (string) get_option( 'permalink_structure' ) ) {
			return add_query_arg(
				array(
					'hyper_season' => $season->slug,
					'post_type'    => Plugin::POST_TYPE,
					$query_var     => $term->slug,
				),
				home_url( '/' )
			);
		}

		$path = array();
		foreach ( array_reverse( get_ancestors( $term->term_id, $term->taxonomy, 'taxonomy' ) ) as $ancestor_id ) {
			$ancestor = get_term( $ancestor_id, $term->taxonomy );
			if ( $ancestor instanceof \WP_Term ) {
				$path[] = $ancestor->slug;
			}
		}
		$path[] = $term->slug;

		$parts = array( $slugs['season'], $season->slug, $slugs['archive'] );
		if ( '' !== $prefix ) {
			$parts[] = $prefix;
		}

		return home_url( user_trailingslashit( implode( '/', array_merge( $parts, $path ) ) ) );
	}

	public static function add_rules(): void {
		$slugs = self::slugs();
		if ( null === $slugs ) {
			return;
		}

		$base = '^' . preg_quote( $slugs['season'], '#' ) . '/([^/]+)/' . preg_quote( $slugs['archive'], '#' ) . '/';

		foreach ( $slugs['prefixes'] as $taxonomy => $prefix ) {
			self::add_pair( $base . preg_quote( $prefix, '#' ) . '/', $taxonomy );
		}

		// Last, so it is matched last.
		self::add_pair( $base, Plugin::TYPE );
	}

	/**
	 * Adds the paged rule, then the unpaged one, for one filter taxonomy ('top': the first added is matched first).
	 *
	 * @param string $prefix   Regex up to and excluding the term path.
	 * @param string $taxonomy Query var of the filter taxonomy.
	 */
	private static function add_pair( string $prefix, string $taxonomy ): void {
		$query = 'index.php?hyper_season=$matches[1]&post_type=' . Plugin::POST_TYPE . '&' . $taxonomy . '=$matches[2]';

		add_rewrite_rule( $prefix . '(.+?)/page/([0-9]+)/?$', $query . '&paged=$matches[3]', 'top' );
		add_rewrite_rule( $prefix . '(.+?)/?$', $query, 'top' );
	}

	/**
	 * Flushes the rewrite rules once when the rule text would change, for
	 * example after the season slug, the archive slug or a district or week
	 * prefix slug was changed. The rules version is part of the hash, so changing
	 * the rule shapes re-flushes existing sites.
	 */
	public static function maybe_flush(): void {
		$hash = md5( (string) wp_json_encode( array( self::RULES_VERSION, self::slugs() ) ) );

		if ( get_option( self::HASH_OPTION ) === $hash ) {
			return;
		}

		// Store first: flush_rewrite_rules() must never run twice for one change.
		update_option( self::HASH_OPTION, $hash );
		flush_rewrite_rules( false );
	}
}
