<?php
namespace HyperPress\Tests\Integration\Event;

use HyperPress\Event\AdminAssets;
use HyperPress\Event\Fields;
use HyperPress\Event\Map;
use HyperPress\Event\Plugin;
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
		if ( $this->registered_post_type ) {
			unregister_post_type( Plugin::POST_TYPE );
			$this->registered_post_type = false;
		}
		parent::tear_down();
	}

	/** @param array<string,mixed> $meta */
	private function event( array $meta ): int {
		$id = self::factory()->post->create( array( 'post_type' => Plugin::POST_TYPE, 'post_status' => 'publish' ) );
		$GLOBALS['hyperpress_test_rwmb_posts'][ $id ] = $meta;
		// Map reads the stored osm text directly: Meta Box would return rendered HTML for it.
		if ( isset( $meta[ Fields::VENUE_MAP ] ) ) {
			update_post_meta( $id, Fields::VENUE_MAP, $meta[ Fields::VENUE_MAP ] );
		}
		return $id;
	}

	private function output( int $id ): string {
		return Map::html( $id );
	}

	public function test_renders_the_events_location_full_width(): void {
		$id = $this->event(
			array(
				Fields::VENUE_NAME    => 'Big Arena',
				Fields::VENUE_ADDRESS => '1 Main St',
				Fields::VENUE_MAP     => '42.1,-71.2,15',
			)
		);
		$html = $this->output( $id );

		$this->assertCount( 1, RWMB_OSM_Field::$calls );
		list( $location, $args ) = RWMB_OSM_Field::$calls[0];
		$this->assertSame( '42.1,-71.2,15', $location );
		$this->assertSame( '300px', $args['height'] );
		$this->assertSame( '100%', $args['width'] );
		$this->assertTrue( $args['marker'] );
		$this->assertFalse( $args['js_options']['dragging'] );
		$this->assertStringContainsString(
			'<div class="hyperpress-map alignfull" role="img" aria-label="Map of Big Arena" style="pointer-events:none;padding-top:0;margin-bottom:1.5rem;">',
			$html
		);
		$this->assertStringContainsString( 'role="img"', $html );
		$this->assertStringContainsString( 'Big Arena', $html );
		$this->assertStringContainsString( 'pointer-events:none;', $html );
	}

	public function test_label_falls_back_without_a_venue_name(): void {
		$id = $this->event(
			array(
				Fields::VENUE_ADDRESS => '1 Main St',
				Fields::VENUE_MAP     => '42.1,-71.2,15',
			)
		);
		$this->assertStringContainsString( 'aria-label="Map of the venue"', $this->output( $id ) );
	}

	/** @return array<string,array{0:string,1:string}> */
	public static function unusable_locations(): array {
		return array(
			'empty address'      => array( '', '42.1,-71.2,15' ),
			'whitespace address' => array( '   ', '42.1,-71.2,15' ),
			'empty map'          => array( '1 Main St', '' ),
			'garbage map'        => array( '1 Main St', 'garbage' ),
			'latitude high'      => array( '1 Main St', '91,0' ),
			'longitude high'     => array( '1 Main St', '0,181' ),
		);
	}

	/** @dataProvider unusable_locations */
	public function test_nothing_without_an_address_or_a_valid_location( string $address, string $map ): void {
		$id = $this->event(
			array(
				Fields::VENUE_ADDRESS => $address,
				Fields::VENUE_MAP     => $map,
			)
		);
		$this->assertSame( '', $this->output( $id ) );
		$this->assertSame( array(), RWMB_OSM_Field::$calls );
	}

	public function test_nothing_for_a_post_without_a_map(): void {
		// An ordinary post holds the raw meta but has no Meta Box fields, so rwmb_meta() returns nothing for it.
		$post = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		update_post_meta( $post, Fields::VENUE_ADDRESS, '1 Main St' );
		update_post_meta( $post, Fields::VENUE_MAP, '42.1,-71.2,15' );
		$this->assertSame( '', $this->output( $post ) );

		$this->assertSame( '', $this->output( $this->event( array() ) ) );

		$this->assertSame( array(), RWMB_OSM_Field::$calls );
	}

	public function test_parse_clamps_zoom_and_defaults_it(): void {
		$this->assertSame( 15, Map::parse( '1,2' )['zoom'] );
		$this->assertSame( 19, Map::parse( '1,2,99' )['zoom'] );
		$this->assertSame( 0, Map::parse( '1,2,-4' )['zoom'] );
		$this->assertNull( Map::parse( 'garbage' ) );
		$this->assertNull( Map::parse( array() ) );
	}

	public function test_boot_registers_admin_assets_and_no_banner_hook(): void {
		Plugin::boot();

		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', array( AdminAssets::class, 'enqueue' ) ) );
		$this->assertFalse( has_action( 'hyperpress_after_banner', array( Map::class, 'render' ) ) );
	}
}
