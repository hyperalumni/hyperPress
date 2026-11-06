<?php
namespace HyperPress\ThemeTests\Integration\Concerns;

require_once __DIR__ . '/../stubs/nextgen.php';

/** Shared helper for the tests that render the theme's `nggallery/` templates. */
trait NggalleryTemplateHelpers {

	/**
	 * Renders a `nggallery/` template with the given variables and returns its output.
	 *
	 * @param string              $file Template file name inside `nggallery/`.
	 * @param array<string,mixed> $vars Variables made available to the template.
	 */
	private function render( string $file, array $vars ): string {
		$path = dirname( __DIR__, 3 ) . '/nggallery/' . $file;
		ob_start();
		( static function ( string $template_file, array $template_vars ): void {
			extract( $template_vars ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $template_file;
		} )( $path, $vars );
		return (string) ob_get_clean();
	}
}
