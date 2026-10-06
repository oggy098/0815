<?php
/**
 * Theme-Setup für NextGen Base.
 *
 * Das Theme ist ein Block-Theme: Templates liegen in templates/ und parts/.
 * Es wird kein jQuery und kein Page-Builder geladen.
 *
 * @package NextGen_Base
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	static function (): void {
		load_theme_textdomain( 'nextgen-base', get_template_directory() . '/languages' );

		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support(
			'html5',
			array(
				'search-form',
				'comment-form',
				'comment-list',
				'gallery',
				'caption',
				'style',
				'script',
			)
		);
	}
);

add_filter(
	'document_title_parts',
	static function ( array $parts ): array {
		unset( $parts['tagline'] );

		return $parts;
	}
);

add_action(
	'init',
	static function (): void {
		register_block_pattern_category(
			'nextgen',
			array(
				'label' => __( 'NextGen', 'nextgen-base' ),
			)
		);
	},
	8
);
