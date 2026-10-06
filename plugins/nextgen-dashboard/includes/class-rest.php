<?php
/**
 * REST-Routen unter /wp-json/nextgen/v1/.
 *
 * Status ist öffentlich. Log, Notizen, Sandbox und Plugin-Schalter
 * verlangen manage_options und das REST-Nonce.
 *
 * @package NextGen\Dashboard
 */

declare(strict_types=1);

namespace NextGen\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Live-Daten und Admin-Aktionen.
 */
final class Rest {

	private function __construct() {}

	public static function register(): void {
		register_rest_route(
			'nextgen/v1',
			'/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'status' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			'nextgen/v1',
			'/plugins',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'toggle_plugin' ),
				'permission_callback' => array( self::class, 'can_plugins' ),
				'args'                => array(
					'plugin' => array(
						'type'     => 'string',
						'required' => true,
					),
					'active' => array(
						'type'     => 'boolean',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			'nextgen/v1',
			'/log',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'clear_log' ),
				'permission_callback' => array( self::class, 'can_admin' ),
			)
		);

		register_rest_route(
			'nextgen/v1',
			'/sandbox',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'sandbox' ),
				'permission_callback' => array( self::class, 'can_admin' ),
				'args'                => array(
					'shortcode' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);

		register_rest_route(
			'nextgen/v1',
			'/notes',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'notes' ),
				'permission_callback' => array( self::class, 'can_admin' ),
				'args'                => array(
					'notes' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		);
	}

	public static function can_admin(): bool {
		return current_user_can( 'manage_options' );
	}

	public static function can_plugins(): bool {
		return self::can_admin() && current_user_can( 'activate_plugins' ) && current_user_can( 'deactivate_plugins' );
	}

	public static function status(): \WP_REST_Response {
		$response = new \WP_REST_Response( Data::snapshot( self::can_admin() ) );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}

	/**
	 * @param \WP_REST_Request $request Anfrage mit plugin und active.
	 */
	public static function toggle_plugin( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$file   = sanitize_text_field( (string) $request->get_param( 'plugin' ) );
		$active = (bool) $request->get_param( 'active' );
		$result = Data::set_plugin_active( $file, $active );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return self::no_store(
			array(
				'ok'      => true,
				'plugins' => Data::plugins(),
			)
		);
	}

	public static function clear_log(): \WP_REST_Response|\WP_Error {
		$result = Data::clear_log();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return self::no_store(
			array(
				'ok'  => true,
				'log' => Data::log_entries(),
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request Anfrage mit shortcode.
	 */
	public static function sandbox( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		$shortcode = str_replace( "\0", '', (string) $request->get_param( 'shortcode' ) );
		$shortcode = trim( $shortcode );

		if ( strlen( $shortcode ) > 300 || ! str_contains( $shortcode, '[' ) || str_contains( $shortcode, '<?' ) ) {
			return new \WP_Error(
				'nextgen_sandbox',
				__( 'Bitte einen Shortcode mit höchstens 300 Zeichen eingeben.', 'nextgen-dashboard' ),
				array( 'status' => 400 )
			);
		}

		$html = do_shortcode( $shortcode );

		if ( strlen( $html ) > 20000 ) {
			$html = substr( $html, 0, 20000 );
		}

		return self::no_store(
			array(
				'html' => wp_kses_post( $html ),
			)
		);
	}

	/**
	 * @param \WP_REST_Request $request Anfrage mit notes.
	 */
	public static function notes( \WP_REST_Request $request ): \WP_REST_Response {
		Data::save_notes( (string) $request->get_param( 'notes' ) );

		return self::no_store(
			array(
				'saved' => true,
			)
		);
	}

	/**
	 * @param array<string, mixed> $data Nutzlast.
	 */
	private static function no_store( array $data ): \WP_REST_Response {
		$response = new \WP_REST_Response( $data );
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}
}
