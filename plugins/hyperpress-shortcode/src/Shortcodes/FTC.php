<?php
namespace HyperPress\Shortcode\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class FTC {

    public static function register(): void {
        add_shortcode( 'hyper_ftc_abbreviation', array( self::class, 'render' ) );
    }

    public static function render(): string {
        return '<abbr title="FIRST Tech Challenge">FTC</abbr>';
    }
}
