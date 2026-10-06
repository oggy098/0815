<?php
/**
 * Kleine Strich-Icons. Kein externes Icon-Set.
 *
 * @package NextGen\Dashboard
 */

declare(strict_types=1);

namespace NextGen\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Liefert ein fest eingebautes SVG. Unbekannte Namen fallen auf einen Punkt zurück.
 */
final class Icons {

	private function __construct() {}

	public static function svg( string $name ): string {
		$paths = array(
			'pulse'    => '<path d="M1.5 8h2.5l1.8-3.5L8.2 12l2-4h4.3"/>',
			'plug'     => '<path d="M6 2.5v3M10 2.5v3"/><rect x="4" y="5.5" width="8" height="4.5" rx="1"/><path d="M8 10v2.2a2.3 2.3 0 0 0 2.3 2.3H12"/>',
			'arrow'    => '<path d="M8 2.5v7M5 7.5 8 10.5 11 7.5M3 13.5h10"/>',
			'terminal' => '<path d="M3 4.5h10v7H3zM5 7l2 1.5L5 10M8.5 10H11"/>',
			'stack'    => '<path d="M2.5 6 8 3.5 13.5 6 8 8.5zM2.5 8.5 8 11l5.5-2.5M2.5 11 8 13.5 13.5 11"/>',
			'upload'   => '<path d="M8 10.5V3M5.2 5.5 8 2.8l2.8 2.7M3 12.5h10"/>',
			'beaker'   => '<path d="M6.5 2.5h3M7 2.5v4L3.8 13h8.4L9 6.5v-4"/>',
			'note'     => '<path d="M4 2.5h5.5L13 6v7.5H4zM9.5 2.5V6H13M6 9h4M6 11.5h2.5"/>',
		);

		$markup = $paths[ $name ] ?? '<circle cx="8" cy="8" r="2"/>';

		return '<svg class="ngd-ico" viewBox="0 0 16 16" aria-hidden="true" focusable="false">' . $markup . '</svg>';
	}
}
