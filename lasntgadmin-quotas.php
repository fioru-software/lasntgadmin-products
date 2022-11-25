<?php
/**
 * Plugin Name:       Lasntg Quotas
 * Plugin URI:        https://github.com/fioru-software/lasntgadmin-plugin_template
 * Description:       Lasntg Quotas
 * Version:           1.0.1
 * Requires PHP:      7.2
 * Text Domain:       lasntgadmin
 * Domain Path:       /languages
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once getenv( 'COMPOSER_AUTOLOAD_FILEPATH' );
use Lasntg\Admin\Quotas\QuotasActionsFilter;
use Lasntg\Admin\Quotas\WaitingListActionsFilters;

define( 'LASNTGADMIN_QUOTAS_DIR_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'LASNTGADMIN_QUOTAS_ASSETS_DIR_PATH', untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/assets/' );


QuotasActionsFilter::init();
WaitingListActionsFilters::init();

