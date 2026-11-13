<?php
namespace HyperPress\Tests\Integration\Event;

use DateTimeImmutable;
use HyperPress\Event\Fields;
use HyperPress\Event\Listing;
use HyperPress\Event\ListShortcode;
use HyperPress\Event\Plugin;
use HyperPress\Event\Settings;
use WP_UnitTestCase;

final class ListingTest extends WP_UnitTestCase {

	/**
	 * @var array<int,array{0:string,1:callable,2:int}>
	 */
	private array $added = array();

	public function set_up(): void {
		parent::set_up();
		delete_option( Settings::OPTION );
		update_option( 'date_format', 'j F Y' );
		$this->clock( '2027-06-15 12:00' );
	}

	public function tear_down(): void {
		foreach ( $this->added as $hook ) {
			remove_filter( $hook[0], $hook[1], $hook[2] );
		}
		$this->added = array();
		remove_all_filters( 'hyperpress_event_now' );
		parent::tear_down();
	}

	private function clock( string $when ): void {
		remove_all_filters( 'hyperpress_event_now' );
		add_filter(
			'hyperpress_event_now',
			static fn(): DateTimeImmutable => new DateTimeImmutable( $when, wp_timezone() )
		);
	}

	private function event( string $title, ?string $start, array $extra = array(), string $series = '' ): int {
		$id = self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => Plugin::POST_TYPE,
					'post_title'  => $title,
					'post_status' => 'publish',
				),
				$extra
			)
		);
		if ( null !== $start ) {
			update_post_meta( $id, Fields::START, $start );
		}
		if ( '' !== $series ) {
			wp_set_object_terms( $id, $series, Plugin::SERIES );
		}

		return $id;
	}

	/**
	 * Two upcoming, two past and one undated event; returns them keyed by name.
	 *
	 * @return array<string,int>
	 */
	private function fixture(): array {
		return array(
			'undated'  => $this->event( 'Undated', null ),
			'past_old' => $this->event( 'Past old', '2027-01-10 10:00' ),
			'later'    => $this->event( 'Later', '2027-09-01 10:00' ),
			'past_new' => $this->event( 'Past new', '2027-05-01 10:00' ),
			'soon'     => $this->event( 'Soon', '2027-07-01 10:00' ),
		);
	}

	public function test_order_is_upcoming_then_past(): void {
		$e = $this->fixture();

		$this->assertSame(
			array( $e['soon'], $e['later'], $e['past_new'], $e['past_old'], $e['undated'] ),
			Listing::ids()
		);
	}

	public function test_an_event_still_running_counts_as_upcoming(): void {
		$running = $this->event( 'Running', '2027-06-14 10:00', array() );
		update_post_meta( $running, Fields::END, '2027-06-16 10:00' );
		$past = $this->event( 'Past', '2027-05-01 10:00' );

		$this->assertSame( array( $running, $past ), Listing::ids() );
		$this->assertSame( array( $running ), Listing::ids( '', 'upcoming' ) );
	}

	public function test_show_upcoming_past_all_and_unknown_means_all(): void {
		$e = $this->fixture();

		$this->assertSame( array( $e['soon'], $e['later'] ), Listing::ids( '', 'upcoming' ) );
		$this->assertSame( array( $e['past_new'], $e['past_old'] ), Listing::ids( '', 'past' ) );
		$all = array( $e['soon'], $e['later'], $e['past_new'], $e['past_old'], $e['undated'] );
		$this->assertSame( $all, Listing::ids( '', 'all' ) );
		$this->assertSame( $all, Listing::ids( '', 'sometimes' ) );
	}

	public function test_show_is_trimmed_and_case_insensitive(): void {
		$e = $this->fixture();

		$this->assertSame( array( $e['soon'], $e['later'] ), Listing::ids( '', ' Upcoming ' ) );
		$html = do_shortcode( '[hyperpress_event_list show=" Past "]' );
		$this->assertStringContainsString( 'event-list', $html );
		$this->assertSame( array( $e['past_new'], $e['past_old'] ), Listing::ids( '', "PAST\n" ) );
	}

	public function test_ids_reads_the_meta_of_all_events_in_one_query(): void {
		$this->fixture();
		wp_cache_flush();
		global $wpdb;
		$meta_reads = 0;
		$count      = static function ( $query ) use ( &$meta_reads, $wpdb ) {
			if ( false !== strpos( (string) $query, 'FROM ' . $wpdb->postmeta ) ) {
				++$meta_reads;
			}

			return $query;
		};
		add_filter( 'query', $count );

		Listing::ids();

		remove_filter( 'query', $count );
		$this->assertLessThanOrEqual( 1, $meta_reads );
	}

	public function test_now_argument_overrides_the_clock(): void {
		$e = $this->fixture();

		$this->assertSame(
			array( $e['later'] ),
			Listing::ids( '', 'upcoming', 0, new DateTimeImmutable( '2027-08-01 00:00', wp_timezone() ) )
		);
	}

	public function test_series_filter_and_unknown_series_is_empty(): void {
		$in_a  = $this->event( 'A1', '2027-07-01 10:00', array(), 'camp' );
		$in_b  = $this->event( 'A2', '2027-08-01 10:00', array(), 'camp' );
		$other = $this->event( 'Other', '2027-07-05 10:00', array(), 'tour' );

		$this->assertSame( array( $in_a, $in_b ), Listing::ids( 'camp' ) );
		$this->assertSame( array( $other ), Listing::ids( 'tour' ) );
		$this->assertSame( array(), Listing::ids( 'nope' ) );
		$this->assertCount( 3, Listing::ids() );
	}

	public function test_limit_applies_after_ordering_and_negative_means_none(): void {
		$e = $this->fixture();

		$this->assertSame( array( $e['soon'], $e['later'] ), Listing::ids( '', 'all', 2 ) );
		$this->assertCount( 5, Listing::ids( '', 'all', 0 ) );
		$this->assertCount( 5, Listing::ids( '', 'all', -3 ) );
	}

	public function test_drafts_private_and_trashed_are_excluded(): void {
		$live = $this->event( 'Live', '2027-07-01 10:00' );
		$this->event( 'Draft', '2027-07-02 10:00', array( 'post_status' => 'draft' ) );
		$this->event( 'Private', '2027-07-03 10:00', array( 'post_status' => 'private' ) );
		$gone = $this->event( 'Trash', '2027-07-04 10:00' );
		wp_trash_post( $gone );

		$this->assertSame( array( $live ), Listing::ids() );
	}

	public function test_shortcode_markup_has_title_link_date_venue_and_thumbnail_when_present(): void {
		$id = $this->event( 'Summer Camp', '2027-07-01 10:00' );
		update_post_meta( $id, Fields::VENUE_NAME, 'Town Hall' );
		$thumb = self::factory()->attachment->create_upload_object( DIR_TESTDATA . '/images/canola.jpg' );
		set_post_thumbnail( $id, $thumb );

		$html = do_shortcode( '[' . ListShortcode::SHORTCODE . ']' );

		$this->assertStringContainsString( 'Summer Camp', $html );
		$this->assertStringContainsString( esc_url( get_permalink( $id ) ), $html );
		$this->assertStringContainsString( '1 July 2027', $html );
		$this->assertStringContainsString( 'Town Hall', $html );
		$this->assertStringContainsString( '<img', $html );

		$bare = $this->event( 'Bare', '2027-08-01 10:00' );
		$html = Listing::html( array( $bare ) );
		$this->assertStringContainsString( 'Bare', $html );
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringNotContainsString( 'event-list-venue', $html );
	}

	public function test_shortcode_attributes_are_applied(): void {
		$soon = $this->event( 'Soon', '2027-07-01 10:00', array(), 'camp' );
		$this->event( 'Old', '2027-01-01 10:00', array(), 'camp' );
		$this->event( 'Elsewhere', '2027-07-02 10:00', array(), 'tour' );

		$html = do_shortcode( '[hyperpress_event_list series="camp" show="upcoming" limit="5"]' );

		$this->assertStringContainsString( 'Soon', $html );
		$this->assertStringNotContainsString( 'Old', $html );
		$this->assertStringNotContainsString( 'Elsewhere', $html );
		$this->assertSame( array( $soon ), Listing::ids( 'camp', 'upcoming' ) );
	}

	public function test_shortcode_is_empty_without_events(): void {
		$this->assertSame( '', do_shortcode( '[' . ListShortcode::SHORTCODE . ']' ) );
		$this->assertSame( '', Listing::html( array() ) );
	}

	public function test_series_archive_uses_the_plugin_template_and_order(): void {
		update_option( 'posts_per_page', 2 );
		$third  = $this->event( 'Third', '2027-09-01 10:00', array(), 'camp' );
		$first  = $this->event( 'First', '2027-07-01 10:00', array(), 'camp' );
		$fourth = $this->event( 'Fourth', '2027-02-01 10:00', array(), 'camp' );
		$second = $this->event( 'Second', '2027-08-01 10:00', array(), 'camp' );

		$this->go_to( '/?' . Plugin::SERIES . '=camp' );
		global $wp_query;
		$this->assertTrue( is_tax( Plugin::SERIES ) );
		$this->assertSame( array( $first, $second, $third, $fourth ), $wp_query->get( 'post__in' ) );
		$this->assertSame( 'post__in', $wp_query->get( 'orderby' ) );
		$this->assertSame( array( $first, $second ), wp_list_pluck( $wp_query->posts, 'ID' ) );
		$this->assertStringEndsWith( 'templates/series.php', (string) apply_filters( 'hyperpress_posts_template', '' ) );

		$this->go_to( '/?' . Plugin::SERIES . '=camp&paged=2' );
		global $wp_query;
		$this->assertSame( array( $third, $fourth ), wp_list_pluck( $wp_query->posts, 'ID' ) );
	}

	public function test_series_archive_still_applies_when_the_request_carries_another_taxonomy(): void {
		$id = $this->event( 'In Series', '2027-07-01 10:00', array(), 'camp' );
		self::factory()->category->create( array( 'slug' => 'news' ) );

		$this->go_to( '/?category_name=news&' . Plugin::SERIES . '=camp' );
		global $wp_query;

		$this->assertSame( array( $id ), $wp_query->get( 'post__in' ) );
		$this->assertStringEndsWith( 'templates/series.php', (string) apply_filters( 'hyperpress_posts_template', '' ) );
	}

	public function test_another_taxonomy_archive_is_not_a_series_archive(): void {
		$this->event( 'In Series', '2027-07-01 10:00', array(), 'camp' );
		self::factory()->category->create( array( 'slug' => 'news' ) );

		$this->go_to( '/?category_name=news' );
		global $wp_query;

		$this->assertSame( array(), (array) $wp_query->get( 'post__in' ) );
		$this->assertSame( 'kept', apply_filters( 'hyperpress_posts_template', 'kept' ) );
		$this->assertSame( array( 'title' => '' ), apply_filters( 'hyperpress_banner_content', array( 'title' => '' ) ) );
	}

	public function test_series_archive_template_prints_the_events(): void {
		$this->event( 'In Series', '2027-07-01 10:00', array(), 'camp' );
		$this->go_to( '/?' . Plugin::SERIES . '=camp' );

		ob_start();
		include (string) apply_filters( 'hyperpress_posts_template', '' );
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'In Series', $html );
	}

	public function test_other_requests_keep_their_own_template_and_query(): void {
		$this->go_to( '/' );
		$this->assertSame( 'kept', apply_filters( 'hyperpress_posts_template', 'kept' ) );
	}

	public function test_empty_series_archive_queries_nothing(): void {
		$this->event( 'Draft', '2027-07-01 10:00', array( 'post_status' => 'draft' ), 'camp' );
		$this->go_to( '/?' . Plugin::SERIES . '=camp' );
		global $wp_query;

		$this->assertSame( array( 0 ), $wp_query->get( 'post__in' ) );
		$this->assertSame( array(), $wp_query->posts );
	}

	public function test_series_banner_uses_the_term_name_and_fallback_subtitle(): void {
		update_option( Settings::OPTION, array( Settings::FALLBACK_SUBTITLE => 'Team HYPER event' ) );
		$this->event( 'One', '2027-07-01 10:00', array(), 'camp' );
		wp_update_term( (int) get_term_by( 'slug', 'camp', Plugin::SERIES )->term_id, Plugin::SERIES, array( 'name' => 'Summer Camps' ) );

		// Stand-ins for the theme's category/tag (80) and generic archive (90) fallbacks.
		$specific = static function ( $banner ) {
			if ( empty( $banner['type'] ) ) {
				$banner['type']     = 'category';
				$banner['subtitle'] = 'Category';
			}
			return $banner;
		};
		$generic  = static function ( $banner ) {
			if ( empty( $banner['title'] ) ) {
				$banner['type']  = $banner['type'] ?? 'archive';
				$banner['title'] = 'Archive';
			}
			return $banner;
		};
		add_filter( 'hyperpress_banner_content', $specific, 80 );
		add_filter( 'hyperpress_banner_content', $generic, 90 );
		$this->added[] = array( 'hyperpress_banner_content', $specific, 80 );
		$this->added[] = array( 'hyperpress_banner_content', $generic, 90 );

		$this->go_to( '/?' . Plugin::SERIES . '=camp' );
		$banner = apply_filters( 'hyperpress_banner_content', array() );

		$this->assertSame( 'event', $banner['type'] );
		$this->assertSame( 'Summer Camps', $banner['title'] );
		$this->assertSame( 'Team HYPER event', $banner['subtitle'] );
	}

	public function test_series_banner_gets_the_settings_default_then_the_hub_colour(): void {
		$this->event( 'One', '2027-07-01 10:00', array(), 'camp' );
		$hub = self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_post_meta( $hub, 'hyperpress_banner_background_color', '#00aa00' );
		update_option( Settings::OPTION, array( Settings::HUB_PAGE => $hub ) );

		$this->go_to( '/?' . Plugin::SERIES . '=camp' );
		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertSame( '#00aa00', $banner['backgroundColor'] );
		$this->assertArrayNotHasKey( 'image', $banner );

		update_option(
			Settings::OPTION,
			array(
				Settings::HUB_PAGE             => $hub,
				Settings::DEFAULT_BANNER_COLOR => '#111111',
			)
		);
		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertSame( '#111111', $banner['backgroundColor'] );

		// No colour anywhere: the key stays absent so the theme default applies.
		update_option( Settings::OPTION, array() );
		$banner = apply_filters( 'hyperpress_banner_content', array() );
		$this->assertArrayNotHasKey( 'backgroundColor', $banner );
	}
}
