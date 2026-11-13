<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\Rewrite;
use HyperPress\Utils\Version;
use WP_UnitTestCase;

final class RewriteTest extends WP_UnitTestCase {

	private const OPTION = 'hyperpress_event_rewrite_version';

	private int $flushes = 0;

	public function set_up(): void {
		parent::set_up();
		$this->flushes = 0;
		add_action( 'generate_rewrite_rules', array( $this, 'flushed' ) );
		delete_option( self::OPTION );
	}

	public function tear_down(): void {
		remove_action( 'generate_rewrite_rules', array( $this, 'flushed' ) );
		delete_option( self::OPTION );
		parent::tear_down();
	}

	/**
	 * Counts the rule rebuilds a flush causes (the soft flush used here skips the `flush_rewrite_rules_hard` filter).
	 */
	public function flushed(): void {
		++$this->flushes;
	}

	private function version(): string {
		return Version::of( dirname( __DIR__, 3 ) . '/hyperpress-event/hyperpress-event.php' );
	}

	public function test_hooked_late_on_init(): void {
		$this->assertSame( 99, has_action( 'init', array( Rewrite::class, 'maybe_flush' ) ) );
	}

	public function test_a_missing_option_flushes_once_and_stores_version_plus_revision(): void {
		Rewrite::maybe_flush();
		$this->assertSame( 1, $this->flushes );
		$this->assertSame( $this->version() . '+2', get_option( self::OPTION ) );

		Rewrite::maybe_flush();
		$this->assertSame( 1, $this->flushes );
	}

	public function test_the_old_version_only_value_flushes_once_more(): void {
		update_option( self::OPTION, $this->version() );

		Rewrite::maybe_flush();
		$this->assertSame( 1, $this->flushes );
		$this->assertSame( $this->version() . '+2', get_option( self::OPTION ) );

		Rewrite::maybe_flush();
		$this->assertSame( 1, $this->flushes );
	}
}
