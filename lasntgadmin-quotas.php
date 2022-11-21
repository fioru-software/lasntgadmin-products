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

// Exit if accessed directly.
define( 'LASNTGADMIN_DIR_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

require LASNTGADMIN_DIR_PATH . '/lib/QuotaUtil.php';
require LASNTGADMIN_DIR_PATH . '/inc/wp-actions.php';
