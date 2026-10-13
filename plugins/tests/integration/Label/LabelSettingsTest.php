<?php
namespace HyperPress\Tests\Integration\Label;

use HyperPress\Label\Registry;
use HyperPress\Label\Resolver;
use HyperPress\Label\Settings;
use WP_UnitTestCase;

final class LabelSettingsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		Registry::reset();
	}

	public function tear_down(): void {
		delete_option( 'hyperpress_label' );
		Registry::reset();
		parent::tear_down();
	}

	public function test_the_constants(): void {
		$this->assertSame( 'hyperpress_label', Settings::OPTION );
		$this->assertSame( 'hyperpress_label_saved', Settings::MARKER );
	}

	public function test_post_types_lists_public_types_without_attachment(): void {
		$types = Settings::post_types();

		$this->assertSame( 'Posts', $types['post'] );
		$this->assertSame( 'Pages', $types['page'] );
		$this->assertArrayNotHasKey( 'attachment', $types );
	}

	public function test_post_types_includes_a_type_named_only_in_label_defaults(): void {
		Registry::reset( true );
		Registry::instance()->add(
			'x.one',
			array(
				'group'              => 'X',
				'title'              => 'One',
				'callback'           => array( LabelSettingsPageTest::class, 'noop' ),
				'default_post_types' => array( 'hyper_x', '', 5 ),
			)
		);

		$types = Settings::post_types();

		$this->assertSame( 'hyper_x', $types['hyper_x'] );
		$this->assertArrayNotHasKey( '', $types );
	}

	public function test_sanitize_labels_keeps_only_registered_ids(): void {
		$this->assertSame(
			array( 'post.date' ),
			Settings::sanitize_labels( array( 'post.date', 'nope', 'post.date', 5 ) )
		);
	}

	public function test_sanitize_labels_rejects_non_arrays(): void {
		$this->assertSame( array(), Settings::sanitize_labels( 'x' ) );
		$this->assertSame( array(), Settings::sanitize_labels( null ) );
	}

	public function test_saved_returns_the_option_array_or_an_empty_array(): void {
		delete_option( 'hyperpress_label' );
		$this->assertSame( array(), Settings::saved() );

		update_option( 'hyperpress_label', 'garbage' );
		$this->assertSame( array(), Settings::saved() );

		update_option( 'hyperpress_label', array( 'hyperpress_label_post_enabled' => false ) );
		$this->assertSame( array( 'hyperpress_label_post_enabled' => false ), Settings::saved() );
	}

	public function test_normalize_fills_the_missing_keys_for_every_type_and_removes_the_marker(): void {
		$value = Settings::normalize(
			array(
				Settings::MARKER                    => '1',
				Resolver::enabled_key( 'post' )     => '1',
				Resolver::labels_key( 'post' )      => array( 'post.date' ),
			)
		);

		$this->assertArrayNotHasKey( Settings::MARKER, $value );
		$this->assertSame( '1', $value[ Resolver::enabled_key( 'post' ) ] );
		$this->assertSame( array( 'post.date' ), $value[ Resolver::labels_key( 'post' ) ] );
		$this->assertFalse( $value[ Resolver::enabled_key( 'page' ) ] );
		$this->assertSame( array(), $value[ Resolver::labels_key( 'page' ) ] );
		foreach ( array_keys( Settings::post_types() ) as $type ) {
			$this->assertArrayHasKey( Resolver::enabled_key( $type ), $value, $type );
			$this->assertArrayHasKey( Resolver::labels_key( $type ), $value, $type );
		}
	}

	public function test_normalize_does_nothing_without_the_marker(): void {
		$value = array( Resolver::labels_key( 'post' ) => array( 'post.date' ) );

		$this->assertSame( $value, Settings::normalize( $value ) );
		$this->assertSame( array(), Settings::normalize( array() ) );
	}

	public function test_normalize_returns_non_arrays_untouched(): void {
		$this->assertSame( 'x', Settings::normalize( 'x' ) );
		$this->assertNull( Settings::normalize( null ) );
		$this->assertFalse( Settings::normalize( false ) );
	}

	public function test_saving_through_the_option_runs_the_normalizer_only_with_the_marker(): void {
		update_option( 'hyperpress_label', array( Settings::MARKER => '1' ) );
		$saved = get_option( 'hyperpress_label' );

		$this->assertArrayNotHasKey( Settings::MARKER, $saved );
		$this->assertFalse( $saved[ Resolver::enabled_key( 'post' ) ] );
		$this->assertSame( array(), $saved[ Resolver::labels_key( 'post' ) ] );

		update_option( 'hyperpress_label', array( 'hyperpress_label_post_enabled' => true ) );

		$this->assertSame( array( 'hyperpress_label_post_enabled' => true ), get_option( 'hyperpress_label' ) );
	}
}
