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
use Lasntg\Admin\Quotas\QuotasActionsFilters;
use Lasntg\Admin\Quotas\WaitingListActionsFilters;

// Inits.
QuotasActionsFilters::init();
WaitingListActionsFilters::init();

