<?php
namespace HyperPress\Sponsor;

defined( 'ABSPATH' ) || exit;

/**
 * Derives the group a sponsor belongs to in the archive (newest season, highest tier) and the headings to
 * print when that group changes. Ranking is delegated to Ordering so it matches the SQL order.
 */
final class Grouping {

	private const SEASON_TAXONOMY = 'hyper_season';

	/**
	 * The (season, tier) group of a sponsor: its newest season and highest tier, or null when it has none.
	 *
	 * @param int $post_id Sponsor post ID.
	 * @return array{season:?\WP_Term,tier:?\WP_Term}
	 */
	public static function keys( int $post_id ): array {
		return array(
			'season' => self::best( $post_id, self::SEASON_TAXONOMY, array( Ordering::class, 'season_compare' ) ),
			'tier'   => self::best( $post_id, Plugin::TIER, array( Ordering::class, 'tier_compare' ) ),
		);
	}

	/**
	 * Headings to print before a sponsor, in print order (season h2, then tier h3). Texts are raw (unescaped).
	 *
	 * @param array{season:?\WP_Term,tier:?\WP_Term}      $current       Keys of the sponsor.
	 * @param array{season:?\WP_Term,tier:?\WP_Term}|null $previous      Keys of the preceding sponsor, null for the first.
	 * @param bool                                        $season_scoped Whether the archive is limited to one season.
	 * @param bool                                        $page_start    Whether the sponsor is first on its page.
	 * @return array<int,array{tag:string,text:string}>
	 */
	public static function headings( array $current, ?array $previous, bool $season_scoped, bool $page_start ): array {
		$season_changed = null === $previous || self::term_id( $current['season'] ) !== self::term_id( $previous['season'] );
		// In a scoped archive the SQL order drops the season key, so the season plays no role at all there.
		$tier_changed = null === $previous
			|| ( ! $season_scoped && $season_changed )
			|| self::term_id( $current['tier'] ) !== self::term_id( $previous['tier'] );

		$headings = array();
		if ( ! $season_scoped && ( $season_changed || $page_start ) ) {
			$headings[] = array(
				'tag'  => 'h2',
				'text' => self::label( 'hyperpress_sponsor_archive_season_label', $current['season'], ! $season_changed ),
			);
		}
		if ( $tier_changed || $page_start ) {
			$headings[] = array(
				'tag'  => 'h3',
				'text' => self::label( 'hyperpress_sponsor_archive_tier_label', $current['tier'], ! $tier_changed ),
			);
		}

		return $headings;
	}

	/**
	 * Best term of a taxonomy for a sponsor under the given comparer, or null when it has none.
	 *
	 * @param int      $post_id  Sponsor post ID.
	 * @param string   $taxonomy Taxonomy to read.
	 * @param callable $comparer Comparer that sorts the best term first.
	 */
	private static function best( int $post_id, string $taxonomy, callable $comparer ): ?\WP_Term {
		// get_the_terms() reads the object term cache primed by the main query (wp_get_post_terms() does not).
		$terms = get_the_terms( $post_id, $taxonomy );
		if ( ! is_array( $terms ) || ! $terms ) {
			return null;
		}
		usort( $terms, $comparer );

		return $terms[0];
	}

	private static function term_id( ?\WP_Term $term ): ?int {
		return $term ? (int) $term->term_id : null;
	}

	private static function label( string $filter, ?\WP_Term $term, bool $continued ): string {
		$text = $term ? $term->name : __( 'Other sponsors', 'hyperpress' );
		$text = (string) apply_filters( $filter, $text, $term );
		if ( $continued ) {
			/* translators: %s: season or tier heading that continues from the previous page. */
			$text = sprintf( __( '%s (continued)', 'hyperpress' ), $text );
		}

		return $text;
	}
}
