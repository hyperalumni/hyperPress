<?php
namespace HyperPress\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/bin/nextgen-set-template.php';

final class NextGenSetTemplateScriptTest extends TestCase {

	private const OLD_ALBUM   = '/home/.sites/nonidesign/hyperonline.org/wp-content/plugins/nextgen-gallery/products/photocrati_nextgen/modules/ngglegacy/view/album-compact.php';
	private const NEW_ALBUM   = '/var/www/wp-content/plugins/nextgen-gallery/src/Legacy/view/album-compact.php';
	private const OTHER_ALBUM = '/home/.sites/hyperonline.org/site/wp-content/plugins/nextgen-gallery/products/photocrati_nextgen/modules/ngglegacy/view/album-compact.php';
	private const OLD_GALLERY = '/home/.sites/hyperonline.org/site/wp-content/plugins/nextgen-gallery/products/photocrati_nextgen/modules/ngglegacy/view/gallery.php';

	private string $theme_dir;

	protected function setUp(): void {
		$this->theme_dir = sys_get_temp_dir() . '/hp-ngg-' . bin2hex( random_bytes( 6 ) ) . '/nggallery';
		mkdir( $this->theme_dir, 0777, true );
		touch( $this->theme_dir . '/album-compact.php' );
		touch( $this->theme_dir . '/gallery.php' );
	}

	protected function tearDown(): void {
		foreach ( glob( $this->theme_dir . '/*' ) as $file ) {
			unlink( $file );
		}
		rmdir( $this->theme_dir );
		rmdir( dirname( $this->theme_dir ) );
	}

	private function encode( string $json ): string {
		return base64_encode( $json );
	}

	private function sample( string $template, string $gallery_display_template = '' ): string {
		// Decoding as objects mirrors what the script does, so `{}` and `[]` stay what they were.
		return $this->encode(
			json_encode(
				json_decode(
					'{"display_settings":{"gallery_display_type":"photocrati-nextgen_basic_thumbnails","template":' . json_encode( $template )
					. ',"override_thumbnail_settings":"0","thumbnail_width":"250","empty_object":{},"nested":{"a":[1,2,{"b":null}],"f":1.5},'
					. '"gallery_display_template":' . json_encode( $gallery_display_template ) . '},'
					. '"order_by":"sortorder","order_direction":"ASC","exclusions":[],"name":"Caf\u00e9"}'
				)
			)
		);
	}

	private function decode( string $encoded ): object {
		return json_decode( base64_decode( $encoded, true ) );
	}

	private function theme_path( string $name ): string {
		return $this->theme_dir . '/' . $name;
	}

	public function test_maps_old_nextgen_path(): void {
		$result = hyperpress_ngg_remap_encoded( $this->sample( self::OLD_ALBUM ), array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertSame(
			array(
				array(
					'key' => 'template',
					'old' => self::OLD_ALBUM,
					'new' => $this->theme_path( 'album-compact.php' ),
				),
			),
			$result['changes']
		);
		$this->assertSame( $this->theme_path( 'album-compact.php' ), $this->decode( $result['encoded'] )->display_settings->template );
	}

	public function test_maps_new_src_legacy_path(): void {
		$result = hyperpress_ngg_remap_encoded( $this->sample( self::NEW_ALBUM ), array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertSame( self::NEW_ALBUM, $result['changes'][0]['old'] );
	}

	public function test_maps_path_from_another_server(): void {
		$result = hyperpress_ngg_remap_encoded( $this->sample( self::OTHER_ALBUM ), array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertSame( $this->theme_path( 'album-compact.php' ), $result['changes'][0]['new'] );
	}

	public function test_maps_gallery_display_template(): void {
		$result = hyperpress_ngg_remap_encoded( $this->sample( '', self::OLD_ALBUM ), array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertCount( 1, $result['changes'] );
		$this->assertSame( 'gallery_display_template', $result['changes'][0]['key'] );
		$decoded = $this->decode( $result['encoded'] );
		$this->assertSame( $this->theme_path( 'album-compact.php' ), $decoded->display_settings->gallery_display_template );
		$this->assertSame( '', $decoded->display_settings->template );
	}

	public function test_maps_both_keys(): void {
		$result = hyperpress_ngg_remap_encoded( $this->sample( self::OLD_ALBUM, self::OTHER_ALBUM ), array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertSame( array( 'template', 'gallery_display_template' ), array_column( $result['changes'], 'key' ) );
	}

	public function test_leaves_unrequested_templates_untouched(): void {
		$this->assertNull( hyperpress_ngg_remap_encoded( $this->sample( self::OLD_GALLERY ), array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_maps_a_template_when_it_is_requested(): void {
		$result = hyperpress_ngg_remap_encoded( $this->sample( self::OLD_GALLERY ), array( 'gallery.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertSame( $this->theme_path( 'gallery.php' ), $this->decode( $result['encoded'] )->display_settings->template );
	}

	public function test_already_theme_path_is_unchanged(): void {
		$this->assertNull( hyperpress_ngg_remap_encoded( $this->sample( $this->theme_path( 'album-compact.php' ) ), array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_trailing_slash_on_theme_dir_is_ignored(): void {
		$result = hyperpress_ngg_remap_encoded( $this->sample( self::OLD_ALBUM ), array( 'album-compact.php' ), $this->theme_dir . '/' );

		$this->assertNotNull( $result );
		$this->assertSame( $this->theme_path( 'album-compact.php' ), $result['changes'][0]['new'] );
	}

	public function test_empty_template_is_unchanged(): void {
		$this->assertNull( hyperpress_ngg_remap_encoded( $this->sample( '' ), array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_file_missing_from_theme_is_not_mapped(): void {
		unlink( $this->theme_path( 'album-compact.php' ) );

		$this->assertNull( hyperpress_ngg_remap_encoded( $this->sample( self::OLD_ALBUM ), array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_windows_separators_are_handled(): void {
		$old    = 'C:\\inetpub\\wp-content\\plugins\\nextgen-gallery\\src\\Legacy\\view\\album-compact.php';
		$result = hyperpress_ngg_remap_encoded( $this->sample( $old ), array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertSame( $old, $result['changes'][0]['old'] );
		$this->assertSame( $this->theme_path( 'album-compact.php' ), $this->decode( $result['encoded'] )->display_settings->template );
	}

	public function test_invalid_base64_returns_null(): void {
		$this->assertNull( hyperpress_ngg_remap_encoded( '%%% not base64 %%%', array( 'album-compact.php' ), $this->theme_dir ) );
		$this->assertNull( hyperpress_ngg_remap_encoded( '', array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_invalid_json_returns_null(): void {
		$this->assertNull( hyperpress_ngg_remap_encoded( $this->encode( '{"display_settings":' ), array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_missing_or_non_object_display_settings_returns_null(): void {
		$this->assertNull( hyperpress_ngg_remap_encoded( $this->encode( '{"order_by":"x"}' ), array( 'album-compact.php' ), $this->theme_dir ) );
		$this->assertNull( hyperpress_ngg_remap_encoded( $this->encode( '{"display_settings":"x"}' ), array( 'album-compact.php' ), $this->theme_dir ) );
		$this->assertNull( hyperpress_ngg_remap_encoded( $this->encode( '[1,2]' ), array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_non_string_template_is_ignored(): void {
		$this->assertNull( hyperpress_ngg_remap_encoded( $this->encode( '{"display_settings":{"template":["album-compact.php"]}}' ), array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_empty_object_and_array_survive_round_trip(): void {
		$result = hyperpress_ngg_remap_encoded( $this->sample( self::OLD_ALBUM ), array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$json = base64_decode( $result['encoded'], true );
		$this->assertStringContainsString( '"exclusions":[]', $json );
		$this->assertStringContainsString( '"empty_object":{}', $json );
		$this->assertStringContainsString( '"name":"Caf\u00e9"', $json );
	}

	public function test_other_settings_are_preserved(): void {
		$before = $this->sample( self::OLD_ALBUM, self::OTHER_ALBUM );
		$result = hyperpress_ngg_remap_encoded( $before, array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$expected                                              = $this->decode( $before );
		$expected->display_settings->template                  = $this->theme_path( 'album-compact.php' );
		$expected->display_settings->gallery_display_template  = $this->theme_path( 'album-compact.php' );
		$this->assertEquals( $expected, $this->decode( $result['encoded'] ) );
	}

	public function test_output_is_valid_base64_json(): void {
		$result = hyperpress_ngg_remap_encoded( $this->sample( self::OLD_ALBUM ), array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$json = base64_decode( $result['encoded'], true );
		$this->assertNotFalse( $json );
		json_decode( $json );
		$this->assertSame( JSON_ERROR_NONE, json_last_error() );
	}

	public function test_slashes_are_escaped_like_nextgen_does(): void {
		$result = hyperpress_ngg_remap_encoded( $this->sample( self::OLD_ALBUM ), array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertStringContainsString( '"template":"' . str_replace( '/', '\/', $this->theme_path( 'album-compact.php' ) ) . '"', base64_decode( $result['encoded'], true ) );
	}

	// display_type_settings (ngg_album / ngg_gallery tables).

	private const STAGING = '/home/hyperonline/webapps/hyperonline-wp-prod-bozy6yiT-staging/wp-content/plugins/nextgen-gallery/src/Legacy/view/album-compact.php';

	/**
	 * Encodes a decoded production sample the way NextGEN stores it.
	 *
	 * @param string $json Decoded JSON text.
	 */
	private function types( string $json ): string {
		return base64_encode( json_encode( json_decode( $json ) ) );
	}

	private function thumbs_only(): string {
		return $this->types( '{"photocrati-nextgen_basic_thumbnails":{"template":' . json_encode( self::STAGING ) . '}}' );
	}

	private function two_types( string $template = self::STAGING ): string {
		return $this->types(
			'{"photocrati-nextgen_basic_thumbnails":{"template":' . json_encode( $template ) . '},'
			. '"photocrati-nextgen_basic_compact_album":{"open_gallery_in_lightbox":0,"gallery_display_type":"photocrati-nextgen_basic_thumbnails","empty":{},"list":[1,2]}}'
		);
	}

	public function test_type_settings_maps_template_from_staging_path(): void {
		$result = hyperpress_ngg_remap_type_settings( $this->thumbs_only(), array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertFalse( $result['legacy'] );
		$this->assertSame(
			array(
				array(
					'type' => 'photocrati-nextgen_basic_thumbnails',
					'key'  => 'template',
					'old'  => self::STAGING,
					'new'  => $this->theme_path( 'album-compact.php' ),
				),
			),
			$result['changes']
		);
		$this->assertSame( $this->theme_path( 'album-compact.php' ), $this->decode( $result['encoded'] )->{'photocrati-nextgen_basic_thumbnails'}->template );
	}

	public function test_type_settings_maps_template_under_any_display_type_key(): void {
		$encoded = $this->types( '{"some-other-display_type":{"template":' . json_encode( self::STAGING ) . '}}' );
		$result  = hyperpress_ngg_remap_type_settings( $encoded, array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertSame( 'some-other-display_type', $result['changes'][0]['type'] );
	}

	public function test_type_settings_maps_gallery_display_template(): void {
		$encoded = $this->types( '{"photocrati-nextgen_basic_compact_album":{"gallery_display_template":' . json_encode( self::STAGING ) . ',"template":""}}' );
		$result  = hyperpress_ngg_remap_type_settings( $encoded, array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertCount( 1, $result['changes'] );
		$this->assertSame( 'gallery_display_template', $result['changes'][0]['key'] );
		$decoded = $this->decode( $result['encoded'] )->{'photocrati-nextgen_basic_compact_album'};
		$this->assertSame( $this->theme_path( 'album-compact.php' ), $decoded->gallery_display_template );
		$this->assertSame( '', $decoded->template );
	}

	public function test_type_settings_only_matching_types_change_and_others_are_preserved(): void {
		$before = $this->two_types();
		$result = hyperpress_ngg_remap_type_settings( $before, array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertCount( 1, $result['changes'] );
		$expected = $this->decode( $before );
		$expected->{'photocrati-nextgen_basic_thumbnails'}->template = $this->theme_path( 'album-compact.php' );
		$this->assertEquals( $expected, $this->decode( $result['encoded'] ) );
		$json = base64_decode( $result['encoded'], true );
		$this->assertStringContainsString( '"open_gallery_in_lightbox":0,', $json );
		$this->assertStringContainsString( '"empty":{}', $json );
		$this->assertStringContainsString( '"list":[1,2]', $json );
	}

	public function test_type_settings_unrelated_template_is_untouched(): void {
		$encoded = $this->types( '{"photocrati-nextgen_basic_thumbnails":{"template":' . json_encode( self::OLD_GALLERY ) . '}}' );

		$this->assertNull( hyperpress_ngg_remap_type_settings( $encoded, array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_type_settings_already_theme_path_is_unchanged(): void {
		$encoded = $this->two_types( $this->theme_path( 'album-compact.php' ) );

		$this->assertNull( hyperpress_ngg_remap_type_settings( $encoded, array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_type_settings_file_missing_from_theme_is_not_mapped(): void {
		unlink( $this->theme_path( 'album-compact.php' ) );

		$this->assertNull( hyperpress_ngg_remap_type_settings( $this->thumbs_only(), array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_type_settings_null_and_empty_return_null(): void {
		$this->assertNull( hyperpress_ngg_remap_type_settings( null, array( 'album-compact.php' ), $this->theme_dir ) );
		$this->assertNull( hyperpress_ngg_remap_type_settings( '', array( 'album-compact.php' ), $this->theme_dir ) );
		$this->assertNull( hyperpress_ngg_decode_type_settings( null ) );
		$this->assertNull( hyperpress_ngg_decode_type_settings( '' ) );
	}

	public function test_type_settings_undecodable_returns_null(): void {
		foreach ( array( '%%% not base64 %%%', base64_encode( '{"a":' ), base64_encode( '"text"' ), base64_encode( '12' ), 'a:1:{' ) as $value ) {
			$this->assertNull( hyperpress_ngg_remap_type_settings( $value, array( 'album-compact.php' ), $this->theme_dir ), $value );
			$this->assertNull( hyperpress_ngg_decode_type_settings( $value ), $value );
		}
	}

	public function test_type_settings_empty_json_array_is_decodable_without_changes(): void {
		$this->assertNotNull( hyperpress_ngg_decode_type_settings( base64_encode( '[]' ) ) );
		$this->assertNull( hyperpress_ngg_remap_type_settings( base64_encode( '[]' ), array( 'album-compact.php' ), $this->theme_dir ) );
	}

	public function test_type_settings_non_object_type_values_are_ignored(): void {
		$encoded = $this->types( '{"x":"album-compact.php","y":[1],"z":{"template":' . json_encode( self::STAGING ) . '}}' );
		$result  = hyperpress_ngg_remap_type_settings( $encoded, array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertSame( array( 'z' ), array_column( $result['changes'], 'type' ) );
	}

	public function test_type_settings_legacy_serialized_is_converted_to_base64_json(): void {
		$legacy = serialize(
			array(
				'photocrati-nextgen_basic_thumbnails'   => array(
					'template' => self::STAGING,
					'list'     => array( 1, 2 ),
				),
				'photocrati-nextgen_basic_compact_album' => array(
					'open_gallery_in_lightbox' => 0,
					'empty'                    => array(),
				),
			)
		);
		$result = hyperpress_ngg_remap_type_settings( $legacy, array( 'album-compact.php' ), $this->theme_dir );

		$this->assertNotNull( $result );
		$this->assertTrue( $result['legacy'] );
		$expected = json_decode(
			json_encode(
				array(
					'photocrati-nextgen_basic_thumbnails'   => array(
						'template' => $this->theme_path( 'album-compact.php' ),
						'list'     => array( 1, 2 ),
					),
					'photocrati-nextgen_basic_compact_album' => array(
						'open_gallery_in_lightbox' => 0,
						'empty'                    => array(),
					),
				)
			)
		);
		$this->assertEquals( $expected, $this->decode( $result['encoded'] ) );
		$this->assertNotFalse( base64_decode( $result['encoded'], true ) );
	}

	public function test_type_settings_legacy_without_matching_template_returns_null(): void {
		$legacy = serialize( array( 'x' => array( 'template' => self::OLD_GALLERY ) ) );

		$this->assertNull( hyperpress_ngg_remap_type_settings( $legacy, array( 'album-compact.php' ), $this->theme_dir ) );
		$this->assertNotNull( hyperpress_ngg_decode_type_settings( $legacy ) );
	}

	public function test_type_settings_serialized_objects_are_refused(): void {
		$values = array(
			'O:8:"stdClass":0:{}',
			'a:1:{s:1:"x";O:8:"stdClass":0:{}}',
			'a:1:{s:1:"x";C:11:"ArrayObject":0:{}}',
			serialize( array( 'x' => array( 'template' => self::STAGING, 'o' => new \stdClass() ) ) ),
		);
		foreach ( $values as $value ) {
			$this->assertNull( hyperpress_ngg_decode_type_settings( $value ), $value );
			$this->assertNull( hyperpress_ngg_remap_type_settings( $value, array( 'album-compact.php' ), $this->theme_dir ), $value );
		}
	}

	// Argument parsing.

	public function test_args_default_to_dry_run_all_sources_and_album_compact(): void {
		$this->assertSame(
			array(
				'mode'    => 'dry-run',
				'names'   => array( 'album-compact.php' ),
				'targets' => array( 'posts', 'albums', 'galleries' ),
				'error'   => null,
			),
			hyperpress_ngg_parse_args( array() )
		);
	}

	public function test_args_parse_mode_templates_and_targets(): void {
		$parsed = hyperpress_ngg_parse_args( array( 'apply', 'gallery.php', 'albums', 'album-compact.php', 'gallery.php', 'galleries' ) );

		$this->assertNull( $parsed['error'] );
		$this->assertSame( 'apply', $parsed['mode'] );
		$this->assertSame( array( 'gallery.php', 'album-compact.php' ), $parsed['names'] );
		$this->assertSame( array( 'albums', 'galleries' ), $parsed['targets'] );
	}

	public function test_args_targets_only_keep_default_template(): void {
		$parsed = hyperpress_ngg_parse_args( array( 'dry-run', 'posts' ) );

		$this->assertNull( $parsed['error'] );
		$this->assertSame( array( 'album-compact.php' ), $parsed['names'] );
		$this->assertSame( array( 'posts' ), $parsed['targets'] );
	}

	public function test_args_errors(): void {
		$this->assertNotNull( hyperpress_ngg_parse_args( array( 'albums' ) )['error'] );
		$this->assertNotNull( hyperpress_ngg_parse_args( array( 'apply', 'bogus' ) )['error'] );
		$this->assertNotNull( hyperpress_ngg_parse_args( array( 'dry-run', 'apply' ) )['error'] );
		$this->assertStringContainsString( 'bogus', hyperpress_ngg_parse_args( array( 'apply', 'bogus' ) )['error'] );
	}
}
