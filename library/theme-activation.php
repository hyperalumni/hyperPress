<?php

if ( ! function_exists( 'hyper_set_initial_theme_mod_values' ) ) :
	function hyper_set_initial_theme_mod_values() {
		$themeModNames = [
			'hyperpress_gear_blue'               => '#092238',
			'hyperpress_gear_orange'             => '#F15622',
			'hyperpress_gear_grey'               => '#222222',
			'hyperpress_hyper_red'               => '#C52026',
			'hyperpress_hyper_orange'            => '#F15623',
			'hyperpress_hyper_green'             => '#049655',
			'hyperpress_hyper_blue'              => '#0B67B2',
			'hyperpress_hyper_purple'            => '#9B3092',
			'hyperpress_countdown_days_color'    => '#F15623',
			'hyperpress_countdown_hours_color'   => '#F15623',
			'hyperpress_countdown_minutes_color' => '#049655',
			'hyperpress_countdown_seconds_color' => '#0B67B2',
			'hyperpress_home_banner_button_link' => '#',
			'hyperpress_home_banner_button_text' => 'Learn More',
			'hyperpress_home_banner_title'       => 'HYPER',
			'hyperpress_home_banner_subtitle'    => 'Dedicated to improving &amp; expanding the abilities of <span class="emphasis">Team HYPER</span>',
		];

		foreach ( $themeModNames as $themeModName => $themeModDefaultValue ) {
			if ( empty( get_theme_mod( $themeModName ) ) ) {
				set_theme_mod( $themeModName, $themeModDefaultValue );
			}
		}
	}

	add_action( 'after_switch_theme', 'hyper_set_initial_theme_mod_values' );
endif;


if ( ! function_exists( 'hyper_clean_up_theme_mod_values' ) ) :
	function hyper_clean_up_theme_mod_values() {
		$themeModNames = [
			'hyperpress_countdown_days_color',
			'hyperpress_countdown_hours_color',
			'hyperpress_countdown_minutes_color',
			'hyperpress_countdown_seconds_color'
		];

		foreach ( $themeModNames as $themeModName ) {
			remove_theme_mod( $themeModName );
		}
	}
// add_action( 'switch_theme', 'hyper_clean_up_theme_mod_values' );
endif;
