<?php
/**
 * Registriert die acht Kacheln und zeichnet das Bento-Grid.
 *
 * @package NextGen\Dashboard
 */

declare(strict_types=1);

namespace NextGen\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Filter nextgen_dashboard_widgets und HTML der Kacheln.
 */
final class Widgets {

	private function __construct() {}

	/**
	 * @param mixed $widgets Bisherige Kacheln.
	 * @return list<array<string, mixed>>
	 */
	public static function defaults( $widgets ): array {
		if ( ! is_array( $widgets ) ) {
			$widgets = array();
		}

		return array_merge(
			$widgets,
			array(
				array(
					'id'              => 'status',
					'titel'           => __( 'System', 'nextgen-dashboard' ),
					'icon'            => 'pulse',
					'grid-area'       => 'status',
					'render-callback' => array( self::class, 'render_status' ),
				),
				array(
					'id'              => 'plugins',
					'titel'           => __( 'Meine Plugins', 'nextgen-dashboard' ),
					'icon'            => 'plug',
					'grid-area'       => 'plugins',
					'render-callback' => array( self::class, 'render_plugins' ),
				),
				array(
					'id'              => 'updates',
					'titel'           => __( 'Updates', 'nextgen-dashboard' ),
					'icon'            => 'arrow',
					'grid-area'       => 'updates',
					'render-callback' => array( self::class, 'render_updates' ),
				),
				array(
					'id'              => 'inhalt',
					'titel'           => __( 'Inhalte', 'nextgen-dashboard' ),
					'icon'            => 'stack',
					'grid-area'       => 'inhalt',
					'render-callback' => array( self::class, 'render_content' ),
				),
				array(
					'id'              => 'deploy',
					'titel'           => __( 'Deployment', 'nextgen-dashboard' ),
					'icon'            => 'upload',
					'grid-area'       => 'deploy',
					'render-callback' => array( self::class, 'render_deploy' ),
				),
				array(
					'id'              => 'log',
					'titel'           => __( 'Debug-Log', 'nextgen-dashboard' ),
					'icon'            => 'terminal',
					'grid-area'       => 'log',
					'restricted'      => true,
					'render-callback' => array( self::class, 'render_log' ),
				),
				array(
					'id'              => 'sandbox',
					'titel'           => __( 'Sandbox', 'nextgen-dashboard' ),
					'icon'            => 'beaker',
					'grid-area'       => 'sandbox',
					'restricted'      => true,
					'render-callback' => array( self::class, 'render_sandbox' ),
				),
				array(
					'id'              => 'notes',
					'titel'           => __( 'Notizen', 'nextgen-dashboard' ),
					'icon'            => 'note',
					'grid-area'       => 'notes',
					'restricted'      => true,
					'render-callback' => array( self::class, 'render_notes' ),
				),
			)
		);
	}

	public static function markup(): string {
		$is_admin = current_user_can( 'manage_options' );
		$data     = Data::snapshot( $is_admin );
		$widgets  = apply_filters( 'nextgen_dashboard_widgets', array() );
		$versions = ( isset( $data['versions'] ) && is_array( $data['versions'] ) ) ? $data['versions'] : array();

		$attrs = get_block_wrapper_attributes(
			array(
				'class'        => 'ngd-root',
				'id'           => 'ngd',
				'data-wp'      => isset( $versions['wp'] ) ? (string) $versions['wp'] : '',
				'data-php'     => isset( $versions['php'] ) ? (string) $versions['php'] : '',
				'data-updated' => isset( $data['updated'] ) ? (string) $data['updated'] : '',
			)
		);

		$html  = '<div ' . $attrs . '>';
		$html .= '<h1 class="ngd-sr">' . esc_html__( 'Dashboard', 'nextgen-dashboard' ) . '</h1>';
		$html .= '<div class="ngd">';

		$index = 0;

		foreach ( $widgets as $widget ) {
			$normal = self::normalize( $widget );

			if ( null === $normal ) {
				continue;
			}

			$locked = ! empty( $normal['restricted'] ) && ! $is_admin;

			if ( $locked ) {
				$body = '<p class="ngd-locked">' . esc_html__( 'Nur für Admin', 'nextgen-dashboard' ) . '</p>';
			} else {
				try {
					$body = (string) call_user_func( $normal['render-callback'], $data, $is_admin );
				} catch ( \Throwable $e ) {
					$body = '<p class="ngd-muted">' . esc_html__( 'Kachel nicht verfügbar.', 'nextgen-dashboard' ) . '</p>';
				}
			}

			$html .= self::shell( $normal, $body, $index, $locked, $is_admin );
			++$index;
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function render_status( array $data, bool $is_admin ): string {
		unset( $is_admin );

		$system = ( isset( $data['system'] ) && is_array( $data['system'] ) ) ? $data['system'] : array();
		$rows   = array(
			'wp'     => __( 'WordPress', 'nextgen-dashboard' ),
			'php'    => __( 'PHP', 'nextgen-dashboard' ),
			'memory' => __( 'Speicher', 'nextgen-dashboard' ),
			'debug'  => __( 'Debug', 'nextgen-dashboard' ),
		);
		$html   = '<ul class="ngd-metrics">';

		foreach ( $rows as $key => $label ) {
			$row   = ( isset( $system[ $key ] ) && is_array( $system[ $key ] ) ) ? $system[ $key ] : array();
			$value = isset( $row['value'] ) ? (string) $row['value'] : '—';
			$dot   = isset( $row['dot'] ) ? self::dot( (string) $row['dot'] ) : 'ok';
			$html .= '<li><span class="ngd-metric__label"><span class="ngd-dot ngd-dot--' . esc_attr( $dot ) . '" data-dot="' . esc_attr( $key ) . '" aria-hidden="true"></span>' . esc_html( $label ) . '</span><strong data-field="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</strong></li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function render_plugins( array $data, bool $is_admin ): string {
		$plugins    = ( isset( $data['plugins'] ) && is_array( $data['plugins'] ) ) ? $data['plugins'] : array();
		$can_toggle = $is_admin && current_user_can( 'activate_plugins' ) && current_user_can( 'deactivate_plugins' );
		$html       = '<ul class="ngd-plugins" id="ngd-plugins">';

		if ( array() === $plugins ) {
			$html .= '<li class="ngd-muted">' . esc_html__( 'Keine NextGen-Plugins gefunden.', 'nextgen-dashboard' ) . '</li>';
		}

		foreach ( $plugins as $plugin ) {
			if ( ! is_array( $plugin ) ) {
				continue;
			}

			$file   = isset( $plugin['file'] ) ? (string) $plugin['file'] : '';
			$name   = isset( $plugin['name'] ) ? (string) $plugin['name'] : $file;
			$version = isset( $plugin['version'] ) ? (string) $plugin['version'] : '';
			$active = ! empty( $plugin['active'] );
			$self   = ! empty( $plugin['self'] );

			$html .= '<li class="ngd-plugin">';
			$html .= '<span class="ngd-plugin__name">' . esc_html( $name ) . '</span>';
			$html .= '<span class="ngd-plugin__ver">' . esc_html( $version ) . '</span>';

			if ( $can_toggle && Data::is_nextgen_file( $file ) ) {
				$html .= '<button type="button" class="ngd-switch" role="switch" aria-checked="' . ( $active ? 'true' : 'false' ) . '" data-plugin="' . esc_attr( $file ) . '" data-self="' . ( $self ? '1' : '0' ) . '">';
				$html .= '<span class="ngd-switch__knob"></span>';
				$html .= '<span class="ngd-sr">' . esc_html( $name ) . '</span>';
				$html .= '</button>';
			} else {
				$html .= '<span class="ngd-plugin__state">' . esc_html( $active ? __( 'aktiv', 'nextgen-dashboard' ) : __( 'inaktiv', 'nextgen-dashboard' ) ) . '</span>';
			}

			$html .= '</li>';
		}

		$html .= '</ul>';

		return $html;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function render_updates( array $data, bool $is_admin ): string {
		unset( $is_admin );

		$updates = ( isset( $data['updates'] ) && is_array( $data['updates'] ) ) ? $data['updates'] : array();
		$items   = array(
			'core'    => __( 'Core', 'nextgen-dashboard' ),
			'plugins' => __( 'Plugins', 'nextgen-dashboard' ),
			'themes'  => __( 'Themes', 'nextgen-dashboard' ),
		);
		$html    = '<div class="ngd-figures">';

		foreach ( $items as $key => $label ) {
			$count = isset( $updates[ $key ] ) ? (int) $updates[ $key ] : 0;
			$html .= '<div><span class="ngd-num" data-count="' . esc_attr( (string) $count ) . '" data-field="' . esc_attr( $key ) . '">' . esc_html( (string) $count ) . '</span><span class="ngd-figlabel">' . esc_html( $label ) . '</span></div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function render_content( array $data, bool $is_admin ): string {
		unset( $is_admin );

		$content = ( isset( $data['content'] ) && is_array( $data['content'] ) ) ? $data['content'] : array();
		$items   = array(
			'pages'    => __( 'Seiten', 'nextgen-dashboard' ),
			'posts'    => __( 'Beiträge', 'nextgen-dashboard' ),
			'media'    => __( 'Medien', 'nextgen-dashboard' ),
			'comments' => __( 'Kommentare', 'nextgen-dashboard' ),
		);
		$spark   = ( isset( $content['spark'] ) && is_array( $content['spark'] ) ) ? $content['spark'] : array();
		$html    = '<ul class="ngd-stats">';

		foreach ( $items as $key => $label ) {
			$count = isset( $content[ $key ] ) ? (int) $content[ $key ] : 0;
			$html .= '<li><span>' . esc_html( $label ) . '</span><strong data-count="' . esc_attr( (string) $count ) . '" data-field="' . esc_attr( $key ) . '">' . esc_html( (string) $count ) . '</strong></li>';
		}

		$html .= '</ul>';
		$html .= '<p class="ngd-hint">' . esc_html__( '30 Tage', 'nextgen-dashboard' ) . '</p>';
		$html .= '<div class="ngd-spark-wrap" id="ngd-spark">' . self::sparkline_svg( $spark ) . '</div>';

		return $html;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function render_deploy( array $data, bool $is_admin ): string {
		$deploy = ( isset( $data['deploy'] ) && is_array( $data['deploy'] ) ) ? $data['deploy'] : array();
		$theme  = isset( $deploy['theme'] ) ? (string) $deploy['theme'] : '—';
		$files  = isset( $deploy['plugins'] ) ? (string) $deploy['plugins'] : '—';
		$html   = '<ul class="ngd-deploy">';
		$html  .= '<li><span>' . esc_html__( 'Theme', 'nextgen-dashboard' ) . '</span><time data-field="theme">' . esc_html( $theme ) . '</time></li>';
		$html  .= '<li><span>' . esc_html__( 'Plugin-Dateien', 'nextgen-dashboard' ) . '</span><time data-field="plugins">' . esc_html( $files ) . '</time></li>';
		$html  .= '</ul>';
		$html  .= '<div id="ngd-github">';

		if ( $is_admin ) {
			$html .= self::github_html( $data );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function render_log( array $data, bool $is_admin ): string {
		unset( $is_admin );

		$entries = ( isset( $data['admin']['log'] ) && is_array( $data['admin']['log'] ) ) ? $data['admin']['log'] : array();

		return '<div class="ngd-log" id="ngd-log">' . self::log_lines_html( $entries ) . '</div>';
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function render_sandbox( array $data, bool $is_admin ): string {
		unset( $data, $is_admin );

		$html  = '<form class="ngd-sandbox-form" id="ngd-sandbox-form">';
		$html .= '<label class="ngd-sr" for="ngd-shortcode">' . esc_html__( 'Shortcode', 'nextgen-dashboard' ) . '</label>';
		$html .= '<input class="ngd-input" id="ngd-shortcode" name="shortcode" type="text" maxlength="300" spellcheck="false" autocomplete="off" placeholder="[nextgen_starter]" />';
		$html .= '<button class="ngd-btn" type="submit">' . esc_html__( 'Rendern', 'nextgen-dashboard' ) . '</button>';
		$html .= '</form>';
		$html .= '<div class="ngd-sandbox-out" id="ngd-sandbox-out"><p class="ngd-muted">' . esc_html__( 'Shortcode eingeben.', 'nextgen-dashboard' ) . '</p></div>';

		return $html;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function render_notes( array $data, bool $is_admin ): string {
		unset( $is_admin );

		$notes = ( isset( $data['admin']['notes'] ) && is_string( $data['admin']['notes'] ) ) ? $data['admin']['notes'] : '';

		return '<textarea class="ngd-notes" id="ngd-notes" maxlength="5000" placeholder="' . esc_attr__( 'Kurz notieren …', 'nextgen-dashboard' ) . '">' . esc_textarea( $notes ) . '</textarea>';
	}

	/**
	 * @param list<int|string> $values
	 */
	public static function sparkline_svg( array $values ): string {
		$series = array();

		foreach ( $values as $value ) {
			$series[] = (int) $value;
		}

		if ( count( $series ) < 2 ) {
			$series = array( 0, 0 );
		}

		$max    = max( 1, max( $series ) );
		$width  = 160;
		$height = 36;
		$last   = count( $series ) - 1;
		$points = array();

		foreach ( $series as $index => $value ) {
			$x        = ( $index / $last ) * $width;
			$y        = ( $height - 4 ) - ( ( $value / $max ) * ( $height - 8 ) );
			$points[] = round( $x, 1 ) . ',' . round( $y, 1 );
		}

		return sprintf(
			'<svg class="ngd-spark" viewBox="0 0 %1$d %2$d" role="img" aria-label="%3$s"><polyline points="%4$s" /></svg>',
			$width,
			$height,
			esc_attr__( 'Beiträge der letzten 30 Tage', 'nextgen-dashboard' ),
			esc_attr( implode( ' ', $points ) )
		);
	}

	/**
	 * @param list<array<string, mixed>> $entries
	 */
	public static function log_lines_html( array $entries ): string {
		if ( array() === $entries ) {
			return '<p class="ngd-muted">' . esc_html__( 'Keine Einträge.', 'nextgen-dashboard' ) . '</p>';
		}

		$html = '';

		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$level = isset( $entry['level'] ) ? (string) $entry['level'] : 'info';

			if ( ! in_array( $level, array( 'error', 'warn', 'info' ), true ) ) {
				$level = 'info';
			}

			$text  = isset( $entry['text'] ) ? (string) $entry['text'] : '';
			$html .= '<div class="ngd-log__line ngd-log__line--' . esc_attr( $level ) . '">' . esc_html( $text ) . '</div>';
		}

		return '' === $html ? '<p class="ngd-muted">' . esc_html__( 'Keine Einträge.', 'nextgen-dashboard' ) . '</p>' : $html;
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function github_html( array $data ): string {
		$admin  = ( isset( $data['admin'] ) && is_array( $data['admin'] ) ) ? $data['admin'] : array();
		$github = ( isset( $admin['github'] ) && is_array( $admin['github'] ) ) ? $admin['github'] : null;

		if ( ! is_array( $github ) ) {
			if ( empty( $admin['repo'] ) ) {
				return '';
			}

			return '<p class="ngd-muted">' . esc_html__( 'Commit nicht geladen.', 'nextgen-dashboard' ) . '</p>';
		}

		$sha     = isset( $github['sha'] ) ? (string) $github['sha'] : '';
		$message = isset( $github['message'] ) ? (string) $github['message'] : '';
		$date    = isset( $github['date'] ) ? (string) $github['date'] : '';
		$url     = isset( $github['url'] ) ? (string) $github['url'] : '';
		$host    = wp_parse_url( $url, PHP_URL_HOST );
		$html    = '<p class="ngd-commit">';

		if ( in_array( $host, array( 'github.com', 'www.github.com' ), true ) ) {
			$html .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer"><code>' . esc_html( $sha ) . '</code></a>';
		} else {
			$html .= '<code>' . esc_html( $sha ) . '</code>';
		}

		$html .= ' <span>' . esc_html( $message ) . '</span>';

		if ( '' !== $date ) {
			$html .= ' <time>' . esc_html( $date ) . '</time>';
		}

		$html .= '</p>';

		return $html;
	}

	/**
	 * @param mixed $widget Rohe Filter-Angabe.
	 * @return array{id: string, titel: string, icon: string, grid-area: string, render-callback: callable, restricted: bool}|null
	 */
	private static function normalize( $widget ): ?array {
		if ( ! is_array( $widget ) ) {
			return null;
		}

		$id = isset( $widget['id'] ) ? sanitize_key( (string) $widget['id'] ) : '';

		if ( '' === $id ) {
			return null;
		}

		$callback = $widget['render-callback'] ?? ( $widget['render'] ?? null );

		if ( ! is_callable( $callback ) ) {
			return null;
		}

		$titel = '';

		if ( isset( $widget['titel'] ) ) {
			$titel = (string) $widget['titel'];
		} elseif ( isset( $widget['title'] ) ) {
			$titel = (string) $widget['title'];
		}

		$area = '';

		if ( isset( $widget['grid-area'] ) ) {
			$area = (string) $widget['grid-area'];
		} elseif ( isset( $widget['area'] ) ) {
			$area = (string) $widget['area'];
		}

		$area = strtolower( (string) preg_replace( '/[^a-z0-9_-]/i', '', $area ) );

		if ( '' === $area ) {
			$area = $id;
		}

		$icon = isset( $widget['icon'] ) ? sanitize_key( (string) $widget['icon'] ) : 'pulse';

		return array(
			'id'              => $id,
			'titel'           => $titel,
			'icon'            => $icon,
			'grid-area'       => $area,
			'render-callback' => $callback,
			'restricted'      => ! empty( $widget['restricted'] ),
		);
	}

	/**
	 * @param array{id: string, titel: string, icon: string, grid-area: string, render-callback: callable, restricted: bool} $widget
	 */
	private static function shell( array $widget, string $body, int $index, bool $locked, bool $is_admin ): string {
		$actions = ( $locked || ! $is_admin ) ? '' : self::actions_html( $widget['id'] );
		$class   = 'ngd-tile' . ( $locked ? ' is-locked' : '' );

		$html  = '<section class="' . esc_attr( $class ) . '" data-area="' . esc_attr( $widget['grid-area'] ) . '" data-widget="' . esc_attr( $widget['id'] ) . '" style="--ngd-i:' . (int) $index . '">';
		$html .= '<header class="ngd-tile__head"><span class="ngd-tile__title">' . Icons::svg( $widget['icon'] ) . '<h2>' . esc_html( $widget['titel'] ) . '</h2></span>' . $actions . '</header>';
		$html .= '<div class="ngd-tile__body" id="ngd-body-' . esc_attr( $widget['id'] ) . '">' . $body . '</div>';
		$html .= '</section>';

		return $html;
	}

	private static function actions_html( string $id ): string {
		if ( 'log' === $id ) {
			return '<button type="button" class="ngd-btn" id="ngd-clear-log">' . esc_html__( 'Log leeren', 'nextgen-dashboard' ) . '</button>';
		}

		if ( 'notes' === $id ) {
			return '<span id="ngd-save" class="ngd-save" aria-live="polite"></span>';
		}

		return '';
	}

	private static function dot( string $level ): string {
		return in_array( $level, array( 'ok', 'warn', 'bad' ), true ) ? $level : 'ok';
	}
}
