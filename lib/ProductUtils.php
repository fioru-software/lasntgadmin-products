<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;
use Lasntg\Admin\Products\ProductApi;

use WP_Error;

/**
 * ProductUtils
 */
class ProductUtils {

	/**
	 * Redirect back
	 *
	 * @return void
	 */
	public static function redirect_back(): void {
		$redirect = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : home_url( '/' );
		$redirect = esc_url( $redirect );
		exit( esc_attr( wp_redirect( $redirect ) ) );
	}

	/**
	 * Check current user has products capabilities.
	 *
	 * @return bool
	 */
	public static function is_allowed_products_edit(): bool {
		return current_user_can( 'publish_products' )
		|| current_user_can( 'read_product' )
		|| current_user_can( 'delete_products' )
		|| current_user_can( 'edit_products' )
		|| current_user_can( 'edit_product' );
	}

	/**
	 * Get products with the same group memberships as my user.
	 *
	 * @return WC_Product[]
	 */
	public static function get_visible_products(): array {
		return wc_get_products(
			[
				'meta_key'     => 'groups-read',
				'meta_compare' => 'IN',
				'meta_value'   => GroupUtils::get_current_users_group_ids(),
			]
		);
	}

}
