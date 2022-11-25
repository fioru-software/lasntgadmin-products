<?php

namespace Lasntg\Admin\Products;

use Groups_Post_Access;
/**
 * QuotaUtil
 */
class QuotaUtils {


	public static $private_client_group_id = 33;


	public static function lasntgadmin_add_group( $post_id ) {
		$group_ids[] = self::$private_client_group_id;
		Groups_Post_Access::update(
			array(
				'post_id'     => $post_id,
				'groups_read' => $group_ids,
			)
		);
	}


	/**
	 * Get the remaining quota
	 *
	 * @param  integer $product_id post/product id.
	 * @param  integer $group_id the group id.
	 * @return string
	 */
	public static function remaining_quota( $product_id, $group_id ): string {
		return get_post_meta( $product_id, '_quotas_field_' . $group_id, true );
	}

	/**
	 * Get Product Quoata
	 *
	 * @param  mixed $product_id Product Id.
	 * @param  bool  $check_cart Whether to check in cart and deduct the items.
	 * @return int
	 */
	public static function get_product_quota( $product_id, $check_cart = true ): int {
		global $wpdb, $woocommerce;
		$already_in_cart = 0;
		if ( $check_cart ) {
			$cart_items = $woocommerce->cart->get_cart();
			foreach ( $cart_items as $item => $values ) {
				if ( $values['data']->get_id() === $product_id ) {
					$already_in_cart = $values['quantity'];
				}
				break;
			}
		}
		$private_client_group_id = self::$private_client_group_id;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
        SELECT *
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        LEFT JOIN {$wpdb->prefix}postmeta as post_meta ON posts.ID = post_meta.post_id
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status in (%s, %s)
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = %s
        AND post_meta.meta_value = %s
        AND post_meta.meta_key = 'groups-read'
    ",
				[ 'wc-completed', 'wc-processing', $product_id, $private_client_group_id ]
			)
		);

		$order_ids = [];
		$total     = 0;
		foreach ( $results as $result ) {
			$order_ids[] = $result->order_item_id;
		}

		if ( $order_ids ) {
			$qty = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}woocommerce_order_itemmeta" .
					" WHERE meta_key = '_qty' AND order_item_id IN (
                                   %s
                                )",
					join( ',', $order_ids )
				)
			);

			foreach ( $qty as $item ) {
				$total += (int) $item->meta_value;
			}
		}//end if

		$private = self::remaining_quota( $product_id, self::$private_client_group_id );
		$product = wc_get_product( $product_id );
		$stock   = $product->get_stock_quantity();
		if ( '' === $private ) {
			// if stock is not set return 0.
			return '' == $stock ? 0 : $stock;
		}
		if ( is_numeric( $private ) && 0 == $private ) {
			return 0;
		}
		// private_allocated - the ones bought.
		$slots_available = (int) $private - $total;
		// if the slots that can be assigned is greater than the stock total now is the stock.
		if ( $slots_available > $stock ) {
			$slots_available = $stock;
		}
		$total_available = $slots_available - $already_in_cart;
		
		return $total_available;
	}
}
