<?php
namespace HyperPress\Tests\Integration\Shortcodes;

use WP_UnitTestCase;

/**
 * A callback can receive null as the shortcode body (do_shortcode_tag() passes it on when WordPress's
 * regex gives no body, and other callers invoke the callbacks directly). Nothing may hand that null to a
 * string function.
 */
final class NullContentTest extends WP_UnitTestCase {

	public static function callbacks(): array {
		return array(
			'hyper_emphasis'       => array( 'hyper_emphasis', array() ),
			'raw'                  => array( 'raw', array() ),
			'time-restrict'        => array( 'time-restrict', array( 'on' => '2000-01-01' ) ),
			'time-restrict-repeat' => array( 'time-restrict-repeat', array( 'type' => 'weekly', 'onday' => 'monday' ) ),
		);
	}

	/**
	 * @dataProvider callbacks
	 */
	public function test_a_null_body_renders_like_an_empty_one( string $tag, array $atts ): void {
		global $shortcode_tags;
		$this->assertArrayHasKey( $tag, $shortcode_tags );
		$callback = $shortcode_tags[ $tag ];

		$previous_level = error_reporting( E_ALL );
		$deprecations   = array();
		set_error_handler(
			static function ( int $errno, string $errstr ) use ( &$deprecations ): bool {
				$deprecations[] = $errstr;
				return true;
			},
			E_DEPRECATED | E_USER_DEPRECATED
		);
		try {
			$output = call_user_func( $callback, $atts, null, $tag );
		} finally {
			restore_error_handler();
			error_reporting( $previous_level );
		}

		$this->assertSame( array(), $deprecations );
		$this->assertSame( call_user_func( $callback, $atts, '', $tag ), $output );
	}
}
