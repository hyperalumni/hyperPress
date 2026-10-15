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

	/** @var callable(string): bool */
	private $probe;

	/** Memoized dev decision: the probe runs at most once per instance (one HTTP check per request). */
	private ?bool $dev = null;

	/**
	 * @param callable(string): bool|null $probe Reachability check for the dev server's internal URL.
	 */
	public function __construct(
		private readonly string $themeDir,
		private readonly string $themeUri,
		?callable $probe = null
	) {
		$this->probe = $probe ?? [ self::class, 'defaultProbe' ];
	}

	public function isDev(): bool {
		if ( null === $this->dev ) {
			$marker    = $this->constantDev() ? null : $this->readMarker();
			$this->dev = $this->constantDev() || ( null !== $marker && (bool) ( $this->probe )( $marker['internal'] ) );
		}
		return $this->dev;
	}

	/**
	 * @return array{browser: string, internal: string}|null
	 */
	public function devServer(): ?array {
		$marker = $this->readMarker();
		if ( null !== $marker ) {
			return $marker;
		}
		return $this->constantDev()
			? [
				'browser'  => self::DEFAULT_BROWSER,
				'internal' => self::DEFAULT_INTERNAL,
			]
			: null;
	}

	/**
	 * @return array{file: string, css: list<string>}|null
	 */
	public function entry( string $key ): ?array {
		$manifest = $this->readManifest();
		if ( null === $manifest || ! isset( $manifest[ $key ]['file'] ) || ! is_string( $manifest[ $key ]['file'] ) ) {
			return null;
		}
		$css = [];
		$this->collectCss( $manifest, $key, $css, [] );
		return [
			'file' => $this->url( $manifest[ $key ]['file'] ),
			'css'  => array_map( fn( string $file ): string => $this->url( $file ), array_values( array_unique( $css ) ) ),
		];
	}

	public function notice(): ?string {
		if ( $this->isDev() ) {
			return null;
		}
		$missing = [];
		if ( ! is_file( $this->themeDir . '/.vite/manifest.json' ) ) {
			$missing[] = '.vite/manifest.json (run the Vite build)';
		}
		if ( ! is_file( $this->themeDir . '/vendor/autoload.php' ) ) {
			$missing[] = 'vendor/autoload.php (run composer install)';
		}
		return [] === $missing ? null : 'hyperPress UIkit theme: missing ' . implode( ' and ', $missing ) . '.';
	}

	public function register(): void {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
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

	public function enqueue(): void {
		$dev = $this->isDev() ? $this->devServer() : null;
		if ( null !== $dev ) {
			wp_enqueue_script( 'hp-uikit-vite-client', $dev['browser'] . '/@vite/client', [], null, true );
			wp_enqueue_script( 'hp-uikit-main', $dev['browser'] . '/' . self::ENTRY, [], null, true );
			return;
		}
		$entry = $this->entry( self::ENTRY );
		if ( null === $entry ) {
			return;
		}
		foreach ( $entry['css'] as $i => $css ) {
			wp_enqueue_style( 'hp-uikit-style-' . $i, $css, [], null );
		}
		wp_enqueue_script( 'hp-uikit-main', $entry['file'], [], null, true );
	}

	/**
	 * The URL the default probe requests. Vite has no index.html here, so its root answers 404;
	 * the client script is what a healthy dev server serves.
	 */
	public static function probeUrl( string $internal ): string {
		return rtrim( $internal, '/' ) . '/@vite/client';
	}

	public static function defaultProbe( string $url ): bool {
		if ( ! function_exists( 'wp_remote_get' ) ) {
			return false;
		}
		$response = wp_remote_get( self::probeUrl( $url ), [ 'timeout' => 0.5 ] );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$code = (int) wp_remote_retrieve_response_code( $response );
		return $code >= 200 && $code < 400;
	}

	private function constantDev(): bool {
		return defined( 'VITE_DEV' ) && true === constant( 'VITE_DEV' );
	}

	private function url( string $file ): string {
		return rtrim( $this->themeUri, '/' ) . '/' . ltrim( $file, '/' );
	}

	/**
	 * @return array{browser: string, internal: string}|null
	 */
	private function readMarker(): ?array {
		$data = $this->readJson( $this->themeDir . '/.vite-dev' );
		if ( ! isset( $data['browser'], $data['internal'] ) || ! is_string( $data['browser'] ) || ! is_string( $data['internal'] ) ) {
			return null;
		}
		return [
			'browser'  => $data['browser'],
			'internal' => $data['internal'],
		];
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function readManifest(): ?array {
		return $this->readJson( $this->themeDir . '/.vite/manifest.json' );
	}

	/**
	 * @return array<string, mixed>|null
	 */
	private function readJson( string $path ): ?array {
		$raw = is_file( $path ) ? file_get_contents( $path ) : false;
		if ( false === $raw ) {
			return null;
		}
		$data = json_decode( $raw, true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Imported chunks first (depth first), then the chunk's own CSS.
	 *
	 * @param array<string, mixed> $manifest
	 * @param list<string>         $css
	 * @param array<string, true>  $seen
	 */
	private function collectCss( array $manifest, string $key, array &$css, array $seen ): void {
		if ( isset( $seen[ $key ] ) || ! isset( $manifest[ $key ] ) || ! is_array( $manifest[ $key ] ) ) {
			return;
		}
		$seen[ $key ] = true;
		$chunk        = $manifest[ $key ];
		foreach ( (array) ( $chunk['imports'] ?? [] ) as $import ) {
			if ( is_string( $import ) ) {
				$this->collectCss( $manifest, $import, $css, $seen );
			}
		}
		foreach ( (array) ( $chunk['css'] ?? [] ) as $file ) {
			if ( is_string( $file ) ) {
				$css[] = $file;
			}
		}
	}
}
