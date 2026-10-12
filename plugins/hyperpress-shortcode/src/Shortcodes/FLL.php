<?php
namespace HyperPress\Shortcode\Shortcodes;

defined( 'ABSPATH' ) || exit;

final class FLL {

    public static function register(): void {
        add_shortcode( 'hyper_fll_abbreviation', array( self::class, 'render' ) );
    }

    public static function render(): string {
        return '<abbr title="FIRST LEGO League">FLL</abbr>';
    }
}
