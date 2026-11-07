<?php
namespace HyperPress\Tests\Integration\Media;

use HyperPress\Media\NextGen\IconStyles;
use HyperPress\Media\Plugin;
use WP_UnitTestCase;

final class NextGenIconStylesTest extends WP_UnitTestCase {

	private const ICONS = 'nextgen_gallery_icons';
	private const SCOPE = 'nextgen_gallery_icons_scope';
	private const OTHER = 'nextgen_basic_album_style';

	public function set_up(): void {
		parent::set_up();
		// Core's own printers on these hooks (a deprecated emoji printer, the footer script printer) are not under test and would echo.
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_print_footer_scripts', '_wp_footer_scripts' );
		IconStyles::register();
		$this->enqueue_nextgen_styles();
	}

	public function tear_down(): void {
		foreach ( array( self::ICONS, self::SCOPE, self::OTHER, 'nextgen_late_style' ) as $handle ) {
			wp_dequeue_style( $handle );
			wp_deregister_style( $handle );
		}
		remove_all_filters( 'hyperpress_media_dequeue_nextgen_icons' );
		unset( $GLOBALS['current_screen'] );
		set_current_screen( 'front' );
		parent::tear_down();
	}

	private function enqueue_nextgen_styles(): void {
		wp_register_style( self::ICONS, 'https://example.test/ngg-icons.min.css', array(), '1' );
		wp_register_style( self::SCOPE, 'https://example.test/ngg-icons-scope.css', array( self::ICONS ), '1' );
		wp_register_style( self::OTHER, 'https://example.test/album.css', array(), '1' );
		wp_enqueue_style( self::ICONS );
		wp_enqueue_style( self::SCOPE );
		wp_enqueue_style( self::OTHER );
	}

	public function test_icon_styles_are_dequeued_when_styles_print(): void {
		do_action( 'wp_print_styles' );

		$this->assertFalse( wp_style_is( self::ICONS, 'enqueued' ) );
		$this->assertFalse( wp_style_is( self::SCOPE, 'enqueued' ) );
	}

	public function test_other_nextgen_styles_are_kept(): void {
		do_action( 'wp_print_styles' );
		do_action( 'wp_print_footer_scripts' );

		$this->assertTrue( wp_style_is( self::OTHER, 'enqueued' ) );
	}

	public function test_styles_enqueued_after_wp_head_are_dequeued_in_the_footer(): void {
		do_action( 'wp_print_styles' );
		wp_register_style( self::ICONS, 'https://example.test/ngg-icons.min.css', array(), '1' );
		wp_enqueue_style( self::ICONS );
		wp_enqueue_style( self::SCOPE );
		$this->assertTrue( wp_style_is( self::ICONS, 'enqueued' ) );

		do_action( 'wp_print_footer_scripts' );

		$this->assertFalse( wp_style_is( self::ICONS, 'enqueued' ) );
		$this->assertFalse( wp_style_is( self::SCOPE, 'enqueued' ) );
		$this->assertTrue( wp_style_is( self::OTHER, 'enqueued' ) );
	}

	public function test_the_filter_can_opt_out(): void {
		add_filter( 'hyperpress_media_dequeue_nextgen_icons', '__return_false' );

		do_action( 'wp_print_styles' );
		do_action( 'wp_print_footer_scripts' );

		$this->assertTrue( wp_style_is( self::ICONS, 'enqueued' ) );
		$this->assertTrue( wp_style_is( self::SCOPE, 'enqueued' ) );
	}

	public function test_the_admin_keeps_the_styles(): void {
		set_current_screen( 'dashboard' );
		$this->assertTrue( is_admin() );

		do_action( 'wp_print_styles' );
		do_action( 'wp_print_footer_scripts' );

		$this->assertTrue( wp_style_is( self::ICONS, 'enqueued' ) );
		$this->assertTrue( wp_style_is( self::SCOPE, 'enqueued' ) );
	}

	public function test_register_twice_registers_the_callbacks_once(): void {
		IconStyles::register();
		IconStyles::register();

		$this->assertSame( 100, has_action( 'wp_print_styles', array( IconStyles::class, 'dequeue' ) ) );
		$this->assertSame( 5, has_action( 'wp_print_footer_scripts', array( IconStyles::class, 'dequeue' ) ) );
	}

	public function test_boot_registers_it(): void {
		remove_action( 'wp_print_styles', array( IconStyles::class, 'dequeue' ), 100 );
		remove_action( 'wp_print_footer_scripts', array( IconStyles::class, 'dequeue' ), 5 );
		$this->assertFalse( has_action( 'wp_print_styles', array( IconStyles::class, 'dequeue' ) ) );

		Plugin::boot();

		$this->assertSame( 100, has_action( 'wp_print_styles', array( IconStyles::class, 'dequeue' ) ) );
		$this->assertSame( 5, has_action( 'wp_print_footer_scripts', array( IconStyles::class, 'dequeue' ) ) );
	}
}
