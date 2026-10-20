<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class SingleTemplateHookTest extends TestCase {

	private const AFTER_BANNER = '/do_action\(\s*[\'"]hyperpress_after_banner[\'"]\s*\)/';

	private static function source( string $relative ): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/' . $relative );
	}

	public function test_the_banner_partial_fires_the_after_banner_action_after_the_closing_header(): void {
		$source = self::source( 'template-parts/banner.php' );

		$this->assertSame( 1, preg_match_all( self::AFTER_BANNER, $source ) );
		$header = strrpos( $source, '</header>' );
		$action = strpos( $source, "do_action( 'hyperpress_after_banner' )" );
		$this->assertNotFalse( $header );
		$this->assertNotFalse( $action );
		$this->assertGreaterThan( $header, $action );
		$this->assertSame( '', trim( substr( $source, $action + strlen( "do_action( 'hyperpress_after_banner' );" ) ), " \t\r\n?>" ), 'The action is the last thing in the partial' );
	}

	public function test_single_no_longer_fires_the_after_banner_action_itself(): void {
		$this->assertDoesNotMatchRegularExpression( self::AFTER_BANNER, self::source( 'single.php' ) );
	}

	/** @return array<string, array{0: string, 1: bool}> template => whether it has a <main>. */
	public static function container_templates(): array {
		return array(
			'single'       => array( 'single.php', true ),
			'archive'      => array( 'archive.php', true ),
			'index'        => array( 'index.php', true ),
			'search'       => array( 'search.php', true ),
			'page'         => array( 'page.php', true ),
			'front page'   => array( 'front-page.php', true ),
			'kitchen sink' => array( 'page-templates/kitchen-sink.php', false ),
			'contact'      => array( 'page-templates/page-contact.php', true ),
			'full screen'  => array( 'page-templates/page-full-screen.php', true ),
			'full width'   => array( 'page-templates/page-full-width.php', true ),
			'sidebar left' => array( 'page-templates/page-sidebar-left.php', true ),
		);
	}

	/** @dataProvider container_templates */
	public function test_templates_with_a_main_container_fire_the_content_and_main_hooks( string $template, bool $has_main ): void {
		$source = self::source( $template );

		$before = strpos( $source, "do_action( 'hyperpress_before_content' )" );
		$open   = strpos( $source, '<div class="main-container' );
		$close  = strpos( $source, '</div><!-- Close main-container -->' );
		if ( false === $close ) {
			$close = strrpos( $source, '</div>' );
		}
		$after = strpos( $source, "do_action( 'hyperpress_after_content' )" );
		$this->assertNotFalse( $before, 'hyperpress_before_content missing' );
		$this->assertNotFalse( $open );
		$this->assertNotFalse( $after, 'hyperpress_after_content missing' );
		$this->assertLessThan( $open, $before, 'before_content must precede the main container' );
		$this->assertGreaterThan( $close, $after, 'after_content must follow the main container' );
		$this->assertLessThan( strpos( $source, 'get_footer()' ), $after );

		if ( ! $has_main ) {
			return;
		}
		$main_open  = strpos( $source, '<main ' );
		$main_close = strpos( $source, '</main>' );
		$before_m   = strpos( $source, "do_action( 'hyperpress_before_main' )" );
		$after_m    = strpos( $source, "do_action( 'hyperpress_after_main' )" );
		$this->assertNotFalse( $before_m, 'hyperpress_before_main missing' );
		$this->assertNotFalse( $after_m, 'hyperpress_after_main missing' );
		$this->assertGreaterThan( $main_open, $before_m );
		$this->assertLessThan( $main_close, $after_m );
		$this->assertLessThan( $after_m, $before_m );
		$breadcrumbs = strpos( $source, 'template-parts/breadcrumbs' );
		if ( false !== $breadcrumbs ) {
			$this->assertLessThan( $breadcrumbs, $before_m, 'before_main precedes the breadcrumbs' );
		}
	}

	public function test_the_front_page_countdown_block_keeps_its_own_hooks_outside_main(): void {
		$source = self::source( 'front-page.php' );

		$this->assertStringContainsString( "do_action( 'hyperpress_page_before_entry_content' )", $source );
		$this->assertStringContainsString( "do_action( 'hyperpress_page_after_entry_content' )", $source );
		$this->assertStringNotContainsString( 'foundationpress_', $source );
		$this->assertLessThan( strpos( $source, '<main ' ), strpos( $source, "do_action( 'hyperpress_page_after_entry_content' )" ) );
		$this->assertGreaterThan( strpos( $source, "do_action( 'hyperpress_page_after_entry_content' )" ), strpos( $source, "do_action( 'hyperpress_before_main' )" ) );
	}
}
