<?php
namespace HyperPress\Shortcode\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class HYPER {

    public static function register(): void {
        add_shortcode( 'hyperpress_hyper_abbreviation', array( self::class, 'render' ) );
    }

    public static function render(): string {
        return '<abbr title="Helping Youth Pursue Engineering & Robotics">HYPER</abbr>';
    }
}
