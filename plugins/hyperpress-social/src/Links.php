<?php
namespace HyperPress\Social;

defined( 'ABSPATH' ) || exit;

/**
 * The social links configured on the Social settings page, ready to print.
 */
final class Links {

	/**
	 * Every social with a non-empty URL, in display order.
	 *
	 * Each item is `key` (the suffix after `hyperpress_social_`), `icon` (Font Awesome classes, '' for an SVG social),
	 * `svg` (name of an `assets/icons/<name>.svg` file printed by {@see Icons::svg()}, '' for a Font Awesome social),
	 * `svg_width` (optional CSS width in em for an SVG social, e.g. `1.45em`, '' when not set, see {@see Icons}),
	 * `title`, `url`, `target` (`_self` for links to this site, else `_blank`) and `rel` (`noopener noreferrer` for `_blank`, else '').
	 *
	 * @return array<int, array{key: string, icon: string, svg: string, svg_width: string, title: string, url: string, target: string, rel: string}>
	 */
	public static function all(): array {
		$socials = array(
			array(
				'key'       => 'firstinspires',
				'svg'       => 'firstinspires',
				'svg_width' => '1.45em',
				'title'     => 'FIRST Inspires',
			),
			array(
				'key'   => 'thebluealliance',
				'svg'   => 'thebluealliance',
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
			$url = 'contact' === $social['key'] ? self::contact_url() : Settings::url( $social['key'] );

			if ( empty( $url ) ) {
				continue;
			}

			$url    = (string) $url;
			$target = \HyperPress\Utils\Link::target( $url );

			$links[] = array(
				'key'       => $social['key'],
				'icon'      => $social['icon'] ?? '',
				'svg'       => $social['svg'] ?? '',
				'svg_width' => $social['svg_width'] ?? '',
				'title'     => $social['title'],
				'url'       => $url,
				'target'    => $target,
				'rel'       => \HyperPress\Utils\Link::rel( $target ),
			);
		}

		return $links;
	}

	/**
	 * Permalink of the contact page from the Social settings page, or an empty string when none is set or it is not published.
	 */
	private static function contact_url(): string {
		$page_id = Settings::contact_page();

		if ( 0 === $page_id || 'publish' !== get_post_status( $page_id ) ) {
			return '';
		}

		$link = get_permalink( $page_id );

		return false === $link ? '' : $link;
	}
}
