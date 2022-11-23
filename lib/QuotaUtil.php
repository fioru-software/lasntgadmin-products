<?php

namespace Lasntg\Admin\Quotas;

/**
 * QuotaUtil
 */
class QuotaUtil
{
	public static $PRIVATE_CLIENT_GROUP_ID = 69;

	/**
	 * Get the remaining quota
	 *
	 * @param  integer $product_id post/product id.
	 * @param  integer $group_id the group id.
	 * @return string
	 */
	public static function remaining_quota($product_id, $group_id): string
	{
		return get_post_meta($product_id, '_quotas_field_' . $group_id, true);
	}

	/**
	 * Reduce quota by number
	 *
	 * @param  integer $product_id post/product id.
	 * @param  integer $group_id the group id.
	 * @param  integer $by the number you want to reduce with.
	 * @return bool
	 */
	public static function reduce_quota(int $product_id, int $group_id, int $by = 1): bool
	{
		$quota = self::remaining_quota($product_id, $group_id);
		if ('0' === $quota) {
			return false;
		}
		if ('' !== $quota) {
			$quota = (int) $quota - $by;
			if ($quota < 0) {
				return false;
			}
			update_post_meta($product_id, '_quotas_field_' . $group_id, esc_attr($quota));
		}

		return true;
	}

	/**
	 * get_private_client_quota
	 *
	 * @param  mixed $product_id the product id
	 * @return void
	 */
	public static function get_private_client_quota(int $product_id)
	{
		return self::remaining_quota($product_id, self::$PRIVATE_CLIENT_GROUP_ID);
	}

	public static function get_product_remaining_quotas($product_id)
	{

		$private_quota = self::get_private_client_quota($product_id);
		echo "Private Quota: $private_quota\n";
		$product =  wc_get_product($product_id);

		$stock = $product->get_stock_quantity();
		echo "Stock: $stock\n";
		self::get_woocommerce_orders_status($product);
	}

	public static function get_previours()
	{
	}

	public static function get_orders_ids_by_product_id($product_id, $order_status = array('wc-completed'))
	{
		global $wpdb;
		$PRIVATE_CLIENT_GROUP_ID = self::$PRIVATE_CLIENT_GROUP_ID;
		$results = $wpdb->get_results("
        SELECT *
        FROM {$wpdb->prefix}woocommerce_order_items as order_items
        LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id
        LEFT JOIN {$wpdb->posts} AS posts ON order_items.order_id = posts.ID
        LEFT JOIN {$wpdb->prefix}postmeta as post_meta ON posts.ID = post_meta.post_id
        WHERE posts.post_type = 'shop_order'
        AND posts.post_status IN ( '" . implode("','", $order_status) . "' )
        AND order_items.order_item_type = 'line_item'
        AND order_item_meta.meta_key = '_product_id'
        AND order_item_meta.meta_value = '$product_id'
        AND post_meta.meta_value = '$PRIVATE_CLIENT_GROUP_ID'
        AND post_meta.meta_key = 'groups-read'
    ");
		$order_ids = [];
		$total = 0;
		foreach ($results as $result) {
			$order_ids[] = $result->order_item_id;
		}
		if ($order_ids) {
			$order_ids = implode("','", $order_ids);
			$qty = $wpdb->get_results("
			SELECT * FROM {$wpdb->prefix}woocommerce_order_itemmeta
			WHERE meta_key = '_qty' AND order_item_id IN ($order_ids)
		");

			foreach ($qty as $item) {
				$total += (int)$item->meta_value;
			}
		}
		$private = self::get_private_client_quota($product_id);
		
		if($private == ""){
			return -1;
		}
		
		$private = (int) $private - $total;
		return $total;
	}

	private static function get_woocommerce_orders_status($product)
	{
		// Is a WC product
		if (is_a($product, 'WC_Product')) {
			// Set the orders statuses (without wc-).
			$statuses = array('wc-completed', 'wc-cancelled', 'wc-processing', 'wc-pending');

			$total_sales = self::get_orders_ids_by_product_id($product->get_id(), $statuses);

			// Display result
			if ($total_sales >= 1) {
				echo sprintf(__('Total sales: %d', 'woocommerce'), $total_sales);
			} else {
				echo __('N/A', 'woocommerce');
			}
		}
	}

	public static function get_all_groups()
	{

		global $wpdb;

		$groups_table = _groups_get_tablename('group');

		return $wpdb->get_results("SELECT * FROM $groups_table ORDER BY name");
	}
}
