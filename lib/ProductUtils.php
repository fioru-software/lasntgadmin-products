<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;
use Lasntg\Admin\Orders\OrderUtils;

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
		return $product->get_status() === self::$publish_status || 'date_passed' === $product->get_status();
	}
	public static function is_open_for_enrollment_by_product_id( int $product_id ): bool {
		$product = wc_get_product( $product_id );
		return $product->get_status() === self::$publish_status || 'date_passed' === $product->get_status();
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
	 * @param int    $group_id The group id.
	 * @param string $status The course status eg. open_for_enrollment.
	 * @return WC_Product[]
	 */
	public static function get_products_visible_to_group( int $group_id, string $status ): array {
		$post_ids = self::get_product_ids_visible_to_group( $group_id, $status );
		$products = array_map( fn( $post_id ) => wc_get_product( $post_id ), $post_ids );
		return $products;
	}

	/**
	 * @param int             $group_id Group product is visible to.
	 * @param string|string[] $status Product status.
	 * @return int[] Course ids.
	 */
	public static function get_product_ids_visible_to_group( int $group_id, $status ): array {
		$post_ids = get_posts(
			[
				'fields'         => 'ids',
				'post_status'    => $status,
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
		return $post_ids;
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
				'post_status'    => [ self::$publish_status, 'date_passed' ],
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'meta_query'     => [
					'relation' => 'OR',
					[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found, Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey
						'key'     => 'groups-read',
						'compare' => 'IN',
						'type'    => 'NUMERIC',
						'value'   => GroupUtils::get_current_users_group_ids_deep(),
					],
					[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found
						'key'     => 'groups-read',
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);
		$products = array_map( fn( $post_id ) => wc_get_product( $post_id ), $post_ids );
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
			$products = array_map( fn( $post_id ) => wc_get_product( $post_id ), $post_ids );
			return $products;
		}
		return [];
	}

	/**
	 * @deprecated
	 */
	public static function get_orders_ids_by_product_id( int $product_id, array $order_status = [ 'wc-processing', 'wc-completed' ] ): array {
		return OrderUtils::get_order_ids_by_product_id( $product_id, 0, $order_status );
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
