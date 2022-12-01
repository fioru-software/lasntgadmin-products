<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;
use Lasntg\Admin\Products\ProductApi;

use WP_Error;

/**
 * ProductUtils
 */
class ProductUtils {

    public static function add_actions() {
        add_action( 'rest_api_init', [ ProductApi::class, 'get_instance' ] );
        add_action('admin_init', [ self::class, 'get_visible_products'] );
    }

    public static function add_filters() {
        add_filter('woocommerce_product_object_query', [ self::class, 'product_query'], 10, 2);
    }

	/**
	 * Has any role
	 *
	 * @param  array|string $roles can be a string or array.
	 * @return bool
	 */
	public static function has_any_role( $roles ): bool {
		if ( is_array( $roles ) ) {
			foreach ( $roles as $role ) {
				if ( self::is_current_user_in_role( $role ) ) {
					return true;
				}
			}
		} else {
			return self::is_current_user_in_role( $roles );
		}
		return false;
	}


	/**
	 * Check if current user is manager or a training officer admin is added.
	 *
	 * @return bool
	 */
	public static function is_manager_or_training_officer(): bool {
		$required_roles = [ 'administrator', 'training_officer', 'national_manager' ];
		return self::has_any_role( $required_roles );
	}

	/**
	 * Does current user have role
	 *
	 * @param  string $role Check user role.
	 * @return bool
	 */
	public static function is_current_user_in_role( $role ): bool {
		$user = wp_get_current_user();
		return in_array( $role, $user->roles );
	}

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
	public static function is_allowed_products_edit():bool {
		 return current_user_can( 'publish_products' )
		|| current_user_can( 'read_product' )
		|| current_user_can( 'delete_products' )
		|| current_user_can( 'edit_products' )
		|| current_user_can( 'edit_product' );
	}

    /**
     * Get products with the same group memberships as my user.
     * @return WC_Product[]
     */
    public static function get_visible_products(): array {
        return wc_get_products(
            [ 
                'meta_key'=> 'groups-read',
                'meta_compare' => 'IN',
                'meta_value' => GroupUtils::get_current_users_group_ids()
            ]
        );
    }

}
