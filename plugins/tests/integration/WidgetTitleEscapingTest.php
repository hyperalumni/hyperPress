<?php
namespace HyperPress\Tests\Integration;

use HyperPress\Social\Widgets\DisplaySocialWidget;
use HyperPress\Sponsor\Widgets\DisplaySponsorsWidget;
use WP_UnitTestCase;

final class WidgetTitleEscapingTest extends WP_UnitTestCase {

	public static function widgets(): array {
		return array(
			'socials' => array( DisplaySocialWidget::class ),
			'sponsor' => array( DisplaySponsorsWidget::class ),
		);
	}

	/**
	 * @dataProvider widgets
	 */
	public function test_a_stored_title_with_markup_is_escaped_on_output( string $class ): void {
		// Core escapes widget titles through a default filter; the widget must not depend on it.
		remove_all_filters( 'widget_title' );
		$widget = new $class();
		$args   = array(
			'before_widget' => '<div class="w">',
			'after_widget'  => '</div>',
			'before_title'  => '<h2 class="t">',
			'after_title'   => '</h2>',
		);

		ob_start();
		$widget->widget( $args, array( 'title' => '<script>x</script>Hi' ) );
		$html = ob_get_clean();

		$this->assertStringNotContainsString( '<script', $html );
		$this->assertStringContainsString( '<h2 class="t">', $html );
		$this->assertStringContainsString( 'Hi</h2>', $html );
	}
}
