<?php
/**
 * Abhängigkeiten für blocks/dashboard/index.js.
 *
 * WordPress liest diese Datei automatisch neben dem Skript aus block.json.
 *
 * @package NextGen\Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-i18n',
	),
	'version'      => '1.0.0',
);
