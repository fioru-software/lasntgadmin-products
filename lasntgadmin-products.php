<?php
/**
 * Plugin Name:       LASNTG Products
 * Plugin URI:        https://github.com/fioru-software/lasntgadmin-products
 * Description:       Lasntg Products
 * Version:           5.14.1
 * Requires PHP:      7.2
 * Text Domain:       lasntgadmin
 * Domain Path:       /languages
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require_once getenv( 'COMPOSER_AUTOLOAD_FILEPATH' );

use Lasntg\Admin\Products\{
	QuotasActionsFilters,
	ProductActionsFilters,
	AcfFields,
	AdminTableView,
	AdminEditView,
	PluginUtils,
	Importer,
	ProductSchedulerActions
};

// Register activation and deactivation hooks.
register_activation_hook( __FILE__, [ PluginUtils::class, 'activate' ] );
register_deactivation_hook( __FILE__, [ PluginUtils::class, 'deactivate' ] );

// Inits.
ProductActionsFilters::init();
QuotasActionsFilters::init();
AcfFields::init();
AdminTableView::init();
AdminEditView::init();
Importer::init();
ProductSchedulerActions::init();
