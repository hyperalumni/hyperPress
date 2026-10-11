<?php
namespace HyperPress\ThemeTests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * body_class() prints on <body>, so the admin-bar offsets must select body.admin-bar, never html.admin-bar.
 */
final class AdminBarStylesTest extends TestCase {

	private static function scss(): string {
		return (string) file_get_contents( dirname( __DIR__, 2 ) . '/src/assets/scss/global/_wp-admin.scss' );
	}

	public function test_no_rule_selects_admin_bar_on_html(): void {
		$this->assertDoesNotMatchRegularExpression( '/\bhtml\.(admin-bar|topbar|offcanvas)/', self::scss() );
	}

	public function test_the_offsets_select_the_body(): void {
		$scss = self::scss();
		$this->assertStringContainsString( 'body.admin-bar', $scss );
		$this->assertStringContainsString( 'html:has(body.admin-bar)', $scss, 'the root height rule needs the html element itself' );
	}
}