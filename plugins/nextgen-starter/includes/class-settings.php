<?php
/**
 * Einstellungsseite unter Einstellungen → NextGen Starter.
 *
 * @package NextGen\Starter
 */

declare(strict_types=1);

namespace NextGen\Starter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Speichert die Begrüssung über die Settings API.
 */
final class Settings {

	public const OPTION = 'nextgen_starter_settings';

	public const DEFAULT_GREETING = 'Hallo von NextGen Starter.';

	private const GROUP = 'nextgen_starter_group';

	private const PAGE = 'nextgen-starter';

	private function __construct() {}

	public static function register(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => array(
					'greeting' => self::DEFAULT_GREETING,
				),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'nextgen_starter_main',
			__( 'Allgemein', 'nextgen-starter' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Diese Begrüssung verwendet der Shortcode [nextgen_starter], wenn kein eigener Text übergeben wird.', 'nextgen-starter' ) . '</p>';
			},
			self::PAGE
		);

		add_settings_field(
			'greeting',
			__( 'Begrüssung', 'nextgen-starter' ),
			array( self::class, 'render_greeting_field' ),
			self::PAGE,
			'nextgen_starter_main',
			array(
				'label_for' => 'nextgen-starter-greeting',
			)
		);
	}

	public static function add_page(): void {
		add_options_page(
			__( 'NextGen Starter', 'nextgen-starter' ),
			__( 'NextGen Starter', 'nextgen-starter' ),
			'manage_options',
			self::PAGE,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * @param mixed $input Rohdaten aus dem Formular.
	 * @return array{greeting: string}
	 */
	public static function sanitize( $input ): array {
		$greeting = '';

		if ( is_array( $input ) && isset( $input['greeting'] ) ) {
			$greeting = sanitize_text_field( (string) $input['greeting'] );
		}

		if ( mb_strlen( $greeting ) > 200 ) {
			$greeting = mb_substr( $greeting, 0, 200 );
		}

		return array(
			'greeting' => $greeting,
		);
	}

	public static function greeting(): string {
		$settings = get_option( self::OPTION, array() );

		if ( ! is_array( $settings ) || ! isset( $settings['greeting'] ) ) {
			return self::DEFAULT_GREETING;
		}

		return (string) $settings['greeting'];
	}

	public static function render_greeting_field(): void {
		printf(
			'<input type="text" class="regular-text" id="nextgen-starter-greeting" name="%1$s[greeting]" value="%2$s" maxlength="200" />',
			esc_attr( self::OPTION ),
			esc_attr( self::greeting() )
		);
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Seite.', 'nextgen-starter' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		settings_errors();
		echo '<form action="options.php" method="post">';
		settings_fields( self::GROUP );
		do_settings_sections( self::PAGE );
		submit_button( __( 'Speichern', 'nextgen-starter' ) );
		echo '</form>';
		echo '</div>';
	}
}
