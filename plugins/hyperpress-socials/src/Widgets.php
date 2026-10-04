<?php
namespace HyperPress\Socials;

defined( 'ABSPATH' ) || exit;

use HyperPress\Socials\Widgets\DisplaySocialsWidget;

final class Widgets {

	public static function register(): void {
		add_action( 'widgets_init', array( self::class, 'load_widgets' ) );
	}

	public static function load_widgets(): void {
		register_widget( DisplaySocialsWidget::class );
	}
}
