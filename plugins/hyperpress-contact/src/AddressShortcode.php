<?php
namespace HyperPress\Contact;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The [hyperpress_address id="slug"] shortcode: one saved address as a Foundation card.
 */
final class AddressShortcode {

	public static function register(): void {
		add_shortcode( 'hyperpress_address', array( self::class, 'render' ) );
	}

	/**
	 * Renders the address with the given id, or a comment for administrators when it is unknown.
	 *
	 * @param mixed $atts Shortcode attributes.
	 */
	public static function render( $atts ): string {
		$atts = shortcode_atts( array( 'id' => '' ), is_array( $atts ) ? $atts : array(), 'hyperpress_address' );
		$id   = (string) $atts['id'];

		// Saved slugs are always sanitize_title() output, so match the typed id the same way.
		$slug    = sanitize_title( $id );
		$address = '' === $slug ? null : Settings::address( $slug );
		if ( null === $address ) {
			if ( ! current_user_can( 'manage_options' ) ) {
				return '';
			}
			// "--" would let the id end the comment early, so drop it and escape the rest.
			$safe = $id;
			while ( false !== strpos( $safe, '--' ) ) {
				$safe = str_replace( '--', '', $safe );
			}
			return '<!-- hyperpress_address: no address with id "' . esc_html( $safe ) . '" -->';
		}

		return self::card( $address );
	}

	/**
	 * The card markup for one address.
	 *
	 * @param array<string,string> $address Address fields; missing ones count as empty.
	 */
	public static function card( array $address ): string {
		$field = static function ( string $key ) use ( $address ): string {
			return isset( $address[ $key ] ) && is_scalar( $address[ $key ] ) ? trim( (string) $address[ $key ] ) : '';
		};

		$lines = array();
		if ( '' !== $field( 'name' ) ) {
			$lines[] = '<span class="fn">' . esc_html( $field( 'name' ) ) . '</span>';
		}
		if ( '' !== $field( 'extra' ) ) {
			$lines[] = '<span class="extended-address">' . esc_html( $field( 'extra' ) ) . '</span>';
		}
		if ( '' !== $field( 'street' ) ) {
			$lines[] = '<span class="street-address">' . esc_html( $field( 'street' ) ) . '</span>';
		}

		$locality = '';
		if ( '' !== $field( 'city' ) ) {
			$locality .= '<span class="locality">' . esc_html( $field( 'city' ) ) . '</span>';
		}
		if ( '' !== $field( 'state' ) ) {
			$locality .= ( '' !== $locality ? ', ' : '' ) . '<span class="state">' . esc_html( $field( 'state' ) ) . '</span>';
		}
		if ( '' !== $field( 'zip' ) ) {
			$locality .= ( '' !== $locality ? ' ' : '' ) . '<span class="zip">' . esc_html( $field( 'zip' ) ) . '</span>';
		}
		if ( '' !== $locality ) {
			$lines[] = $locality;
		}

		if ( '' !== $field( 'phone' ) ) {
			$href    = self::tel_href( $field( 'phone' ) );
			$text    = esc_html( $field( 'phone' ) );
			$lines[] = '<span class="phone">' . ( '' !== $href ? '<a href="' . esc_url( 'tel:' . $href ) . '">' . $text . '</a>' : $text ) . '</span>';
		}

		$out  = '<div class="card">';
		$out .= '<div class="card-divider"><h5>' . esc_html( $field( 'heading' ) ) . '</h5></div>';
		$out .= '<div class="card-section"><address class="vcard">' . implode( '<br>', $lines ) . '</address></div>';
		$out .= '</div>';
		return $out;
	}

	/**
	 * The dialable part of a phone number: a leading "+" and digits, "" when there are none.
	 *
	 * A trailing extension is dropped. Without a "+", 10 digits get "+1" and 11 digits starting with 1 get "+".
	 *
	 * @param string $phone Phone number as typed.
	 */
	public static function tel_href( string $phone ): string {
		$phone  = trim( (string) preg_replace( '/\s*(x|ext\.?)\s*\d+$/i', '', trim( $phone ) ) );
		$digits = (string) preg_replace( '/\D+/', '', $phone );
		if ( '' === $digits ) {
			return '';
		}
		if ( 0 === strpos( $phone, '+' ) ) {
			return '+' . $digits;
		}
		if ( 10 === strlen( $digits ) ) {
			return '+1' . $digits;
		}
		if ( 11 === strlen( $digits ) && '1' === $digits[0] ) {
			return '+' . $digits;
		}
		return $digits;
	}
}
