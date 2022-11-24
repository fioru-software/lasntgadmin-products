<?php

namespace Lasntg\Admin\Quotas;

/**
 * QuotaUtil
 */
class QuotaUtil {


	public static $private_client_group_id = 69;


	public static function lasntgadmin_add_group( $post_id ) {
		$group_ids[] = self::$private_client_group_id;
		error_log( "==== Post Id $post_id === add to groups" );
		\Groups_Post_Access::update(
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
		$statuses                = [ 'wc-completed', 'wc-processing' ];

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
        SELECT *
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        LEFT JOIN {$wpdb->prefix}postmeta as post_meta ON posts.ID = post_meta.post_id
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status in ('" . implode( "','", $statuses ) . "')
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = %s
        AND post_meta.meta_value = %s
        AND post_meta.meta_key = 'groups-read'
    ",
				[ $product_id, $private_client_group_id ]
			)
		);

		$order_ids = [];
		$total     = 0;
		foreach ( $results as $result ) {
			$order_ids[] = $result->order_item_id;
		}

		if ( $order_ids ) {
			$in_str_arr = array_fill( 0, count( $order_ids ), '%s' );
			// create a string of %s - one for each array value. This creates array( '%s', '%s', '%s' )
			$in_str = join( ',', $in_str_arr );
			// now turn it into a comma separated string. This creates "%s,%s,%s"

			$sql = $wpdb->prepare(
				" SELECT * FROM {$wpdb->prefix}woocommerce_order_itemmeta
			WHERE meta_key = '_qty' AND order_item_id IN (
                                   $in_str
                                )",
				$order_ids
			);

			$qty = $wpdb->get_results(
				$sql
			);

			foreach ( $qty as $item ) {
				$total += (int) $item->meta_value;
			}
		}//end if

		$private = self::remaining_quota( $product_id, self::$private_client_group_id );

		if ( '' === $private ) {
			$product = wc_get_product( $product_id );
			$stock   = $product->get_stock_quantity();
			// if stock is not set return 0.
			return '' == $stock ? 0 : $stock;
		}
		if ( is_numeric( $private ) && 0 == $private ) {
			return 0;
		}
		return ( (int) $private - $total ) - $already_in_cart;
	}
}
