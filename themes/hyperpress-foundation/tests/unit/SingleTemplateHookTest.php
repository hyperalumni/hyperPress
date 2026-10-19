<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

final class SingleTemplateHookTest extends TestCase {

	public function test_single_fires_the_after_banner_action_between_the_banner_and_the_main_container(): void {
		$source = (string) file_get_contents( dirname( __DIR__, 2 ) . '/single.php' );

		$this->assertMatchesRegularExpression( '/do_action\(\s*[\'"]hyperpress_after_banner[\'"]\s*\)/', $source );
		$banner = strpos( $source, 'template-parts/banner' );
		$action = strpos( $source, "do_action( 'hyperpress_after_banner' )" );
		$main   = strpos( $source, 'main-container' );
		$this->assertNotFalse( $banner );
		$this->assertNotFalse( $action );
		$this->assertNotFalse( $main );
		$this->assertGreaterThan( $banner, $action );
		$this->assertLessThan( $main, $action );
	}
}
