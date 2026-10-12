<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use HyperPress\Tests\Support\Convention;
use WP_UnitTestCase;

/**
 * The plugin is `hyperpress-shortcode`: the shortcode tags are prefixed `hyperpress_`, and the hook names follow the plugin slug.
 */
final class PluginIdentifiersTest extends WP_UnitTestCase {

	public static function tags(): array {
		return array(
			'hyperpress_banner'                 => array( 'hyperpress_banner' ),
			'hyperpress_emphasis'               => array( 'hyperpress_emphasis' ),
			'hyperpress_date_distance'          => array( 'hyperpress_date_distance' ),
			'hyperpress_raw'                    => array( 'hyperpress_raw' ),
			'hyperpress_time_restrict'          => array( 'hyperpress_time_restrict' ),
			'hyperpress_time_restrict_repeat'   => array( 'hyperpress_time_restrict_repeat' ),
			'hyperpress_time_restrict_repeat_1' => array( 'hyperpress_time_restrict_repeat_1' ),
			'hyperpress_time_restrict_repeat_2' => array( 'hyperpress_time_restrict_repeat_2' ),
			'hyperpress_time_restrict_repeat_3' => array( 'hyperpress_time_restrict_repeat_3' ),
		);
	}

	/**
	 * @dataProvider tags
	 */
	public function test_the_shortcode_tags_are_registered( string $tag ): void {
		$this->assertTrue( shortcode_exists( $tag ) );
	}

	public function test_the_classes_live_in_the_singular_namespace(): void {
		$this->assertTrue( class_exists( '\HyperPress\Shortcode\Plugin' ) );
		$this->assertFalse( class_exists( '\HyperPress\Shortcodes\Plugin' ) );
		foreach ( array( 'Banner', 'DateDistance', 'TimeRestrict', 'Raw', 'Emphasis' ) as $class_name ) {
			$this->assertTrue( class_exists( '\HyperPress\Shortcode\Shortcodes\\' . $class_name ), $class_name );
		}
	}

	public function test_the_hooks_use_the_singular_names_and_the_old_ones_are_gone(): void {
		$source = '';
		$files  = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( Convention::root() . '/hyperpress-shortcode', \FilesystemIterator::SKIP_DOTS ) );
		foreach ( $files as $file ) {
			if ( 'php' === $file->getExtension() ) {
				$source .= file_get_contents( $file->getPathname() );
			}
		}
		$this->assertStringContainsString( "'hyperpress_shortcode_now'", $source );
		$this->assertStringContainsString( "'hyperpress_shortcode_ordinals'", $source );
		$this->assertStringNotContainsString( 'hyperpress_shortcodes_', $source );
	}

	public function tear_down(): void {
		remove_all_filters( 'hyperpress_shortcode_now' );
		parent::tear_down();
	}

	public function test_a_frozen_now_outside_the_window_hides_the_content(): void {
		// The real current date is inside this window, so only the filter can make the block hide.
		add_filter( 'hyperpress_shortcode_now', static fn() => strtotime( '2150-01-01 12:00:00' ) );
		$this->assertSame( 'HIDDEN', do_shortcode( '[hyperpress_time_restrict on="2000-01-01" off="2100-01-01" else="HIDDEN"]SHOWN[/hyperpress_time_restrict]' ) );
	}

	public function test_a_frozen_now_inside_a_past_window_shows_the_content(): void {
		// The real current date is outside this window, so only the filter can make the block show.
		add_filter( 'hyperpress_shortcode_now', static fn() => strtotime( '2000-06-01 12:00:00' ) );
		$this->assertSame( 'SHOWN', do_shortcode( '[hyperpress_time_restrict on="2000-01-01" off="2001-01-01" else="HIDDEN"]SHOWN[/hyperpress_time_restrict]' ) );
	}
}
