<?php
namespace HyperPress\Tests\Support;

/**
 * Markup fed to do_shortcode(). Every attribute of every tag appears in at least one case.
 *
 * Time-dependent shortcodes use dates in 2000 and 2100 so the result cannot change as time passes.
 * The hyperpress_time_restrict_repeat cases for weekly, monthly and annually use the same text for the content
 * and the else attribute, so the branch taken (which depends on today's date) cannot change the output.
 * The ordinal suffix of hyperpress_date_distance needs ext-intl (installed in the test image).
 */
final class ShortcodeCases {

	public const CASES = array(
		'raw_basic'                     => '[hyperpress_raw]<b>x</b>[/hyperpress_raw]',
		'raw_with_leading_markup'       => "[hyperpress_raw]</p>\n<br />Line[/hyperpress_raw]",
		'raw_nested_shortcode'          => '[hyperpress_raw][hyperpress_emphasis]in raw[/hyperpress_emphasis][/hyperpress_raw]',
		'raw_empty'                     => '[hyperpress_raw][/hyperpress_raw]',
		'raw_leading_br'                => '[hyperpress_raw]<br />Line[/hyperpress_raw]',
		'raw_trailing_p'                => '[hyperpress_raw]Line<p>[/hyperpress_raw]',

		'emphasis'                      => '[hyperpress_emphasis]Hello[/hyperpress_emphasis]',
		'emphasis_nested'               => '[hyperpress_emphasis]Hello [hyperpress_raw]inner[/hyperpress_raw][/hyperpress_emphasis]',
		'emphasis_self_closing'         => '[hyperpress_emphasis]',

		'banner_title'                  => '[hyperpress_banner title="Welcome"]',
		'banner_subtitle'               => '[hyperpress_banner subtitle="Only a subtitle"]',
		'banner_title_subtitle'         => '[hyperpress_banner title="Welcome" subtitle="Hello you"]',
		'banner_anchor_title'           => '[hyperpress_banner anchor="top" title="Welcome"]',
		'banner_all'                    => '[hyperpress_banner anchor="about" title="About us" subtitle="Who we are"]',
		'banner_anchor_only'            => '[hyperpress_banner anchor="nothing"]',
		'banner_empty'                  => '[hyperpress_banner]',

		'date_distance_default'         => '[hyperpress_date_distance]',
		'date_distance_past_sign'       => '[hyperpress_date_distance date="2000-01-01" format="%R"]',
		'date_distance_future_sign'     => '[hyperpress_date_distance date="2100-01-01" format="%R"]',
		'date_distance_inclusive'       => '[hyperpress_date_distance date="2100-01-01" format="%R1" inclusive="true"]',
		'date_distance_inclusive_past'  => '[hyperpress_date_distance date="2000-01-01" format="%R1" inclusive="true"]',
		'date_distance_not_inclusive'   => '[hyperpress_date_distance date="2100-01-01" format="%R1" inclusive="false"]',
		'date_distance_locale_no_suffix' => '[hyperpress_date_distance date="2100-01-01" format="%R1" suffix="false" locale="fr-FR"]',

		'date_distance_suffix_default_locale' => '[hyperpress_date_distance date="2100-01-01" format="%R1" inclusive="true" suffix="true"]',
		'date_distance_suffix_first'    => '[hyperpress_date_distance date="2100-01-01" format="%R1" suffix="true"]',
		'date_distance_suffix_third'    => '[hyperpress_date_distance date="2100-01-01" format="%R3" suffix="true"]',
		'date_distance_suffix_fourth'   => '[hyperpress_date_distance date="2100-01-01" format="%R3" inclusive="true" suffix="true"]',
		'date_distance_suffix_past'     => '[hyperpress_date_distance date="2000-01-01" format="%R1" inclusive="true" suffix="true"]',
		'date_distance_suffix_fr'       => '[hyperpress_date_distance date="2100-01-01" format="%R1" inclusive="true" suffix="true" locale="fr-FR"]',
		'date_distance_suffix_de'       => '[hyperpress_date_distance date="2100-01-01" format="%R1" inclusive="true" suffix="true" locale="de-DE"]',
		'date_distance_suffix_en_gb'    => '[hyperpress_date_distance date="2100-01-01" format="%R3" suffix="true" locale="en-GB"]',

		'restrict_plain'                => '[hyperpress_time_restrict]Always[/hyperpress_time_restrict]',
		'restrict_on_past'              => '[hyperpress_time_restrict on="2000-01-01"]Shown[/hyperpress_time_restrict]',
		'restrict_on_future'            => '[hyperpress_time_restrict on="2100-01-01"]Hidden[/hyperpress_time_restrict]',
		'restrict_on_future_else'       => '[hyperpress_time_restrict on="2100-01-01" else="<em>Not yet</em>"]Hidden[/hyperpress_time_restrict]',
		'restrict_off_future'           => '[hyperpress_time_restrict off="2100-01-01"]Shown[/hyperpress_time_restrict]',
		'restrict_off_past'             => '[hyperpress_time_restrict off="2000-01-01"]Hidden[/hyperpress_time_restrict]',
		'restrict_off_past_else'        => '[hyperpress_time_restrict off="2000-01-01" else="Gone"]Hidden[/hyperpress_time_restrict]',
		'restrict_on_and_off'           => '[hyperpress_time_restrict on="2000-01-01" off="2100-01-01"]Window[/hyperpress_time_restrict]',
		'restrict_show_past'            => '[hyperpress_time_restrict show="2000-01-01"]Shown via show[/hyperpress_time_restrict]',
		'restrict_show_future'          => '[hyperpress_time_restrict show="2100-01-01" else="Not yet"]Hidden[/hyperpress_time_restrict]',
		'restrict_hide_future'          => '[hyperpress_time_restrict hide="2100-01-01"]Shown via hide[/hyperpress_time_restrict]',
		'restrict_hide_past'            => '[hyperpress_time_restrict hide="2000-01-01" else="Gone"]Hidden[/hyperpress_time_restrict]',
		'restrict_on_wins_over_show'    => '[hyperpress_time_restrict on="2000-01-01" show="2100-01-01"]On wins[/hyperpress_time_restrict]',
		'restrict_off_wins_over_hide'   => '[hyperpress_time_restrict off="2100-01-01" hide="2000-01-01"]Off wins[/hyperpress_time_restrict]',
		'restrict_nested_shortcode'     => '[hyperpress_time_restrict on="2000-01-01"][hyperpress_emphasis]Nested[/hyperpress_emphasis][/hyperpress_time_restrict]',
		'restrict_else_filtered'        => '[hyperpress_time_restrict on="2100-01-01" else="ok<script>alert(1)</script>"]Hidden[/hyperpress_time_restrict]',

		'repeat_no_type'                => '[hyperpress_time_restrict_repeat]Hidden[/hyperpress_time_restrict_repeat]',
		'repeat_no_type_else'           => '[hyperpress_time_restrict_repeat else="No type"]Hidden[/hyperpress_time_restrict_repeat]',
		'repeat_daily_never'            => '[hyperpress_time_restrict_repeat type="daily" ontime="12:00:00" offtime="12:00:00" else="Never"]Hidden[/hyperpress_time_restrict_repeat]',
		'repeat_daily_empty_times'      => '[hyperpress_time_restrict_repeat type="daily" ontime="" offtime="" else="Hidden"]Daily empty[/hyperpress_time_restrict_repeat]',
		'repeat_unknown_type'           => '[hyperpress_time_restrict_repeat type="hourly"]Hidden[/hyperpress_time_restrict_repeat]',
		'repeat_unknown_type_else'      => '[hyperpress_time_restrict_repeat_2 type="hourly" else="Unknown type"]Hidden[/hyperpress_time_restrict_repeat_2]',
		'repeat_daily_default_times'    => '[hyperpress_time_restrict_repeat_1 type="daily" ontime="00:00:00" offtime="23:59:59" else="Daily default"]Daily[/hyperpress_time_restrict_repeat_1]',
		'repeat_daily_crossing_days'    => '[hyperpress_time_restrict_repeat_2 type="daily" ontime="00:00:01" offtime="00:00:00" else="Crossing"]Crossing[/hyperpress_time_restrict_repeat_2]',
		'repeat_3_hidden_else'          => '[hyperpress_time_restrict_repeat_3 type="daily" ontime="09:00:00" offtime="09:00:00" else="Three"]Hidden[/hyperpress_time_restrict_repeat_3]',
		'repeat_weekly_incomplete'      => '[hyperpress_time_restrict_repeat type="weekly" onday="monday"]Incomplete weekly[/hyperpress_time_restrict_repeat]',
		'repeat_weekly_full'            => '[hyperpress_time_restrict_repeat type="weekly" onday="monday" offday="friday" ontime="08:00:00" offtime="17:00:00" else="Weekly"]Weekly[/hyperpress_time_restrict_repeat]',
		'repeat_monthly_incomplete'     => '[hyperpress_time_restrict_repeat_1 type="monthly" ondate="5"]Incomplete monthly[/hyperpress_time_restrict_repeat_1]',
		'repeat_monthly_full'           => '[hyperpress_time_restrict_repeat type="monthly" ondate="5" offdate="20" ontime="08:00:00" offtime="17:00:00" else="Monthly"]Monthly[/hyperpress_time_restrict_repeat]',
		'repeat_monthly_crossing'       => '[hyperpress_time_restrict_repeat_2 type="monthly" ondate="25" offdate="5" else="Monthly crossing"]Monthly crossing[/hyperpress_time_restrict_repeat_2]',
		'repeat_annually_incomplete'    => '[hyperpress_time_restrict_repeat_3 type="annually" onmonth="march"]Incomplete annually[/hyperpress_time_restrict_repeat_3]',
		'repeat_annually_full'          => '[hyperpress_time_restrict_repeat type="annually" onmonth="march" offmonth="september" ondate="5" offdate="20" else="Annually"]Annually[/hyperpress_time_restrict_repeat]',
		'repeat_annually_crossing'      => '[hyperpress_time_restrict_repeat_1 type="annually" onmonth="november" offmonth="february" ondate="3" offdate="10" offtime="18:00:00" else="Annually crossing"]Annually crossing[/hyperpress_time_restrict_repeat_1]',
		'repeat_nested_shortcode'       => '[hyperpress_time_restrict_repeat type="weekly" onday="monday" else="x"][hyperpress_emphasis]x[/hyperpress_emphasis][/hyperpress_time_restrict_repeat]',
	);
}
