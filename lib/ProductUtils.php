<?php

namespace Lasntg\Admin\Products;

/**
 * ProductUtils
 */
class ProductUtils {

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

	public static function is_allowed_products_edit()
	{
		return current_user_can('publish_products') 
		|| current_user_can('read_product') 
		|| current_user_can('delete_products') 
		|| current_user_can('edit_products') 
		|| current_user_can('edit_product');
	}
}
