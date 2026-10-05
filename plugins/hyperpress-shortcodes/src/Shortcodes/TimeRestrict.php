<?php
namespace HyperPress\Shortcodes\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcodes to show or hide content on a one-off basis or on a recurring schedule.
 *
 * Moved from the theme (library/shortcodes/show-hide-content.php). Behavior is unchanged except for audit fixes
 * (S24: the annual same-month branch read an undefined variable; S25: attribute validation).
 *
 * Attribute validation: a date or time attribute that cannot be parsed counts as if it was not set.
 * - time-restrict: an unparsable `on` or `off` is ignored, so the content shows whenever the other condition allows.
 * - time-restrict-repeat: an unparsable month name counts as a missing month (annually shows the content when
 *   a required attribute is missing), an unparsable `ontime` or `offtime` falls back to its default, and dates
 *   and times are normalized (`05` is 5, `9:00` is 09:00:00) before they are compared.
 *
 * @author Dave Clements
 */
final class TimeRestrict {

	public static function register(): void {
		add_shortcode( 'time-restrict', array( self::class, 'render' ) );
		add_shortcode( 'time-restrict-repeat', array( self::class, 'render_repeat' ) );
		add_shortcode( 'time-restrict-repeat-1', array( self::class, 'render_repeat' ) );
		add_shortcode( 'time-restrict-repeat-2', array( self::class, 'render_repeat' ) );
		add_shortcode( 'time-restrict-repeat-3', array( self::class, 'render_repeat' ) );
	}

	/**
	 * The current (site-local) timestamp used by every time comparison in this class.
	 *
	 * Filterable so the schedule logic can be tested with a frozen clock.
	 */
	private static function now(): int {
		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- compared with user-entered site-local times, so the site-offset timestamp is intended
		return (int) apply_filters( 'hyperpress_shortcodes_now', current_time( 'timestamp' ) );
	}

	/**
	 * Time Restricted Shortcode
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content The content contained between the shortcode.
	 *
	 * @return string The content to display
	 */
	public static function render( $atts, $content ): string {
		$content = (string) $content;
		$atts    = shortcode_atts(
			array(
				'show' => '', // Retired in favor of 'on' for better consistency.
				'hide' => '', // Retired in favor of 'off' for better consistency.
				'on'   => '',
				'off'  => '',
				'else' => '',
			),
			$atts
		);

		$show = $atts['show'];
		$hide = $atts['hide'];
		$on   = $atts['on'];
		$off  = $atts['off'];
		$else = $atts['else'];

		$showit = 0; // Set some defaults.
		$hideit = 1;

		if ( empty( $on ) && ! empty( $show ) ) { // Replace 'show' attribute with 'on'.
			$on = $show;
		}

		if ( empty( $off ) && ! empty( $hide ) ) { // Replace 'hide' attribute with 'off'.
			$off = $hide;
		}

		$on_ts  = empty( $on ) ? false : strtotime( $on ); // An unparsable time is treated as not set.
		$off_ts = empty( $off ) ? false : strtotime( $off );

		if ( false === $on_ts || $on_ts < self::now() ) { // If on time isn't set, or has already passed, show the content.
			$showit = 1;
		}

		if ( false === $off_ts || $off_ts > self::now() ) {  // If off time isn't set, or is in the future, show the content.
			$hideit = 0;
		}

		if ( 1 === $showit && 0 === $hideit ) { // Show the content if conditions met.
			return do_shortcode( $content );
		} elseif ( ! empty( $else ) ) {
			return wp_kses_post( $else );
		}

		return '';
	}

	/**
	 * A time attribute as zero padded H:i:s, or the fallback when it cannot be parsed.
	 *
	 * @param string $time     The attribute value.
	 * @param string $fallback The H:i:s to use when the value is not a time.
	 */
	private static function normalize_time( string $time, string $fallback ): string {
		$timestamp = strtotime( $time );
		if ( false === $timestamp ) {
			return $fallback;
		}
		return gmdate( 'H:i:s', $timestamp ); // WordPress runs PHP in UTC, so this equals date().
	}

	/**
	 * Time Restricted Repeat Shortcode
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content The content contained between the shortcode.
	 *
	 * @return string The content to display
	 */
	public static function render_repeat( $atts, $content ): string {
		$content = (string) $content;
		$atts    = shortcode_atts(
			array(
				'type'     => '',
				'ontime'   => '00:00:00',
				'offtime'  => '23:59:59',
				'onday'    => '',
				'offday'   => '',
				'ondate'   => '',
				'offdate'  => '',
				'onmonth'  => '',
				'offmonth' => '',
				'else'     => '',
			),
			$atts
		);

		$type     = $atts['type'];
		$ontime   = $atts['ontime'];
		$offtime  = $atts['offtime'];
		$onday    = $atts['onday'];
		$offday   = $atts['offday'];
		$ondate   = $atts['ondate'];
		$offdate  = $atts['offdate'];
		$onmonth  = $atts['onmonth'];
		$offmonth = $atts['offmonth'];
		$else     = $atts['else'];

		$showit = 0; // Set some defaults.

		// phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date,WordPress.DateTime.CurrentTimeTimestamp.Requested -- the schedule compares site-local wall-clock values: date() on a site-offset timestamp (current_time( 'timestamp' )) yields the site-local weekday, date and time
		$onwday  = (int) date( 'w', strtotime( ucfirst( $onday ) ) ); // Convert day to PHP numerical representation of day (w).
		$offwday = (int) date( 'w', strtotime( ucfirst( $offday ) ) );

		// Check what was set before normalizing: a missing or unparsable month counts as missing.
		$ondate_set   = ! empty( $ondate );
		$offdate_set  = ! empty( $offdate );
		$ontime_set   = ! empty( $ontime );
		$offtime_set  = ! empty( $offtime );
		$onmonth_ts   = empty( $onmonth ) ? false : strtotime( $onmonth );
		$offmonth_ts  = empty( $offmonth ) ? false : strtotime( $offmonth );
		$onmonth_set  = false !== $onmonth_ts;
		$offmonth_set = false !== $offmonth_ts;

		$onmonth  = $onmonth_set ? (int) date( 'n', $onmonth_ts ) : 0; // Make sure month is in numerical format.
		$offmonth = $offmonth_set ? (int) date( 'n', $offmonth_ts ) : 0;
		$ondate   = (int) $ondate; // Dates are compared as numbers: '05' is 5.
		$offdate  = (int) $offdate;
		$ontime   = self::normalize_time( $ontime, '00:00:00' ); // Times are compared as zero padded H:i:s strings.
		$offtime  = self::normalize_time( $offtime, '23:59:59' );

		$todaysday   = (int) date( 'w', self::now() ); // What's the time, Mr. Wolf?
		$todaysdate  = (int) date( 'j', self::now() );
		$todaysmonth = (int) date( 'n', self::now() );
		$currenttime = date( 'H:i:s', self::now() );
		// phpcs:enable WordPress.DateTime.RestrictedFunctions.date_date,WordPress.DateTime.CurrentTimeTimestamp.Requested

		if ( 'annually' === $type && ( ! $ondate_set || ! $offdate_set || ! $onmonth_set || ! $offmonth_set ) ) { // Show content if not all required attributes set.
			$showit = 1;
		} elseif ( 'annually' === $type && $onmonth < $offmonth ) { // Months do not cross years.
			if ( $onmonth < $todaysmonth && $offmonth > $todaysmonth ) { // Today is in between show months.
				$showit = 1;
			} elseif ( $onmonth === $todaysmonth ) { // Turns on this month and off later.
				if ( $ondate < $todaysdate ) { // On date has already passed.
					$showit = 1;
				} elseif ( $ondate === $todaysdate && $ontime < $currenttime ) { // Today is on date and on time has passed.
					$showit = 1;
				}
			} elseif ( $offmonth === $todaysmonth ) { // Turns off this month and on earlier.
				if ( $offdate > $todaysdate ) { // Off date hasn't arrived yet.
					$showit = 1;
				} elseif ( $offdate === $todaysdate && $offtime > $currenttime ) { // Today is off date and off time hasn't yet passed.
					$showit = 1;
				}
			}
		} elseif ( 'annually' === $type && $onmonth > $offmonth ) { // Months cross years.
			if ( $onmonth < $todaysmonth || $offmonth > $todaysmonth ) { // Today is in between show months.
				$showit = 1;
			} elseif ( $onmonth === $todaysmonth ) { // Turns on this month and off next year.
				if ( $ondate < $todaysdate ) { // On date has already passed.
					$showit = 1;
				} elseif ( $ondate === $todaysdate && $ontime < $currenttime ) { // Today is on date and on time has passed.
					$showit = 1;
				}
			} elseif ( $offmonth === $todaysmonth ) { // Turns off this month and on last year.
				if ( $offdate > $todaysdate ) { // Off date hasn't arrived yet.
					$showit = 1;
				} elseif ( $offdate === $todaysdate && $offtime > $currenttime ) { // Today is off date and off time hasn't yet passed.
					$showit = 1;
				}
			}
		} elseif ( 'annually' === $type && $onmonth === $offmonth && $onmonth === $todaysmonth ) { // Turns on and off this month.
			if ( $ondate < $todaysdate ) { // Today is after on day.
				if ( $offdate > $todaysdate ) { // Off day hasn't yet happened.
					$showit = 1;
				} elseif ( $offdate < $ondate ) { // Doesn't turn off until next year.
					$showit = 1;
				} elseif ( $offdate === $todaysdate && $offtime > $currenttime ) { // Today is off date and off time hasn't yet passed.
					$showit = 1;
				} elseif ( $ondate === $offdate && $offdate !== $todaysdate && $offtime < $ontime ) { // Turns on for almost a full year, less a few hours.
					$showit = 1;
				}
			} elseif ( $ondate > $todaysdate ) { // Today is before on day.
				if ( $offdate < $ondate && $offdate > $todaysdate ) { // Turns off later this month after being on for nearly a year.
					$showit = 1;
				} elseif ( $offdate === $todaysdate && $offtime > $currenttime ) { // Turns off today after being on for nearly a year.
					$showit = 1;
				} elseif ( $ondate === $offdate && $offdate !== $todaysdate && $offtime < $ontime ) { // Turns on for almost a full year, less a few hours.
					$showit = 1;
				}
			} elseif ( $ondate === $todaysdate ) { // Today is on day.
				if ( $offdate > $todaysdate && $ontime < $currenttime ) { // Turns off later this month and on time has passed.
					$showit = 1;
				} elseif ( $offdate < $todaysdate && $ontime < $currenttime ) { // Turns off next year and on time has passed.
					$showit = 1;
				} elseif ( $offdate === $todaysdate && $ontime < $currenttime && $offtime > $currenttime ) { // Turns on and off today and is between show times.
					$showit = 1;
				} elseif ( $offdate === $todaysdate && $ontime > $offtime && $ontime < $currenttime ) { // Turns on for almost a full year, less a few hours (earlier today).
					$showit = 1;
				} elseif ( $offdate === $todaysdate && $ontime > $offtime && $offtime > $currenttime ) { // Turns on for almost a full year, less a few hours (later today).
					$showit = 1;
				}
			}
		} elseif ( 'annually' === $type && $onmonth === $offmonth && $onmonth !== $todaysmonth && $offdate < $ondate ) { // Turns on and off the same month (but not this month) and is on for most of the year.
			$showit = 1;
		} elseif ( 'annually' === $type && $onmonth === $offmonth && $onmonth !== $todaysmonth && $offdate === $ondate && $offtime < $ontime ) { // Turns on for almost a full year, less a few hours.
			$showit = 1;
		}

		if ( 'monthly' === $type && ( ! $ondate_set || ! $offdate_set ) ) { // Show content if not all required attributes set.
			$showit = 1;
		} elseif ( 'monthly' === $type && $ondate < $offdate ) { // Dates do not cross months.
			if ( $ondate < $todaysdate && $offdate > $todaysdate ) { // Today is between show dates.
				$showit = 1;
			} elseif ( $ondate === $todaysdate && $ontime < $currenttime ) { // Today is on date and on time has passed.
				$showit = 1;
			} elseif ( $offdate === $todaysdate && $offtime > $currenttime ) { // Today is off date and off time hasn't yet passed.
				$showit = 1;
			}
		} elseif ( 'monthly' === $type && $ondate > $offdate ) { // Dates cross months.
			if ( $ondate < $todaysdate || $offdate < $todaysdate ) { // Today is between show dates.
				$showit = 1;
			} elseif ( $ondate === $todaysdate && $ontime < $currenttime ) { // Today is on date and on time has passed.
				$showit = 1;
			} elseif ( $offdate === $todaysdate && $offtime > $currenttime ) { // Today is off date and off time hasn't yet passed.
				$showit = 1;
			}
		} elseif ( 'monthly' === $type && $ondate === $offdate && $ondate === $todaysdate && $ontime < $currenttime && $offtime > $currenttime ) { // On and off on same date (today), and within show times.
			$showit = 1;
		}

		if ( 'weekly' === $type && ( empty( $onday ) || empty( $offday ) ) ) { // Show content if not all required attributes set.
			$showit = 1;
		} elseif ( 'weekly' === $type && $onwday < $offwday ) { // Days do not cross weeks.
			if ( $onwday < $todaysday && $offwday > $todaysday ) { // Today is between show days.
				$showit = 1;
			} elseif ( $onwday === $todaysday && $ontime < $currenttime ) { // Today is on day and on time has passed.
				$showit = 1;
			} elseif ( $offwday === $todaysday && $offtime > $currenttime ) { // Today is off day and off time hasn't yet passed.
				$showit = 1;
			}
		} elseif ( 'weekly' === $type && $onwday > $offwday ) { // Days cross weeks.
			if ( $onwday < $todaysday || $offwday > $todaysday ) { // Today is between show days.
				$showit = 1;
			} elseif ( $onwday === $todaysday && $ontime < $currenttime ) { // Today is on day and on time has passed.
				$showit = 1;
			} elseif ( $offwday === $todaysday && $offtime > $currenttime ) { // Today is off day and off time hasn't yet passed.
				$showit = 1;
			}
		} elseif ( 'weekly' === $type && $onwday === $offwday && $onwday === $todaysday && $ontime < $currenttime && $offtime > $currenttime ) { // On and off on same day (today), and within show times.
			$showit = 1;
		}

		if ( 'daily' === $type && ( ! $ontime_set || ! $offtime_set ) ) { // Show content is not all required attributes set.
			$showit = 1;
		} elseif ( 'daily' === $type && $ontime < $offtime ) { // Time does not cross days.
			if ( $ontime < $currenttime && $currenttime < $offtime ) { // Current time is between show times.
				$showit = 1;
			}
		} elseif ( 'daily' === $type && $ontime > $offtime ) { // Time crosses days.
			if ( $ontime < $currenttime || $currenttime < $offtime ) { // Current time is between show times.
				$showit = 1;
			}
		}

		if ( 1 === $showit ) { // If everything's in order, show the content.
			return do_shortcode( $content );
		} elseif ( ! empty( $else ) ) {
			return wp_kses_post( $else );
		}

		return '';
	}
}
