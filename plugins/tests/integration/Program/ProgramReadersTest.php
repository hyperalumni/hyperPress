<?php
namespace HyperPress\Tests\Integration\Program;

use HyperPress\Program\Plugin;
use WP_Customize_Manager;
use WP_UnitTestCase;

final class ProgramReadersTest extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( 'hyperpress_program' );
		Plugin::register();
		parent::tear_down();
	}

	public function test_the_taxonomy_object_types_follow_the_saved_post_types(): void {
		update_option( 'hyperpress_program', array( 'hyperpress_program_post_types' => array( 'page' ) ) );
		Plugin::register();

		$this->assertSame( array( 'page' ), get_taxonomy( Plugin::TAXONOMY )->object_type );

		update_option( 'hyperpress_program', array( 'hyperpress_program_post_types' => array( 'post', 'page' ) ) );
		Plugin::register();

		$this->assertSame( array( 'post', 'page' ), get_taxonomy( Plugin::TAXONOMY )->object_type );
	}

	public function test_the_taxonomy_attaches_to_posts_with_nothing_saved(): void {
		delete_option( 'hyperpress_program' );
		Plugin::register();

		$this->assertSame( array( 'post' ), get_taxonomy( Plugin::TAXONOMY )->object_type );
	}

	public function test_the_definition_object_types_is_the_saved_list(): void {
		update_option( 'hyperpress_program', array( 'hyperpress_program_post_types' => array( 'page' ) ) );

		$this->assertSame( array( 'page' ), Plugin::definition()['object_types'] );
	}

	public function test_the_customizer_has_no_program_section_or_settings(): void {
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$manager = new WP_Customize_Manager();
		do_action( 'customize_register', $manager );

		$this->assertNull( $manager->get_section( 'hyperpress_program' ) );
		$this->assertNull( $manager->get_setting( 'hyperpress_program_post_types' ) );
	}

	public function test_the_old_customizer_box_is_gone_and_the_program_box_remains(): void {
		$ids = wp_list_pluck( apply_filters( 'rwmb_meta_boxes', array() ), 'id' );

		$this->assertNotContains( 'hyperpress_program', $ids );
		$this->assertContains( 'hyperpress_program_box', $ids );
	}
}
