<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Settings;
use HyperPress\Utils\Banner;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class CompetitionSubtitleTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_competition' );
		parent::tear_down();
	}

	private function archive_banner(): array {
		$this->go_to( get_post_type_archive_link( 'hyper_competition' ) );
		return apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) );
	}

	/**
	 * The banner for a competition season archive, built from the plugin's own settings accessor.
	 */
	private function season_banner(): array {
		return Banner::apply(
			array( 'title' => '' ),
			array(
				'type'          => 'competition',
				'archive_title' => 'Competitions',
				'term_joiner'   => 'in',
			),
			array(
				'is_singular'   => false,
				'is_archive'    => true,
				'is_tax_season' => true,
				'subtitle'      => Settings::banner_subtitle(),
				'color'         => '',
				'term_name'     => '2026',
			)
		);
	}

	public function test_the_default_shows_no_subtitle_on_the_archive(): void {
		delete_option( 'hyperpress_competition' );

		$this->assertSame( '', $this->archive_banner()['subtitle'] );
	}

	public function test_the_default_shows_no_subtitle_and_no_joiner_on_a_season_archive(): void {
		delete_option( 'hyperpress_competition' );

		$this->assertSame( '', $this->season_banner()['subtitle'] );
	}

	public function test_a_saved_subtitle_is_joined_to_the_season(): void {
		update_option( 'hyperpress_competition', array( 'hyperpress_competition_banner_subtitle' => 'Compete' ) );

		$this->assertSame( 'Compete in 2026', $this->season_banner()['subtitle'] );
	}

	public function test_a_saved_value_replaces_the_default_on_the_archive(): void {
		update_option( 'hyperpress_competition', array( 'hyperpress_competition_banner_subtitle' => 'Custom subtitle' ) );

		$this->assertSame( 'Custom subtitle', $this->archive_banner()['subtitle'] );
	}

	public function test_the_customizer_has_no_competition_section_or_setting(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNull( $manager->get_section( 'hyperpress_competition' ) );
		$this->assertNull( $manager->get_setting( 'hyperpress_competition_banner_subtitle' ) );
	}

	public function test_the_sanitizer_drops_scripts(): void {
		$sanitized = Banner::sanitize_subtitle( '<script>x</script><b>ok</b>' );

		$this->assertStringNotContainsString( '<script', $sanitized );
		$this->assertStringContainsString( '<b>ok</b>', $sanitized );
	}
}
