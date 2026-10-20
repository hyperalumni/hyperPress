<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

final class Rewrite {

	private const HASH_OPTION = 'hyperpress_season_rewrite_hash';

	/** Bump when the shape of the added rules changes. 2: paged archives. */
	private const RULES_VERSION = 2;

	public static function register(): void {
		// After the post types register at priority 10.
		add_action( 'init', array( self::class, 'add_rules' ), 20 );
		// After the rules exist, so a changed set of archives is persisted.
		add_action( 'init', array( self::class, 'maybe_flush' ), 99 );
	}

	/**
	 * Lists the (archive slug, post type key) pairs that get a season rule.
	 *
	 * @return array<int, array{0: string, 1: string}>
	 */
	private static function archives(): array {
		$archives = array();

		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $key => $type ) {
			if ( 0 !== strpos( $key, 'hyper_' ) || ! is_string( $type->has_archive ) || '' === $type->has_archive ) {
				continue;
			}
			$archives[] = array( $type->has_archive, $key );
		}

		sort( $archives );

		return $archives;
	}

	public static function add_rules(): void {
		$taxonomy = get_taxonomy( Plugin::TAXONOMY );
		if ( ! $taxonomy || empty( $taxonomy->rewrite['slug'] ) ) {
			return;
		}
		$season_slug = $taxonomy->rewrite['slug'];

		foreach ( self::archives() as list( $archive, $key ) ) {
			add_rewrite_rule(
				'^' . preg_quote( $season_slug, '#' ) . '/([^/]+)/' . preg_quote( $archive, '#' ) . '/?$',
				'index.php?' . Plugin::TAXONOMY . '=$matches[1]&post_type=' . $key,
				'top'
			);
			// Pagination as its own rule: an empty `paged=` would be sent on page 1.
			add_rewrite_rule(
				'^' . preg_quote( $season_slug, '#' ) . '/([^/]+)/' . preg_quote( $archive, '#' ) . '/page/([0-9]+)/?$',
				'index.php?' . Plugin::TAXONOMY . '=$matches[1]&post_type=' . $key . '&paged=$matches[2]',
				'top'
			);
		}
	}

	/**
	 * Flushes the rewrite rules once when the set of archives changed, for
	 * example after another plugin registering a post type was activated.
	 */
	public static function maybe_flush(): void {
		// The rules version is part of the hash, so changing the rule shapes re-flushes existing sites.
		$hash = md5( (string) wp_json_encode( array( self::RULES_VERSION, self::archives() ) ) );

		if ( get_option( self::HASH_OPTION ) === $hash ) {
			return;
		}

		// Store first: flush_rewrite_rules() must never run twice for one change.
		update_option( self::HASH_OPTION, $hash );
		flush_rewrite_rules( false );
	}
}
