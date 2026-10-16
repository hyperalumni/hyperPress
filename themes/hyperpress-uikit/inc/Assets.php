<?php
declare(strict_types=1);

namespace HyperPress\Uikit;

/**
 * Resolves the theme's Vite assets: the dev server (marker file or constant) or the production manifest.
 */
final class Assets {

	private const ENTRY            = 'src/main.ts';
	private const DEFAULT_BROWSER  = 'http://localhost:5173';
	private const DEFAULT_INTERNAL = 'http://host.docker.internal:5173';

	/**
	 * Reachability check for the dev server's internal URL.
	 *
	 * @var callable(string): bool
	 */
	private $probe;

	/**
	 * Memoized dev decision: the probe runs at most once per instance (one HTTP check per request).
	 *
	 * @var bool|null
	 */
	private ?bool $dev = null;

	/**
	 * Creates the resolver.
	 *
	 * @param string                      $themeDir Absolute path of the theme directory.
	 * @param string                      $themeUri URL of the theme directory.
	 * @param callable(string): bool|null $probe    Reachability check for the dev server's internal URL.
	 */
	public function __construct(
		private readonly string $themeDir,
		private readonly string $themeUri,
		?callable $probe = null
	) {
		$this->probe = $probe ?? array( self::class, 'defaultProbe' );
	}

	/**
	 * Whether the Vite dev server serves the assets.
	 *
	 * @return bool
	 */
	public function isDev(): bool {
		if ( null === $this->dev ) {
			$marker    = $this->constantDev() ? null : $this->readMarker();
			$this->dev = $this->constantDev() || ( null !== $marker && (bool) ( $this->probe )( $marker['internal'] ) );
		}
		return $this->dev;
	}

	/**
	 * The dev server's browser and internal URLs, or null when it is not configured.
	 *
	 * @return array{browser: string, internal: string}|null
	 */
	public function devServer(): ?array {
		$marker = $this->readMarker();
		if ( null !== $marker ) {
			return $marker;
		}
		return $this->constantDev()
			? array(
				'browser'  => self::DEFAULT_BROWSER,
				'internal' => self::DEFAULT_INTERNAL,
			)
			: null;
	}

	/**
	 * The production URLs of an entry's script and its stylesheets, or null without a manifest entry.
	 *
	 * @param string $key Manifest key of the entry.
	 * @return array{file: string, css: list<string>}|null
	 */
	public function entry( string $key ): ?array {
		$manifest = $this->readManifest();
		if ( null === $manifest || ! isset( $manifest[ $key ]['file'] ) || ! is_string( $manifest[ $key ]['file'] ) ) {
			return null;
		}
		$css = array();
		$this->collectCss( $manifest, $key, $css, array() );
		return array(
			'file' => $this->url( $manifest[ $key ]['file'] ),
			'css'  => array_map( fn( string $file ): string => $this->url( $file ), array_values( array_unique( $css ) ) ),
		);
	}

	/**
	 * Admin notice text when the build output or Composer autoloader is missing.
	 *
	 * @return string|null
	 */
	public function notice(): ?string {
		if ( $this->isDev() ) {
			return null;
		}
		$missing = array();
		if ( ! is_file( $this->themeDir . '/.vite/manifest.json' ) ) {
			$missing[] = '.vite/manifest.json (run the Vite build)';
		}
		if ( ! is_file( $this->themeDir . '/vendor/autoload.php' ) ) {
			$missing[] = 'vendor/autoload.php (run composer install)';
		}
		return array() === $missing ? null : 'hyperPress UIkit theme: missing ' . implode( ' and ', $missing ) . '.';
	}

	/**
	 * Hooks the asset enqueueing, the module script tag and the admin notice.
	 */
	public function register(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action(
			'admin_notices',
			function (): void {
				$notice = $this->notice();
				if ( null !== $notice ) {
					printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html( $notice ) );
				}
			}
		);
		add_filter(
			'script_loader_tag',
			static fn( string $tag, string $handle ): string => str_starts_with( $handle, 'hp-uikit' )
				? str_replace( ' src=', ' type="module" src=', $tag )
				: $tag,
			10,
			2
		);
	}

	/**
	 * Enqueues the Vite dev client or the production entry.
	 */
	public function enqueue(): void {
		$dev = $this->isDev() ? $this->devServer() : null;
		if ( null !== $dev ) {
			wp_enqueue_script( 'hp-uikit-vite-client', $dev['browser'] . '/@vite/client', array(), null, true );
			wp_enqueue_script( 'hp-uikit-main', $dev['browser'] . '/' . self::ENTRY, array(), null, true );
			return;
		}
		$entry = $this->entry( self::ENTRY );
		if ( null === $entry ) {
			return;
		}
		foreach ( $entry['css'] as $i => $css ) {
			wp_enqueue_style( 'hp-uikit-style-' . $i, $css, array(), null );
		}
		wp_enqueue_script( 'hp-uikit-main', $entry['file'], array(), null, true );
	}

	/**
	 * The URL the default probe requests. Vite has no index.html here, so its root answers 404;
	 * the client script is what a healthy dev server serves.
	 *
	 * @param string $internal Internal URL of the dev server.
	 * @return string
	 */
	public static function probeUrl( string $internal ): string {
		return rtrim( $internal, '/' ) . '/@vite/client';
	}

	/**
	 * Checks that the dev server answers with a non-error status.
	 *
	 * @param string $url Internal URL of the dev server.
	 * @return bool
	 */
	public static function defaultProbe( string $url ): bool {
		if ( ! function_exists( 'wp_remote_get' ) ) {
			return false;
		}
		$response = wp_remote_get( self::probeUrl( $url ), array( 'timeout' => 0.5 ) );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 400;
	}

	/**
	 * Whether the VITE_DEV constant forces the dev server.
	 *
	 * @return bool
	 */
	private function constantDev(): bool {
		return defined( 'VITE_DEV' ) && true === constant( 'VITE_DEV' );
	}

	/**
	 * Absolute URL of a theme file.
	 *
	 * @param string $file Path relative to the theme.
	 * @return string
	 */
	private function url( string $file ): string {
		return rtrim( $this->themeUri, '/' ) . '/' . ltrim( $file, '/' );
	}

	/**
	 * Reads the dev server marker the Nx serve target writes.
	 *
	 * @return array{browser: string, internal: string}|null
	 */
	private function readMarker(): ?array {
		$data = $this->readJson( $this->themeDir . '/.vite-dev' );
		if ( ! isset( $data['browser'], $data['internal'] ) || ! is_string( $data['browser'] ) || ! is_string( $data['internal'] ) ) {
			return null;
		}
		return array(
			'browser'  => $data['browser'],
			'internal' => $data['internal'],
		);
	}

	/**
	 * Reads the Vite manifest of the production build.
	 *
	 * @return array<string, mixed>|null
	 */
	private function readManifest(): ?array {
		return $this->readJson( $this->themeDir . '/.vite/manifest.json' );
	}

	/**
	 * Reads and decodes a JSON file, or returns null when it is missing or not an object.
	 *
	 * @param string $path Absolute path of the file.
	 * @return array<string, mixed>|null
	 */
	private function readJson( string $path ): ?array {
		$raw = is_file( $path ) ? file_get_contents( $path ) : false; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local file, not a remote URL.
		if ( false === $raw ) {
			return null;
		}
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Imported chunks first (depth first), then the chunk's own CSS.
	 *
	 * @param array<string, mixed> $manifest Parsed Vite manifest.
	 * @param string               $key      Manifest key to collect from.
	 * @param array<int, string>   $css      Collected stylesheet files, by reference.
	 * @param array<string, true>  $seen      Keys already visited.
	 */
	private function collectCss( array $manifest, string $key, array &$css, array $seen ): void {
		if ( isset( $seen[ $key ] ) || ! isset( $manifest[ $key ] ) || ! is_array( $manifest[ $key ] ) ) {
			return;
		}
		$seen[ $key ] = true;
		$chunk        = $manifest[ $key ];
		foreach ( (array) ( $chunk['imports'] ?? array() ) as $import ) {
			if ( is_string( $import ) ) {
				$this->collectCss( $manifest, $import, $css, $seen );
			}
		}
		foreach ( (array) ( $chunk['css'] ?? array() ) as $file ) {
			if ( is_string( $file ) ) {
				$css[] = $file;
			}
		}
	}
}
