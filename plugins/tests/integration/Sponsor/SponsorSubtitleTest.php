<?php
namespace HyperPress\Tests\Integration\Sponsor;

use HyperPress\Utils\Banner;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class SponsorSubtitleTest extends WP_UnitTestCase {

	private const DEFAULT = 'Helping Make HYPER Happen';

	public function tear_down(): void {
		delete_option( 'hyperpress_sponsor' );
		parent::tear_down();
	}

	private function archive_banner(): array {
		$this->go_to( get_post_type_archive_link( 'hyper_sponsor' ) );
		return apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
	}

	public function test_the_default_shows_on_the_banner_until_a_value_is_saved(): void {
		delete_option( 'hyperpress_sponsor' );

		$this->assertSame( self::DEFAULT, $this->archive_banner()['subtitle'] );
	}

	public function test_a_saved_value_replaces_the_default(): void {
		update_option( 'hyperpress_sponsor', array( 'hyperpress_sponsor_banner_subtitle' => 'Custom subtitle' ) );

		$this->assertSame( 'Custom subtitle', $this->archive_banner()['subtitle'] );
	}

	public function test_the_customizer_has_no_sponsor_section_or_setting(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNull( $manager->get_section( 'hyperpress_sponsor' ) );
		$this->assertNull( $manager->get_setting( 'hyperpress_sponsor_banner_subtitle' ) );
	}

	public function test_the_sanitizer_drops_scripts(): void {
		$this->assertSame( 'xalert(1)<b>ok</b>', Banner::sanitize_subtitle( 'x<script>alert(1)</script><b>ok</b>' ) );
	}
}
