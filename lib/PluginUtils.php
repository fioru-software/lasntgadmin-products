<?php

namespace Lasntg\Admin\Products;

/**
 * Plugin utilities
 */
class PluginUtils {

	public static function get_camel_case_name(): string {
		return 'lasntgadmin_products';
	}

	public static function get_kebab_case_name(): string {
		return 'lasntgadmin-products';
	}

	public static function get_absolute_plugin_path():string {
		return sprintf( '/var/www/html/wp-content/plugins/%s', self::get_kebab_case_name() );
	}
}


