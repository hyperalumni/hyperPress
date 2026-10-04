<?php
namespace HyperPress\Tests\Support;

/**
 * Markup fed to do_shortcode(). Every attribute of every tag appears in at least one case.
 *
 * Time-dependent shortcodes use dates in 2000 and 2100 so the result cannot change as time passes.
 * The time-restrict-repeat cases for weekly, monthly and annually use the same text for the content
 * and the else attribute, so the branch taken (which depends on today's date) cannot change the output.
 * The ordinal suffix of hyper_date_distance needs ext-intl (installed in the test image).
 */
final class ShortcodeCases {

	public const CASES = array(
		'raw_basic'                     => '[raw]<b>x</b>[/raw]',
		'raw_with_leading_markup'       => "[raw]</p>\n<br />Line[/raw]",
		'raw_nested_shortcode'          => '[raw][hyper_emphasis]in raw[/hyper_emphasis][/raw]',
		'raw_empty'                     => '[raw][/raw]',
		'raw_leading_br'                => '[raw]<br />Line[/raw]',
		'raw_trailing_p'                => '[raw]Line<p>[/raw]',

		'emphasis'                      => '[hyper_emphasis]Hello[/hyper_emphasis]',
		'emphasis_nested'               => '[hyper_emphasis]Hello [raw]inner[/raw][/hyper_emphasis]',
		'emphasis_self_closing'         => '[hyper_emphasis]',

		'banner_title'                  => '[hyper_banner title="Welcome"]',
		'banner_subtitle'               => '[hyper_banner subtitle="Only a subtitle"]',
		'banner_title_subtitle'         => '[hyper_banner title="Welcome" subtitle="Hello you"]',
		'banner_anchor_title'           => '[hyper_banner anchor="top" title="Welcome"]',
		'banner_all'                    => '[hyper_banner anchor="about" title="About us" subtitle="Who we are"]',
		'banner_anchor_only'            => '[hyper_banner anchor="nothing"]',
		'banner_empty'                  => '[hyper_banner]',

		'date_distance_default'         => '[hyper_date_distance]',
		'date_distance_past_sign'       => '[hyper_date_distance date="2000-01-01" format="%R"]',
		'date_distance_future_sign'     => '[hyper_date_distance date="2100-01-01" format="%R"]',
		'date_distance_inclusive'       => '[hyper_date_distance date="2100-01-01" format="%R1" inclusive="true"]',
		'date_distance_inclusive_past'  => '[hyper_date_distance date="2000-01-01" format="%R1" inclusive="true"]',
		'date_distance_not_inclusive'   => '[hyper_date_distance date="2100-01-01" format="%R1" inclusive="false"]',
		'date_distance_locale_no_suffix' => '[hyper_date_distance date="2100-01-01" format="%R1" suffix="false" locale="fr-FR"]',

		'date_distance_suffix_default_locale' => '[hyper_date_distance date="2100-01-01" format="%R1" inclusive="true" suffix="true"]',
		'date_distance_suffix_first'    => '[hyper_date_distance date="2100-01-01" format="%R1" suffix="true"]',
		'date_distance_suffix_third'    => '[hyper_date_distance date="2100-01-01" format="%R3" suffix="true"]',
		'date_distance_suffix_fourth'   => '[hyper_date_distance date="2100-01-01" format="%R3" inclusive="true" suffix="true"]',
		'date_distance_suffix_past'     => '[hyper_date_distance date="2000-01-01" format="%R1" inclusive="true" suffix="true"]',
		'date_distance_suffix_fr'       => '[hyper_date_distance date="2100-01-01" format="%R1" inclusive="true" suffix="true" locale="fr-FR"]',
		'date_distance_suffix_de'       => '[hyper_date_distance date="2100-01-01" format="%R1" inclusive="true" suffix="true" locale="de-DE"]',
		'date_distance_suffix_en_gb'    => '[hyper_date_distance date="2100-01-01" format="%R3" suffix="true" locale="en-GB"]',

		'restrict_plain'                => '[time-restrict]Always[/time-restrict]',
		'restrict_on_past'              => '[time-restrict on="2000-01-01"]Shown[/time-restrict]',
		'restrict_on_future'            => '[time-restrict on="2100-01-01"]Hidden[/time-restrict]',
		'restrict_on_future_else'       => '[time-restrict on="2100-01-01" else="<em>Not yet</em>"]Hidden[/time-restrict]',
		'restrict_off_future'           => '[time-restrict off="2100-01-01"]Shown[/time-restrict]',
		'restrict_off_past'             => '[time-restrict off="2000-01-01"]Hidden[/time-restrict]',
		'restrict_off_past_else'        => '[time-restrict off="2000-01-01" else="Gone"]Hidden[/time-restrict]',
		'restrict_on_and_off'           => '[time-restrict on="2000-01-01" off="2100-01-01"]Window[/time-restrict]',
		'restrict_show_past'            => '[time-restrict show="2000-01-01"]Shown via show[/time-restrict]',
		'restrict_show_future'          => '[time-restrict show="2100-01-01" else="Not yet"]Hidden[/time-restrict]',
		'restrict_hide_future'          => '[time-restrict hide="2100-01-01"]Shown via hide[/time-restrict]',
		'restrict_hide_past'            => '[time-restrict hide="2000-01-01" else="Gone"]Hidden[/time-restrict]',
		'restrict_on_wins_over_show'    => '[time-restrict on="2000-01-01" show="2100-01-01"]On wins[/time-restrict]',
		'restrict_off_wins_over_hide'   => '[time-restrict off="2100-01-01" hide="2000-01-01"]Off wins[/time-restrict]',
		'restrict_nested_shortcode'     => '[time-restrict on="2000-01-01"][hyper_emphasis]Nested[/hyper_emphasis][/time-restrict]',
		'restrict_else_filtered'        => '[time-restrict on="2100-01-01" else="ok<script>alert(1)</script>"]Hidden[/time-restrict]',

		'repeat_no_type'                => '[time-restrict-repeat]Hidden[/time-restrict-repeat]',
		'repeat_no_type_else'           => '[time-restrict-repeat else="No type"]Hidden[/time-restrict-repeat]',
		'repeat_daily_never'            => '[time-restrict-repeat type="daily" ontime="12:00:00" offtime="12:00:00" else="Never"]Hidden[/time-restrict-repeat]',
		'repeat_daily_empty_times'      => '[time-restrict-repeat type="daily" ontime="" offtime="" else="Hidden"]Daily empty[/time-restrict-repeat]',
		'repeat_unknown_type'           => '[time-restrict-repeat type="hourly"]Hidden[/time-restrict-repeat]',
		'repeat_unknown_type_else'      => '[time-restrict-repeat-2 type="hourly" else="Unknown type"]Hidden[/time-restrict-repeat-2]',
		'repeat_daily_default_times'    => '[time-restrict-repeat-1 type="daily" ontime="00:00:00" offtime="23:59:59" else="Daily default"]Daily[/time-restrict-repeat-1]',
		'repeat_daily_crossing_days'    => '[time-restrict-repeat-2 type="daily" ontime="00:00:01" offtime="00:00:00" else="Crossing"]Crossing[/time-restrict-repeat-2]',
		'repeat_3_hidden_else'          => '[time-restrict-repeat-3 type="daily" ontime="09:00:00" offtime="09:00:00" else="Three"]Hidden[/time-restrict-repeat-3]',
		'repeat_weekly_incomplete'      => '[time-restrict-repeat type="weekly" onday="monday"]Incomplete weekly[/time-restrict-repeat]',
		'repeat_weekly_full'            => '[time-restrict-repeat type="weekly" onday="monday" offday="friday" ontime="08:00:00" offtime="17:00:00" else="Weekly"]Weekly[/time-restrict-repeat]',
		'repeat_monthly_incomplete'     => '[time-restrict-repeat-1 type="monthly" ondate="5"]Incomplete monthly[/time-restrict-repeat-1]',
		'repeat_monthly_full'           => '[time-restrict-repeat type="monthly" ondate="5" offdate="20" ontime="08:00:00" offtime="17:00:00" else="Monthly"]Monthly[/time-restrict-repeat]',
		'repeat_monthly_crossing'       => '[time-restrict-repeat-2 type="monthly" ondate="25" offdate="5" else="Monthly crossing"]Monthly crossing[/time-restrict-repeat-2]',
		'repeat_annually_incomplete'    => '[time-restrict-repeat-3 type="annually" onmonth="march"]Incomplete annually[/time-restrict-repeat-3]',
		'repeat_annually_full'          => '[time-restrict-repeat type="annually" onmonth="march" offmonth="september" ondate="5" offdate="20" else="Annually"]Annually[/time-restrict-repeat]',
		'repeat_annually_crossing'      => '[time-restrict-repeat-1 type="annually" onmonth="november" offmonth="february" ondate="3" offdate="10" offtime="18:00:00" else="Annually crossing"]Annually crossing[/time-restrict-repeat-1]',
		'repeat_nested_shortcode'       => '[time-restrict-repeat type="weekly" onday="monday" else="x"][hyper_emphasis]x[/hyper_emphasis][/time-restrict-repeat]',
	);
}
