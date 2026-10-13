<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SocialsTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_social' );
		parent::tear_down();
	}

	private function render(): string {
		ob_start();
		the_widget( 'HyperPress\Social\Widgets\DisplaySocialWidget', array( 'title' => 'Follow' ) );
		return (string) ob_get_clean();
	}

	public function test_external_links_carry_rel_noopener_noreferrer(): void {
		update_option( 'hyperpress_social', array( 'hyperpress_social_github' => 'https://github.com/x' ) );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_blank" rel="noopener noreferrer"', $html );
	}

	public function test_internal_links_open_in_self_without_rel(): void {
		update_option( 'hyperpress_social', array( 'hyperpress_social_github' => get_site_url() . '/about/' ) );

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
		update_option( 'hyperpress_social', array( 'hyperpress_social_github' => $url ) );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_blank" rel="noopener noreferrer"', $html );
		$this->assertStringNotContainsString( 'target="_self"', $html );
	}

	public function test_a_relative_url_is_internal(): void {
		update_option( 'hyperpress_social', array( 'hyperpress_social_github' => '/about/' ) );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_self"', $html );
		$this->assertStringNotContainsString( 'rel="noopener', $html );
	}

	public function test_the_host_comparison_ignores_case(): void {
		$site = get_site_url();
		update_option( 'hyperpress_social', array( 'hyperpress_social_github' => strtoupper( $site ) . '/About/' ) );

		$html = $this->render();

		$this->assertStringContainsString( 'target="_self"', $html );
	}

	public function test_the_widget_renders_the_default_title_for_an_empty_instance(): void {
		ob_start();
		the_widget( 'HyperPress\Social\Widgets\DisplaySocialWidget', array() );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'Stalk Us', $html );
	}

	public function test_update_turns_an_array_title_into_an_empty_string(): void {
		$widget = new \HyperPress\Social\Widgets\DisplaySocialWidget();

		$this->assertSame( array( 'title' => '', 'layout' => 'grid' ), $widget->update( array( 'title' => array( 'x' ) ), array() ) );
		$this->assertSame( array( 'title' => 'Hi', 'layout' => 'grid' ), $widget->update( array( 'title' => '<b>Hi</b>' ), array() ) );
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

		$this->assertSame( array( array( 'title' => 'Follow' ), 'hyperpress_social' ), $received );
	}

	public function test_the_widget_base_id_is_hyperpress_social(): void {
		$widget = new \HyperPress\Social\Widgets\DisplaySocialWidget();

		$this->assertSame( 'hyperpress_social', $widget->id_base );
		$this->assertArrayHasKey( 'HyperPress\\Social\\Widgets\\DisplaySocialWidget', $GLOBALS['wp_widget_factory']->widgets );
	}

	public function test_no_plugin_source_file_uses_the_old_plural_identifiers(): void {
		$files = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( dirname( __DIR__, 2 ) . '/hyperpress-social', \FilesystemIterator::SKIP_DOTS ) );

		$count = 0;
		foreach ( $files as $file ) {
			if ( 'php' !== $file->getExtension() ) {
				continue;
			}
			++$count;
			$source = (string) file_get_contents( $file->getPathname() );
			$this->assertStringNotContainsString( 'hyperpress_socials', $source, $file->getFilename() );
			$this->assertStringNotContainsString( 'hyperpress-socials', $source, $file->getFilename() );
			$this->assertStringNotContainsString( 'HyperPress\\Socials', $source, $file->getFilename() );
			$this->assertStringNotContainsString( 'DisplaySocialsWidget', $source, $file->getFilename() );
		}
		$this->assertGreaterThan( 0, $count );
	}

	public function test_the_contact_link_shows_for_a_published_page(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		update_option( 'hyperpress_social', array( 'hyperpress_social_contact' => $page_id ) );

		$this->assertStringContainsString( 'title="Contact Us"', $this->render() );
	}

	public function test_the_contact_link_is_omitted_when_the_page_does_not_exist(): void {
		update_option( 'hyperpress_social', array( 'hyperpress_social_contact' => 999999 ) );

		$this->assertStringNotContainsString( 'Contact Us', $this->render() );
	}

	public function test_the_contact_link_is_omitted_for_an_unpublished_page(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'draft' ) );
		update_option( 'hyperpress_social', array( 'hyperpress_social_contact' => $page_id ) );

		$this->assertStringNotContainsString( 'Contact Us', $this->render() );
	}
}
