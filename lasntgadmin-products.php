<?php

/**
 * Plugin Name:       LASNTG Products
 * Plugin URI:        https://github.com/fioru-software/lasntgadmin-products
 * Description:       Lasntg Products
 * Version:           5.4.0-rc1
 * Requires PHP:      7.2
 * Text Domain:       lasntgadmin
 * Domain Path:       /languages
 */

// Exit if accessed directly.
defined('ABSPATH') || exit;

require_once getenv('COMPOSER_AUTOLOAD_FILEPATH');

use Lasntg\Admin\Products\PluginUtils;

use Lasntg\Admin\Products\{
	QuotasActionsFilters,
	ProductActionsFilters,
	AcfFields
};
// Register activation and deactivation hooks.
register_activation_hook(__FILE__, [PluginUtils::class, 'activate']);
register_deactivation_hook(__FILE__, [PluginUtils::class, 'deactivate']);

// Inits.
ProductActionsFilters::init();
QuotasActionsFilters::init();
AcfFields::init();

 AND ( 
  wp_posts.ID NOT IN (
				SELECT object_id
				FROM wp_term_relationships
				WHERE term_taxonomy_id IN (7)
			)
) AND ( 
  ( wp_postmeta.meta_key = 'groups-read' AND CAST(wp_postmeta.meta_value AS SIGNED) = '33' )
) AND wp_posts.post_type = 'product' AND ((wp_posts.post_status = 'open_for_enrollment')) AND wp_posts.ID NOT IN ( SELECT ID FROM wp_posts WHERE post_type IN ('post','page','attachment','product') AND ID IN ( SELECT post_id FROM wp_postmeta pm WHERE pm.meta_key = 'groups-read' AND pm.meta_value NOT IN (0) AND pm.meta_value IN ( SELECT group_id FROM wp_groups_group ) AND post_id NOT IN ( SELECT post_id FROM wp_postmeta pm WHERE pm.meta_key = 'groups-read' AND pm.meta_value IN (0) ) ) ) 