<?php
/**
 * Liest System-, Inhalts- und Deploy-Daten für HTML und REST.
 *
 * Öffentliche Werte liegen 30 Sekunden im Transient. Log, Notizen und
 * GitHub-Commits hängen nur an Admin-Antworten und werden nicht gecacht.
 *
 * @package NextGen\Dashboard
 */

declare(strict_types=1);

namespace NextGen\Dashboard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Datenquelle der Kacheln.
 */
final class Data {

	public const NOTES_OPTION = 'nextgen_dashboard_notes';

	public const PUBLIC_TRANSIENT = 'nextgen_dashboard_public';

	public const GITHUB_TRANSIENT = 'nextgen_dashboard_github';

	private function __construct() {}

	/**
	 * @return array<string, mixed>
	 */
	public static function snapshot( bool $with_admin ): array {
		$data = get_transient( self::PUBLIC_TRANSIENT );

		if ( ! is_array( $data ) ) {
			$data = self::collect_public();
			set_transient( self::PUBLIC_TRANSIENT, $data, 30 );
		}

		unset( $data['admin'] );

		// Dateipfade der Plugins nur an Admins. Besucher sehen Name, Version und Status.
		if ( ! $with_admin && isset( $data['plugins'] ) && is_array( $data['plugins'] ) ) {
			$data['plugins'] = array_map(
				static function ( $plugin ) {
					if ( ! is_array( $plugin ) ) {
						return $plugin;
					}

					unset( $plugin['file'], $plugin['self'] );

					return $plugin;
				},
				$data['plugins']
			);
		}

		if ( $with_admin ) {
			$repo           = Settings::repo();
			$data['admin'] = array(
				'log'    => self::log_entries(),
				'notes'  => self::notes(),
				'repo'   => '' !== $repo,
				'github' => '' !== $repo ? self::github( $repo ) : null,
			);
		}

		return $data;
	}

	public static function forget(): void {
		delete_transient( self::PUBLIC_TRANSIENT );
	}

	/**
	 * @return array<string, mixed>
	 */
	private static function collect_public(): array {
		self::load_plugin_api();

		$updates      = self::updates();
		$theme_mtime  = self::latest_mtime( get_stylesheet_directory() );
		$plugin_mtime = self::plugin_mtime();
		$latest       = max( $theme_mtime, $plugin_mtime );

		return array(
			'versions' => array(
				'wp'  => self::wp_version(),
				'php' => PHP_VERSION,
			),
			'updated'  => self::format_time( $latest ),
			'system'   => self::system( $updates['core'] > 0 ),
			'plugins'  => self::plugins(),
			'updates'  => $updates,
			'content'  => self::content(),
			'deploy'   => array(
				'theme'   => self::format_time( $theme_mtime ),
				'plugins' => self::format_time( $plugin_mtime ),
			),
		);
	}

	/**
	 * @return array{wp: array{value: string, dot: string}, php: array{value: string, dot: string}, memory: array{value: string, dot: string}, debug: array{value: string, dot: string}}
	 */
	private static function system( bool $core_update ): array {
		$limit = self::ini_bytes( (string) ini_get( 'memory_limit' ) );
		$used  = memory_get_usage( true );
		$ratio = ( $limit > 0 ) ? ( $used / $limit ) : 0;

		if ( $limit < 0 ) {
			$memory_dot   = 'ok';
			$memory_value = __( 'unbegrenzt', 'nextgen-dashboard' );
		} else {
			$memory_dot   = $ratio >= 0.9 ? 'bad' : ( $ratio >= 0.7 ? 'warn' : 'ok' );
			$percent      = (int) round( $ratio * 100 );
			$memory_value = $percent . ' % · ' . self::format_bytes( $used ) . ' / ' . self::format_bytes( $limit );
		}

		$debug   = defined( 'WP_DEBUG' ) && WP_DEBUG;
		$display = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY;

		if ( $debug && $display ) {
			$debug_dot   = 'bad';
			$debug_value = __( 'ein, sichtbar', 'nextgen-dashboard' );
		} elseif ( $debug ) {
			$debug_dot   = 'warn';
			$debug_value = __( 'ein', 'nextgen-dashboard' );
		} else {
			$debug_dot   = 'ok';
			$debug_value = __( 'aus', 'nextgen-dashboard' );
		}

		$php_dot = 'bad';

		if ( version_compare( PHP_VERSION, '8.1', '>=' ) ) {
			$php_dot = 'ok';
		} elseif ( version_compare( PHP_VERSION, '8.0', '>=' ) ) {
			$php_dot = 'warn';
		}

		return array(
			'wp'     => array(
				'value' => self::wp_version(),
				'dot'   => $core_update ? 'warn' : 'ok',
			),
			'php'    => array(
				'value' => PHP_VERSION,
				'dot'   => $php_dot,
			),
			'memory' => array(
				'value' => $memory_value,
				'dot'   => $memory_dot,
			),
			'debug'  => array(
				'value' => $debug_value,
				'dot'   => $debug_dot,
			),
		);
	}

	/**
	 * @return list<array{file: string, name: string, version: string, active: bool, self: bool}>
	 */
	public static function plugins(): array {
		self::load_plugin_api();

		$all  = get_plugins();
		$list = array();
		$self = plugin_basename( NEXTGEN_DASHBOARD_FILE );

		foreach ( $all as $file => $header ) {
			if ( ! is_string( $file ) || ! self::is_nextgen_file( $file ) || ! is_array( $header ) ) {
				continue;
			}

			$list[] = array(
				'file'    => $file,
				'name'    => isset( $header['Name'] ) ? (string) $header['Name'] : $file,
				'version' => isset( $header['Version'] ) ? (string) $header['Version'] : '',
				'active'  => is_plugin_active( $file ),
				'self'    => $file === $self,
			);
		}

		usort(
			$list,
			static function ( array $a, array $b ): int {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		return $list;
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function set_plugin_active( string $file, bool $active ) {
		if ( ! self::is_nextgen_file( $file ) ) {
			return new \WP_Error(
				'nextgen_plugin',
				__( 'Nur Plugins mit Präfix nextgen- können umgeschaltet werden.', 'nextgen-dashboard' ),
				array( 'status' => 400 )
			);
		}

		self::load_plugin_api();

		$known = get_plugins();

		if ( ! isset( $known[ $file ] ) ) {
			return new \WP_Error(
				'nextgen_plugin',
				__( 'Plugin nicht gefunden.', 'nextgen-dashboard' ),
				array( 'status' => 404 )
			);
		}

		if ( $active ) {
			$result = activate_plugin( $file, '', false, true );

			if ( is_wp_error( $result ) ) {
				return new \WP_Error(
					'nextgen_plugin',
					$result->get_error_message(),
					array( 'status' => 500 )
				);
			}
		} else {
			deactivate_plugins( $file, true );
		}

		self::forget();

		return true;
	}

	/**
	 * @return array{core: int, plugins: int, themes: int}
	 */
	private static function updates(): array {
		$core   = 0;
		$stored = get_site_transient( 'update_core' );

		if ( is_object( $stored ) && isset( $stored->updates ) && is_array( $stored->updates ) ) {
			foreach ( $stored->updates as $update ) {
				if ( is_object( $update ) && isset( $update->response ) && 'upgrade' === $update->response ) {
					++$core;
				}
			}
		}

		$plugins = get_site_transient( 'update_plugins' );
		$themes  = get_site_transient( 'update_themes' );

		return array(
			'core'    => $core,
			'plugins' => ( is_object( $plugins ) && isset( $plugins->response ) && is_array( $plugins->response ) ) ? count( $plugins->response ) : 0,
			'themes'  => ( is_object( $themes ) && isset( $themes->response ) && is_array( $themes->response ) ) ? count( $themes->response ) : 0,
		);
	}

	/**
	 * @return array{pages: int, posts: int, media: int, comments: int, spark: list<int>}
	 */
	private static function content(): array {
		$pages    = wp_count_posts( 'page' );
		$posts    = wp_count_posts( 'post' );
		$media    = wp_count_posts( 'attachment' );
		$comments = wp_count_comments();

		return array(
			'pages'    => ( is_object( $pages ) && isset( $pages->publish ) ) ? (int) $pages->publish : 0,
			'posts'    => ( is_object( $posts ) && isset( $posts->publish ) ) ? (int) $posts->publish : 0,
			'media'    => ( is_object( $media ) && isset( $media->inherit ) ) ? (int) $media->inherit : 0,
			'comments' => ( is_object( $comments ) && isset( $comments->approved ) ) ? (int) $comments->approved : 0,
			'spark'    => self::sparkline(),
		);
	}

	/**
	 * @return list<int>
	 */
	private static function sparkline(): array {
		global $wpdb;

		$now    = current_datetime();
		$cutoff = ( clone $now )->modify( '-29 days' )->format( 'Y-m-d' ) . ' 00:00:00';
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DATE(post_date) AS day, COUNT(*) AS total
				FROM {$wpdb->posts}
				WHERE post_type = %s AND post_status = %s AND post_date >= %s
				GROUP BY DATE(post_date)",
				'post',
				'publish',
				$cutoff
			),
			ARRAY_A
		);

		$map = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) || ! isset( $row['day'] ) ) {
					continue;
				}

				$map[ (string) $row['day'] ] = (int) $row['total'];
			}
		}

		$series = array();

		for ( $i = 29; $i >= 0; $i-- ) {
			$key       = ( clone $now )->modify( '-' . $i . ' days' )->format( 'Y-m-d' );
			$series[]  = $map[ $key ] ?? 0;
		}

		return $series;
	}

	/**
	 * @return list<array{level: string, text: string}>
	 */
	public static function log_entries(): array {
		$path = self::checked_log_path();

		if ( '' === $path || ! is_readable( $path ) ) {
			return array();
		}

		$size   = filesize( $path );
		$handle = fopen( $path, 'rb' );

		if ( false === $handle ) {
			return array();
		}

		$chunk = 65536;

		if ( is_int( $size ) && $size > $chunk ) {
			fseek( $handle, -$chunk, SEEK_END );
		}

		$text = stream_get_contents( $handle );
		fclose( $handle );

		if ( ! is_string( $text ) || '' === $text ) {
			return array();
		}

		$lines = preg_split( "/\r\n|\n|\r/", $text );

		if ( ! is_array( $lines ) ) {
			return array();
		}

		$lines = array_values(
			array_filter(
				$lines,
				static function ( $line ): bool {
					return is_string( $line ) && '' !== trim( $line );
				}
			)
		);

		$lines   = array_slice( $lines, -30 );
		$entries = array();

		foreach ( $lines as $line ) {
			$clean = self::clean_log_line( $line );

			if ( '' === $clean ) {
				continue;
			}

			$entries[] = array(
				'level' => self::log_level( $clean ),
				'text'  => $clean,
			);
		}

		return $entries;
	}

	/**
	 * @return true|\WP_Error
	 */
	public static function clear_log() {
		$path = self::checked_log_path();

		if ( '' === $path ) {
			return true;
		}

		if ( ! is_writable( $path ) ) {
			return new \WP_Error(
				'nextgen_log',
				__( 'Debug-Log ist nicht beschreibbar.', 'nextgen-dashboard' ),
				array( 'status' => 500 )
			);
		}

		if ( false === file_put_contents( $path, '' ) ) {
			return new \WP_Error(
				'nextgen_log',
				__( 'Debug-Log konnte nicht geleert werden.', 'nextgen-dashboard' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	public static function notes(): string {
		$notes = get_option( self::NOTES_OPTION, '' );

		return is_string( $notes ) ? $notes : '';
	}

	public static function save_notes( string $notes ): void {
		$notes = str_replace( "\0", '', $notes );

		if ( mb_strlen( $notes ) > 5000 ) {
			$notes = mb_substr( $notes, 0, 5000 );
		}

		update_option( self::NOTES_OPTION, sanitize_textarea_field( $notes ), false );
	}

	/**
	 * @return array{sha: string, message: string, date: string, url: string}|null
	 */
	private static function github( string $repo ): ?array {
		$cached = get_transient( self::GITHUB_TRANSIENT );

		if ( is_array( $cached ) && ( $cached['repo'] ?? '' ) === $repo && array_key_exists( 'commit', $cached ) ) {
			$commit = $cached['commit'];

			return is_array( $commit ) ? $commit : null;
		}

		$parts = explode( '/', $repo, 2 );

		if ( 2 !== count( $parts ) ) {
			return null;
		}

		$url     = sprintf(
			'https://api.github.com/repos/%s/%s/commits?per_page=1',
			rawurlencode( $parts[0] ),
			rawurlencode( $parts[1] )
		);
		$headers = array(
			'Accept'     => 'application/vnd.github+json',
			'User-Agent' => 'NextGen-Dashboard',
		);
		$token   = Settings::token();

		if ( '' !== $token ) {
			$headers['Authorization'] = 'Bearer ' . $token;
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 3,
				'redirection' => 2,
				'headers'     => $headers,
			)
		);

		$commit = null;

		if ( ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response ) ) {
			$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
			$item = ( is_array( $body ) && isset( $body[0] ) && is_array( $body[0] ) ) ? $body[0] : null;

			if ( is_array( $item ) ) {
				$sha     = isset( $item['sha'] ) ? substr( (string) $item['sha'], 0, 7 ) : '';
				$message = isset( $item['commit']['message'] ) ? (string) $item['commit']['message'] : '';
				$lines   = preg_split( "/\r\n|\n|\r/", $message );
				$message = sanitize_text_field( (string) ( is_array( $lines ) ? ( $lines[0] ?? '' ) : '' ) );

				if ( mb_strlen( $message ) > 90 ) {
					$message = mb_substr( $message, 0, 87 ) . '…';
				}

				$date_raw = isset( $item['commit']['author']['date'] ) ? (string) $item['commit']['author']['date'] : '';
				$unix     = '' !== $date_raw ? strtotime( $date_raw ) : false;
				$html_url = isset( $item['html_url'] ) ? esc_url_raw( (string) $item['html_url'] ) : '';
				$host     = is_string( $html_url ) ? wp_parse_url( $html_url, PHP_URL_HOST ) : '';

				if ( ! in_array( $host, array( 'github.com', 'www.github.com' ), true ) ) {
					$html_url = '';
				}

				if ( '' !== $sha ) {
					$commit = array(
						'sha'     => $sha,
						'message' => $message,
						'date'    => self::format_time( false === $unix ? 0 : $unix ),
						'url'     => $html_url,
					);
				}
			}
		}

		set_transient(
			self::GITHUB_TRANSIENT,
			array(
				'repo'   => $repo,
				'commit' => $commit,
			),
			null === $commit ? 2 * MINUTE_IN_SECONDS : 5 * MINUTE_IN_SECONDS
		);

		return $commit;
	}

	public static function is_nextgen_file( string $file ): bool {
		return 1 === preg_match( '#^nextgen-[A-Za-z0-9][A-Za-z0-9._-]*/[A-Za-z0-9][A-Za-z0-9._-]*\.php$#', $file );
	}

	private static function load_plugin_api(): void {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
	}

	private static function wp_version(): string {
		global $wp_version;

		return is_string( $wp_version ) ? $wp_version : '';
	}

	private static function format_time( int $unix ): string {
		if ( $unix <= 0 ) {
			return '—';
		}

		return wp_date( 'd.m.Y H:i', $unix );
	}

	private static function ini_bytes( string $value ): int {
		$value = trim( $value );

		if ( '' === $value || '-1' === $value ) {
			return -1;
		}

		$unit   = strtolower( substr( $value, -1 ) );
		$number = (float) $value;

		switch ( $unit ) {
			case 'g':
				return (int) ( $number * 1073741824 );
			case 'm':
				return (int) ( $number * 1048576 );
			case 'k':
				return (int) ( $number * 1024 );
			default:
				return (int) $number;
		}
	}

	private static function format_bytes( int $bytes ): string {
		if ( $bytes < 0 ) {
			return __( 'unbegrenzt', 'nextgen-dashboard' );
		}

		$mb = $bytes / 1048576;

		if ( $mb >= 1024 ) {
			return number_format_i18n( $mb / 1024, 1 ) . ' GB';
		}

		return number_format_i18n( $mb, 0 ) . ' MB';
	}

	private static function plugin_mtime(): int {
		$root   = wp_normalize_path( WP_PLUGIN_DIR );
		$latest = 0;
		$dirs   = glob( WP_PLUGIN_DIR . '/nextgen-*', GLOB_ONLYDIR );

		if ( ! is_array( $dirs ) ) {
			return 0;
		}

		foreach ( $dirs as $dir ) {
			$real = realpath( $dir );

			if ( false === $real ) {
				continue;
			}

			$real = wp_normalize_path( $real );

			if ( ! str_starts_with( $real, $root . '/' ) ) {
				continue;
			}

			$latest = max( $latest, self::latest_mtime( $real ) );
		}

		return $latest;
	}

	/**
	 * Jüngstes filemtime, begrenzt auf 5000 Dateien.
	 */
	private static function latest_mtime( string $dir ): int {
		if ( ! is_dir( $dir ) ) {
			return 0;
		}

		$latest = 0;
		$count  = 0;

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $dir, \FilesystemIterator::SKIP_DOTS )
			);

			foreach ( $iterator as $file ) {
				if ( ! $file instanceof \SplFileInfo || ! $file->isFile() ) {
					continue;
				}

				$path = wp_normalize_path( $file->getPathname() );

				if ( str_contains( $path, '/.git/' ) || str_contains( $path, '/node_modules/' ) || str_contains( $path, '/vendor/' ) ) {
					continue;
				}

				$mtime  = $file->getMTime();
				$latest = max( $latest, $mtime );
				++$count;

				if ( $count >= 5000 ) {
					break;
				}
			}
		} catch ( \Throwable $e ) {
			return $latest;
		}

		return $latest;
	}

	private static function checked_log_path(): string {
		$path   = wp_normalize_path( WP_CONTENT_DIR . '/debug.log' );
		$origin = realpath( WP_CONTENT_DIR );

		if ( false === $origin || ! is_file( $path ) ) {
			return '';
		}

		$root = wp_normalize_path( $origin );

		$real = realpath( $path );

		if ( false === $real ) {
			return '';
		}

		$real = wp_normalize_path( $real );

		if ( $real !== rtrim( $root, '/' ) . '/debug.log' ) {
			return '';
		}

		return $real;
	}

	private static function clean_log_line( string $line ): string {
		$line = wp_strip_all_tags( $line );
		$line = (string) preg_replace( '/\x1b\[[0-9;]*[A-Za-z]/', '', $line );
		$line = (string) preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $line );
		$line = trim( $line );

		if ( mb_strlen( $line ) > 400 ) {
			$line = mb_substr( $line, 0, 397 ) . '…';
		}

		return $line;
	}

	private static function log_level( string $line ): string {
		$hay = strtolower( $line );

		if ( preg_match( '/\b(fatal|error|exception|uncaught)\b/', $hay ) ) {
			return 'error';
		}

		if ( preg_match( '/\b(warning|notice|deprecated)\b/', $hay ) ) {
			return 'warn';
		}

		return 'info';
	}
}
