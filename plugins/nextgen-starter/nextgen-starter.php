<?php
/**
 * Plugin Name: NextGen Starter
 * Plugin URI: https://nextgen-builds.ch
 * Description: Vorlage für eigene NextGen-Plugins mit Einstellungsseite, Shortcode und Block.
 * Version: 1.0.0
 * Requires at least: 6.7
 * Requires PHP: 8.1
 * Author: NextGen
 * Author URI: https://nextgen-builds.ch
 * Text Domain: nextgen-starter
 * Domain Path: /languages
 * Update URI: https://nextgen-builds.ch
 *
 * Kopiervorlage:
 * 1. Ordner nach plugins/nextgen-mein-plugin kopieren.
 * 2. Diese Datei in nextgen-mein-plugin.php umbenennen.
 * 3. NextGen\Starter, nextgen-starter und nextgen/starter überall ersetzen.
 * 4. Plugin-Header und Texte anpassen.
 *
 * @package NextGen\Starter
 */

declare(strict_types=1);

namespace NextGen\Starter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'NEXTGEN_STARTER_FILE', __FILE__ );
define( 'NEXTGEN_STARTER_DIR', __DIR__ );
define( 'NEXTGEN_STARTER_VERSION', '1.0.0' );

require_once NEXTGEN_STARTER_DIR . '/includes/class-settings.php';
require_once NEXTGEN_STARTER_DIR . '/includes/class-shortcode.php';
require_once NEXTGEN_STARTER_DIR . '/includes/class-block.php';

/**
 * Legt die Standardoption an. Bestehende Einstellungen bleiben erhalten.
 */
function activate(): void {
	if ( null === get_option( Settings::OPTION, null ) ) {
		add_option(
			Settings::OPTION,
			array(
				'greeting' => Settings::DEFAULT_GREETING,
			)
		);
	}
}

/**
 * Deaktivierung löscht keine gespeicherten Daten.
 * Optionen entfernt erst uninstall.php, damit eine versehentliche
 * Deaktivierung die Einstellungen nicht verwirft.
 */
function deactivate(): void {
	// Keine Optionen löschen. Siehe uninstall.php.
}

register_activation_hook( NEXTGEN_STARTER_FILE, __NAMESPACE__ . '\\activate' );
register_deactivation_hook( NEXTGEN_STARTER_FILE, __NAMESPACE__ . '\\deactivate' );

add_action(
	'init',
	static function (): void {
		load_plugin_textdomain(
			'nextgen-starter',
			false,
			dirname( plugin_basename( NEXTGEN_STARTER_FILE ) ) . '/languages'
		);

		Shortcode::register();
		Block::register();
	}
);

add_action( 'admin_menu', array( Settings::class, 'add_page' ) );
add_action( 'admin_init', array( Settings::class, 'register' ) );
