<?php
/**
 * Einstellungen unter Einstellungen → NextGen Dashboard.
 *
 * Repo und Token dienen nur dem Abruf des letzten Commits.
 * Der Token wird nie ins Frontend geschrieben.
 *
 * @package NextGen\Dashboard
 */

declare(strict_types=1);

namespace NextGen\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings API für GitHub-Repo und Token.
 */
final class Settings {

	public const OPTION = 'nextgen_dashboard_settings';

	private const GROUP = 'nextgen_dashboard_group';

	private const PAGE = 'nextgen-dashboard';

	private function __construct() {}

	public static function register(): void {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => array(
					'github_repo'  => '',
					'github_token' => '',
				),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'nextgen_dashboard_github',
			__( 'GitHub', 'nextgen-dashboard' ),
			static function (): void {
				echo '<p>' . esc_html__( 'Optional. Ein öffentliches Repo funktioniert ohne Token. Für ein privates Repo genügt ein Token mit Leserecht. Der Token bleibt in der Datenbank und erscheint nicht im Dashboard.', 'nextgen-dashboard' ) . '</p>';
			},
			self::PAGE
		);

		add_settings_field(
			'github_repo',
			__( 'Repository', 'nextgen-dashboard' ),
			array( self::class, 'render_repo_field' ),
			self::PAGE,
			'nextgen_dashboard_github',
			array(
				'label_for' => 'nextgen-dashboard-repo',
			)
		);

		add_settings_field(
			'github_token',
			__( 'Token', 'nextgen-dashboard' ),
			array( self::class, 'render_token_field' ),
			self::PAGE,
			'nextgen_dashboard_github',
			array(
				'label_for' => 'nextgen-dashboard-token',
			)
		);

		add_action(
			'update_option_' . self::OPTION,
			static function (): void {
				delete_transient( Data::GITHUB_TRANSIENT );
				Data::forget();
			}
		);
	}

	public static function add_page(): void {
		add_options_page(
			__( 'NextGen Dashboard', 'nextgen-dashboard' ),
			__( 'NextGen Dashboard', 'nextgen-dashboard' ),
			'manage_options',
			self::PAGE,
			array( self::class, 'render_page' )
		);
	}

	/**
	 * @return array{github_repo: string, github_token: string}
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array(
			'github_repo'  => isset( $stored['github_repo'] ) ? (string) $stored['github_repo'] : '',
			'github_token' => isset( $stored['github_token'] ) ? (string) $stored['github_token'] : '',
		);
	}

	public static function repo(): string {
		return self::get()['github_repo'];
	}

	/**
	 * Niemals ausgeben, weder im HTML noch in der REST-Antwort.
	 */
	public static function token(): string {
		return self::get()['github_token'];
	}

	/**
	 * @param mixed $input Rohdaten aus dem Formular.
	 * @return array{github_repo: string, github_token: string}
	 */
	public static function sanitize( $input ): array {
		$current = self::get();
		$repo    = '';
		$raw     = '';

		if ( is_array( $input ) && isset( $input['github_repo'] ) ) {
			$raw  = trim( (string) $input['github_repo'] );
			$repo = $raw;
		}

		if ( '' !== $repo && ! preg_match( '#^[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+$#', $repo ) ) {
			add_settings_error(
				self::OPTION,
				'repo',
				__( 'Das Repository muss als owner/name angegeben werden.', 'nextgen-dashboard' )
			);
			$repo = $current['github_repo'];
		}

		$token = $current['github_token'];
		$clear = is_array( $input ) && ! empty( $input['clear_token'] );

		if ( $clear ) {
			$token = '';
		} elseif ( is_array( $input ) && isset( $input['github_token'] ) ) {
			$incoming = trim( sanitize_text_field( (string) $input['github_token'] ) );

			if ( '' !== $incoming ) {
				$token = $incoming;
			}
		}

		return array(
			'github_repo'  => $repo,
			'github_token' => $token,
		);
	}

	public static function render_repo_field(): void {
		printf(
			'<input type="text" class="regular-text" id="nextgen-dashboard-repo" name="%1$s[github_repo]" value="%2$s" placeholder="owner/name" autocomplete="off" />',
			esc_attr( self::OPTION ),
			esc_attr( self::repo() )
		);
	}

	public static function render_token_field(): void {
		$stored = '' !== self::token();

		printf(
			'<input type="password" class="regular-text" id="nextgen-dashboard-token" name="%1$s[github_token]" value="" placeholder="%2$s" autocomplete="new-password" />',
			esc_attr( self::OPTION ),
			esc_attr( $stored ? __( 'Gespeichert. Leer lassen, um ihn zu behalten.', 'nextgen-dashboard' ) : '' )
		);

		echo '<p><label><input type="checkbox" name="' . esc_attr( self::OPTION ) . '[clear_token]" value="1" /> ' . esc_html__( 'Token entfernen', 'nextgen-dashboard' ) . '</label></p>';
	}

	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Du hast keine Berechtigung für diese Seite.', 'nextgen-dashboard' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
		settings_errors();
		echo '<form action="options.php" method="post">';
		settings_fields( self::GROUP );
		do_settings_sections( self::PAGE );
		submit_button( __( 'Speichern', 'nextgen-dashboard' ) );
		echo '</form>';
		echo '</div>';
	}
}
