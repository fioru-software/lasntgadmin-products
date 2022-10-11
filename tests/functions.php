<?php

/**
 * Enqueue scripts and styles.
 */
function veri_enqueue() {
	wp_enqueue_style( 'veri-styles', get_stylesheet_uri() );
}
add_action( 'wp_enqueue_scripts', 'veri_enqueue', 11 );
