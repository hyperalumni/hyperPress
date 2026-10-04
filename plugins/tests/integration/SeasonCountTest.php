<?php
namespace HyperPress\Tests\Integration;

use WP_UnitTestCase;

final class SeasonCountTest extends WP_UnitTestCase {

	public function set_up(): void {
		parent::set_up();
		add_shortcode(
			'hyper_date_distance',
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
		remove_shortcode( 'hyper_date_distance' );
		parent::tear_down();
	}

	public function test_tag_is_registered(): void {
		$this->assertTrue( shortcode_exists( 'hyper_season_count' ) );
	}

	public function test_default_output_matches_the_theme_shortcode(): void {
		$this->assertSame( '[1995-09-01||true] Season', do_shortcode( '[hyper_season_count]' ) );
	}

	public function test_suffix_true_and_season_false(): void {
		$this->assertSame( '[1995-09-01|1|true] ', do_shortcode( '[hyper_season_count suffix="true" season="false"]' ) );
	}
}
