<?php
namespace HyperPress\Shortcode\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class FIRST {

    public static function register(): void {
        add_shortcode( 'hyperpress_first_abbreviation', array( self::class, 'render' ) );
    }

    public static function render(): string {
        return '<abbr title="For Inspiration and Recognition of Science and Technology">FIRST</abbr>';
    }
}
