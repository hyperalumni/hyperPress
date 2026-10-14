<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

/**
 * Routes /seasons/<season>/competitions/<type>/ to the competition archive
 * filtered by season and competition type. Season\Rewrite owns the unfiltered
 * /seasons/<season>/competitions/ rule.
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
	 * The slugs that determine the rule text, or null when no rule applies.
	 *
	 * @return array{0: string, 1: string}|null Season rewrite slug and competition archive slug.
	 */
	private static function slugs(): ?array {
		$season = get_taxonomy( 'hyper_season' );
		$type   = get_post_type_object( Plugin::POST_TYPE );

		if ( ! $season || ! $type || ! taxonomy_exists( Plugin::TYPE ) ) {
			return null;
		}

		$season_slug = is_array( $season->rewrite ) ? ( $season->rewrite['slug'] ?? null ) : null;
		$archive     = $type->has_archive;

		if ( ! is_string( $season_slug ) || '' === $season_slug || ! is_string( $archive ) || '' === $archive ) {
			return null;
		}

		return array( $season_slug, $archive );
	}

	public static function add_rules(): void {
		$slugs = self::slugs();
		if ( null === $slugs ) {
			return;
		}

		add_rewrite_rule(
			'^' . preg_quote( $slugs[0], '#' ) . '/([^/]+)/' . preg_quote( $slugs[1], '#' ) . '/(.+?)/?$',
			'index.php?hyper_season=$matches[1]&post_type=' . Plugin::POST_TYPE . '&' . Plugin::TYPE . '=$matches[2]',
			'top'
		);
	}

	/**
	 * Flushes the rewrite rules once when the rule text would change, for
	 * example after the season slug or the archive slug was changed.
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
