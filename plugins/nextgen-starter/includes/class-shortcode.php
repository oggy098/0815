<?php
/**
 * Shortcode [nextgen_starter].
 *
 * @package NextGen\Starter
 */

declare(strict_types=1);

namespace NextGen\Starter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gibt die gespeicherte Begrüssung oder einen übergebenen Text aus.
 */
final class Shortcode {

	private function __construct() {}

	public static function register(): void {
		add_shortcode( 'nextgen_starter', array( self::class, 'render' ) );
	}

	/**
	 * @param array<string, string>|string $atts Shortcode-Attribute.
	 */
	public static function render( $atts = array() ): string {
		$pairs = array(
			'message' => Settings::greeting(),
		);

		$atts = shortcode_atts( $pairs, is_array( $atts ) ? $atts : array(), 'nextgen_starter' );

		$message = sanitize_text_field( strip_shortcodes( (string) $atts['message'] ) );

		return sprintf(
			'<p class="nextgen-starter-shortcode">%s</p>',
			esc_html( $message )
		);
	}
}
