<?php
namespace HyperPress\ThemeTests\Integration;

use WP_UnitTestCase;

final class DefaultBrandingSeedTest extends WP_UnitTestCase {

	public function test_seed_creates_tagged_attachments(): void {
		$this->assertTrue( hyperpress_seed_default_branding() );

		$logo = hyperpress_default_branding_id( 'logo' );
		$icon = hyperpress_default_branding_id( 'icon' );

		$this->assertGreaterThan( 0, $logo );
		$this->assertGreaterThan( 0, $icon );
		$this->assertSame( 'image/svg+xml', get_post_mime_type( $logo ) );
		$this->assertSame( 'image/png', get_post_mime_type( $icon ) );
		$this->assertSame( 'logo', get_post_meta( $logo, '_hyperpress_default', true ) );
		$this->assertSame( 'icon', get_post_meta( $icon, '_hyperpress_default', true ) );
	}

	public function test_seed_is_idempotent(): void {
		$this->assertTrue( hyperpress_seed_default_branding() );
		$logo = hyperpress_default_branding_id( 'logo' );
		$icon = hyperpress_default_branding_id( 'icon' );

		$this->assertTrue( hyperpress_seed_default_branding() );
		$this->assertSame( $logo, hyperpress_default_branding_id( 'logo' ) );
		$this->assertSame( $icon, hyperpress_default_branding_id( 'icon' ) );

		$tagged = get_posts(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'any',
				'meta_key'    => '_hyperpress_default', // phpcs:ignore WordPress.DB.SlowDBQuery -- test.
				'fields'      => 'ids',
				'numberposts' => -1,
			)
		);
		$this->assertCount( 2, $tagged );
	}

	public function test_fresh_lock_blocks_seeding(): void {
		add_option( 'hyperpress_branding_lock', (string) time() );

		$this->assertFalse( hyperpress_seed_default_branding() );
		$this->assertSame( 0, hyperpress_default_branding_id( 'logo' ) );
	}

	public function test_stale_lock_is_taken_over(): void {
		add_option( 'hyperpress_branding_lock', (string) ( time() - 600 ) );

		$this->assertTrue( hyperpress_seed_default_branding() );
	}

	public function test_lock_taken_over_by_another_holder_is_not_deleted(): void {
		$other = (string) time() . ':other-holder';
		add_filter(
			'upload_dir',
			static function ( array $dirs ) use ( $other ): array {
				update_option( 'hyperpress_branding_lock', $other );
				return $dirs;
			}
		);

		$this->assertTrue( hyperpress_seed_default_branding() );
		$this->assertSame( $other, get_option( 'hyperpress_branding_lock' ) );
	}

	public function test_own_lock_is_released_after_seeding(): void {
		$this->assertTrue( hyperpress_seed_default_branding() );
		$this->assertFalse( get_option( 'hyperpress_branding_lock' ) );
	}

	public function test_unwritable_uploads_fails_quietly(): void {
		add_filter(
			'upload_dir',
			static function ( array $dirs ): array {
				// wp_upload_dir() re-tests the path after this filter and overwrites 'error',
				// so point it at a directory that cannot be created (as an unwritable uploads dir would).
				return array_merge(
					$dirs,
					array(
						'path'    => '/proc/hyperpress-blocked/sub',
						'basedir' => '/proc/hyperpress-blocked',
						'subdir'  => '/sub',
						'error'   => 'blocked',
					)
				);
			}
		);

		$this->assertFalse( hyperpress_seed_default_branding() );
		$this->assertSame( 0, hyperpress_default_branding_id( 'logo' ) );
		$this->assertSame( 0, hyperpress_default_branding_id( 'icon' ) );
		$this->assertFalse( get_option( 'hyperpress_branding_version' ) );
	}

	public function test_deleted_default_is_detected_and_reseeded(): void {
		$this->assertTrue( hyperpress_seed_default_branding() );
		$id = hyperpress_default_branding_id( 'logo' );
		wp_delete_attachment( $id, true );

		$this->assertSame( 0, hyperpress_default_branding_id( 'logo' ) );

		hyperpress_maybe_seed_default_branding();

		$this->assertGreaterThan( 0, hyperpress_default_branding_id( 'logo' ) );
		$this->assertGreaterThan( 0, hyperpress_default_branding_id( 'icon' ) );
	}

	public function test_maybe_seed_skips_when_current(): void {
		$this->assertTrue( hyperpress_seed_default_branding() );

		// Count lock acquisitions: get_attached_file() in the presence check also runs the upload_dir filter.
		$calls = 0;
		add_action(
			'add_option_hyperpress_branding_lock',
			static function () use ( &$calls ): void {
				++$calls;
			}
		);

		hyperpress_maybe_seed_default_branding();

		$this->assertSame( 0, $calls );
	}

	public function test_reseed_refreshes_existing_file_from_bundle(): void {
		$this->assertTrue( hyperpress_seed_default_branding() );
		$id   = hyperpress_default_branding_id( 'logo' );
		$file = get_attached_file( $id );
		file_put_contents( $file, 'stale' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test.

		$this->assertTrue( hyperpress_seed_default_branding() );

		$this->assertSame( $id, hyperpress_default_branding_id( 'logo' ) );
		$this->assertFileEquals( hyperpress_default_branding_files()['logo']['file'], $file );
	}

	public function test_version_bump_refreshes_files(): void {
		$this->assertTrue( hyperpress_seed_default_branding() );
		$id   = hyperpress_default_branding_id( 'logo' );
		$file = get_attached_file( $id );
		file_put_contents( $file, 'stale' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test.
		update_option( 'hyperpress_branding_version', 'older' );

		hyperpress_maybe_seed_default_branding();

		$this->assertFileEquals( hyperpress_default_branding_files()['logo']['file'], $file );
	}

	public function test_missing_file_is_healed_without_changing_the_id(): void {
		$this->assertTrue( hyperpress_seed_default_branding() );
		$id   = hyperpress_default_branding_id( 'logo' );
		$file = get_attached_file( $id );
		unlink( $file );
		$this->assertFileDoesNotExist( $file );

		hyperpress_maybe_seed_default_branding();

		$this->assertFileExists( get_attached_file( $id ) );
		$this->assertSame( $id, hyperpress_default_branding_id( 'logo' ) );
	}

	private function block_uploads_and_count( int &$calls ): void {
		add_filter(
			'upload_dir',
			static function ( array $dirs ) use ( &$calls ): array {
				++$calls;
				return array_merge(
					$dirs,
					array(
						'path'    => '/proc/hyperpress-blocked/sub',
						'basedir' => '/proc/hyperpress-blocked',
						'subdir'  => '/sub',
						'error'   => 'blocked',
					)
				);
			}
		);
	}

	public function test_failure_sets_backoff_and_second_call_is_skipped(): void {
		$calls = 0;
		$this->block_uploads_and_count( $calls );

		hyperpress_maybe_seed_default_branding();
		$after_first = $calls;

		$this->assertGreaterThan( 0, $after_first );
		$this->assertGreaterThan( 0, (int) get_option( 'hyperpress_branding_failed_at' ) );

		hyperpress_maybe_seed_default_branding();

		$this->assertSame( $after_first, $calls );
	}

	public function test_old_failure_allows_retry_and_success_clears_it(): void {
		update_option( 'hyperpress_branding_failed_at', time() - 600 );

		hyperpress_maybe_seed_default_branding();

		$this->assertGreaterThan( 0, hyperpress_default_branding_id( 'logo' ) );
		$this->assertFalse( get_option( 'hyperpress_branding_failed_at' ) );
	}

	public function test_fresh_lock_does_not_record_a_failure(): void {
		add_option( 'hyperpress_branding_lock', (string) time() );

		hyperpress_maybe_seed_default_branding();

		$this->assertFalse( get_option( 'hyperpress_branding_failed_at' ) );
	}

	public function test_admin_wrapper_does_nothing_when_logged_out(): void {
		wp_set_current_user( 0 );

		hyperpress_maybe_seed_default_branding_on_admin();

		$this->assertSame( 0, hyperpress_default_branding_id( 'logo' ) );
	}

	public function test_admin_wrapper_seeds_for_theme_options_capability(): void {
		wp_set_current_user( $this->factory()->user->create( array( 'role' => 'administrator' ) ) );

		hyperpress_maybe_seed_default_branding_on_admin();

		$this->assertGreaterThan( 0, hyperpress_default_branding_id( 'logo' ) );
	}

	public function test_hooks_registered(): void {
		$this->assertNotFalse( has_action( 'admin_init', 'hyperpress_maybe_seed_default_branding_on_admin' ) );
		$this->assertFalse( has_action( 'admin_init', 'hyperpress_maybe_seed_default_branding' ) );
		$this->assertNotFalse( has_action( 'after_switch_theme', 'hyperpress_maybe_seed_default_branding' ) );
	}
}
