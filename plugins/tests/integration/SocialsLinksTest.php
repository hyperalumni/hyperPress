<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Social\Links;
use WP_UnitTestCase;

final class SocialsLinksTest extends WP_UnitTestCase {

	private const URL_KEYS = array(
		'firstinspires',
		'thebluealliance',
		'github',
		'facebook',
		'instagram',
		'twitter',
		'youtube',
		'snapchat',
		'tiktok',
		'discord',
		'add_calendar',
	);

	public function tear_down(): void {
		foreach ( self::URL_KEYS as $key ) {
			remove_theme_mod( 'hyperpress_social_' . $key );
		}
		remove_theme_mod( 'hyperpress_social_contact' );
		parent::tear_down();
	}

	/** @return array<string, array{0: string, 1: string, 2: string, 3: string}> key => key, icon, svg, title */
	public static function catalog(): array {
		return array(
			'firstinspires'   => array( 'firstinspires', '', 'firstinspires', 'FIRST Inspires' ),
			'thebluealliance' => array( 'thebluealliance', '', 'thebluealliance', 'The Blue Alliance' ),
			'github'          => array( 'github', 'fa-brands fa-github', '', 'GitHub' ),
			'facebook'        => array( 'facebook', 'fa-brands fa-facebook-f', '', 'Facebook' ),
			'instagram'       => array( 'instagram', 'fa-brands fa-instagram', '', 'Instagram' ),
			'twitter'         => array( 'twitter', 'fa-brands fa-twitter', '', 'Twitter' ),
			'youtube'         => array( 'youtube', 'fa-brands fa-youtube', '', 'YouTube' ),
			'snapchat'        => array( 'snapchat', 'fa-brands fa-snapchat', '', 'Snapchat' ),
			'tiktok'          => array( 'tiktok', 'fa-brands fa-tiktok', '', 'TikTok' ),
			'discord'         => array( 'discord', 'fa-brands fa-discord', '', 'Discord Server' ),
			'add_calendar'    => array( 'add_calendar', 'fa-regular fa-calendar', '', 'Add Our Calendar' ),
		);
	}

	private function set_all_urls(): void {
		foreach ( self::URL_KEYS as $key ) {
			set_theme_mod( 'hyperpress_social_' . $key, 'https://example.test/' . $key );
		}
	}

	public function test_nothing_configured_gives_an_empty_list(): void {
		$this->assertSame( array(), Links::all() );
	}

	/** @dataProvider catalog */
	public function test_each_social_has_its_key_icon_svg_and_title( string $key, string $icon, string $svg, string $title ): void {
		set_theme_mod( 'hyperpress_social_' . $key, 'https://example.test/x' );

		$this->assertSame(
			array(
				array(
					'key'    => $key,
					'icon'   => $icon,
					'svg'    => $svg,
					'title'  => $title,
					'url'    => 'https://example.test/x',
					'target' => '_blank',
					'rel'    => 'noopener noreferrer',
				),
			),
			Links::all()
		);
	}

	public function test_items_come_in_the_documented_order_with_contact_before_the_calendar(): void {
		$this->set_all_urls();
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		set_theme_mod( 'hyperpress_social_contact', $page_id );

		$this->assertSame(
			array(
				'firstinspires',
				'thebluealliance',
				'github',
				'facebook',
				'instagram',
				'twitter',
				'youtube',
				'snapchat',
				'tiktok',
				'discord',
				'contact',
				'add_calendar',
			),
			array_column( Links::all(), 'key' )
		);
	}

	public function test_the_contact_item_has_its_icon_and_title(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		set_theme_mod( 'hyperpress_social_contact', $page_id );

		$items = Links::all();

		$this->assertCount( 1, $items );
		$this->assertSame( 'contact', $items[0]['key'] );
		$this->assertSame( 'fa-regular fa-envelope', $items[0]['icon'] );
		$this->assertSame( '', $items[0]['svg'] );
		$this->assertSame( 'Contact Us', $items[0]['title'] );
	}

	public function test_items_have_exactly_the_documented_keys(): void {
		$this->set_all_urls();

		foreach ( Links::all() as $item ) {
			$this->assertSame( array( 'key', 'icon', 'svg', 'title', 'url', 'target', 'rel' ), array_keys( $item ) );
		}
	}

	public function test_an_external_url_opens_in_a_new_tab_with_rel(): void {
		set_theme_mod( 'hyperpress_social_github', 'https://github.com/x' );

		$item = Links::all()[0];

		$this->assertSame( 'https://github.com/x', $item['url'] );
		$this->assertSame( '_blank', $item['target'] );
		$this->assertSame( 'noopener noreferrer', $item['rel'] );
	}

	public function test_an_absolute_internal_url_is_self_without_rel(): void {
		set_theme_mod( 'hyperpress_social_github', get_site_url() . '/about/' );

		$item = Links::all()[0];

		$this->assertSame( '_self', $item['target'] );
		$this->assertSame( '', $item['rel'] );
	}

	public function test_a_relative_url_is_self_without_rel(): void {
		set_theme_mod( 'hyperpress_social_github', '/about/' );

		$item = Links::all()[0];

		$this->assertSame( '_self', $item['target'] );
		$this->assertSame( '', $item['rel'] );
	}

	public function test_a_scheme_relative_other_host_is_external(): void {
		set_theme_mod( 'hyperpress_social_github', '//evil.example/x' );

		$this->assertSame( '_blank', Links::all()[0]['target'] );
	}

	public function test_a_lookalike_url_is_external(): void {
		set_theme_mod( 'hyperpress_social_github', 'https://evil.example/?r=' . get_site_url() );

		$item = Links::all()[0];

		$this->assertSame( '_blank', $item['target'] );
		$this->assertSame( 'noopener noreferrer', $item['rel'] );
	}

	public function test_the_host_comparison_ignores_case(): void {
		set_theme_mod( 'hyperpress_social_github', strtoupper( get_site_url() ) . '/About/' );

		$this->assertSame( '_self', Links::all()[0]['target'] );
	}

	public function test_a_published_contact_page_gives_its_permalink(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		set_theme_mod( 'hyperpress_social_contact', $page_id );

		$item = Links::all()[0];

		$this->assertSame( get_permalink( $page_id ), $item['url'] );
		$this->assertSame( '_self', $item['target'] );
		$this->assertSame( '', $item['rel'] );
	}

	public function test_a_draft_contact_page_gives_no_item(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'draft' ) );
		set_theme_mod( 'hyperpress_social_contact', $page_id );

		$this->assertSame( array(), Links::all() );
	}

	public function test_a_missing_contact_page_gives_no_item(): void {
		set_theme_mod( 'hyperpress_social_contact', 999999 );

		$this->assertSame( array(), Links::all() );
	}

	public function test_a_zero_contact_page_gives_no_item(): void {
		set_theme_mod( 'hyperpress_social_contact', 0 );

		$this->assertSame( array(), Links::all() );
	}

	public function test_a_negative_contact_page_id_is_taken_as_its_absolute_value(): void {
		$page_id = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		set_theme_mod( 'hyperpress_social_contact', -$page_id );

		$this->assertSame( get_permalink( $page_id ), Links::all()[0]['url'] );
	}

	public function test_items_with_an_empty_url_are_omitted(): void {
		set_theme_mod( 'hyperpress_social_github', 'https://github.com/x' );
		set_theme_mod( 'hyperpress_social_facebook', '' );

		$this->assertSame( array( 'github' ), array_column( Links::all(), 'key' ) );
	}
}
