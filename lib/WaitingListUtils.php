<?php
/**
 * Waiting list Utility functions
 */

namespace Lasntg\Admin\Products;

/**
 * WaitingList Utility
 */
class WaitingListUtils {

	const LASNTGADMIN_EMAIL_META_KEY = '_LASNTG_ADMIN_EMAIL';

	/**
	 * Check if already in whitelist
	 *
	 * @param  int  $current_product_id product id to check.
	 * @param  bool $delete should the whishlist be deleted.
	 * @return bool
	 */
	public static function check_already_in_whishlist( int $current_product_id, bool $delete = false ): bool {
		if ( ! get_current_user_id() ) {
			return false;
		}

		$orders  = wc_get_orders(
			[
				'limit'       => -1,
				'customer_id' => get_current_user_id(),
				'status'      => 'wc-waiting_list',
			]
		);
		$removed = false;
		foreach ( $orders as $order ) {
			$items = $order->get_items();
			foreach ( $items as $item ) {
				$product_id = $item->get_product_id();
				if ( $product_id == $current_product_id ) {
					if ( ! $delete ) {
						return true;
					}
					$removed = true;
					wp_delete_post( $order->get_id(), true );
				}
				// assumes the order has one item.
				break;
			}
		}
		return $removed;
	}

	/**
	 * Get the order by meta
	 *
	 * @param  string $key Meta Key.
	 * @param  string $email Email meta value.
	 * @return array
	 */
	public static function check_order_meta( string $key, string $email ): array {
		global $wpdb;
		$results = $wpdb->get_results( $wpdb->prepare( "select * from $wpdb->postmeta where meta_key = %s and meta_value = %s", $key, $email ) );
		return $results;
	}

	/**
	 * Get Order by email
	 *
	 * @param  string $email Email.
	 * @return array
	 */
	public static function get_orders_by_meta( $email ): array {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( "select * from $wpdb->postmeta where meta_key like %s and meta_value = %s ", '%' . self::LASNTGADMIN_EMAIL_META_KEY . '_%', $email ) );
	}

	/**
	 * Remove guest order
	 *
	 * @param  string  $email Email.
	 * @param  integer $product_id product Id.
	 * @return bool
	 */
	public static function remove_guest( string $email, int $product_id ): bool {
		global $wpdb;
		$order_id = $wpdb->get_var( $wpdb->prepare( "select post_id from $wpdb->postmeta where meta_key = %s and meta_value = %s ", self::LASNTGADMIN_EMAIL_META_KEY . '_' . $product_id, $email ) );
		if ( ! $order_id ) {
			return false;
		}
		wp_delete_post( $order_id, true );
		return true;
	}
}


