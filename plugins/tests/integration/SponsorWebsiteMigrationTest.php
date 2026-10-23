<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Sponsor\WebsiteMigration;
use WP_UnitTestCase;

final class SponsorWebsiteMigrationTest extends WP_UnitTestCase {

	private const META = 'hyperpress_sponsor_website';

	public function set_up(): void {
		parent::set_up();
		delete_option( WebsiteMigration::OPTION );
	}

	private function sponsor( $website, string $status = 'publish' ): int {
		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'hyper_sponsor',
				'post_status' => $status,
			)
		);
		if ( null !== $website ) {
			// Bypass update_post_meta filters so the stored shape is exactly what the legacy site had.
			update_post_meta( $post_id, self::META, wp_slash( $website ) );
		}

		return $post_id;
	}

	private static function link( string $url ): array {
		return array(
			'url'     => $url,
			'title'   => '',
			'target'  => '',
			'post_id' => 0,
		);
	}

	public function test_legacy_string_becomes_a_link_array_with_an_empty_title(): void {
		$post_id = $this->sponsor( 'https://external.example/sponsor/' );

		WebsiteMigration::run();

		$this->assertSame( self::link( 'https://external.example/sponsor/' ), get_post_meta( $post_id, self::META, true ) );
	}

	public function test_legacy_string_is_trimmed_and_sanitized(): void {
		$post_id = $this->sponsor( '  https://external.example/a b  ' );

		WebsiteMigration::run();

		$this->assertSame( self::link( esc_url_raw( 'https://external.example/a b' ) ), get_post_meta( $post_id, self::META, true ) );
	}

	public function test_every_post_status_is_migrated(): void {
		$ids = array();
		foreach ( array( 'publish', 'draft', 'private', 'pending', 'future', 'trash' ) as $status ) {
			$ids[ $status ] = $this->sponsor( 'https://external.example/' . $status, 'future' === $status ? 'future' : $status );
		}

		WebsiteMigration::run();

		foreach ( $ids as $status => $post_id ) {
			$this->assertSame( self::link( 'https://external.example/' . $status ), get_post_meta( $post_id, self::META, true ), $status );
		}
	}

	public function test_existing_link_array_is_untouched(): void {
		$value   = array(
			'url'     => 'https://external.example/sponsor/',
			'title'   => 'Meet Acme',
			'target'  => '_blank',
			'post_id' => 7,
		);
		$post_id = $this->sponsor( $value );

		WebsiteMigration::run();

		$this->assertSame( $value, get_post_meta( $post_id, self::META, true ) );
	}

	public function test_empty_and_unsafe_values_are_left_as_they_were(): void {
		$empty  = $this->sponsor( '' );
		$unsafe = $this->sponsor( 'javascript:alert(1)' );
		$none   = $this->sponsor( null );

		WebsiteMigration::run();

		$this->assertSame( '', get_post_meta( $empty, self::META, true ) );
		$this->assertSame( 'javascript:alert(1)', get_post_meta( $unsafe, self::META, true ) );
		$this->assertSame( array(), get_post_meta( $none, self::META ) );
		$this->assertSame( WebsiteMigration::VERSION, get_option( WebsiteMigration::OPTION ) );
	}

	public function test_other_post_types_are_ignored(): void {
		$post_id = self::factory()->post->create( array( 'post_type' => 'post' ) );
		update_post_meta( $post_id, self::META, 'https://external.example/' );

		WebsiteMigration::run();

		$this->assertSame( 'https://external.example/', get_post_meta( $post_id, self::META, true ) );
	}

	public function test_second_run_changes_nothing(): void {
		$post_id = $this->sponsor( 'https://external.example/sponsor/' );

		WebsiteMigration::run();
		$first = get_post_meta( $post_id, self::META, true );
		delete_option( WebsiteMigration::OPTION );
		WebsiteMigration::run();

		$this->assertSame( $first, get_post_meta( $post_id, self::META, true ) );
		$this->assertSame( self::link( 'https://external.example/sponsor/' ), $first );
	}

	public function test_records_the_version_and_does_nothing_when_current(): void {
		WebsiteMigration::run();
		$this->assertSame( WebsiteMigration::VERSION, get_option( WebsiteMigration::OPTION ) );

		$post_id = $this->sponsor( 'https://external.example/late/' );
		WebsiteMigration::run();

		$this->assertSame( 'https://external.example/late/', get_post_meta( $post_id, self::META, true ) );
	}

	public function test_boot_hooks_the_migration_on_init(): void {
		$this->assertNotFalse( has_action( 'init', array( WebsiteMigration::class, 'run' ) ) );
	}

	public function test_migrated_legacy_sponsor_renders_the_default_button_text(): void {
		global $post;

		$post_id = $this->sponsor( 'https://external.example/sponsor/' );
		WebsiteMigration::run();

		$this->go_to( get_permalink( $post_id ) );
		$post = get_post( $post_id ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );

		ob_start();
		include dirname( __DIR__, 2 ) . '/hyperpress-sponsor/templates/content.php';
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'href="https://external.example/sponsor/"', $html );
		$this->assertStringContainsString( '>Visit our sponsor</a>', $html );
	}
}
