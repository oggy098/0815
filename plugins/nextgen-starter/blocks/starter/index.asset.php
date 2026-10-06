<?php
/**
 * Abhängigkeiten für blocks/starter/index.js.
 *
 * WordPress liest diese Datei automatisch neben dem Skript aus block.json.
 * Sie ersetzt die sonst von @wordpress/scripts erzeugte Asset-Datei,
 * damit kein npm-Build nötig ist.
 *
 * @package NextGen\Starter
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
