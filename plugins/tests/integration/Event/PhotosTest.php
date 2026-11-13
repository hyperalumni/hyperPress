<?php
namespace HyperPress\Tests\Integration\Event;

use DateTimeImmutable;
use HyperPress\Event\Fields;
use HyperPress\Event\Galleries;
use HyperPress\Event\NextGenShortcodes;
use HyperPress\Event\Photos;
use HyperPress\Event\PhotosBlock;
use HyperPress\Event\Plugin;
use WP_UnitTestCase;

final class PhotosTest extends WP_UnitTestCase {

	/**
	 * Shortcode strings the faked [ngg] received.
	 *
	 * @var array<int,string>
	 */
	private array $calls = array();

	private string $theme_dir = '';

	public function set_up(): void {
		parent::set_up();
		global $wpdb;
		$this->calls = array();
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ngg_gallery" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ngg_pictures" );
		$wpdb->query( "CREATE TABLE {$wpdb->prefix}ngg_gallery ( gid BIGINT NOT NULL, title VARCHAR(255) NOT NULL DEFAULT '', name VARCHAR(255) NOT NULL DEFAULT '' )" );
		$wpdb->query( "CREATE TABLE {$wpdb->prefix}ngg_pictures ( galleryid BIGINT NOT NULL, exclude TINYINT NOT NULL DEFAULT 0 )" );
		add_filter( 'hyperpress_event_nextgen_active', '__return_true' );
		add_filter( 'hyperpress_event_now', array( $this, 'now' ) );
		add_filter( 'pre_do_shortcode_tag', array( $this, 'fake_ngg' ), 10, 4 );
		// do_shortcode() only dispatches registered tags; NextGEN is not installed here.
		add_shortcode( 'ngg', '__return_empty_string' );
	}

	public function tear_down(): void {
		global $wpdb;
		remove_filter( 'hyperpress_event_nextgen_active', '__return_true' );
		remove_filter( 'hyperpress_event_now', array( $this, 'now' ) );
		remove_filter( 'pre_do_shortcode_tag', array( $this, 'fake_ngg' ), 10 );
		remove_shortcode( 'ngg' );
		remove_all_filters( 'pre_option_permalink_structure', 99 );
		remove_all_filters( 'hyperpress_event_nextgen_template' );
		remove_all_filters( 'template_directory' );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ngg_gallery" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}ngg_pictures" );
		if ( '' !== $this->theme_dir ) {
			array_map( 'unlink', glob( $this->theme_dir . '/nggallery/*' ) ?: array() );
			@rmdir( $this->theme_dir . '/nggallery' ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test cleanup.
			@rmdir( $this->theme_dir ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Test cleanup.
			$this->theme_dir = '';
		}
		unset( $GLOBALS['current_screen'] );
		parent::tear_down();
	}

	public function now(): DateTimeImmutable {
		return new DateTimeImmutable( '2030-01-01 10:00', wp_timezone() );
	}

	/**
	 * @param mixed                $return Short-circuit value.
	 * @param string               $tag    Shortcode tag.
	 * @param array|string         $attr   Attributes.
	 * @param array<int,string>    $m      Regex match.
	 * @return mixed
	 */
	public function fake_ngg( $return, $tag, $attr, $m ) {
		if ( 'ngg' !== $tag ) {
			return $return;
		}
		$this->calls[] = $m[0];

		return '<div class="fake-ngg">' . $m[0] . '</div>';
	}

	private function gallery( int $gid, string $title = '', string $name = '', int $images = 0, int $excluded = 0 ): void {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'ngg_gallery', array( 'gid' => $gid, 'title' => $title, 'name' => $name ) );
		for ( $i = 0; $i < $images; $i++ ) {
			$wpdb->insert( $wpdb->prefix . 'ngg_pictures', array( 'galleryid' => $gid, 'exclude' => 0 ) );
		}
		for ( $i = 0; $i < $excluded; $i++ ) {
			$wpdb->insert( $wpdb->prefix . 'ngg_pictures', array( 'galleryid' => $gid, 'exclude' => 1 ) );
		}
	}

	/**
	 * @param array<int> $gallery_ids Selected galleries.
	 */
	private function event( array $gallery_ids = array(), string $start = '2030-03-10 09:00', string $end = '2030-03-10 17:00' ): int {
		$id = self::factory()->post->create( array( 'post_type' => 'hyper_event', 'post_status' => 'publish', 'post_title' => 'Bar Crawl' ) );
		update_post_meta( $id, Fields::START, $start );
		update_post_meta( $id, Fields::END, $end );
		foreach ( $gallery_ids as $gid ) {
			add_post_meta( $id, Fields::GALLERIES, $gid );
		}

		return $id;
	}

	private function theme_with_template( string $file ): void {
		$this->theme_dir = sys_get_temp_dir() . '/hp-ngg-' . uniqid();
		mkdir( $this->theme_dir . '/nggallery', 0777, true );
		file_put_contents( $this->theme_dir . '/nggallery/' . $file, '<?php' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Test fixture.
		$dir = $this->theme_dir;
		add_filter( 'template_directory', static fn() => $dir );
	}

	public function test_options_and_existing(): void {
		$this->gallery( 2, 'Beta' );
		$this->gallery( 1, 'Alpha' );
		$this->gallery( 3, '', 'untitled-name' );

		$this->assertSame(
			array(
				1 => 'Alpha',
				2 => 'Beta',
				3 => 'untitled-name',
			),
			Galleries::options()
		);
		$this->assertSame( array( 2, 1 ), Galleries::existing( array( '2', 99, 1, 0, 'abc', 2 ) ) );
		$this->assertSame( array(), Galleries::existing( array() ) );
	}

	public function test_excluded_pictures_are_ignored_by_image_count(): void {
		$this->gallery( 1, 'Alpha', '', 4, 3 );

		$this->assertSame( 4, Galleries::image_count( 1 ) );
		$this->assertSame( 0, Galleries::image_count( 99 ) );
	}

	public function test_one_gallery_uses_the_single_display_with_a_limit_and_a_view_more_link(): void {
		$this->gallery( 1, 'Alpha', '', 7 );
		$id = $this->event( array( 1 ) );
		$this->go_to( get_permalink( $id ) );

		$html = Photos::shortcode( array() );

		$this->assertSame( array( '[ngg source="galleries" ids="1" display="basic_thumbnail" images_per_page="6"]' ), $this->calls );
		$this->assertStringContainsString( 'fake-ngg', $html );
		$this->assertStringContainsString( 'nggallery/page/2#photos', $html );
	}

	public function test_no_view_more_at_or_below_the_limit(): void {
		$this->gallery( 1, 'Alpha', '', 6 );
		$this->gallery( 2, 'Beta', '', 3 );
		$exact = $this->event( array( 1 ) );
		$few   = $this->event( array( 2 ) );

		$this->go_to( get_permalink( $exact ) );
		$this->assertStringNotContainsString( 'nggallery/page', Photos::shortcode( array() ) );
		$this->go_to( get_permalink( $few ) );
		$this->assertStringNotContainsString( 'nggallery/page', Photos::shortcode( array() ) );
	}

	public function test_excluded_pictures_do_not_trigger_view_more(): void {
		$this->gallery( 1, 'Alpha', '', 6, 5 );
		$id = $this->event( array( 1 ) );
		$this->go_to( get_permalink( $id ) );

		$this->assertStringNotContainsString( 'nggallery/page', Photos::shortcode( array() ) );
	}

	public function test_several_galleries_use_the_album_display(): void {
		$this->gallery( 1, 'Alpha', '', 20 );
		$this->gallery( 2, 'Beta', '', 20 );
		$id = $this->event( array( 1, 2 ) );
		$this->go_to( get_permalink( $id ) );

		$html = Photos::shortcode( array() );

		$this->assertSame( array( '[ngg source="albums" gallery_ids="1,2" display="basic_compact_album"]' ), $this->calls );
		$this->assertStringNotContainsString( 'nggallery/page', $html );
	}

	public function test_a_serialized_list_of_galleries_is_accepted(): void {
		$this->gallery( 1, 'Alpha', '', 2 );
		$this->gallery( 2, 'Beta', '', 2 );
		$id = $this->event();
		add_post_meta( $id, Fields::GALLERIES, array( 1, 2 ) );
		$this->go_to( get_permalink( $id ) );

		Photos::shortcode( array() );

		$this->assertSame( array( '[ngg source="albums" gallery_ids="1,2" display="basic_compact_album"]' ), $this->calls );
	}

	public function test_more_url_uses_the_nggallery_page_path_with_pretty_permalinks(): void {
		$this->assertSame( 'https://example.org/event/bar/nggallery/page/2', NextGenShortcodes::more_url( 'https://example.org/event/bar/' ) );
		$this->assertSame( 'https://example.org/event/bar/nggallery/page/3', NextGenShortcodes::more_url( 'https://example.org/event/bar', 3 ) );
	}

	public function test_more_url_uses_the_nggpage_query_argument_with_plain_permalinks(): void {
		// The integration bootstrap forces pretty permalinks with a pre_option filter; override it.
		add_filter( 'pre_option_permalink_structure', '__return_empty_string', 99 );

		$this->assertSame( 'https://example.org/?hyper_event=bar&nggpage=2', NextGenShortcodes::more_url( 'https://example.org/?hyper_event=bar' ) );
	}

	public function test_view_more_link_is_the_current_post_permalink_plus_page_2_and_the_anchor(): void {
		$this->gallery( 1, 'Alpha', '', 9 );
		$id = $this->event( array( 1 ) );
		$this->go_to( get_permalink( $id ) );

		$html = Photos::shortcode( array( 'anchor' => 'pics' ) );

		$this->assertStringContainsString( 'href="' . trailingslashit( get_permalink( $id ) ) . 'nggallery/page/2#pics"', $html );
	}

	public function test_block_html_is_pure_and_takes_permalink_and_anchor(): void {
		$this->gallery( 1, 'Alpha', '', 9 );

		$html = PhotosBlock::html( array( 1 ), false, '<b>BANNER</b>', 'https://example.org/event/x/', 'gallery' );

		$this->assertStringStartsWith( '<b>BANNER</b>', $html );
		$this->assertStringContainsString( 'href="https://example.org/event/x/nggallery/page/2#gallery"', $html );
	}

	public function test_template_argument_is_added_only_when_the_parent_theme_has_the_file(): void {
		$this->assertSame( '', NextGenShortcodes::template( 'gallery.php' ) );
		$this->assertStringNotContainsString( 'template=', NextGenShortcodes::single( 1 ) );
		$this->assertStringNotContainsString( 'template=', NextGenShortcodes::album( array( 1, 2 ) ) );

		$this->theme_with_template( 'gallery.php' );

		$this->assertSame( ' template="gallery.php"', NextGenShortcodes::template( 'gallery.php' ) );
		$this->assertSame( '[ngg source="galleries" ids="1" display="basic_thumbnail" images_per_page="6" template="gallery.php"]', NextGenShortcodes::single( 1 ) );
		$this->assertSame( '', NextGenShortcodes::template( 'album-compact.php' ) );
	}

	public function test_template_filter_can_rename_or_remove_the_file(): void {
		$this->theme_with_template( 'custom.php' );
		add_filter( 'hyperpress_event_nextgen_template', static fn( $file ) => 'gallery.php' === $file ? 'custom.php' : $file );

		$this->assertSame( ' template="custom.php"', NextGenShortcodes::template( 'gallery.php' ) );

		remove_all_filters( 'hyperpress_event_nextgen_template' );
		add_filter( 'hyperpress_event_nextgen_template', '__return_empty_string' );

		$this->assertSame( '', NextGenShortcodes::template( 'gallery.php' ) );
	}

	public function test_album_shortcode_uses_source_albums_and_gallery_ids(): void {
		$shortcode = NextGenShortcodes::album( array( '1', 2, 0, 'x' ) );

		$this->assertSame( '[ngg source="albums" gallery_ids="1,2" display="basic_compact_album"]', $shortcode );
		$this->assertStringNotContainsString( 'src=', $shortcode );
	}

	public function test_the_shortcode_is_registered(): void {
		$this->assertTrue( shortcode_exists( Photos::SHORTCODE ) );
		$this->assertSame( 'hyperpress_event_photos', Photos::SHORTCODE );
	}

	public function test_no_gallery_and_event_not_over_prints_check_back_after_the_event(): void {
		$id = $this->event();
		$this->go_to( get_permalink( $id ) );

		$html = Photos::shortcode( array() );

		$this->assertStringContainsString( 'Check back after the event', $html );
		$this->assertStringContainsString( 'Photos', $html );
		$this->assertSame( array(), $this->calls );
	}

	public function test_no_gallery_and_event_over_prints_nothing(): void {
		$id = $this->event( array(), '2029-03-10 09:00', '2029-03-10 17:00' );
		$this->go_to( get_permalink( $id ) );

		$this->assertSame( '', Photos::shortcode( array() ) );
	}

	public function test_selected_gallery_shows_whatever_the_date(): void {
		$this->gallery( 1, 'Alpha', '', 3 );
		$past   = $this->event( array( 1 ), '2029-03-10 09:00', '2029-03-10 17:00' );
		$future = $this->event( array( 1 ) );

		$this->go_to( get_permalink( $past ) );
		$this->assertStringContainsString( 'fake-ngg', Photos::shortcode( array() ) );
		$this->go_to( get_permalink( $future ) );
		$this->assertStringContainsString( 'fake-ngg', Photos::shortcode( array() ) );
	}

	public function test_deleted_gallery_ids_fall_back_to_the_no_gallery_state(): void {
		$upcoming = $this->event( array( 98, 99 ) );
		$over     = $this->event( array( 98, 99 ), '2029-03-10 09:00', '2029-03-10 17:00' );

		$this->go_to( get_permalink( $upcoming ) );
		$html = Photos::shortcode( array() );
		$this->assertStringContainsString( 'Check back after the event', $html );
		$this->assertSame( array(), $this->calls );

		$this->go_to( get_permalink( $over ) );
		$this->assertSame( '', Photos::shortcode( array() ) );
	}

	public function test_a_deleted_gallery_among_live_ones_is_skipped(): void {
		$this->gallery( 1, 'Alpha', '', 9 );
		$id = $this->event( array( 99, 1 ) );
		$this->go_to( get_permalink( $id ) );

		Photos::shortcode( array() );

		$this->assertSame( array( '[ngg source="galleries" ids="1" display="basic_thumbnail" images_per_page="6"]' ), $this->calls );
	}

	public function test_empty_when_nextgen_inactive(): void {
		$this->gallery( 1, 'Alpha', '', 3 );
		$id = $this->event( array( 1 ) );
		$this->go_to( get_permalink( $id ) );
		remove_filter( 'hyperpress_event_nextgen_active', '__return_true' );
		add_filter( 'hyperpress_event_nextgen_active', '__return_false' );

		$this->assertSame( '', Photos::shortcode( array() ) );

		remove_filter( 'hyperpress_event_nextgen_active', '__return_false' );
	}

	public function test_empty_outside_an_event_without_notices(): void {
		$this->gallery( 1, 'Alpha', '', 3 );
		$page = self::factory()->post->create( array( 'post_type' => 'page', 'post_status' => 'publish' ) );
		$this->go_to( get_permalink( $page ) );

		$this->assertSame( '', Photos::shortcode( array() ) );

		unset( $GLOBALS['post'] );
		$this->assertSame( '', Photos::shortcode( '' ) );
	}

	public function test_prints_its_own_banner_with_default_title_photos(): void {
		$this->gallery( 1, 'Alpha', '', 3 );
		$id = $this->event( array( 1 ) );
		$this->go_to( get_permalink( $id ) );

		$default = Photos::shortcode( array() );
		$custom  = Photos::shortcode( array( 'title' => 'Snaps', 'subtitle' => 'Look back' ) );

		$this->assertStringContainsString( 'Photos', $default );
		$this->assertStringContainsString( 'id="photos"', $default );
		$this->assertStringContainsString( 'Snaps', $custom );
		$this->assertStringContainsString( 'Look back', $custom );
		$this->assertStringNotContainsString( '>Photos<', $custom );
	}

	public function test_the_galleries_field_lists_the_nextgen_galleries(): void {
		$this->gallery( 1, 'Alpha' );

		set_current_screen( 'edit-' . Plugin::POST_TYPE );
		$boxes = Fields::boxes( array() );
		$ids   = array();
		foreach ( $boxes[0]['fields'] as $field ) {
			if ( Fields::GALLERIES === $field['id'] ) {
				$ids = $field['options'];
			}
		}

		$this->assertSame( array( 1 => 'Alpha' ), $ids );
	}
}
