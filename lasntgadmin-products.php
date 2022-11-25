<?php
/**
 * Plugin Name:       Lasntg Products
 * Plugin URI:        https://github.com/fioru-software/lasntgadmin-products
 * Description:       Lasntg Products
 * Version:           3.0.0
 * Requires PHP:      7.2
 * Text Domain:       lasntgadmin
 * Domain Path:       /languages
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once getenv( 'COMPOSER_AUTOLOAD_FILEPATH' );
use Lasntg\Admin\Products\QuotasActionsFilters;
use Lasntg\Admin\Products\WaitingListActionsFilters;

// Inits.
QuotasActionsFilters::init();
WaitingListActionsFilters::init();

