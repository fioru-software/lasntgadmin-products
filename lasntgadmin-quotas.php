<?php
/**
 * Plugin Name:       Lasntg Quotas
 * Plugin URI:        https://github.com/fioru-software/lasntgadmin-plugin_template
 * Description:       Lasntg Quotas
 * Version:           0.0.1
 * Requires PHP:      7.2
 * Text Domain:       lasntgadmin
 * Domain Path:       /languages
 */

defined( 'ABSPATH' ) || exit;


defined( 'ABSPATH' ) || exit;


/**
 * Register the JS.
 *
 * @param  string $hook_suffix filename.
 * @return void
 */
function lasntgadmin_add_extension_register_script( string $hook_suffix ) {
	$post_type = isset( $_GET['post_type'] ) ? sanitize_text_field( wp_unslash( $_GET['post_type'] ) ) : '';

	if ( 'product' !== $post_type || 'post-new.php' !== $hook_suffix ) {
		return;
	}

	error_log( '================= heading =============' );
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

add_action( 'admin_enqueue_scripts', 'lasntgadmin_add_extension_register_script' );
// Exit if accessed directly.
define( 'LASNTGADMIN_DIR_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

require LASNTGADMIN_DIR_PATH . '/lib/QuotaUtil.php';
require LASNTGADMIN_DIR_PATH . '/inc/wp-actions.php';
