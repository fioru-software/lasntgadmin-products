<?php

namespace Lasntg\Admin\Products;

class AdminTableUtils {

	/**
	 * Determine if the current view is the "All" view.
	 *
	 * @since 4.2.0
	 * @see https://github.com/WordPress/wordpress-develop/blob/6.2/src/wp-admin/includes/class-wp-posts-list-table.php#L235
	 * @return bool Whether the current view is the "All" view.
	 */
	public static function is_base_request() {
		$screen = get_current_screen();
		$vars   = $_GET;
		unset( $vars['paged'] );

		if ( empty( $vars ) ) {
			return true;
		} elseif ( 1 === count( $vars ) && ! empty( $vars['post_type'] ) ) {
			return $screen->post_type === $vars['post_type'];
		}

		return 1 === count( $vars ) && ! empty( $vars['mode'] );
	}
}
