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
 */
final class Rewrite {

	private const HASH_OPTION = 'hyperpress_competition_rewrite_hash';

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

	public static function add_rules(): void {
		$slugs = self::slugs();
		if ( null === $slugs ) {
			return;
		}

		$base = '^' . preg_quote( $slugs['season'], '#' ) . '/([^/]+)/' . preg_quote( $slugs['archive'], '#' ) . '/';

		foreach ( $slugs['prefixes'] as $taxonomy => $prefix ) {
			add_rewrite_rule(
				$base . preg_quote( $prefix, '#' ) . '/(.+?)/?$',
				'index.php?hyper_season=$matches[1]&post_type=' . Plugin::POST_TYPE . '&' . $taxonomy . '=$matches[2]',
				'top'
			);
		}

		// Last, so it is matched last.
		add_rewrite_rule(
			$base . '(.+?)/?$',
			'index.php?hyper_season=$matches[1]&post_type=' . Plugin::POST_TYPE . '&' . Plugin::TYPE . '=$matches[2]',
			'top'
		);
	}

	/**
	 * Flushes the rewrite rules once when the rule text would change, for
	 * example after the season slug, the archive slug or a district or week
	 * prefix slug was changed.
	 */
	public static function maybe_flush(): void {
		$hash = md5( (string) wp_json_encode( self::slugs() ) );

		if ( get_option( self::HASH_OPTION ) === $hash ) {
			return;
		}

		// Store first: flush_rewrite_rules() must never run twice for one change.
		update_option( self::HASH_OPTION, $hash );
		flush_rewrite_rules( false );
	}
}
