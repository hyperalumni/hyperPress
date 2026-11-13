<?php
namespace HyperPress\Event;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The markup of the ticket rows.
 */
final class TicketsBlock {

	/**
	 * One compact row per ticket.
	 *
	 * @param array<Ticket> $tickets  Tickets to show.
	 * @param int           $event_id Event ID; the buy form goes to its permalink.
	 */
	public static function html( array $tickets, int $event_id ): string {
		if ( array() === $tickets ) {
			return '';
		}

		$rows = '';
		foreach ( $tickets as $ticket ) {
			$rows .= self::row( $ticket, $event_id );
		}

		return self::notices() . '<div class="hyperpress-event-tickets">' . $rows . '</div>';
	}

	/**
	 * WooCommerce's pending notices (a failed add-to-cart explains itself here), or '' without WooCommerce.
	 */
	private static function notices(): string {
		if ( ! function_exists( 'wc_print_notices' ) ) {
			return '';
		}
		$html = wc_print_notices( true );

		return is_string( $html ) ? $html : '';
	}

	private static function row( Ticket $ticket, int $event_id ): string {
		$html  = '<div class="hyperpress-event-ticket">';
		$html .= '<span class="hyperpress-event-ticket-name">' . esc_html( $ticket->name ) . '</span> ';
		$html .= '<span class="hyperpress-event-ticket-price">' . wp_kses_post( $ticket->price_html ) . '</span> ';

		if ( ! $ticket->simple ) {
			$html .= '<a class="hyperpress-event-ticket-link" href="' . esc_url( $ticket->url ) . '">' . esc_html__( 'View', 'hyperpress' ) . '</a>';
		} elseif ( ! $ticket->in_stock ) {
			$html .= '<span class="hyperpress-event-ticket-soldout">' . esc_html__( 'Sold out', 'hyperpress' ) . '</span>';
		} else {
			$max   = $ticket->max > 0 ? ' max="' . esc_attr( (string) $ticket->max ) . '"' : '';
			$html .= '<form class="hyperpress-event-ticket-form" method="get" action="' . esc_url( (string) get_permalink( $event_id ) ) . '">';
			$html .= '<input type="hidden" name="add-to-cart" value="' . esc_attr( (string) $ticket->id ) . '">';
			$html .= '<input type="hidden" name="' . esc_attr( Tickets::MARKER ) . '" value="1">';
			$html .= '<input name="quantity" type="number" min="1"' . $max . ' value="1" aria-label="' . esc_attr__( 'Quantity', 'hyperpress' ) . '"> ';
			$html .= '<button type="submit" class="button">' . esc_html__( 'Buy', 'hyperpress' ) . '</button>';
			$html .= '</form>';
		}

		return $html . '</div>';
	}
}
