<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

use HyperPress\Sponsor\Widgets\DisplaySponsorsWidget;

final class Widgets {

	public static function register(): void {
		add_action( 'widgets_init', array( self::class, 'load_widgets' ) );
	}

	public static function load_widgets(): void {
		register_widget( DisplaySponsorsWidget::class );
	}
}
