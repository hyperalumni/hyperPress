<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Season\Rewrite;
use WP_UnitTestCase;

final class SeasonRewriteQuotingTest extends WP_UnitTestCase {

	private const HASH_OPTION = 'hyperpress_season_rewrite_hash';

	public function tear_down(): void {
		global $wp_rewrite;

		unregister_post_type( 'hyper_quotetest' );
		foreach ( array_keys( $wp_rewrite->extra_rules_top ) as $regex ) {
			if ( false !== strpos( $regex, '/quo' ) ) {
				unset( $wp_rewrite->extra_rules_top[ $regex ] );
			}
		}
		delete_option( self::HASH_OPTION );
		parent::tear_down();
	}

	public function test_the_archive_slug_is_quoted_for_the_hash_delimiter(): void {
		register_post_type(
			'hyper_quotetest',
			array(
				'public'      => true,
				'has_archive' => 'quo#te.s',
				'rewrite'     => array( 'slug' => 'quotetest' ),
			)
		);
		Rewrite::add_rules();
		Rewrite::maybe_flush();

		$rules = (array) get_option( 'rewrite_rules' );
		$key   = '^seasons/([^/]+)/quo\#te\.s/?$';

		$this->assertArrayHasKey( $key, $rules );
		$this->assertSame( 'index.php?hyper_season=$matches[1]&post_type=hyper_quotetest', $rules[ $key ] );
	}
}
