<?php
/**
 * Plugin Name:       LASNTG Products
 * Plugin URI:        https://github.com/fioru-software/lasntgadmin-products
 * Description:       Lasntg Products
 * Version:           4.0.0
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
    ProductActionsFilters,
    ProductUtils
};

// Inits.
QuotasActionsFilters::init();
WaitingListActionsFilters::init();
ProductActionsFilters::init();
ProductUtils::add_actions();


add_action('admin_init', function() {
    $req = new WP_REST_Request( 'GET', "/lasntgadmin/product/v1/products" );

    $res = rest_do_request( $req );
    error_log(print_r($res->get_data(), true));
}, 500);
