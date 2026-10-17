<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\ImageEditor\AvifViaGd;
use ReflectionMethod;
use WP_UnitTestCase;

final class AvifViaGdTest extends WP_UnitTestCase {

	private const SUBCLASS = 'HyperPress\Media\ImageEditor\ImagickWithoutAvif';
	private const FILTER   = 'hyperpress_media_avif_via_gd';

	/** @var string[] */
	private array $temp_files = array();

	public function set_up(): void {
		parent::set_up();
		wp_cache_delete( 'wp_image_editor_choose', 'image_editor' );
		$this->flush_editor_cache();
	}

	public function tear_down(): void {
		foreach ( $this->temp_files as $file ) {
			if ( file_exists( $file ) ) {
				wp_delete_file( $file );
			}
		}
		$this->temp_files = array();
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

	public function test_register_adds_the_filter(): void {
		remove_filter( 'wp_image_editors', array( AvifViaGd::class, 'editors' ), 20 );
		$this->assertFalse( has_filter( 'wp_image_editors', array( AvifViaGd::class, 'editors' ) ) );

		AvifViaGd::register();

		$this->assertSame( 20, has_filter( 'wp_image_editors', array( AvifViaGd::class, 'editors' ) ) );
	}

	/**
	 * The empty-.avif bug: an Imagick editor chosen for a PNG source and asked to
	 * save as AVIF must fall back to the default format instead of targeting AVIF.
	 */
	public function test_imagick_editor_without_avif_falls_back_to_jpeg_for_a_png_source(): void {
		if ( ! extension_loaded( 'imagick' ) ) {
			$this->markTestSkipped( 'The imagick extension is not loaded: ImagickWithoutAvif extends WP_Image_Editor_Imagick, whose constructor and supports_mime_type() need it.' );
		}

		add_filter( self::FILTER, '__return_true' );
		AvifViaGd::editors( array( 'WP_Image_Editor_Imagick' ) );

		$png = wp_tempnam( 'avif-fallback.png', get_temp_dir() );
		// phpcs:ignore WordPress.WP_SDK.AlternativeFunctions.file_system_operations_file_put_contents -- test fixture.
		file_put_contents( $png, base64_decode( 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==' ) );
		$this->temp_files[] = $png;

		// The core constructor is protected: build the editor through reflection.
		$class  = new \ReflectionClass( self::SUBCLASS );
		$editor = $class->newInstanceWithoutConstructor();
		$class->getConstructor()->invoke( $editor, $png );
		$method = new ReflectionMethod( $editor, 'get_output_format' );

		$result = $method->invoke( $editor, $png . '.avif', 'image/avif' );

		$this->assertNotSame( 'image/avif', $result[2] );
		$this->assertSame( apply_filters( 'image_editor_default_mime_type', 'image/jpeg' ), $result[2] );
		$this->assertSame( 'image/jpeg', $result[2] );
	}

	/**
	 * Same core logic without Imagick: an editor that declines AVIF never targets it.
	 */
	public function test_editor_declining_avif_falls_back_to_the_default_mime_type(): void {
		$editor = new class() extends \WP_Image_Editor {
				public function __construct() {}
				public static function supports_mime_type( $mime_type ) {
					return 'image/avif' !== $mime_type;
				}
				public function load() {
					return true;
				}
				public function save( $destfilename = null, $mime_type = null ) {
					return array();
				}
				public function resize( $max_w, $max_h, $crop = false ) {
					return true;
				}
				public function multi_resize( $sizes ) {
					return array();
				}
				public function crop( $src_x, $src_y, $src_w, $src_h, $dst_w = null, $dst_h = null, $src_abs = false ) {
					return true;
				}
				public function rotate( $angle ) {
					return true;
				}
				public function flip( $horz, $vert ) {
					return true;
				}
				public function stream( $mime_type = null ) {
					return true;
				}
		};

		$method = new ReflectionMethod( $editor, 'get_output_format' );

		$result = $method->invoke( $editor, '/tmp/image.avif', 'image/avif' );

		$this->assertNotSame( 'image/avif', $result[2] );
		$this->assertSame( 'image/jpeg', $result[2] );
		$this->assertSame( 'jpg', pathinfo( (string) $result[0], PATHINFO_EXTENSION ) );
	}
}
