<?php
namespace HyperPress\Tests\Integration\Event;

use DateTimeImmutable;
use HyperPress\Event\Fields;
use HyperPress\Event\Ticket;
use HyperPress\Event\Tickets;
use HyperPress\Event\TicketsBlock;
use HyperPress\Event\WooTickets;
use HyperPress\Tests\Support\FakeProduct;
use WP_UnitTestCase;

final class TicketsTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		FakeProduct::reset();
		add_filter( 'hyperpress_event_woocommerce_active', '__return_true' );
		add_filter( 'hyperpress_event_now', array( $this, 'now' ) );
	}

	public function tear_down(): void {
		remove_filter( 'hyperpress_event_woocommerce_active', '__return_true' );
		remove_filter( 'hyperpress_event_now', array( $this, 'now' ) );
		FakeProduct::reset();
		unset( $GLOBALS['post'], $_GET['hyperpress_event_buy'] );
		parent::tear_down();
	}

	public function now(): DateTimeImmutable {
		return new DateTimeImmutable( '2030-01-01 10:00', wp_timezone() );
	}

	/**
	 * @param array<int> $product_ids Selected products.
	 */
	private function event( array $product_ids = array(), string $start = '2030-03-10 09:00', string $end = '2030-03-10 17:00' ): int {
		$id = self::factory()->post->create( array( 'post_type' => 'hyper_event', 'post_status' => 'publish', 'post_title' => 'Bar Crawl' ) );
		update_post_meta( $id, Fields::START, $start );
		update_post_meta( $id, Fields::END, $end );
		foreach ( $product_ids as $product_id ) {
			add_post_meta( $id, Fields::PRODUCTS, $product_id );
		}

		return $id;
	}

	private function ticket( int $id = 5, bool $in_stock = true, bool $simple = true, int $max = 0 ): Ticket {
		return new Ticket( $id, 'Early bird', '<span>$10</span>', $in_stock, $simple, 'https://example.org/product/' . $id . '/', $max );
	}

	public function test_published_simple_in_stock_product_becomes_a_ticket(): void {
		new FakeProduct( 5, 'Early bird', '<span>$10</span>', true, true, 'simple', 'publish', -1 );

		$tickets = WooTickets::load( array( '5' ) );

		$this->assertCount( 1, $tickets );
		$this->assertSame( 5, $tickets[0]->id );
		$this->assertSame( 'Early bird', $tickets[0]->name );
		$this->assertSame( '<span>$10</span>', $tickets[0]->price_html );
		$this->assertTrue( $tickets[0]->in_stock );
		$this->assertTrue( $tickets[0]->simple );
		$this->assertSame( 0, $tickets[0]->max );
	}

	public function test_out_of_stock_product_stays_with_in_stock_false(): void {
		new FakeProduct( 5, 'Early bird', '', false );

		$tickets = WooTickets::load( array( 5 ) );

		$this->assertCount( 1, $tickets );
		$this->assertFalse( $tickets[0]->in_stock );
	}

	public function test_limited_stock_is_carried_as_max(): void {
		new FakeProduct( 5, 'Early bird', '', true, true, 'simple', 'publish', 12 );

		$this->assertSame( 12, WooTickets::load( array( 5 ) )[0]->max );
	}

	public function test_missing_unpublished_trashed_and_non_product_ids_are_skipped(): void {
		new FakeProduct( 1, 'Draft', '', true, true, 'simple', 'draft' );
		new FakeProduct( 2, 'Trashed', '', true, true, 'simple', 'trash' );
		new FakeProduct( 3, 'Good' );

		// 4 is missing; 0 and 'abc' are not IDs.
		$tickets = WooTickets::load( array( 1, 2, 4, 0, 'abc', 3 ) );

		$this->assertSame( array( 3 ), array_map( static fn( Ticket $t ): int => $t->id, $tickets ) );
	}

	public function test_non_simple_product_is_flagged_and_links_to_its_page(): void {
		new FakeProduct( 7, 'Bundle', '', true, true, 'variable' );

		$ticket = WooTickets::load( array( 7 ) )[0];

		$this->assertFalse( $ticket->simple );
		$this->assertSame( 'https://example.org/product/7/', $ticket->url );
	}

	public function test_selection_order_kept_and_duplicates_dropped(): void {
		new FakeProduct( 1 );
		new FakeProduct( 2 );
		new FakeProduct( 3 );

		$tickets = WooTickets::load( array( 3, 1, 3, 2, 1 ) );

		$this->assertSame( array( 3, 1, 2 ), array_map( static fn( Ticket $t ): int => $t->id, $tickets ) );
	}

	public function test_row_has_name_price_quantity_and_buy_form(): void {
		$event = $this->event();

		$html = TicketsBlock::html( array( $this->ticket( 5 ) ), $event );

		$this->assertStringContainsString( 'Early bird', $html );
		$this->assertStringContainsString( '<span>$10</span>', $html );
		$this->assertStringContainsString( 'name="quantity" type="number" min="1" value="1"', $html );
		$this->assertStringContainsString( 'name="add-to-cart" value="5"', $html );
		$this->assertStringContainsString( 'name="hyperpress_event_buy" value="1"', $html );
		$this->assertStringContainsString( 'method="get"', $html );
		$this->assertStringContainsString( 'action="' . esc_url( get_permalink( $event ) ) . '"', $html );
		$this->assertStringContainsString( 'Buy', $html );
	}

	public function test_limited_stock_sets_max(): void {
		$html = TicketsBlock::html( array( $this->ticket( 5, true, true, 8 ) ), $this->event() );

		$this->assertStringContainsString( 'name="quantity" type="number" min="1" max="8" value="1"', $html );
	}

	public function test_unlimited_has_no_max(): void {
		$html = TicketsBlock::html( array( $this->ticket( 5, true, true, 0 ) ), $this->event() );

		$this->assertStringNotContainsString( 'max=', $html );
	}

	public function test_sold_out_row_has_text_and_no_button(): void {
		$html = TicketsBlock::html( array( $this->ticket( 5, false ) ), $this->event() );

		$this->assertStringContainsString( 'Early bird', $html );
		$this->assertStringContainsString( 'Sold out', $html );
		$this->assertStringNotContainsString( '<form', $html );
		$this->assertStringNotContainsString( '<button', $html );
	}

	public function test_non_simple_row_is_a_link_without_a_form(): void {
		$html = TicketsBlock::html( array( $this->ticket( 7, true, false ) ), $this->event() );

		$this->assertStringContainsString( 'href="https://example.org/product/7/"', $html );
		$this->assertStringNotContainsString( '<form', $html );
	}

	public function test_names_are_escaped(): void {
		$ticket = new Ticket( 5, '<script>x</script>', '', true, true, 'https://example.org/p/', 0 );

		$this->assertStringNotContainsString( '<script>', TicketsBlock::html( array( $ticket ), $this->event() ) );
	}

	public function test_the_shortcode_is_registered(): void {
		$this->assertTrue( shortcode_exists( Tickets::SHORTCODE ) );
		$this->assertSame( 'hyperpress_event_tickets', Tickets::SHORTCODE );
		$this->assertNotFalse( has_filter( 'woocommerce_add_to_cart_redirect', array( Tickets::class, 'checkout_redirect' ) ) );
	}

	public function test_renders_rows_for_the_current_event(): void {
		new FakeProduct( 5, 'Early bird' );
		$id = $this->event( array( 5 ) );
		$this->go_to( get_permalink( $id ) );

		$html = Tickets::shortcode( array() );

		$this->assertStringContainsString( 'Early bird', $html );
		$this->assertStringContainsString( '<form', $html );
	}

	public function test_empty_when_woocommerce_inactive(): void {
		new FakeProduct( 5 );
		$id = $this->event( array( 5 ) );
		$this->go_to( get_permalink( $id ) );
		remove_filter( 'hyperpress_event_woocommerce_active', '__return_true' );
		add_filter( 'hyperpress_event_woocommerce_active', '__return_false' );

		$this->assertSame( '', Tickets::shortcode( array() ) );

		remove_filter( 'hyperpress_event_woocommerce_active', '__return_false' );
	}

	public function test_empty_without_products(): void {
		$id = $this->event();
		$this->go_to( get_permalink( $id ) );

		$this->assertSame( '', Tickets::shortcode( array( 'title' => 'Tickets' ) ) );
	}

	public function test_empty_when_no_product_remains(): void {
		new FakeProduct( 5, 'Gone', '', true, true, 'simple', 'trash' );
		$id = $this->event( array( 5, 99 ) );
		$this->go_to( get_permalink( $id ) );

		$this->assertSame( '', Tickets::shortcode( array( 'title' => 'Tickets' ) ) );
	}

	public function test_empty_when_the_event_is_over(): void {
		new FakeProduct( 5 );
		$id = $this->event( array( 5 ), '2029-03-10 09:00', '2029-03-10 17:00' );
		$this->go_to( get_permalink( $id ) );

		$this->assertSame( '', Tickets::shortcode( array() ) );
	}

	public function test_empty_outside_an_event_without_notices(): void {
		new FakeProduct( 5 );
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		$this->go_to( get_permalink( $page ) );

		$this->assertSame( '', Tickets::shortcode( array() ) );

		unset( $GLOBALS['post'] );
		$this->assertSame( '', Tickets::shortcode( '' ) );
	}

	public function test_prints_its_own_banner_with_default_title_tickets_and_custom_attributes(): void {
		new FakeProduct( 5, 'Early bird' );
		$id = $this->event( array( 5 ) );
		$this->go_to( get_permalink( $id ) );

		$default = Tickets::shortcode( array() );
		$custom  = Tickets::shortcode( array( 'title' => 'Get in', 'subtitle' => 'Limited', 'anchor' => 'buy' ) );

		$this->assertStringContainsString( 'Tickets', $default );
		$this->assertStringContainsString( 'Get in', $custom );
		$this->assertStringContainsString( 'Limited', $custom );
		$this->assertStringContainsString( 'buy', $custom );
		$this->assertStringNotContainsString( '>Tickets<', $custom );
		$this->assertStringContainsString( 'Early bird', $custom );
	}

	public function test_checkout_redirect_with_the_marker_returns_the_checkout_url(): void {
		FakeProduct::$checkout_url = 'https://example.org/checkout/';
		$_GET['hyperpress_event_buy'] = '1';

		$this->assertSame( 'https://example.org/checkout/', Tickets::checkout_redirect( 'https://example.org/cart/' ) );
	}

	public function test_checkout_redirect_without_the_marker_returns_the_input(): void {
		$this->assertSame( 'https://example.org/cart/', Tickets::checkout_redirect( 'https://example.org/cart/' ) );
	}

	public function test_checkout_redirect_passes_non_strings_through(): void {
		$this->assertFalse( Tickets::checkout_redirect( false ) );
		$this->assertNull( Tickets::checkout_redirect( null ) );

		$_GET['hyperpress_event_buy'] = '1';
		FakeProduct::$checkout_url    = 'https://example.org/checkout/';
		$this->assertSame( 'https://example.org/checkout/', Tickets::checkout_redirect( false ) );
	}

	public function test_no_notice_markup_without_woocommerce_notices(): void {
		$html = TicketsBlock::html( array( $this->ticket( 5 ) ), $this->event() );

		$this->assertStringStartsWith( '<div class="hyperpress-event-tickets"><div class="hyperpress-event-ticket">', $html );
	}

	public function test_woocommerce_notices_are_printed_above_the_rows(): void {
		require_once dirname( __DIR__, 2 ) . '/src/Support/wc_print_notices.php';
		FakeProduct::$notices = '<div class="woocommerce-error">FAKE-NOTICE</div>';

		$html = TicketsBlock::html( array( $this->ticket( 5 ) ), $this->event() );

		$this->assertStringContainsString( 'FAKE-NOTICE', $html );
		$this->assertLessThan( strpos( $html, 'hyperpress-event-ticket-name' ), strpos( $html, 'FAKE-NOTICE' ) );
	}
}
