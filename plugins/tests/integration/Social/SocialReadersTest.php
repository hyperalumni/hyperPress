<?php
namespace HyperPress\Tests\Integration\Social;

use HyperPress\Social\Links;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class SocialReadersTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_social' );
		parent::tear_down();
	}

	private function render(): string {
		ob_start();
		the_widget( 'HyperPress\Social\Widgets\DisplaySocialWidget', array( 'title' => 'Follow' ) );
		return (string) ob_get_clean();
	}

	public function test_the_customizer_has_no_social_section_or_settings(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNull( $manager->get_section( 'hyperpress_social' ) );
		$ids = array(
			'firstinspires',
			'thebluealliance',
			'facebook',
			'instagram',
			'youtube',
			'twitter',
			'github',
			'snapchat',
			'tiktok',
			'discord',
			'contact',
			'add_calendar',
		);
		$this->assertCount( 12, $ids );
		foreach ( $ids as $id ) {
			$this->assertNull( $manager->get_setting( 'hyperpress_social_' . $id ), $id );
		}
	}

	public function test_the_links_are_empty_when_the_option_was_never_saved(): void {
		delete_option( 'hyperpress_social' );

		$this->assertSame( array(), Links::all() );
	}

	public function test_a_lookalike_host_written_into_the_option_is_external(): void {
		update_option( 'hyperpress_social', array( 'hyperpress_social_github' => 'https://evil.example/?r=' . get_site_url() ) );

		$link = Links::all()[0];

		$this->assertSame( '_blank', $link['target'] );
		$this->assertSame( 'noopener noreferrer', $link['rel'] );
	}

	public function test_a_relative_url_written_into_the_option_is_internal(): void {
		update_option( 'hyperpress_social', array( 'hyperpress_social_github' => '/about/' ) );

		$link = Links::all()[0];

		$this->assertSame( '/about/', $link['url'] );
		$this->assertSame( '_self', $link['target'] );
		$this->assertSame( '', $link['rel'] );
	}

	public function test_a_javascript_url_written_straight_into_the_option_is_passed_on_and_escaped_by_the_widget(): void {
		update_option(
			'hyperpress_social',
			array(
				'hyperpress_social_github'    => 'javascript:alert(1)',
				'hyperpress_social_instagram' => 'https://github.com/x',
			)
		);

		$this->assertSame( 'javascript:alert(1)', Links::all()[0]['url'] );

		$html = $this->render();

		$this->assertStringContainsString( 'github.com/x', $html );
		$this->assertStringNotContainsString( 'javascript:', $html );
	}

	public function test_the_widget_renders_no_links_and_no_notices_when_the_option_was_never_saved(): void {
		delete_option( 'hyperpress_social' );

		$html = $this->render();

		$this->assertStringNotContainsString( '<a ', $html );
		$this->assertStringNotContainsString( 'Notice', $html );
		$this->assertStringNotContainsString( 'Warning', $html );
	}
}
