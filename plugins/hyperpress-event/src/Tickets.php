<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The [hyperpress_event_tickets] section and the one-step checkout redirect.
 */
final class Tickets {

	public const SHORTCODE = 'hyperpress_event_tickets';
	public const MARKER    = 'hyperpress_event_buy';

	public static function register(): void {
		add_shortcode( self::SHORTCODE, array( self::class, 'shortcode' ) );
		add_filter( 'woocommerce_add_to_cart_redirect', array( self::class, 'checkout_redirect' ) );
	}

	/**
	 * Shortcode handler. Prints nothing (not even the banner) unless WooCommerce is active, the current post is
	 * an event that is not over, and at least one selected product remains.
	 *
	 * @param array<string,mixed>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		if ( ! Integrations::woocommerce() ) {
			return '';
		}
		$post = get_post();
		if ( ! $post instanceof \WP_Post || Plugin::POST_TYPE !== $post->post_type || Dates::is_over( $post->ID ) ) {
			return '';
		}
		$ids = get_post_meta( $post->ID, Fields::PRODUCTS, false );
		if ( ! is_array( $ids ) || array() === $ids ) {
			return '';
		}
		// Meta Box saves one row per product; tolerate a single serialized list too.
		if ( 1 === count( $ids ) && is_array( $ids[0] ) ) {
			$ids = $ids[0];
		}
		$tickets = WooTickets::load( $ids );
		if ( array() === $tickets ) {
			return '';
		}

		$atts = Sections::atts( is_array( $atts ) ? $atts : array(), __( 'Tickets', 'hyperpress' ) );

		return Sections::banner( $post->ID, $atts ) . TicketsBlock::html( $tickets, $post->ID );
	}

	/**
	 * Sends a buy from the ticket rows straight to the checkout.
	 *
	 * @param mixed $url The redirect WooCommerce would use (it passes false when there is none); returned unchanged
	 *                   without the marker.
	 */
	public static function checkout_redirect( mixed $url ): mixed {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public GET add-to-cart flow, like WooCommerce's own; nothing is changed here, only the redirect target.
		if ( isset( $_GET[ self::MARKER ] ) && function_exists( 'wc_get_checkout_url' ) ) {
			return wc_get_checkout_url();
		}

		return $url;
	}
}
