<?php
namespace HyperPress\Tests\Integration\Social;

use HyperPress\Social\Settings;
use WP_UnitTestCase;

final class SocialSettingsPageTest extends WP_UnitTestCase {

	/**
	 * The Social boxes from the Meta Box filter.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function boxes(): array {
		return array_values(
			array_filter(
				apply_filters( 'rwmb_meta_boxes', array() ),
				static fn( $box ) => 'hyperpress_social_box' === ( $box['id'] ?? '' )
			)
		);
	}

	public function test_the_settings_page_is_registered_under_the_hyperpress_menu(): void {
		$pages = array_values(
			array_filter(
				apply_filters( 'mb_settings_pages', array() ),
				static fn( $page ) => 'hyperpress_social' === ( $page['id'] ?? '' )
			)
		);

		$this->assertCount( 1, $pages );
		$this->assertSame( 'hyperpress_social', $pages[0]['option_name'] );
		$this->assertSame( 'Social', $pages[0]['menu_title'] );
		$this->assertSame( 'Social', $pages[0]['page_title'] );
		$this->assertSame( 'hyperpress', $pages[0]['parent'] );
		$this->assertSame( 'manage_options', $pages[0]['capability'] );
		$this->assertSame( 'no-boxes', $pages[0]['style'] );
	}

	public function test_one_box_is_attached_to_the_settings_page(): void {
		$boxes = $this->boxes();

		$this->assertCount( 1, $boxes );
		$this->assertSame( 'hyperpress_social', $boxes[0]['settings_pages'] );
	}

	public function test_the_box_has_the_twelve_fields_in_order(): void {
		$expected = array(
			'hyperpress_social_firstinspires',
			'hyperpress_social_thebluealliance',
			'hyperpress_social_facebook',
			'hyperpress_social_instagram',
			'hyperpress_social_youtube',
			'hyperpress_social_twitter',
			'hyperpress_social_github',
			'hyperpress_social_snapchat',
			'hyperpress_social_tiktok',
			'hyperpress_social_discord',
			'hyperpress_social_contact',
			'hyperpress_social_add_calendar',
		);

		$this->assertSame( $expected, wp_list_pluck( $this->boxes()[0]['fields'], 'id' ) );
	}

	public function test_the_url_fields_are_url_inputs_with_the_url_sanitizer_and_old_labels(): void {
		$labels = array(
			'hyperpress_social_firstinspires'   => 'FIRST Inspires URL',
			'hyperpress_social_thebluealliance' => 'TheBlueAlliance URL',
			'hyperpress_social_facebook'        => 'Facebook URL',
			'hyperpress_social_instagram'       => 'Instagram URL',
			'hyperpress_social_youtube'         => 'YouTube URL',
			'hyperpress_social_twitter'         => 'Twitter URL',
			'hyperpress_social_github'          => 'GitHub URL',
			'hyperpress_social_snapchat'        => 'Snapchat URL',
			'hyperpress_social_tiktok'          => 'TikTok URL',
			'hyperpress_social_discord'         => 'Discord URL',
			'hyperpress_social_add_calendar'    => 'Add Calendar URL',
		);

		$seen = 0;
		foreach ( $this->boxes()[0]['fields'] as $field ) {
			if ( 'hyperpress_social_contact' === $field['id'] ) {
				continue;
			}
			++$seen;
			$this->assertSame( 'url', $field['type'], $field['id'] );
			$this->assertSame( 'esc_url_raw', $field['sanitize_callback'], $field['id'] );
			$this->assertSame( $labels[ $field['id'] ], $field['name'], $field['id'] );
			$this->assertArrayNotHasKey( 'std', $field, $field['id'] );
		}
		$this->assertSame( 11, $seen );
	}

	public function test_the_settings_class_lists_the_same_networks_and_labels(): void {
		$url_fields = array_values(
			array_filter(
				$this->boxes()[0]['fields'],
				static fn( $field ) => 'hyperpress_social_contact' !== $field['id']
			)
		);

		$this->assertCount( 11, Settings::NETWORKS );
		$this->assertSame( wp_list_pluck( $url_fields, 'id' ), array_map( array( Settings::class, 'field_id' ), array_keys( Settings::NETWORKS ) ) );
		$this->assertSame( wp_list_pluck( $url_fields, 'name' ), array_values( Settings::NETWORKS ) );
		$this->assertSame( 'hyperpress_social_contact', Settings::field_id( Settings::CONTACT ) );
	}

	public function test_the_contact_field_is_a_single_page_picker(): void {
		$contact = null;
		foreach ( $this->boxes()[0]['fields'] as $field ) {
			if ( 'hyperpress_social_contact' === $field['id'] ) {
				$contact = $field;
			}
		}

		$this->assertIsArray( $contact );
		$this->assertSame( 'Contact Page', $contact['name'] );
		$this->assertSame( 'post', $contact['type'] );
		$this->assertSame( 'page', $contact['post_type'] );
		$this->assertSame( 'select_advanced', $contact['field_type'] );
		$this->assertEmpty( $contact['multiple'] ?? false );
		$this->assertSame( 'Select a page', $contact['placeholder'] );
		$this->assertSame(
			array(
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			),
			$contact['query_args']
		);
		$this->assertSame( 'absint', $contact['sanitize_callback'] );
	}
}
