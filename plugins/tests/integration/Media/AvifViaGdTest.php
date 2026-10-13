<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\ImageEditor\AvifViaGd;
use HyperPress\Media\Plugin;
use ReflectionMethod;
use WP_UnitTestCase;

final class AvifViaGdTest extends WP_UnitTestCase {

	private const SUBCLASS = 'HyperPress\Media\ImageEditor\ImagickWithoutAvif';
	private const FILTER   = 'hyperpress_media_avif_via_gd';

	public function set_up(): void {
		parent::set_up();
		wp_cache_delete( 'wp_image_editor_choose', 'image_editor' );
		$this->flush_editor_cache();
	}

	public function tear_down(): void {
		wp_cache_delete( 'wp_image_editor_choose', 'image_editor' );
		$this->flush_editor_cache();
		parent::tear_down();
	}

	private function flush_editor_cache(): void {
		if ( function_exists( 'wp_cache_flush_group' ) && wp_cache_supports( 'flush_group' ) ) {
			wp_cache_flush_group( 'image_editor' );
		}
	}

	public function test_editors_replaces_only_the_imagick_entry_and_keeps_order(): void {
		add_filter( self::FILTER, '__return_true' );

		$result = AvifViaGd::editors( array( 'Other_Editor', 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );

		$this->assertSame( array( 'Other_Editor', self::SUBCLASS, 'WP_Image_Editor_GD' ), $result );
	}

	public function test_editors_adds_no_duplicate_entries(): void {
		add_filter( self::FILTER, '__return_true' );

		$result = AvifViaGd::editors( array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' ) );

		$this->assertCount( 2, $result );
		$this->assertSame( $result, array_values( array_unique( $result ) ) );
		$this->assertNotContains( 'WP_Image_Editor_Imagick', $result );
	}

	public function test_editors_leaves_a_list_without_imagick_untouched(): void {
		add_filter( self::FILTER, '__return_true' );

		$list = array( 'WP_Image_Editor_GD' );

		$this->assertSame( $list, AvifViaGd::editors( $list ) );
	}

	public function test_editors_is_untouched_when_the_filter_returns_false(): void {
		add_filter( self::FILTER, '__return_false' );

		$list = array( 'WP_Image_Editor_Imagick', 'WP_Image_Editor_GD' );

		$this->assertSame( $list, AvifViaGd::editors( $list ) );
	}

	public function test_filter_default_follows_imageavif_availability(): void {
		$seen = null;
		add_filter(
			self::FILTER,
			static function ( $value ) use ( &$seen ) {
				$seen = $value;
				return $value;
			}
		);

		AvifViaGd::editors( array( 'WP_Image_Editor_Imagick' ) );

		$this->assertSame( function_exists( 'imageavif' ), $seen );
	}

	public function test_subclass_file_loads_after_the_parent_and_extends_it(): void {
		add_filter( self::FILTER, '__return_true' );

		AvifViaGd::editors( array( 'WP_Image_Editor_Imagick' ) );

		$this->assertTrue( class_exists( self::SUBCLASS, false ) );
		$this->assertTrue( is_subclass_of( self::SUBCLASS, 'WP_Image_Editor_Imagick' ) );
	}

	public function test_subclass_declines_avif_without_needing_imagick(): void {
		add_filter( self::FILTER, '__return_true' );
		AvifViaGd::editors( array( 'WP_Image_Editor_Imagick' ) );

		$this->assertFalse( call_user_func( array( self::SUBCLASS, 'supports_mime_type' ), 'image/avif' ) );
	}

	public function test_subclass_matches_the_parent_for_other_mime_types(): void {
		if ( ! extension_loaded( 'imagick' ) ) {
			$this->markTestSkipped( 'The imagick extension is not loaded: WP_Image_Editor_Imagick::supports_mime_type() needs Imagick::queryFormats().' );
		}

		add_filter( self::FILTER, '__return_true' );
		AvifViaGd::editors( array( 'WP_Image_Editor_Imagick' ) );

		foreach ( array( 'image/jpeg', 'image/png', 'image/webp' ) as $mime ) {
			$this->assertSame(
				\WP_Image_Editor_Imagick::supports_mime_type( $mime ),
				call_user_func( array( self::SUBCLASS, 'supports_mime_type' ), $mime ),
				$mime
			);
		}
	}

	public function test_register_does_not_reference_the_subclass(): void {
		$method = new ReflectionMethod( AvifViaGd::class, 'register' );
		$lines  = array_slice(
			(array) file( (string) $method->getFileName() ),
			$method->getStartLine() - 1,
			$method->getEndLine() - $method->getStartLine() + 1
		);

		$this->assertStringNotContainsString( 'ImagickWithoutAvif', implode( '', $lines ) );
		$this->assertStringNotContainsString( 'require', implode( '', $lines ) );
	}

	public function test_choose_routes_avif_to_gd_and_other_types_to_the_subclass(): void {
		if ( ! extension_loaded( 'imagick' ) || ! function_exists( 'imageavif' ) ) {
			$this->markTestSkipped( 'Needs both the imagick extension and GD with imageavif(); one is missing here.' );
		}

		add_filter( self::FILTER, '__return_true' );
		add_filter( 'wp_image_editors', array( AvifViaGd::class, 'editors' ), 20 );
		wp_cache_delete( 'wp_image_editor_choose', 'image_editor' );
		$this->flush_editor_cache();

		$avif = _wp_image_editor_choose( array( 'mime_type' => 'image/avif' ) );
		$this->flush_editor_cache();
		$jpeg = _wp_image_editor_choose( array( 'mime_type' => 'image/jpeg' ) );

		$this->assertSame( 'WP_Image_Editor_GD', $avif );
		$this->assertSame( self::SUBCLASS, $jpeg );
	}

	public function test_plugin_boot_registers_the_filter(): void {
		remove_filter( 'wp_image_editors', array( AvifViaGd::class, 'editors' ), 20 );
		$this->assertFalse( has_filter( 'wp_image_editors', array( AvifViaGd::class, 'editors' ) ) );

		Plugin::boot();

		$this->assertSame( 20, has_filter( 'wp_image_editors', array( AvifViaGd::class, 'editors' ) ) );
	}
}
