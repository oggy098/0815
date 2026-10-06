<?php
/**
 * Frontend-Ausgabe des Blocks nextgen/dashboard.
 *
 * @package NextGen\Dashboard
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo \NextGen\Dashboard\Widgets::markup();
