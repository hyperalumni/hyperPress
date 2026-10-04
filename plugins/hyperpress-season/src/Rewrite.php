<?php
namespace HyperPress\Season;

defined( 'ABSPATH' ) || exit;

final class Rewrite {

	public static function register(): void {
		// After the post types register at priority 10.
		add_action( 'init', array( self::class, 'add_rules' ), 20 );
	}

	public static function add_rules(): void {
		$taxonomy = get_taxonomy( Plugin::TAXONOMY );
		if ( ! $taxonomy || empty( $taxonomy->rewrite['slug'] ) ) {
			return;
		}
		$season_slug = $taxonomy->rewrite['slug'];

		foreach ( get_post_types( array( 'public' => true ), 'objects' ) as $key => $type ) {
			if ( 0 !== strpos( $key, 'hyper_' ) || ! is_string( $type->has_archive ) || '' === $type->has_archive ) {
				continue;
			}
			add_rewrite_rule(
				'^' . preg_quote( $season_slug ) . '/([^/]+)/' . preg_quote( $type->has_archive ) . '/?$', // phpcs:ignore WordPress.PHP.PregQuoteDelimiter.Missing -- plugins audit
				'index.php?' . Plugin::TAXONOMY . '=$matches[1]&post_type=' . $key,
				'top'
			);
		}
	}
}
