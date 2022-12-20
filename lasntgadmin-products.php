<?php
/**
 * Plugin Name:       LASNTG Products
 * Plugin URI:        https://github.com/fioru-software/lasntgadmin-products
 * Description:       Lasntg Products
 * Version:           4.0.2
 * Requires PHP:      7.2
 * Text Domain:       lasntgadmin
 * Domain Path:       /languages
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once getenv( 'COMPOSER_AUTOLOAD_FILEPATH' );

use Lasntg\Admin\Products\{
	QuotasActionsFilters,
	WaitingListActionsFilters,
	ProductActionsFilters
};

// Inits.
QuotasActionsFilters::init();
WaitingListActionsFilters::init();
ProductActionsFilters::init();
