<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\ArchiveImage;
use WP_UnitTestCase;

final class ArchiveImageTest extends WP_UnitTestCase {

	private function image(): int {
		return (int) self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/test-image.jpg' );
	}

	private function pdf(): int {
		return (int) self::factory()->attachment->create( array( 'post_mime_type' => 'application/pdf' ) );
	}

	public function test_valid_id_returns_real_image_id(): void {
		$id = $this->image();
		$this->assertGreaterThan( 0, $id );
		$this->assertSame( $id, ArchiveImage::valid_id( $id ) );
		$this->assertSame( $id, ArchiveImage::valid_id( (string) $id ) );
	}

	public function test_valid_id_rejects_deleted_attachment(): void {
		$id = $this->image();
		wp_delete_attachment( $id, true );
		$this->assertSame( 0, ArchiveImage::valid_id( $id ) );
	}

	public function test_valid_id_rejects_non_image_attachment(): void {
		$this->assertSame( 0, ArchiveImage::valid_id( $this->pdf() ) );
	}

	public function test_valid_id_rejects_junk(): void {
		$this->assertSame( 0, ArchiveImage::valid_id( 'abc' ) );
		$this->assertSame( 0, ArchiveImage::valid_id( -3 ) );
		$this->assertSame( 0, ArchiveImage::valid_id( 0 ) );
		$this->assertSame( 0, ArchiveImage::valid_id( null ) );
		$this->assertSame( 0, ArchiveImage::valid_id( array( 1 ) ) );
	}

	public function test_sanitize(): void {
		$this->assertSame( 7, ArchiveImage::sanitize( '7' ) );
		$this->assertSame( 3, ArchiveImage::sanitize( -3 ) );
		$this->assertSame( 0, ArchiveImage::sanitize( '' ) );
		$this->assertSame( 0, ArchiveImage::sanitize( null ) );
		$this->assertSame( 0, ArchiveImage::sanitize( array( 5 ) ) );
	}

	public function test_term_image_id_prefers_valid_term_meta(): void {
		$term     = self::factory()->term->create();
		$meta     = $this->image();
		$fallback = $this->image();
		update_term_meta( $term, 'hyperpress_test_image', $meta );
		$this->assertSame( $meta, ArchiveImage::term_image_id( $term, 'hyperpress_test_image', $fallback ) );
	}

	public function test_term_image_id_falls_back_when_meta_absent_or_invalid(): void {
		$term     = self::factory()->term->create();
		$fallback = $this->image();
		$this->assertSame( $fallback, ArchiveImage::term_image_id( $term, 'hyperpress_test_image', $fallback ) );

		$gone = $this->image();
		wp_delete_attachment( $gone, true );
		update_term_meta( $term, 'hyperpress_test_image', $gone );
		$this->assertSame( $fallback, ArchiveImage::term_image_id( $term, 'hyperpress_test_image', $fallback ) );
	}

	public function test_term_image_id_is_zero_when_neither_is_valid(): void {
		$term = self::factory()->term->create();
		$this->assertSame( 0, ArchiveImage::term_image_id( $term, 'hyperpress_test_image', 0 ) );
		$this->assertSame( 0, ArchiveImage::term_image_id( $term, 'hyperpress_test_image', $this->pdf() ) );
	}

	public function test_field_is_a_single_image_field(): void {
		$field = ArchiveImage::field( 'hyperpress_x_image', 'Archive image', 'Shown on the archive.' );
		$this->assertSame( 'single_image', $field['type'] );
		$this->assertSame( 'hyperpress_x_image', $field['id'] );
		$this->assertSame( array( ArchiveImage::class, 'sanitize' ), $field['sanitize_callback'] );
	}
}
