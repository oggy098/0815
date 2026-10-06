<?php
/**
 * Entfernt Plugin-Daten nur bei der Deinstallation, nicht bei der Deaktivierung.
 *
 * Die Optionsnamen entsprechen Settings::OPTION und Data::NOTES_OPTION.
 *
 * @package NextGen\Dashboard
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'nextgen_dashboard_settings' );
delete_option( 'nextgen_dashboard_notes' );
delete_transient( 'nextgen_dashboard_public' );
delete_transient( 'nextgen_dashboard_github' );
