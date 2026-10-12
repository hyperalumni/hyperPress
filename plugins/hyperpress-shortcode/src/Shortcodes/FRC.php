<?php
namespace HyperPress\Shortcode\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class FRC {

    public static function register(): void {
        add_shortcode( 'hyperpress_frc_abbreviation', array( self::class, 'render' ) );
    }

    public static function render(): string {
        return '<abbr title="FIRST Robotics Competition">FRC</abbr>';
    }
}
