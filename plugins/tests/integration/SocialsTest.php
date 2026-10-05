<?php
namespace HyperPress\Tests\Integration;

use WP_Customize_Manager;
use WP_UnitTestCase;

final class SocialsTest extends WP_UnitTestCase {

	private const URL_SETTINGS = array(
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
		'add_calendar',
	);

	private function customizer(): WP_Customize_Manager {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );
		return $manager;
	}

	public function tear_down(): void {
		foreach ( self::URL_SETTINGS as $name ) {
			remove_theme_mod( 'hyperpress_socials_' . $name );
		}
		remove_theme_mod( 'hyperpress_socials_contact' );
		parent::tear_down();
	}

	public function test_every_url_setting_drops_a_javascript_url(): void {
		$manager = $this->customizer();

		foreach ( self::URL_SETTINGS as $name ) {
			$setting = $manager->get_setting( 'hyperpress_socials_' . $name );
			$this->assertNotNull( $setting, $name );
			$this->assertSame( '', $setting->sanitize( 'javascript:alert(1)' ), $name );
		}
	}

	public function test_every_url_setting_keeps_an_https_url(): void {
		$manager = $this->customizer();

		foreach ( self::URL_SETTINGS as $name ) {
			$this->assertSame(
				'https://github.com/x',
				$manager->get_setting( 'hyperpress_socials_' . $name )->sanitize( 'https://github.com/x' ),
				$name
			);
		}
	}

	private function render(): string {
		ob_start();
		the_widget( 'HyperPress\Socials\Widgets\DisplaySocialsWidget', array( 'title' => 'Follow' ) );
		return (string) ob_get_clean();
	}

	public function test_external_links_carry_rel_noopener_noreferrer(): void {
		set_theme_mod( 'hyperpress_socials_github', 'https://github.com/x' );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_blank" rel="noopener noreferrer"', $html );
	}

	public function test_internal_links_open_in_self_without_rel(): void {
		set_theme_mod( 'hyperpress_socials_github', get_site_url() . '/about/' );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_self"', $html );
		$this->assertStringNotContainsString( 'rel="noopener', $html );
	}

	public static function lookalikes(): array {
		$site = get_site_url();
		return array(
			'site url in the query'    => array( 'https://evil.example/?r=' . $site ),
			'site url in the path'     => array( 'https://evil.example/' . $site ),
			'site host as a subdomain' => array( 'https://' . wp_parse_url( $site, PHP_URL_HOST ) . '.evil.example/x' ),
			'site url as the userinfo' => array( 'https://' . wp_parse_url( $site, PHP_URL_HOST ) . '@evil.example/x' ),
			'scheme-relative other host' => array( '//evil.example/x' ),
		);
	}

	/** @dataProvider lookalikes */
	public function test_lookalike_urls_are_treated_as_external( string $url ): void {
		set_theme_mod( 'hyperpress_socials_github', $url );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_blank" rel="noopener noreferrer"', $html );
		$this->assertStringNotContainsString( 'target="_self"', $html );
	}

	public function test_a_relative_url_is_internal(): void {
		set_theme_mod( 'hyperpress_socials_github', '/about/' );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_self"', $html );
		$this->assertStringNotContainsString( 'rel="noopener', $html );
	}

	public function test_the_host_comparison_ignores_case(): void {
		$site = get_site_url();
		set_theme_mod( 'hyperpress_socials_github', strtoupper( $site ) . '/About/' );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_self"', $html );
	}

	public function test_the_widget_renders_the_default_title_for_an_empty_instance(): void {
		ob_start();
		the_widget( 'HyperPress\Socials\Widgets\DisplaySocialsWidget', array() );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Stalk Us', $html );
	}

	public function test_update_turns_an_array_title_into_an_empty_string(): void {
		$widget = new \HyperPress\Socials\Widgets\DisplaySocialsWidget();

		$this->assertSame( array( 'title' => '' ), $widget->update( array( 'title' => array( 'x' ) ), array() ) );
		$this->assertSame( array( 'title' => 'Hi' ), $widget->update( array( 'title' => '<b>Hi</b>' ), array() ) );
	}

	public function test_the_widget_title_filter_receives_the_instance_and_id_base(): void {
		$received = null;
		add_filter(
			'widget_title',
			static function ( $title, $instance = null, $id_base = null ) use ( &$received ) {
				$received = array( $instance, $id_base );
				return $title;
			},
			10,
			3
		);

		$this->render();

		$this->assertSame( array( array( 'title' => 'Follow' ), 'hyperpress_socials' ), $received );
	}

	public function test_the_contact_page_setting_is_an_absolute_integer(): void {
		$setting = $this->customizer()->get_setting( 'hyperpress_socials_contact' );

		$this->assertSame( 5, $setting->sanitize( '5abc' ) );
		$this->assertSame( 3, $setting->sanitize( -3 ) );
	}

	public function test_the_contact_link_shows_for_a_published_page(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		set_theme_mod( 'hyperpress_socials_contact', $page_id );

		$this->assertStringContainsString( 'title="Contact Us"', $this->render() );
	}

	public function test_the_contact_link_is_omitted_when_the_page_does_not_exist(): void {
		set_theme_mod( 'hyperpress_socials_contact', 999999 );

		$this->assertStringNotContainsString( 'Contact Us', $this->render() );
	}

	public function test_the_contact_link_is_omitted_for_an_unpublished_page(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'draft' ) );
		set_theme_mod( 'hyperpress_socials_contact', $page_id );

		$this->assertStringNotContainsString( 'Contact Us', $this->render() );
	}
}
