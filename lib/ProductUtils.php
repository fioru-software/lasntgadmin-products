<?php

namespace Lasntg\Admin\Products;

/**
 * ProductUtils
 */
class ProductUtils {

	public static function has_any_role( $roles ) {
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


	private static function is_current_user_in_role( $role ) {
		$user = wp_get_current_user();
		return in_array( $role, $user->roles );
	}
}
