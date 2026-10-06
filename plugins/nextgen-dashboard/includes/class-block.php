<?php
/**
 * Block nextgen/dashboard und Assets nur auf der Startseite.
 *
 * @package NextGen\Dashboard
 */

declare(strict_types=1);

namespace NextGen\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registrierung, Darstellung und Skripte.
 */
final class Block {

	private function __construct() {}

	public static function register(): void {
		register_block_type( NEXTGEN_DASHBOARD_DIR . '/blocks/dashboard' );
	}

	/**
	 * @param mixed $classes Bestehende Body-Klassen.
	 * @return list<string>
	 */
	public static function body_class( $classes ): array {
		if ( ! is_array( $classes ) ) {
			$classes = array();
		}

		if ( self::is_screen() ) {
			$classes[] = 'nextgen-dashboard-screen';
		}

		return $classes;
	}

	/**
	 * Setzt Dark/Light vor dem ersten Paint, damit nichts aufblitzt.
	 */
	public static function boot_theme(): void {
		if ( ! self::is_screen() ) {
			return;
		}

		echo "<script>(function(){try{var t=localStorage.getItem('ngd-theme');if(t!=='light'&&t!=='dark'){t='dark';}document.documentElement.setAttribute('data-ngd-theme',t);document.documentElement.style.colorScheme=t==='light'?'light':'dark';}catch(e){}})();</script>\n";
	}

	public static function enqueue(): void {
		if ( ! self::is_screen() ) {
			return;
		}

		$css = NEXTGEN_DASHBOARD_DIR . '/assets/dashboard.css';
		$js  = NEXTGEN_DASHBOARD_DIR . '/assets/dashboard.js';

		wp_enqueue_style(
			'nextgen-dashboard',
			plugins_url( 'assets/dashboard.css', NEXTGEN_DASHBOARD_FILE ),
			array(),
			(string) filemtime( $css )
		);

		wp_enqueue_script(
			'nextgen-dashboard',
			plugins_url( 'assets/dashboard.js', NEXTGEN_DASHBOARD_FILE ),
			array(),
			(string) filemtime( $js ),
			true
		);

		wp_localize_script(
			'nextgen-dashboard',
			'nextgenDashboard',
			array(
				'statusUrl'  => esc_url_raw( rest_url( 'nextgen/v1/status' ) ),
				'pluginUrl'  => esc_url_raw( rest_url( 'nextgen/v1/plugins' ) ),
				'logUrl'     => esc_url_raw( rest_url( 'nextgen/v1/log' ) ),
				'sandboxUrl' => esc_url_raw( rest_url( 'nextgen/v1/sandbox' ) ),
				'notesUrl'   => esc_url_raw( rest_url( 'nextgen/v1/notes' ) ),
				'nonce'      => wp_create_nonce( 'wp_rest' ),
				'poll'       => 60000,
				'isAdmin'    => current_user_can( 'manage_options' ) ? '1' : '0',
				'canPlugins' => ( current_user_can( 'manage_options' ) && current_user_can( 'activate_plugins' ) && current_user_can( 'deactivate_plugins' ) ) ? '1' : '0',
				'i18n'       => array(
					'updated'      => __( 'Zuletzt aktualisiert:', 'nextgen-dashboard' ),
					'saving'       => __( 'Speichert …', 'nextgen-dashboard' ),
					'saved'        => __( 'Gespeichert', 'nextgen-dashboard' ),
					'saveError'    => __( 'Konnte nicht gespeichert werden.', 'nextgen-dashboard' ),
					'confirmClear' => __( 'Debug-Log wirklich leeren?', 'nextgen-dashboard' ),
					'confirmSelf'  => __( 'Dieses Plugin steuert das Dashboard. Trotzdem deaktivieren?', 'nextgen-dashboard' ),
					'emptyLog'     => __( 'Keine Einträge.', 'nextgen-dashboard' ),
					'logError'     => __( 'Log konnte nicht geleert werden.', 'nextgen-dashboard' ),
					'toggleError'  => __( 'Konnte nicht geändert werden.', 'nextgen-dashboard' ),
					'sandboxError' => __( 'Ausgabe fehlgeschlagen.', 'nextgen-dashboard' ),
					'sandboxEmpty' => __( 'Keine Ausgabe.', 'nextgen-dashboard' ),
					'sandboxHint'  => __( 'Shortcode eingeben.', 'nextgen-dashboard' ),
					'noPlugins'    => __( 'Keine NextGen-Plugins gefunden.', 'nextgen-dashboard' ),
					'active'       => __( 'aktiv', 'nextgen-dashboard' ),
					'inactive'     => __( 'inaktiv', 'nextgen-dashboard' ),
					'themeLight'   => __( 'Helle Darstellung', 'nextgen-dashboard' ),
					'themeDark'    => __( 'Dunkle Darstellung', 'nextgen-dashboard' ),
					'commitMiss'   => __( 'Commit nicht geladen.', 'nextgen-dashboard' ),
					'days'         => __( 'Beiträge der letzten 30 Tage', 'nextgen-dashboard' ),
				),
			)
		);
	}

	/**
	 * Verhindert, dass ein Seiten-Cache die Admin-Kacheln an Besucher ausliefert.
	 */
	public static function nocache_for_admins(): void {
		if ( ! self::is_screen() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}

		nocache_headers();
	}

	private static function is_screen(): bool {
		return ! is_admin() && is_front_page();
	}
}
