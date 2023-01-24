<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;

/**
 * ProductUtils
 */
class ProductUtils {

	public static $publish_status = 'open_for_enrollment';
	public static $statuses       = [
		'template'            => 'Template',
		'open_for_enrollment' => 'Open for enrollment',
		'enrollment_closed'   => 'Enrollment Closed',
		'date_passed'         => 'Date Passed',
		'closed'              => 'Closed',
		'cancelled'           => 'Cancelled',
		'archived'            => 'Archived',
	];

	public static function get_status_name( $status ) {
		return isset( self::$statuses[ $status ] ) ? self::$statuses[ $status ] : $status;
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
	public static function is_allowed_products_edit(): bool {
		return current_user_can( 'publish_products' )
		|| current_user_can( 'read_product' )
		|| current_user_can( 'delete_products' )
		|| current_user_can( 'edit_products' )
		|| current_user_can( 'edit_product' );
	}

	/**
	 * Get products with specific group membership.
	 *
	 * @param int $group_id The group id.
	 * @return WC_Product[]
	 */
	public static function get_products_visible_to_group( int $group_id ): array {
		return wc_get_products(
			[
				'status'       => 'open_for_enrollment',
				'meta_key'     => 'groups-read',
				'meta_compare' => '=',
				'meta_value'   => $group_id,
				'meta_type'    => 'NUMERIC',
			]
		);
	}

	/**
	 * Get products with the same group memberships as my user.
	 *
	 * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 * @return WC_Product[]
	 */
	public static function get_visible_products(): array {
		return wc_get_products(
			[
				'status'       => 'open_for_enrollment',
				'meta_key'     => 'groups-read',
				'meta_compare' => 'IN',
				'meta_value'   => GroupUtils::get_current_users_group_ids(),
			]
		);
	}

	/**
	 * Get All orders IDs for a given product ID.
	 *
	 * @param  integer $product_id (required).
	 * @param  array   $order_status (optional) Default is ['wc-processing','wc-completed'].
	 *
	 * @return array
	 */
	public static function get_orders_ids_by_product_id( $product_id, $order_status = array( 'wc-processing', 'wc-completed' ) ) {
		global $wpdb;
		$args = implode( ',', array_fill( 0, count( $order_status ), '%s' ) );

		$results = $wpdb->get_col(
			$wpdb->prepare(
				"
			SELECT order_items.order_id
			FROM {$wpdb->prefix}woocommerce_order_items as order_items
			LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
			LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
			WHERE posts.post_type = 'shop_order'
			AND posts.post_status IN ( $args )" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				. "AND order_items.order_item_type = 'line_item'
			AND order_item_meta.meta_key = '_product_id'
			AND order_item_meta.meta_value = %s
			",
				array_merge( $order_status, [ $product_id ] )
			)
		);

		return $results;
	}
}
