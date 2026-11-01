<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\Banner;
use WP_UnitTestCase;

final class BannerImageArgTest extends WP_UnitTestCase {

	private int $calls = 0;

	public function set_up(): void {
		parent::set_up();
		register_post_type( 'hyper_gizmo', array( 'public' => true, 'has_archive' => 'gizmos' ) );
		flush_rewrite_rules();
	}

	public function tear_down(): void {
		unregister_post_type( 'hyper_gizmo' );
		parent::tear_down();
	}

	private function register( array $extra ): void {
		Banner::register(
			'hyper_gizmo',
			array_merge(
				array(
					'type'          => 'gizmo',
					'subtitle'      => static fn() => 'Sub',
					'color_mod'     => 'hyperpress_hyper_green',
					'archive_title' => 'Gizmos',
				),
				$extra
			)
		);
	}

	private function archive_banner(): array {
		$this->go_to( get_post_type_archive_link( 'hyper_gizmo' ) );
		return apply_filters( 'hyperpress_banner_content', array() );
	}

	public function test_an_absent_image_arg_gives_no_image_key(): void {
		$this->register( array() );

		$this->assertArrayNotHasKey( 'image', $this->archive_banner() );
	}

	public function test_a_non_callable_image_arg_gives_no_image_key(): void {
		$this->register( array( 'image' => 'no_such_function_hyperpress' ) );

		$this->assertArrayNotHasKey( 'image', $this->archive_banner() );
	}

	public function test_the_image_callable_is_not_invoked_on_a_non_archive_request(): void {
		$this->register(
			array(
				'image' => function (): int {
					++$this->calls;
					return 0;
				},
			)
		);
		$post = self::factory()->post->create( array( 'post_type' => 'hyper_gizmo' ) );

		$this->go_to( get_permalink( $post ) );
		apply_filters( 'hyperpress_banner_content', array() );
		$this->go_to( home_url( '/' ) );
		apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 0, $this->calls );

		// Positive control: the same callable is invoked on the post type's archive request.
		$this->archive_banner();
		$this->assertSame( 1, $this->calls );
	}
}
