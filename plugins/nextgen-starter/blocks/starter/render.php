<?php
/**
 * Frontend-Ausgabe des Blocks nextgen/starter.
 *
 * @package NextGen\Starter
 *
 * @var array<string, mixed> $attributes Block-Attribute.
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$message = isset( $attributes['message'] ) ? (string) $attributes['message'] : '';

if ( '' === $message ) {
	$message = \NextGen\Starter\Settings::greeting();
}

$message = sanitize_text_field( $message );

printf(
	'<div %1$s><p>%2$s</p></div>',
	get_block_wrapper_attributes(),
	esc_html( $message )
);
