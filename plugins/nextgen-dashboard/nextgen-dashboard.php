<?php
/**
 * Plugin Name: NextGen Dashboard
 * Plugin URI: https://nextgen-builds.ch
 * Description: Persönliches Control-Panel für die Startseite. Kacheln lassen sich über den Filter nextgen_dashboard_widgets ergänzen.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.1
 * Author: NextGen
 * Author URI: https://nextgen-builds.ch
 * Text Domain: nextgen-dashboard
 * Domain Path: /languages
 * Update URI: https://nextgen-builds.ch
 *
 * Eigene Kachel aus einem anderen Plugin:
 *
 * add_filter( 'nextgen_dashboard_widgets', function ( array $widgets ): array {
 *     $widgets[] = array(
 *         'id'               => 'mein-widget',
 *         'titel'            => 'Mein Widget',
 *         'icon'             => 'pulse',
 *         'grid-area'        => 'mein-widget',
 *         'render-callback'  => 'mein_plugin_render',
 *         'restricted'       => false,
 *     );
 *     return $widgets;
 * } );
 *
 * Eingebaute Flächen: status, plugins, updates, inhalt, deploy, log, sandbox, notes.
 * Eine neue Fläche braucht zusätzlich CSS (grid-template-areas), sonst liegt sie
 * in einer eigenen Zeile unter dem Raster.
 *
 * @package NextGen\Dashboard
 */

declare(strict_types=1);

namespace NextGen\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NEXTGEN_DASHBOARD_FILE', __FILE__ );
define( 'NEXTGEN_DASHBOARD_DIR', __DIR__ );
define( 'NEXTGEN_DASHBOARD_VERSION', '1.0.0' );

require_once NEXTGEN_DASHBOARD_DIR . '/includes/class-settings.php';
require_once NEXTGEN_DASHBOARD_DIR . '/includes/class-icons.php';
require_once NEXTGEN_DASHBOARD_DIR . '/includes/class-data.php';
require_once NEXTGEN_DASHBOARD_DIR . '/includes/class-widgets.php';
require_once NEXTGEN_DASHBOARD_DIR . '/includes/class-rest.php';
require_once NEXTGEN_DASHBOARD_DIR . '/includes/class-block.php';

/**
 * Legt leere Einstellungen an. Ein vorhandenes Token bleibt erhalten.
 */
function activate(): void {
	if ( null === get_option( Settings::OPTION, null ) ) {
		add_option(
			Settings::OPTION,
			array(
				'github_repo'  => '',
				'github_token' => '',
			)
		);
	}
}

/**
 * Deaktivierung löscht keine Notizen und kein Token.
 * Beides entfernt erst uninstall.php.
 */
function deactivate(): void {
	// Keine Optionen löschen. Siehe uninstall.php.
}

register_activation_hook( NEXTGEN_DASHBOARD_FILE, __NAMESPACE__ . '\\activate' );
register_deactivation_hook( NEXTGEN_DASHBOARD_FILE, __NAMESPACE__ . '\\deactivate' );

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain(
			'nextgen-dashboard',
			false,
			dirname( plugin_basename( NEXTGEN_DASHBOARD_FILE ) ) . '/languages'
		);

		Block::register();
	}
);

add_action( 'admin_menu', array( Settings::class, 'add_page' ) );
add_action( 'admin_init', array( Settings::class, 'register' ) );
add_action( 'rest_api_init', array( Rest::class, 'register' ) );
add_filter( 'nextgen_dashboard_widgets', array( Widgets::class, 'defaults' ) );
add_filter( 'body_class', array( Block::class, 'body_class' ) );
add_action( 'wp_head', array( Block::class, 'boot_theme' ), 1 );
add_action( 'wp_enqueue_scripts', array( Block::class, 'enqueue' ) );
add_action( 'template_redirect', array( Block::class, 'nocache_for_admins' ) );
