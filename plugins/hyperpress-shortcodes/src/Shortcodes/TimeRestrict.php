<?php
namespace HyperPress\Shortcodes\Shortcodes;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcodes to show or hide content on a one-off basis or on a recurring schedule.
 *
 * Moved unchanged from the theme (library/shortcodes/show-hide-content.php).
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
	 * Time Restricted Shortcode
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content The content contained between the shortcode.
	 *
	 * @return string The content to display
	 */
	public static function render( $atts, $content ): string {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- plugins audit
		extract(
			shortcode_atts(
				array(
					'show' => '', // Retired in favor of 'on' for better consistency.
					'hide' => '', // Retired in favor of 'off' for better consistency.
					'on'   => '',
					'off'  => '',
					'else' => '',
				),
				$atts
			)
		);

		$showit = 0; // Set some defaults.
		$hideit = 1;

		if ( empty( $on ) && ! empty( $show ) ) { // Replace 'show' attribute with 'on'.
			$on = $show;
		}

		if ( empty( $off ) && ! empty( $hide ) ) { // Replace 'hide' attribute with 'off'.
			$off = $hide;
		}

		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- plugins audit
		if ( empty( $on ) || strtotime( $on ) < current_time( 'timestamp' ) ) { // If on time isn't set, or has already passed, show the content.
			$showit = 1;
		}

		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- plugins audit
		if ( empty( $off ) || strtotime( $off ) > current_time( 'timestamp' ) ) {  // If off time isn't set, or is in the future, show the content.
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
	 * Time Restricted Repeat Shortcode
	 *
	 * @param array  $atts    Shortcode attributes.
	 * @param string $content The content contained between the shortcode.
	 *
	 * @return string The content to display
	 */
	public static function render_repeat( $atts, $content ): string {
		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- plugins audit
		extract(
			shortcode_atts(
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
			)
		);

		$showit = 0; // Set some defaults.

		// phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date,WordPress.DateTime.CurrentTimeTimestamp.Requested -- plugins audit
		$onwday   = date( 'w', strtotime( ucfirst( $onday ) ) ); // Convert day to PHP numerical representation of day (w).
		$offwday  = date( 'w', strtotime( ucfirst( $offday ) ) );
		$onmonth  = date( 'n', strtotime( $onmonth ) ); // Make sure month is in numberical format.
		$offmonth = date( 'n', strtotime( $offmonth ) );

		$todaysday   = date( 'w', current_time( 'timestamp' ) ); // What's the time, Mr. Wolf?
		$todaysdate  = date( 'j', current_time( 'timestamp' ) );
		$todaysmonth = date( 'n', current_time( 'timestamp' ) );
		$currenttime = date( 'H:i:s', current_time( 'timestamp' ) );
		// phpcs:enable WordPress.DateTime.RestrictedFunctions.date_date,WordPress.DateTime.CurrentTimeTimestamp.Requested

		if ( 'annually' === $type && ( empty( $ondate ) || empty( $offdate ) || empty( $onmonth ) || empty( $offmonth ) ) ) { // Show content if not all required attributes set.
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
				} elseif ( $offdate < $todaydate && $ontime < $currenttime ) { // Turns off next year and on time has passed.
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

		if ( 'monthly' === $type && ( empty( $ondate ) || empty( $offdate ) ) ) { // Show content if not all required attributes set.
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

		if ( 'daily' === $type && ( empty( $ontime ) || empty( $offtime ) ) ) { // Show content is not all required attributes set.
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
