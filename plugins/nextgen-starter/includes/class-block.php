<?php
/**
 * Registriert den Block aus blocks/starter/block.json.
 *
 * @package NextGen\Starter
 */

declare(strict_types=1);

namespace NextGen\Starter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dynamischer Block ohne Build-Schritt.
 */
final class Block {

	private function __construct() {}

	public static function register(): void {
		register_block_type( NEXTGEN_STARTER_DIR . '/blocks/starter' );
	}
}
