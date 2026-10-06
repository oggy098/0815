<?php
/**
 * Title: Text und Bild
 * Slug: nextgen-base/text-bild
 * Categories: nextgen, text
 * Keywords: text, bild, spalten
 * Description: Zwei Spalten. Die rechte Fläche ist ein Platzhalter und kann durch einen Bild-Block ersetzt werden.
 * Viewport Width: 1280
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<!-- wp:columns {"align":"wide","verticalAlignment":"center"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php esc_html_e( 'Raum für den Inhalt', 'nextgen-base' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php esc_html_e( 'Beschreibe hier das Projekt, die Leistung oder den nächsten Schritt. Kurze Absätze bleiben auf dem schmalen Satzspiegel gut lesbar.', 'nextgen-base' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"50%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:50%"><!-- wp:group {"backgroundColor":"surface","style":{"dimensions":{"minHeight":"280px"},"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}},"border":{"color":"var:preset|color|border","width":"1px"}},"layout":{"type":"flex","orientation":"vertical","verticalAlignment":"center","justifyContent":"center"}} -->
<div class="wp-block-group has-border-color has-surface-background-color has-background" style="border-color:var(--wp--preset--color--border);border-width:1px;min-height:280px;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"center","textColor":"muted"} -->
<p class="has-text-align-center has-muted-color has-text-color"><?php esc_html_e( 'Bild hier einsetzen', 'nextgen-base' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
