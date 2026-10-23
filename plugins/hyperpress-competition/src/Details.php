<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

final class Details {

	private const TBA_KEY = 'hyperpress_competition_tba_event_key';

	private const STREAMS = 'hyperpress_competition_stream_url';

	public const BLURB = 'hyperpress_competition_blurb';

	/** Link fields in display order; the derived Blue Alliance link follows `first` + `frc_events`, then the agenda. */
	private const LINKS_BEFORE_TBA = array(
		'hyperpress_competition_ne_first_event_url' => 'NE FIRST Event',
		'hyperpress_competition_ftc_ma_event_url'   => 'FTC MA Event',
		'hyperpress_competition_first_event_url'    => 'FIRST Event',
		'hyperpress_competition_frc_events_url'     => 'FRC Events',
	);

	private const LINKS_AFTER_TBA = array(
		'hyperpress_competition_agenda_url' => 'Public Agenda',
	);

	/**
	 * Collects the displayable details of a competition.
	 *
	 * @param int $post_id Competition post ID.
	 *
	 * @return array{host:string,venue:string,address:string,blurb:string,when:?\DateTimeImmutable,days:list<array{label:string,start:\DateTimeImmutable,state:string}>,links:list<array{label:string,url:string,target:string,rel:string,group:string}>} The blurb is printable HTML. Each link's `group` is `info` or `live` (streams); each day's `state` is `past`, `current` (the day the countdown targets) or `upcoming`.
	 */
	public static function for_post( int $post_id ): array {
		$days    = Schedule::days( $post_id );
		$now     = Schedule::now();
		$current = Schedule::pick_index( $days, $now );
		$links   = array();
		foreach ( self::LINKS_BEFORE_TBA as $key => $default_label ) {
			$entry = self::link_entry( \HyperPress\Utils\Meta::get( $key, $post_id ), $default_label, 'info' );
			if ( null !== $entry ) {
				$links[] = $entry;
			}
		}

		$tba = self::tba_link( \HyperPress\Utils\Meta::get( self::TBA_KEY, $post_id ) );
		if ( null !== $tba ) {
			$links[] = $tba;
		}

		foreach ( self::LINKS_AFTER_TBA as $key => $default_label ) {
			$entry = self::link_entry( \HyperPress\Utils\Meta::get( $key, $post_id ), $default_label, 'info' );
			if ( null !== $entry ) {
				$links[] = $entry;
			}
		}

		$streams = self::stream_list( \HyperPress\Utils\Meta::get( self::STREAMS, $post_id ) );
		foreach ( $streams as $index => $stream ) {
			$label = count( $streams ) > 1 ? 'Stream ' . ( $index + 1 ) : 'Stream';
			$entry = self::link_entry( $stream, $label, 'live' );
			if ( null !== $entry ) {
				$links[] = $entry;
			}
		}

		return array(
			'host'    => self::text( \HyperPress\Utils\Meta::get( 'hyperpress_competition_host_name', $post_id ) ),
			'venue'   => self::text( \HyperPress\Utils\Meta::get( 'hyperpress_competition_venue_name', $post_id ) ),
			'address' => self::text( \HyperPress\Utils\Meta::get( 'hyperpress_competition_venue_address', $post_id ) ),
			'blurb'   => self::blurb( \HyperPress\Utils\Meta::get( self::BLURB, $post_id ) ),
			'when'    => null === $current ? null : $days[ $current ]['start'],
			'days'    => self::day_states( $days, $current, $now ),
			'links'   => $links,
		);
	}

	/**
	 * Every day with its display state, from each day's end.
	 *
	 * `past` is a day whose end is not after `$now`; `current` is the day at `$current` (the countdown's target) unless
	 * it is past; everything else is `upcoming`, which includes a skipped day that is in progress. After the last day
	 * ends every day is `past` and none is `current`.
	 *
	 * @param list<array{label:string,start:\DateTimeImmutable,end:\DateTimeImmutable,countdown:bool}> $days    Days in chronological order.
	 * @param int|null                                                                                 $current Index of the day the countdown targets.
	 * @param \DateTimeImmutable                                                                       $now     The moment to compare against.
	 *
	 * @return list<array{label:string,start:\DateTimeImmutable,state:string}>
	 */
	private static function day_states( array $days, ?int $current, \DateTimeImmutable $now ): array {
		$states = array();
		foreach ( $days as $index => $day ) {
			if ( $day['end'] <= $now ) {
				$state = 'past';
			} else {
				$state = $index === $current ? 'current' : 'upcoming';
			}
			$states[] = array(
				'label' => $day['label'],
				'start' => $day['start'],
				'state' => $state,
			);
		}

		return $states;
	}

	/**
	 * One link from a Meta Box link array, or null when it has no safe URL.
	 *
	 * @param mixed  $link          Stored link array.
	 * @param string $default_label Label used when the link has no title.
	 * @param string $group         Display group: `info` or `live`.
	 * @return array{label:string,url:string,target:string,rel:string,group:string}|null
	 */
	private static function link_entry( mixed $link, string $default_label, string $group ): ?array {
		if ( ! is_array( $link ) ) {
			return null;
		}
		$raw = self::text( $link['url'] ?? '' );
		$url = esc_url_raw( $raw );
		if ( '' === $url ) {
			return null;
		}
		$title = self::text( $link['title'] ?? '' );
		// Meta Box fills the title with the URL when none is typed.
		if ( 0 === strcasecmp( $title, $raw ) || 0 === strcasecmp( $title, $url ) ) {
			$title = '';
		}

		$target = \HyperPress\Utils\Link::target( $url );

		return array(
			'label'  => '' !== $title ? $title : $default_label,
			'url'    => $url,
			'target' => $target,
			'rel'    => \HyperPress\Utils\Link::rel( $target ),
			'group'  => $group,
		);
	}

	/**
	 * The Blue Alliance link for an event key such as 2026mabos, or null when the key is empty or malformed.
	 *
	 * @param mixed $key Stored event key.
	 * @return array{label:string,url:string,target:string,rel:string,group:string}|null
	 */
	private static function tba_link( mixed $key ): ?array {
		$key = strtolower( self::text( $key ) );
		if ( 1 !== preg_match( '/^\d{4}[a-z0-9]+$/', $key ) ) {
			return null;
		}

		$url    = 'https://www.thebluealliance.com/event/' . $key;
		$target = \HyperPress\Utils\Link::target( $url );

		return array(
			'label'  => 'The Blue Alliance',
			'url'    => $url,
			'target' => $target,
			'rel'    => \HyperPress\Utils\Link::rel( $target ),
			'group'  => 'info',
		);
	}

	/**
	 * Normalizes the stream field to a list of link arrays. Cloned fields stored as multiple rows come back as a
	 * list; data saved before the field was cloneable is a single link array.
	 *
	 * @param mixed $value Stored value.
	 * @return list<mixed>
	 */
	private static function stream_list( mixed $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}
		if ( array_key_exists( 'url', $value ) || array_key_exists( 'title', $value ) ) {
			return array( $value );
		}

		return array_values( $value );
	}

	/**
	 * The blurb as printable HTML: allowed post markup in paragraphs, or '' when empty or not text.
	 *
	 * @param mixed $value Stored value.
	 */
	private static function blurb( mixed $value ): string {
		$text = self::text( $value );

		return '' === $text ? '' : wpautop( wp_kses_post( $text ) );
	}

	private static function text( mixed $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
