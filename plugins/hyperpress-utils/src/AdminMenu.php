<?php
namespace HyperPress\Utils;

defined( 'ABSPATH' ) || ( defined( 'HYPERPRESS_TESTING' ) || exit );

/**
 * The top-level "hyperPress" wp-admin menu.
 *
 * Other plugins attach their settings pages to it with `parent => AdminMenu::SLUG`.
 */
final class AdminMenu {

	public const SLUG = 'hyperpress';

	public static function boot(): void {
		// Early, so the parent exists (and "Overview" comes first) before children attach at the default priority.
		add_action( 'admin_menu', array( self::class, 'register' ), 5 );
	}

	public static function register(): void {
		add_menu_page(
			__( 'hyperPress', 'hyperpress' ),
			__( 'hyperPress', 'hyperpress' ),
			'manage_options',
			self::SLUG,
			array( self::class, 'render' ),
			'dashicons-admin-settings',
			80
		);
		// Without this, WordPress repeats the parent's name as the first submenu item.
		add_submenu_page(
			self::SLUG,
			__( 'hyperPress', 'hyperpress' ),
			__( 'Overview', 'hyperpress' ),
			'manage_options',
			self::SLUG,
			array( self::class, 'render' )
		);
	}

	/**
	 * The child settings pages the current user can open, as slug => title.
	 *
	 * @return array<string,string>
	 */
	public static function children(): array {
		global $submenu;

		$children = array();
		foreach ( (array) ( $submenu[ self::SLUG ] ?? array() ) as $item ) {
			if ( self::SLUG === $item[2] || ! current_user_can( $item[1] ) ) {
				continue;
			}
			$children[ $item[2] ] = wp_strip_all_tags( $item[0] );
		}
		return $children;
	}

	public static function render(): void {
		$children = self::children();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'hyperPress', 'hyperpress' ); ?></h1>
			<?php if ( empty( $children ) ) : ?>
				<p><?php esc_html_e( 'No settings pages yet.', 'hyperpress' ); ?></p>
			<?php else : ?>
				<ul>
					<?php foreach ( $children as $slug => $title ) : ?>
						<li>
							<a href="<?php echo esc_url( false !== strpos( $slug, '.php' ) ? admin_url( $slug ) : admin_url( 'admin.php?page=' . $slug ) ); ?>"><?php echo esc_html( $title ); ?></a>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>
		<?php
	}
}
