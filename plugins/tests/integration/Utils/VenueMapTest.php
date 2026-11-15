<?php
namespace HyperPress\Tests\Integration\Utils;

use HyperPress\Utils\VenueMap;
use RWMB_OSM_Field;
use WP_UnitTestCase;

final class VenueMapTest extends WP_UnitTestCase {

	private const RULE = '.ui-autocomplete.ui-front{z-index:999999}';

	public function set_up(): void {
		parent::set_up();
		RWMB_OSM_Field::$calls = array();
		// Meta Box is stubbed in the integration suite, so register the handle its osm field enqueues.
		wp_register_style( 'rwmb-osm', 'https://example.test/osm.css', array(), '1' );
	}

	public function tear_down(): void {
		unset( $GLOBALS['hyperpress_test_rwmb_posts'], $GLOBALS['current_screen'] );
		wp_deregister_style( 'rwmb-osm' );
		parent::tear_down();
	}

	public function test_render_makes_one_non_interactive_map_call(): void {
		$html = VenueMap::render(
			array(
				'lat'  => 42.1,
				'lng'  => -71.2,
				'zoom' => 15,
			),
			'L',
			'250px'
		);

		$this->assertCount( 1, RWMB_OSM_Field::$calls );
		list( $location, $args ) = RWMB_OSM_Field::$calls[0];
		$this->assertSame( '42.1,-71.2,15', $location );
		$this->assertSame( '100%', $args['width'] );
		$this->assertSame( '250px', $args['height'] );
		$this->assertTrue( $args['marker'] );
		$this->assertSame(
			array(
				'dragging'        => false,
				'zoomControl'     => false,
				'scrollWheelZoom' => false,
				'doubleClickZoom' => false,
				'touchZoom'       => false,
				'boxZoom'         => false,
				'keyboard'        => false,
			),
			$args['js_options']
		);
		$this->assertSame(
			'<div class="hyperpress-map" role="img" aria-label="L" style="pointer-events:none;"><div class="rwmb-map-canvas-stub"></div></div>',
			$html
		);
	}

	public function test_render_adds_a_class_and_style(): void {
		$html = VenueMap::render(
			array(
				'lat'  => 1.0,
				'lng'  => 2.0,
				'zoom' => 15,
			),
			'L',
			'300px',
			'alignfull',
			'padding-top:0;margin-bottom:1.5rem;'
		);

		$this->assertStringStartsWith(
			'<div class="hyperpress-map alignfull" role="img" aria-label="L" style="pointer-events:none;padding-top:0;margin-bottom:1.5rem;">',
			$html
		);
	}

	public function test_render_escapes_the_label_class_and_style(): void {
		$html = VenueMap::render(
			array(
				'lat'  => 1.0,
				'lng'  => 2.0,
				'zoom' => 15,
			),
			'Map of "A" <b>',
			'300px',
			'x"y',
			'a"b'
		);

		$this->assertStringContainsString( 'aria-label="Map of &quot;A&quot; &lt;b&gt;"', $html );
		$this->assertStringContainsString( 'class="hyperpress-map x&quot;y"', $html );
		$this->assertStringContainsString( 'style="pointer-events:none;a&quot;b"', $html );
		$this->assertStringNotContainsString( '<b>', $html );
	}

	/** @return array<string,array{0:float,1:float,2:string}> */
	public static function coordinates(): array {
		return array(
			'rounds to seven places' => array( 1.00000001, 2.0, '1,2,15' ),
			'no exponent'            => array( 1.0E-5, 2.0, '0.00001,2,15' ),
			'negative zero'          => array( -0.0, 2.0, '0,2,15' ),
		);
	}

	/** @dataProvider coordinates */
	public function test_render_formats_coordinates_as_plain_decimals( float $lat, float $lng, string $expected ): void {
		VenueMap::render(
			array(
				'lat'  => $lat,
				'lng'  => $lng,
				'zoom' => 15,
			),
			'L'
		);

		$this->assertSame( $expected, RWMB_OSM_Field::$calls[0][0] );
	}

	/**
	 * @param array<string,mixed> $meta Stubbed Meta Box fields (name and address).
	 */
	private function post( array $meta, string $pin = '' ): int {
		$id = self::factory()->post->create( array( 'post_status' => 'publish' ) );
		$GLOBALS['hyperpress_test_rwmb_posts'][ $id ] = $meta;
		if ( '' !== $pin ) {
			update_post_meta( $id, 'v_map', $pin );
		}
		return $id;
	}

	public function test_for_post_renders_with_the_venue_name(): void {
		$id = $this->post(
			array(
				'v_name'    => 'Big Arena',
				'v_address' => '1 Main St',
			),
			'42.1,-71.2,15'
		);

		$html = VenueMap::for_post( $id, 'v_name', 'v_address', 'v_map' );

		$this->assertCount( 1, RWMB_OSM_Field::$calls );
		$this->assertSame( '42.1,-71.2,15', RWMB_OSM_Field::$calls[0][0] );
		$this->assertSame( '300px', RWMB_OSM_Field::$calls[0][1]['height'] );
		$this->assertStringContainsString( 'aria-label="Map of Big Arena"', $html );
	}

	public function test_for_post_falls_back_to_a_generic_label(): void {
		$id = $this->post( array( 'v_address' => '1 Main St' ), '42.1,-71.2' );

		$this->assertStringContainsString( 'aria-label="Map of the venue"', VenueMap::for_post( $id, 'v_name', 'v_address', 'v_map' ) );
	}

	public function test_for_post_passes_the_class_and_style_through(): void {
		$id = $this->post( array( 'v_address' => '1 Main St' ), '42.1,-71.2' );

		$html = VenueMap::for_post( $id, 'v_name', 'v_address', 'v_map', 'alignfull', 'padding-top:0;' );

		$this->assertStringStartsWith(
			'<div class="hyperpress-map alignfull" role="img" aria-label="Map of the venue" style="pointer-events:none;padding-top:0;">',
			$html
		);
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
	public function test_for_post_is_empty_without_an_address_or_a_valid_location( string $address, string $pin ): void {
		$id = $this->post( array( 'v_address' => $address ), $pin );

		$this->assertSame( '', VenueMap::for_post( $id, 'v_name', 'v_address', 'v_map' ) );
		$this->assertSame( array(), RWMB_OSM_Field::$calls );
	}

	public function test_for_post_is_empty_for_post_id_zero(): void {
		$this->assertSame( '', VenueMap::for_post( 0, 'v_name', 'v_address', 'v_map' ) );
		$this->assertSame( array(), RWMB_OSM_Field::$calls );
	}

	private function screen( string $base, string $post_type ): void {
		set_current_screen( $base );
		$screen            = get_current_screen();
		$screen->post_type = $post_type;
	}

	/** @return string[] */
	private function inline(): array {
		$data = wp_styles()->get_data( 'rwmb-osm', 'after' );
		return is_array( $data ) ? $data : array();
	}

	public function test_register_admin_fix_hooks_after_meta_box(): void {
		$count = static function (): int {
			$hook = $GLOBALS['wp_filter']['admin_enqueue_scripts'] ?? null;
			return $hook instanceof \WP_Hook ? count( $hook->callbacks[20] ?? array() ) : 0;
		};
		$before = $count();

		VenueMap::register_admin_fix( 'x' );

		// Meta Box enqueues its field styles at priority 10; the fix must come later.
		$this->assertSame( $before + 1, $count() );
	}

	/** @return array<string,array{0:string}> */
	public static function edit_hooks(): array {
		return array(
			'edit'   => array( 'post.php' ),
			'create' => array( 'post-new.php' ),
		);
	}

	/** @dataProvider edit_hooks */
	public function test_the_edit_screens_raise_the_autocomplete_menu( string $hook ): void {
		$this->screen( 'post', 'hyper_x' );

		VenueMap::enqueue_admin_fix( $hook, 'hyper_x' );

		$this->assertContains( self::RULE, $this->inline() );
	}

	public function test_other_post_types_are_left_alone(): void {
		$this->screen( 'post', 'post' );

		VenueMap::enqueue_admin_fix( 'post.php', 'hyper_x' );

		$this->assertSame( array(), $this->inline() );
	}

	public function test_other_screens_are_left_alone(): void {
		$this->screen( 'edit', 'hyper_x' );

		VenueMap::enqueue_admin_fix( 'edit.php', 'hyper_x' );

		$this->assertSame( array(), $this->inline() );
	}

	public function test_nothing_happens_without_a_screen(): void {
		unset( $GLOBALS['current_screen'] );

		VenueMap::enqueue_admin_fix( 'post.php', 'hyper_x' );

		$this->assertSame( array(), $this->inline() );
	}
}
