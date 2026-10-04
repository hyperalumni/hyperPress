<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Season\Rewrite;
use WP_UnitTestCase;

final class SeasonRewriteFlushTest extends WP_UnitTestCase {

	private const HASH_OPTION = 'hyperpress_season_rewrite_hash';

	private int $generations = 0;

	public function set_up(): void {
		parent::set_up();
		Rewrite::add_rules();
		Rewrite::maybe_flush();
	}

	public function tear_down(): void {
		global $wp_rewrite;

		unregister_post_type( 'hyper_flushtest' );
		foreach ( array_keys( $wp_rewrite->extra_rules_top ) as $regex ) {
			if ( false !== strpos( $regex, 'flushtests' ) ) {
				unset( $wp_rewrite->extra_rules_top[ $regex ] );
			}
		}
		delete_option( self::HASH_OPTION );
		parent::tear_down();
	}

	private function register_late_archive(): void {
		register_post_type(
			'hyper_flushtest',
			array(
				'public'      => true,
				'has_archive' => 'flushtests',
				'rewrite'     => array( 'slug' => 'flushtest' ),
			)
		);
		Rewrite::add_rules();
	}

	private function count_rule_generations(): void {
		add_filter(
			'rewrite_rules_array',
			function ( $rules ) {
				++$this->generations;
				return $rules;
			}
		);
	}

	public function test_a_late_archive_changes_the_hash_and_is_flushed_into_the_stored_rules(): void {
		$before = get_option( self::HASH_OPTION );
		$this->assertNotEmpty( $before, 'The gate should have stored a hash.' );

		$this->register_late_archive();
		Rewrite::maybe_flush();

		$this->assertNotSame( $before, get_option( self::HASH_OPTION ) );
		$rules = (array) get_option( 'rewrite_rules' );
		$this->assertArrayHasKey( '^seasons/([^/]+)/flushtests/?$', $rules );
		$this->assertSame( 'index.php?hyper_season=$matches[1]&post_type=hyper_flushtest', $rules['^seasons/([^/]+)/flushtests/?$'] );
	}

	public function test_the_gate_does_not_flush_again_when_nothing_changed(): void {
		$this->register_late_archive();
		Rewrite::maybe_flush();
		$hash = get_option( self::HASH_OPTION );

		$this->count_rule_generations();
		Rewrite::maybe_flush();
		Rewrite::maybe_flush();

		$this->assertSame( 0, $this->generations, 'Rules were regenerated although nothing changed.' );
		$this->assertSame( $hash, get_option( self::HASH_OPTION ) );
	}

	public function test_the_gate_flushes_when_a_change_is_detected(): void {
		$this->count_rule_generations();
		$this->register_late_archive();
		Rewrite::maybe_flush();

		$this->assertGreaterThan( 0, $this->generations );
	}
}
