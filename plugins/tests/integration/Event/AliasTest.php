<?php
namespace HyperPress\Tests\Integration\Event;

use DateTimeImmutable;
use HyperPress\Event\Alias;
use HyperPress\Event\Fields;
use HyperPress\Event\Plugin;
use WP_UnitTestCase;

final class AliasTest extends WP_UnitTestCase {

	/**
	 * @var array<int,array{0:string,1:int}>
	 */
	private array $redirects = array();

	public function set_up(): void {
		parent::set_up();
		$this->set_permalink_structure( '/%postname%/' );
		Plugin::register();
		flush_rewrite_rules();
		$this->redirects = array();
		add_filter( 'hyperpress_event_now', static fn(): DateTimeImmutable => new DateTimeImmutable( '2026-06-15 12:00', wp_timezone() ) );
		add_filter( 'wp_redirect', array( $this, 'capture' ), 10, 2 );
	}

	public function tear_down(): void {
		remove_filter( 'wp_redirect', array( $this, 'capture' ), 10 );
		remove_all_filters( 'hyperpress_event_now' );
		parent::tear_down();
	}

	/**
	 * Records the redirect and cancels it, so wp_safe_redirect() returns false and nothing exits.
	 *
	 * @param string $location Target.
	 * @param int    $status   Status code.
	 */
	public function capture( $location, $status ): bool {
		$this->redirects[] = array( (string) $location, (int) $status );

		return false;
	}

	private function event( string $slug, string $start, ?string $end = null, string $status = 'publish', string $series = 'bar-crawl' ): int {
		$id = self::factory()->post->create(
			array(
				'post_type'   => Plugin::POST_TYPE,
				'post_title'  => $slug,
				'post_name'   => $slug,
				'post_status' => $status,
			)
		);
		update_post_meta( $id, Fields::START, $start );
		if ( null !== $end ) {
			update_post_meta( $id, Fields::END, $end );
		}
		if ( '' !== $series ) {
			wp_set_object_terms( $id, $series, Plugin::SERIES );
		}

		return $id;
	}

	private function series(): \WP_Term {
		$term = get_term_by( 'slug', 'bar-crawl', Plugin::SERIES );
		$this->assertInstanceOf( \WP_Term::class, $term );

		return $term;
	}

	/**
	 * Runs the handler for a request. Not do_action( 'template_redirect' ): core's redirect_canonical
	 * ends in an unconditional exit that a test cannot survive.
	 *
	 * @param string $path Request path.
	 */
	private function request( string $path ): void {
		$this->go_to( $path );
		Alias::maybe_redirect();
	}

	public function test_nearest_upcoming_wins(): void {
		$this->event( 'bar-crawl-2025', '2025-10-01 20:00' );
		$later = $this->event( 'bar-crawl-2027', '2027-10-01 20:00' );
		$near  = $this->event( 'bar-crawl-2026', '2026-10-01 20:00' );

		$this->assertSame( $near, Alias::target( $this->series() ) );
		$this->assertNotSame( $later, Alias::target( $this->series() ) );
	}

	public function test_latest_past_when_none_upcoming(): void {
		$this->event( 'bar-crawl-2023', '2023-10-01 20:00' );
		$latest = $this->event( 'bar-crawl-2025', '2025-10-01 20:00' );
		$this->event( 'bar-crawl-2024', '2024-10-01 20:00' );

		$this->assertSame( $latest, Alias::target( $this->series() ) );
	}

	public function test_tie_goes_to_the_lowest_id(): void {
		$first = $this->event( 'bar-crawl-a', '2026-10-01 20:00' );
		$this->event( 'bar-crawl-b', '2026-10-01 20:00' );

		$this->assertSame( $first, Alias::target( $this->series() ) );
	}

	public function test_an_event_still_running_is_upcoming(): void {
		$this->event( 'bar-crawl-2027', '2027-10-01 20:00' );
		$running = $this->event( 'bar-crawl-2026', '2026-06-15 10:00', '2026-06-15 14:00' );

		$this->assertSame( $running, Alias::target( $this->series() ) );
	}

	public function test_target_takes_an_explicit_now(): void {
		$this->event( 'bar-crawl-2025', '2025-10-01 20:00' );
		$latest = $this->event( 'bar-crawl-2026', '2026-10-01 20:00' );
		$past   = new DateTimeImmutable( '2027-01-01 00:00', wp_timezone() );

		$this->assertSame( $latest, Alias::target( $this->series(), $past ) );
	}

	public function test_unpublished_events_are_ignored(): void {
		$live = $this->event( 'bar-crawl-2027', '2027-10-01 20:00' );
		$this->event( 'bar-crawl-2026', '2026-10-01 20:00', null, 'draft' );

		$this->assertSame( $live, Alias::target( $this->series() ) );
	}

	public function test_series_without_published_events_stays_a_404(): void {
		$this->event( 'bar-crawl-2026', '2026-10-01 20:00', null, 'draft' );

		$this->assertNull( Alias::target( $this->series() ) );
		$this->request( '/event/bar-crawl/' );
		$this->assertTrue( is_404() );
		$this->assertSame( array(), $this->redirects );
	}

	public function test_runs_before_the_core_guess(): void {
		$past     = $this->event( 'bar-crawl-2025', '2025-10-01 20:00' );
		$upcoming = $this->event( 'bar-crawl-2026', '2026-10-01 20:00' );

		$this->request( '/event/bar-crawl/' );

		$this->assertCount( 1, $this->redirects );
		$this->assertSame( get_permalink( $upcoming ), $this->redirects[0][0] );
		// Core's slug-prefix guess would pick the past event, and it runs at priority 10 after the alias.
		$this->assertSame( get_permalink( $past ), redirect_guess_404_permalink() );
		$alias = has_action( 'template_redirect', array( Alias::class, 'maybe_redirect' ) );
		$core  = has_action( 'template_redirect', 'redirect_canonical' );
		$this->assertIsInt( $alias );
		$this->assertIsInt( $core );
		$this->assertLessThan( $core, $alias );
	}

	public function test_status_is_302(): void {
		$this->event( 'bar-crawl-2026', '2026-10-01 20:00' );

		$this->request( '/event/bar-crawl/' );

		$this->assertSame( 302, $this->redirects[0][1] );
	}

	public function test_mixed_case_alias_redirects(): void {
		$id = $this->event( 'bar-crawl-2026', '2026-10-01 20:00' );

		$this->request( '/event/Bar-Crawl/' );

		$this->assertCount( 1, $this->redirects );
		$this->assertSame( get_permalink( $id ), $this->redirects[0][0] );
	}

	public function test_a_real_event_with_the_series_slug_is_served_directly(): void {
		$this->event( 'bar-crawl-2026', '2026-10-01 20:00' );
		$real = $this->event( 'bar-crawl', '2025-01-01 20:00' );

		$this->request( '/event/bar-crawl/' );

		$this->assertFalse( is_404() );
		$this->assertSame( $real, get_queried_object_id() );
		$this->assertSame( array(), $this->redirects );
	}

	public function test_unrelated_404_is_untouched(): void {
		$this->event( 'bar-crawl-2026', '2026-10-01 20:00' );

		$this->request( '/event/nothing/' );

		$this->assertTrue( is_404() );
		$this->assertSame( array(), $this->redirects );
	}

	public function test_non_404_requests_are_untouched(): void {
		$this->event( 'bar-crawl-2026', '2026-10-01 20:00' );

		$this->request( '/' );

		$this->assertSame( array(), $this->redirects );
	}

	public function test_register_hooks_at_priority_5(): void {
		remove_action( 'template_redirect', array( Alias::class, 'maybe_redirect' ), 5 );
		Alias::register();

		$this->assertSame( 5, has_action( 'template_redirect', array( Alias::class, 'maybe_redirect' ) ) );
	}
}
