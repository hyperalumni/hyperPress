<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SeasonCountTest extends WP_UnitTestCase {

	/** @var callable|null */
	private $previous = null;

	public function set_up(): void {
		parent::set_up();
		global $shortcode_tags;
		$this->previous = $shortcode_tags['hyperpress_date_distance'] ?? null;
		add_shortcode(
			'hyperpress_date_distance',
			static function ( $atts ): string {
				$atts = shortcode_atts(
					array(
						'date'      => '',
						'suffix'    => '',
						'inclusive' => '',
					),
					$atts
				);
				return '[' . $atts['date'] . '|' . $atts['suffix'] . '|' . $atts['inclusive'] . ']';
			}
		);
	}

	public function tear_down(): void {
		remove_shortcode( 'hyperpress_date_distance' );
		if ( null !== $this->previous ) {
			add_shortcode( 'hyperpress_date_distance', $this->previous );
		}
		parent::tear_down();
	}

	public function test_tag_is_registered(): void {
		$this->assertTrue( shortcode_exists( 'hyperpress_season_count' ) );
	}

	public function test_default_output_matches_the_theme_shortcode(): void {
		$this->assertSame( '[1995-09-01|false|true] Season', do_shortcode( '[hyperpress_season_count]' ) );
	}

	public function test_suffix_true_and_season_false(): void {
		$this->assertSame( '[1995-09-01|true|true] ', do_shortcode( '[hyperpress_season_count suffix="true" season="false"]' ) );
	}
}
