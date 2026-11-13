<?php
/**
 * Test stand-in for WooCommerce's wc_print_notices(), loaded only by the tests that need it: once defined it stays
 * defined for the rest of the process, so it prints FakeProduct::$notices (empty by default).
 */

use HyperPress\Tests\Support\FakeProduct;

if ( ! function_exists( 'wc_print_notices' ) ) {
	/**
	 * Prints (or returns) the fake notices.
	 *
	 * @param bool $return Return the markup instead of printing it.
	 */
	function wc_print_notices( $return = false ) {
		if ( $return ) {
			return FakeProduct::$notices;
		}
		echo FakeProduct::$notices; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Test stand-in.

		return null;
	}
}
