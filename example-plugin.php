<?php
/**
 * Plugin Name:       Example Plugin
 * Plugin URI:        https://github.com/fioru-software/lasntg-plugin_template
 * Description:       An example plugin.
 * Version:           0.0.0
 * Requires PHP:      7.2
 * Text Domain:       my-basics-plugin
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register the JS.
 */
function lasntgadmin_add_extension_register_script() {
	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! \Automattic\WooCommerce\Admin\PageController::is_admin_page() ) {
		return;
	}

	$script_path       = '/build/index.js';
	$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require $script_asset_path
		: array(
			'dependencies' => array(),
			'version'      => filemtime( $script_path ),
		);
	$script_url        = plugins_url( $script_path, __FILE__ );

	wp_register_script(
		'test-extension',
		$script_url,
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	wp_register_style(
		'test-extension',
		plugins_url( '/build/index.css', __FILE__ ),
		// Add any dependencies styles may have, such as wp-components.
		array(),
		filemtime( dirname( __FILE__ ) . '/build/index.css' )
	);

	wp_enqueue_script( 'test-extension' );
	wp_enqueue_style( 'test-extension' );
}

add_action( 'admin_enqueue_scripts', 'add_extension_register_script' );
