<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Loads the selected WooCommerce products as tickets.
 */
final class WooTickets {

	/**
	 * Tickets for the saved product IDs, in selection order. Missing, unpublished, trashed and non-product IDs
	 * and duplicates are skipped.
	 *
	 * @param array<mixed> $ids Saved product IDs.
	 * @return array<Ticket>
	 */
	public static function load( array $ids ): array {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return array();
		}

		$tickets = array();
		foreach ( $ids as $raw ) {
			$id = is_scalar( $raw ) ? absint( $raw ) : 0;
			if ( 0 === $id || isset( $tickets[ $id ] ) ) {
				continue;
			}
			$product = wc_get_product( $id );
			if ( ! is_object( $product ) || ! method_exists( $product, 'get_status' ) || 'publish' !== $product->get_status() ) {
				continue;
			}
			$max             = (int) $product->get_max_purchase_quantity();
			$tickets[ $id ] = new Ticket(
				$id,
				(string) $product->get_name(),
				(string) $product->get_price_html(),
				(bool) $product->is_in_stock(),
				(bool) $product->is_type( 'simple' ) && (bool) $product->is_purchasable(),
				(string) $product->get_permalink(),
				$max > 0 ? $max : 0
			);
		}

		return array_values( $tickets );
	}
}
