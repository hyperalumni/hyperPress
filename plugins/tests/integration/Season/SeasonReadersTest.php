<?php
namespace HyperPress\Tests\Integration\Season;

use HyperPress\Season\Plugin;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class SeasonReadersTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_season' );
		Plugin::register();
		parent::tear_down();
	}

	public function test_the_banner_shows_the_saved_subtitle_on_a_season_archive(): void {
		update_option( 'hyperpress_season', array( 'hyperpress_season_banner_subtitle' => '<b>Go</b> team' ) );
		$term = self::factory()->term->create_and_get( array( 'taxonomy' => Plugin::TAXONOMY, 'name' => 'Season 7', 'slug' => 'season-7' ) );
		$this->go_to( get_term_link( $term ) );
		$this->assertTrue( is_tax( Plugin::TAXONOMY ) );

		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'season', $banner['type'] );
		$this->assertSame( '<b>Go</b> team', $banner['subtitle'] );
	}

	public function test_the_taxonomy_object_types_follow_the_saved_post_types(): void {
		update_option( 'hyperpress_season', array( 'hyperpress_season_post_types' => array( 'page' ) ) );
		Plugin::register();

		$this->assertSame( array( 'page' ), get_taxonomy( Plugin::TAXONOMY )->object_type );

		update_option( 'hyperpress_season', array( 'hyperpress_season_post_types' => array( 'post', 'page' ) ) );
		Plugin::register();

		$this->assertSame( array( 'post', 'page' ), get_taxonomy( Plugin::TAXONOMY )->object_type );
	}

	public function test_a_cleared_post_type_list_attaches_the_taxonomy_to_nothing(): void {
		// A saved row without the post types key is what Meta Box stores for a cleared multi-select.
		update_option( 'hyperpress_season', array() );
		Plugin::register();

		$this->assertSame( array(), get_taxonomy( Plugin::TAXONOMY )->object_type );

		update_option( 'hyperpress_season', array( 'other_key' => 'x' ) );
		Plugin::register();

		$this->assertSame( array(), get_taxonomy( Plugin::TAXONOMY )->object_type );
	}

	public function test_the_definition_object_types_is_the_saved_list(): void {
		update_option( 'hyperpress_season', array( 'hyperpress_season_post_types' => array( 'page' ) ) );

		$this->assertSame( array( 'page' ), Plugin::definition()['object_types'] );
	}

	public function test_the_customizer_has_no_season_section_or_settings(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNull( $manager->get_section( 'hyperpress_season' ) );
		foreach ( array( 'hyperpress_season_current', 'hyperpress_season_banner_subtitle', 'hyperpress_season_post_types' ) as $id ) {
			$this->assertNull( $manager->get_setting( $id ), $id );
		}
	}

	public function test_the_old_customizer_box_is_gone_and_the_season_box_remains(): void {
		$ids = wp_list_pluck( apply_filters( 'rwmb_meta_boxes', array() ), 'id' );

		$this->assertNotContains( 'hyperpress_season', $ids );
		$this->assertContains( 'hyperpress_season_box', $ids );
	}
}
