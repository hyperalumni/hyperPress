<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

final class Details {

	private const LINKS = array(
		'hyperpress_competition_ne_first_event_url' => 'NE FIRST event',
		'hyperpress_competition_ftc_ma_event_url'   => 'FTC MA event',
		'hyperpress_competition_first_event_url'    => 'FIRST event',
		'hyperpress_competition_stream_url'         => 'Stream',
	);

	/**
	 * Collects the displayable details of a competition.
	 *
	 * @param int $post_id Competition post ID.
	 *
	 * @return array{host:string,venue:string,address:string,when:?\DateTimeImmutable,links:list<array{label:string,url:string,target:string}>}
	 */
	public static function for_post( int $post_id ): array {
		$links = array();
		foreach ( self::LINKS as $key => $default_label ) {
			$link = \HyperPress\Utils\Meta::get( $key, $post_id );
			if ( ! is_array( $link ) ) {
				continue;
			}
			$raw = self::text( $link['url'] ?? '' );
			$url = esc_url_raw( $raw );
			if ( '' === $url ) {
				continue;
			}
			$title = self::text( $link['title'] ?? '' );
			// Meta Box fills the title with the URL when none is typed.
			if ( 0 === strcasecmp( $title, $raw ) || 0 === strcasecmp( $title, $url ) ) {
				$title = '';
			}
			$links[] = array(
				'label'  => '' !== $title ? $title : $default_label,
				'url'    => $url,
				'target' => '_blank',
			);
		}

		return array(
			'host'    => self::text( \HyperPress\Utils\Meta::get( 'hyperpress_competition_host_name', $post_id ) ),
			'venue'   => self::text( \HyperPress\Utils\Meta::get( 'hyperpress_competition_venue_name', $post_id ) ),
			'address' => self::text( \HyperPress\Utils\Meta::get( 'hyperpress_competition_venue_address', $post_id ) ),
			'when'    => StartDate::instant( $post_id ),
			'links'   => $links,
		);
	}

	private static function text( mixed $value ): string {
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
