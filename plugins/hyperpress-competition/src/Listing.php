<?php
namespace HyperPress\Competition;

defined( 'ABSPATH' ) || exit;

use HyperPress\Season\Plugin as Season;
use HyperPress\Season\Settings as SeasonSettings;

/**
 * Finds the competitions for a list and puts them in order: unfinished ones by next start, then finished ones by date.
 */
final class Listing {

	private const PROGRAM = 'hyper_program';

	/**
	 * Public competitions matching the filters (published and without a password), in listing order.
	 *
	 * A filter that is empty is not applied, except `season`, which falls back to the current season. A filter naming a
	 * term that does not exist matches nothing. `program` is ignored unless its taxonomy is attached to competitions.
	 *
	 * @param array<string,string>    $filters Optional `season`, `district`, `type` and `program`: a term slug or name.
	 * @param \DateTimeImmutable|null $now     The moment unfinished competitions are told from finished ones; defaults to Schedule::now().
	 *
	 * @return list<int> Competition post IDs.
	 */
	public static function ids( array $filters, ?\DateTimeImmutable $now = null ): array {
		$filters = array_merge(
			array(
				'season'   => '',
				'district' => '',
				'type'     => '',
				'program'  => '',
			),
			$filters
		);
		if ( '' === trim( $filters['season'] ) ) {
			$filters['season'] = SeasonSettings::current();
		}

		$taxonomies = array(
			'season'   => Season::TAXONOMY,
			'district' => Plugin::DISTRICT,
			'type'     => Plugin::TYPE,
			'program'  => self::PROGRAM,
		);
		$tax_query  = array();
		foreach ( $taxonomies as $filter => $taxonomy ) {
			$value = trim( $filters[ $filter ] );
			if ( '' === $value || ( self::PROGRAM === $taxonomy && ! self::program_applies() ) ) {
				continue;
			}
			$term = self::term( $taxonomy, $value );
			if ( null === $term ) {
				return array();
			}
			$tax_query[] = array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => array( $term->term_id ),
			);
		}

		$query = new \WP_Query(
			array(
				'post_type'           => Plugin::POST_TYPE,
				'post_status'         => 'publish',
				'has_password'        => false,
				'posts_per_page'      => -1,
				'fields'              => 'ids',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'tax_query'           => $tax_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Bounded by the number of competitions.
			)
		);

		return self::sort( array_map( 'intval', $query->posts ), $now ?? Schedule::now() );
	}

	/**
	 * Orders competitions: unfinished ones by the start of their next unfinished day, then finished ones by first start,
	 * then ones without days; ties by title.
	 *
	 * @param array<int,int>     $ids Competition post IDs.
	 * @param \DateTimeImmutable $now The moment unfinished competitions are told from finished ones.
	 *
	 * @return list<int>
	 */
	public static function sort( array $ids, \DateTimeImmutable $now ): array {
		$keys = array();
		foreach ( $ids as $id ) {
			$keys[ $id ] = self::sort_key( $id, $now );
		}
		usort( $ids, static fn( int $a, int $b ): int => $keys[ $a ] <=> $keys[ $b ] );

		return $ids;
	}

	/**
	 * The sort key of one competition.
	 *
	 * @param int                $id  Competition post ID.
	 * @param \DateTimeImmutable $now The current moment.
	 *
	 * @return array{0:int,1:int,2:string} Group (0 unfinished, 1 finished, 2 undated), timestamp, lower-cased title.
	 */
	private static function sort_key( int $id, \DateTimeImmutable $now ): array {
		$title = strtolower( get_the_title( $id ) );
		$days  = Schedule::days( $id );
		if ( array() === $days ) {
			return array( 2, 0, $title );
		}
		foreach ( $days as $day ) {
			if ( $day['end'] > $now ) {
				return array( 0, $day['start']->getTimestamp(), $title );
			}
		}

		return array( 1, $days[0]['start']->getTimestamp(), $title );
	}

	/**
	 * Whether the program taxonomy exists and is attached to competitions (the admin attaches it in the Program settings).
	 */
	private static function program_applies(): bool {
		return taxonomy_exists( self::PROGRAM ) && is_object_in_taxonomy( Plugin::POST_TYPE, self::PROGRAM );
	}

	/**
	 * The term whose slug, else name, is `$value`, or null.
	 *
	 * @param string $taxonomy Taxonomy key.
	 * @param string $value    Slug or name.
	 */
	private static function term( string $taxonomy, string $value ): ?\WP_Term {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return null;
		}
		$term = get_term_by( 'slug', sanitize_title( $value ), $taxonomy );
		if ( ! $term instanceof \WP_Term ) {
			$term = get_term_by( 'name', $value, $taxonomy );
		}

		return $term instanceof \WP_Term ? $term : null;
	}
}
