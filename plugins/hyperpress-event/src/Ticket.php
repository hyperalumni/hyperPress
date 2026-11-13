<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * One product offered for an event.
 */
final readonly class Ticket {

	/**
	 * @param int    $id         Product ID.
	 * @param string $name       Product name.
	 * @param string $price_html Price markup from the product (shows a sale price as the discount).
	 * @param bool   $in_stock   Whether it can be bought now.
	 * @param bool   $simple     Whether it is a simple, purchasable product (otherwise only linked).
	 * @param string $url        Product page URL.
	 * @param int    $max        Largest quantity per order; 0 means unlimited.
	 */
	public function __construct(
		public int $id,
		public string $name,
		public string $price_html,
		public bool $in_stock,
		public bool $simple,
		public string $url,
		public int $max
	) {
	}
}
