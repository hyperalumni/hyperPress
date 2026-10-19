<?php
namespace HyperPress\Tests\Integration\Competition;

use HyperPress\Competition\Map;
use HyperPress\Competition\Plugin;
use RWMB_OSM_Field;
use WP_UnitTestCase;

final class MapTest extends WP_UnitTestCase {

	/**
	 * Whether set_up() registered the post type itself (and so must remove it).
	 *
	 * @var bool
	 */
	private bool $registered_post_type = false;

	public function set_up(): void {
		parent::set_up();
		RWMB_OSM_Field::$calls = array();
		if ( ! post_type_exists( Plugin::POST_TYPE ) ) {
			register_post_type( Plugin::POST_TYPE, array( 'public' => true, 'has_archive' => true ) );
			$this->registered_post_type = true;
		}
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb_posts'] );
		remove_action( 'hyperpress_after_banner', array( Map::class, 'render' ) );
		if ( $this->registered_post_type ) {
			unregister_post_type( Plugin::POST_TYPE );
			$this->registered_post_type = false;
		}
		parent::tear_down();
	}

	/** @param array<string,mixed> $meta */
	private function competition( array $meta ): int {
		$id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_status' => 'publish' ) );
		$GLOBALS['hyperpress_test_rwmb_posts'][ $id ] = $meta;
		// Map reads the stored osm text directly: Meta Box would return rendered HTML for it.
		if ( isset( $meta['hyperpress_competition_venue_map'] ) ) {
			update_post_meta( $id, 'hyperpress_competition_venue_map', $meta['hyperpress_competition_venue_map'] );
		}
		return $id;
	}

	private function output(): string {
		ob_start();
		Map::render();
		return (string) ob_get_clean();
	}

	public function test_renders_the_competitions_location_full_width(): void {
		$id = $this->competition(
			array(
				'hyperpress_competition_venue_name'    => 'Big Arena',
				'hyperpress_competition_venue_address' => '1 Main St',
				'hyperpress_competition_venue_map'     => '42.1,-71.2,15',
			)
		);
		$this->go_to( get_permalink( $id ) );

		$html = $this->output();

		$this->assertCount( 1, RWMB_OSM_Field::$calls );
		list( $location, $args ) = RWMB_OSM_Field::$calls[0];
		$this->assertSame( '42.1,-71.2,15', $location );
		$this->assertSame( '300px', $args['height'] );
		$this->assertSame( '100%', $args['width'] );
		$this->assertTrue( $args['marker'] );
		$this->assertFalse( $args['js_options']['dragging'] );
		$this->assertStringContainsString( 'class="hyperpress-map"', $html );
		$this->assertStringContainsString( 'role="img"', $html );
		$this->assertStringContainsString( 'Big Arena', $html );
		$this->assertStringContainsString( 'pointer-events:none;', $html );
	}

	/** @return array<string,array{0:string,1:string}> */
	public static function unusable_locations(): array {
		return array(
			'empty address'  => array( '', '42.1,-71.2,15' ),
			'empty map'      => array( '1 Main St', '' ),
			'garbage map'    => array( '1 Main St', 'garbage' ),
			'latitude high'  => array( '1 Main St', '91,0' ),
			'longitude high' => array( '1 Main St', '0,181' ),
		);
	}

	/** @dataProvider unusable_locations */
	public function test_nothing_without_an_address_or_a_valid_location( string $address, string $map ): void {
		$id = $this->competition(
			array(
				'hyperpress_competition_venue_address' => $address,
				'hyperpress_competition_venue_map'     => $map,
			)
		);
		$this->go_to( get_permalink( $id ) );

		$this->assertSame( '', $this->output() );
		$this->assertSame( array(), RWMB_OSM_Field::$calls );
	}

	public function test_nothing_on_lists_and_other_posts(): void {
		$meta = array(
			'hyperpress_competition_venue_address' => '1 Main St',
			'hyperpress_competition_venue_map'     => '42.1,-71.2,15',
		);
		$id   = $this->competition( $meta );

		$this->go_to( home_url( '/' ) );
		$this->assertSame( '', $this->output() );

		$post = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$GLOBALS['hyperpress_test_rwmb_posts'][ $post ] = $meta;
		update_post_meta( $post, 'hyperpress_competition_venue_map', $meta['hyperpress_competition_venue_map'] );
		$this->go_to( get_permalink( $post ) );
		$this->assertSame( '', $this->output() );

		// A different competition is the queried object: its own (empty) meta decides, not this one's.
		$other = $this->competition( array() );
		$this->go_to( get_permalink( $id ) );
		$GLOBALS['wp_query']->queried_object    = get_post( $other );
		$GLOBALS['wp_query']->queried_object_id = $other;
		$this->assertSame( '', $this->output() );

		$this->assertSame( array(), RWMB_OSM_Field::$calls );
	}

	public function test_parse_clamps_zoom_and_defaults_it(): void {
		$this->assertSame( 15, Map::parse( '1,2' )['zoom'] );
		$this->assertSame( 19, Map::parse( '1,2,99' )['zoom'] );
		$this->assertSame( 0, Map::parse( '1,2,-4' )['zoom'] );
		$this->assertNull( Map::parse( 'garbage' ) );
		$this->assertNull( Map::parse( array() ) );
	}

	public function test_hook_is_registered(): void {
		Map::register();

		$this->assertNotFalse( has_action( 'hyperpress_after_banner', array( Map::class, 'render' ) ) );
	}
}
