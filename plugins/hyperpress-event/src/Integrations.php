<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Runtime checks for the optional plugins events integrate with. Nothing here is a hard requirement.
 */
final class Integrations {

	/**
	 * Whether WooCommerce is active (overridable with the hyperpress_event_woocommerce_active filter).
	 */
	public static function woocommerce(): bool {
		return (bool) apply_filters( 'hyperpress_event_woocommerce_active', class_exists( 'WooCommerce' ) );
	}

	/**
	 * Whether NextGEN Gallery is active (overridable with the hyperpress_event_nextgen_active filter).
	 */
	public static function nextgen(): bool {
		return (bool) apply_filters( 'hyperpress_event_nextgen_active', class_exists( 'Imagely\NGG\DataMappers\Album' ) );
	}
}
