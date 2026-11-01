<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * Shared pieces of an archive-banner settings page: the option defaults, the banner subtitle accessor,
 * the settings page entry, the subtitle field and the archive hero image. One instance per plugin.
 */
final class ArchiveBannerSettings {

	/**
	 * Binds the helper to one plugin's option, subtitle field and optional hero image field.
	 *
	 * @param string $option           Option name of the plugin's settings page.
	 * @param string $subtitle_key     Field id (option key) of the banner subtitle.
	 * @param string $subtitle_default Default banner subtitle.
	 * @param string $image_key        Field id (option key) of the archive hero image; '' for none.
	 */
	public function __construct(
		private readonly string $option,
		private readonly string $subtitle_key,
		private readonly string $subtitle_default,
		private readonly string $image_key = ''
	) {}

	/**
	 * Default values, keyed by field id.
	 *
	 * @return array<string,mixed>
	 */
	public function defaults(): array {
		return array(
			$this->subtitle_key => $this->subtitle_default,
		);
	}

	/**
	 * Filter on default_option_{option}: the defaults, unless the caller passed its own.
	 *
	 * @param mixed  $fallback       The default value.
	 * @param string $option         Option name.
	 * @param bool   $passed_default Whether get_option() was given an explicit default.
	 * @return mixed
	 */
	public function default_option( $fallback, $option = '', $passed_default = false ) {
		unset( $option );
		return $passed_default ? $fallback : $this->defaults();
	}

	/**
	 * The banner subtitle.
	 *
	 * Option row missing: the default_option filter makes get_option() return the defaults, so the default
	 * text shows until the page is saved. Row present but key missing: Meta Box drops the key of an empty
	 * field, so the editor cleared it and the subtitle is ''. Option not an array, or value not a string:
	 * the default.
	 */
	public function banner_subtitle(): string {
		$option = get_option( $this->option );
		if ( ! is_array( $option ) ) {
			return $this->subtitle_default;
		}
		if ( ! array_key_exists( $this->subtitle_key, $option ) ) {
			return '';
		}
		return is_string( $option[ $this->subtitle_key ] ) ? $option[ $this->subtitle_key ] : $this->subtitle_default;
	}

	/**
	 * The mb_settings_pages entry for a plugin's settings page under the hyperPress menu.
	 *
	 * @param string $id     Page id.
	 * @param string $option Option name.
	 * @param string $title  Menu and page title (already translated).
	 * @return array<string,mixed>
	 */
	public static function page( string $id, string $option, string $title ): array {
		return array(
			'id'          => $id,
			'option_name' => $option,
			'menu_title'  => $title,
			'page_title'  => $title,
			'parent'      => AdminMenu::SLUG,
			'capability'  => 'manage_options',
			'style'       => 'no-boxes',
		);
	}

	/**
	 * The banner subtitle meta box field.
	 *
	 * @param string $name     Field label.
	 * @param string $desc     Field description.
	 * @param bool   $with_std Whether to set `std` to the default subtitle.
	 * @return array<string,mixed>
	 */
	public function subtitle_field( string $name, string $desc, bool $with_std = false ): array {
		$field = array(
			'id'                => $this->subtitle_key,
			'name'              => $name,
			'desc'              => $desc,
			'type'              => 'textarea',
			'raw'               => true,
			'sanitize_callback' => array( Banner::class, 'sanitize_subtitle' ),
		);
		if ( $with_std ) {
			$field['std'] = $this->subtitle_default;
		}
		return $field;
	}

	/**
	 * The archive hero image attachment ID.
	 *
	 * 0 when no image key is configured, the option is not an array (including a missing row), the key is
	 * absent (Meta Box drops the key of a cleared field), or the stored value is not an image attachment.
	 */
	public function image_id(): int {
		if ( '' === $this->image_key ) {
			return 0;
		}
		$option = get_option( $this->option );
		if ( ! is_array( $option ) || ! array_key_exists( $this->image_key, $option ) ) {
			return 0;
		}
		return ArchiveImage::valid_id( $option[ $this->image_key ] );
	}

	/**
	 * The archive hero image meta box field.
	 *
	 * @param string $name Field label.
	 * @param string $desc Field description.
	 * @return array<string,mixed>
	 */
	public function image_field( string $name, string $desc ): array {
		return ArchiveImage::field( $this->image_key, $name, $desc );
	}
}
