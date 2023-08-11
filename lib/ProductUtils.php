<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;

use WC_Product;

/**
 * ProductUtils
 */
class ProductUtils {

	public static $publish_status = 'open_for_enrollment';
	public static $statuses       = [
		'template'            => 'Template',
		'open_for_enrollment' => 'Open for Enrolment',
		'enrollment_closed'   => 'Enrolment Closed',
		'date_passed'         => 'Date Passed',
		'closed'              => 'Closed',
		'cancelled'           => 'Cancelled',
		'archived'            => 'Archived',
	];

	public static function is_open_for_enrollment( WC_Product $product ): bool {
		return $product->get_status() === self::$publish_status;
	}
	public static function is_open_for_enrollment_by_product_id( int $product_id ): bool {
		$product = wc_get_product( $product_id );
		return $product->get_status() === self::$publish_status;
	}

	public static function is_funded( WC_Product $product ): bool {
		$funding_source_slugs = $product->get_meta( 'funding_sources', true );
		$grant_year           = $product->get_meta( 'grant_year', true );
		return empty( $funding_source_slugs ) || empty( $grant_year ) ? false : true;
	}

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
		$post_ids = get_posts(
			[
				'fields'         => 'ids',
				'post_status'    => self::$publish_status,
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'meta_query'     => [
					'relation' => 'OR',
					[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found, Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey
						'key'     => 'groups-read',
						'compare' => '=',
						'type'    => 'NUMERIC',
						'value'   => $group_id,
					],
					[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found
						'key'     => 'groups-read',
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);
		$products = array_map( fn( $post_id) => wc_get_product( $post_id ), $post_ids );
		return $products;
	}

	/**
	 * Get products with the same group memberships as my user.
	 *
	 * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 * @return WC_Product[]
	 */
	public static function get_visible_products(): array {
		$post_ids = get_posts(
			[
				'fields'         => 'ids',
				'post_status'    => self::$publish_status,
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'meta_query'     => [
					'relation' => 'OR',
					[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found, Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey
						'key'     => 'groups-read',
						'compare' => 'IN',
						'type'    => 'NUMERIC',
						'value'   => GroupUtils::get_current_users_group_ids(),
					],
					[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found
						'key'     => 'groups-read',
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);
		$products = array_map( fn( $post_id) => wc_get_product( $post_id ), $post_ids );
		return $products;
	}

	/**
	 * Get products with status
	 *
	 * @return WC_Product[]
	 */
	public static function get_products_with_status( string $status ): array {
		require_once '/var/www/html/wp-admin/includes/post.php';
		$statuses = get_available_post_statuses( 'product' );
		if ( in_array( $status, get_available_post_statuses( 'product' ) ) ) {
			$post_ids = get_posts(
				[
					'fields'         => 'ids',
					'post_status'    => trim( $status ),
					'post_type'      => 'product',
					'posts_per_page' => -1,
				]
			);
			$products = array_map( fn( $post_id) => wc_get_product( $post_id ), $post_ids );
			return $products;
		}
		return [];
	}

	/**
	 * Get All orders IDs for a given product ID.
	 *
	 * @todo rename to get_order_ids_by_product_id
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

	public static function get_total_items( $order_ids ) {
		$total = 0;
		foreach ( $order_ids as $order_id ) {
			$order  = wc_get_order( $order_id );
			$total += $order->get_item_count();
		}

		return $total;
	}
}
