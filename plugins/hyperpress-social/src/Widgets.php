<?php
namespace HyperPress\Social;

defined( 'ABSPATH' ) || exit;

use HyperPress\Social\Widgets\DisplaySocialWidget;

final class Widgets {

	public static function register(): void {
		add_action( 'widgets_init', array( self::class, 'load_widgets' ) );
	}

	public static function load_widgets(): void {
		register_widget( DisplaySocialWidget::class );
	}
}
