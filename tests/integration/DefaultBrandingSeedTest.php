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

		$calls = 0;
		add_filter(
			'upload_dir',
			static function ( array $dirs ) use ( &$calls ): array {
				++$calls;
				return $dirs;
			}
		);

		hyperpress_maybe_seed_default_branding();

		$this->assertSame( 0, $calls );
	}
}
