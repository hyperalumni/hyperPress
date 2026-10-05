<?php
namespace HyperPress\Socials;

defined( 'ABSPATH' ) || exit;

/**
 * The social links configured in the Customizer Socials section, ready to print.
 */
final class Links {

	/**
	 * Every social with a non-empty URL, in display order.
	 *
	 * Each item is `key` (the suffix after `hyperpress_socials_`), `icon` (Font Awesome classes), `title`, `url`,
	 * `target` (`_self` for links to this site, else `_blank`) and `rel` (`noopener noreferrer` for `_blank`, else '').
	 *
	 * @return array<int, array{key: string, icon: string, title: string, url: string, target: string, rel: string}>
	 */
	public static function all(): array {
		$socials = array(
			array(
				'key'   => 'firstinspires',
				'icon'  => 'fa-solid fa-robot',
				'title' => 'FIRST Inspires',
			),
			array(
				'key'   => 'thebluealliance',
				'icon'  => 'fa-regular fa-lightbulb',
				'title' => 'The Blue Alliance',
			),
			array(
				'key'   => 'github',
				'icon'  => 'fa-brands fa-github',
				'title' => 'GitHub',
			),
			array(
				'key'   => 'facebook',
				'icon'  => 'fa-brands fa-facebook-f',
				'title' => 'Facebook',
			),
			array(
				'key'   => 'instagram',
				'icon'  => 'fa-brands fa-instagram',
				'title' => 'Instagram',
			),
			array(
				'key'   => 'twitter',
				'icon'  => 'fa-brands fa-twitter',
				'title' => 'Twitter',
			),
			array(
				'key'   => 'youtube',
				'icon'  => 'fa-brands fa-youtube',
				'title' => 'YouTube',
			),
			array(
				'key'   => 'snapchat',
				'icon'  => 'fa-brands fa-snapchat',
				'title' => 'Snapchat',
			),
			array(
				'key'   => 'tiktok',
				'icon'  => 'fa-brands fa-tiktok',
				'title' => 'TikTok',
			),
			array(
				'key'   => 'discord',
				'icon'  => 'fa-brands fa-discord',
				'title' => 'Discord Server',
			),
			array(
				'key'   => 'contact',
				'icon'  => 'fa-regular fa-envelope',
				'title' => 'Contact Us',
			),
			array(
				'key'   => 'add_calendar',
				'icon'  => 'fa-regular fa-calendar',
				'title' => 'Add Our Calendar',
			),
		);

		$links = array();

		foreach ( $socials as $social ) {
			$url = 'contact' === $social['key'] ? self::contact_url() : get_theme_mod( 'hyperpress_socials_' . $social['key'] );

			if ( empty( $url ) ) {
				continue;
			}

			$url    = (string) $url;
			$target = self::is_internal( $url ) ? '_self' : '_blank';

			$links[] = array(
				'key'    => $social['key'],
				'icon'   => $social['icon'],
				'title'  => $social['title'],
				'url'    => $url,
				'target' => $target,
				'rel'    => '_blank' === $target ? 'noopener noreferrer' : '',
			);
		}

		return $links;
	}

	/**
	 * Permalink of the Customizer contact page, or an empty string when none is set or it is not published.
	 */
	private static function contact_url(): string {
		$page_id = absint( get_theme_mod( 'hyperpress_socials_contact' ) );

		if ( 0 === $page_id || 'publish' !== get_post_status( $page_id ) ) {
			return '';
		}

		$link = get_permalink( $page_id );

		return false === $link ? '' : $link;
	}

	/**
	 * True when the URL points at this site: a relative URL, or one whose host equals the site's host.
	 * Comparing hosts (not substrings) stops `https://other.example/?r=<site url>` passing as internal.
	 *
	 * @param string $url Link URL.
	 */
	private static function is_internal( string $url ): bool {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( null === $host || false === $host ) {
			// No host: a relative URL (or an unparseable one, which esc_url() will reject).
			return 0 === strpos( $url, '/' ) && 0 !== strpos( $url, '//' );
		}

		return strtolower( $host ) === strtolower( (string) wp_parse_url( get_site_url(), PHP_URL_HOST ) );
	}
}
