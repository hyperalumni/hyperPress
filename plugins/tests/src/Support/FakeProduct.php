<?php
namespace HyperPress\Tests\Support {

	/**
	 * A stand-in for WC_Product, so the ticket code is tested without WooCommerce.
	 *
	 * The global stubs wc_get_product() and wc_get_checkout_url() below read the static state here. Call reset()
	 * in tear_down().
	 */
	final class FakeProduct {

		/**
		 * Products by ID.
		 *
		 * @var array<int,FakeProduct>
		 */
		public static array $products = array();

		public static string $checkout_url = 'https://example.org/checkout/';

		public function __construct(
			public int $id,
			public string $name = 'Ticket',
			public string $price_html = '<span class="amount">$10.00</span>',
			public bool $in_stock = true,
			public bool $purchasable = true,
			public string $type = 'simple',
			public string $status = 'publish',
			public int $max = -1
		) {
			self::$products[ $id ] = $this;
		}

		public static function reset(): void {
			self::$products     = array();
			self::$checkout_url = 'https://example.org/checkout/';
		}

		public function get_id(): int {
			return $this->id;
		}

		public function get_name(): string {
			return $this->name;
		}

		public function get_price_html(): string {
			return $this->price_html;
		}

		public function is_in_stock(): bool {
			return $this->in_stock;
		}

		public function is_purchasable(): bool {
			return $this->purchasable;
		}

		public function is_type( string $type ): bool {
			return $this->type === $type;
		}

		public function get_permalink(): string {
			return 'https://example.org/product/' . $this->id . '/';
		}

		public function get_status(): string {
			return $this->status;
		}

		public function get_max_purchase_quantity(): int {
			return $this->max;
		}
	}
}

namespace {

	use HyperPress\Tests\Support\FakeProduct;

	if ( ! function_exists( 'wc_get_product' ) ) {
		/**
		 * Test stand-in: the fake product, or false.
		 *
		 * @param mixed $id Product ID.
		 */
		function wc_get_product( $id ) {
			return FakeProduct::$products[ (int) $id ] ?? false;
		}
	}

	if ( ! function_exists( 'wc_get_checkout_url' ) ) {
		/**
		 * Test stand-in for the checkout URL.
		 */
		function wc_get_checkout_url(): string {
			return FakeProduct::$checkout_url;
		}
	}
}
