<?php
/**
 * Entfernt Plugin-Daten nur bei der Deinstallation, nicht bei der Deaktivierung.
 *
 * @package NextGen\Starter
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'nextgen_starter_settings' );
