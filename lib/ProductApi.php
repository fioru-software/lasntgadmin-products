<?php

namespace Lasntg\Admin\Products;

use WP_REST_Request, WP_Error, WP_REST_Server;
use Lasntg\Admin\Products\ProductUtils;
use Lasntg\Admin\Group\GroupUtils;

/**
 * ProductApi
 */
class ProductApi {

	protected static $instance = null;

	const PATH_PREFIX = 'lasntgadmin/products/v1';

	protected function __construct() {

		register_rest_route(
			self::PATH_PREFIX,
			'/product/(?P<product_id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_product_by_id' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::PATH_PREFIX,
			'/product/group-quota/?',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_product_group_quota' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::PATH_PREFIX,
			'/products/?',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_products_visible_to_current_user' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::PATH_PREFIX,
			'/products/(?P<group_id>\d+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_products_in_group' ],
				'permission_callback' => '__return_true',
			]
		);

		register_rest_route(
			self::PATH_PREFIX,
			'/products/(?P<status>\w+)',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ self::class, 'get_products_with_status' ],
				'permission_callback' => '__return_true',
			]
		);
	}

	public static function get_instance(): ProductApi {
		if ( null === self::$instance ) {
			self::$instance = new ProductApi();
		}
		return self::$instance;
	}

	public static function get_api_path(): string {
		return sprintf( '/%s', self::PATH_PREFIX );
	}

	public static function verify_group_membership( WP_REST_Request $req ) {

		/**
		 * Verify user is a member of the group
		 */
		$group_id = $req->get_param( 'group_id' );
		if ( ! in_array( $group_id, GroupUtils::get_current_users_group_ids() ) ) {
			return new WP_Error( 'invalid_group', 'Invalid group', array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * GET
	 *
	 * @param WP_REST_Request $req The WordPress request object.
	 * @return array|WP_Error
	 */
	public static function get_products_in_group( WP_REST_Request $req ): array {
		$group_id = intval( $req->get_param( 'group_id' ) );
		$status   = ProductUtils::$publish_status;

		/**
		 * Products in the private client group is public and should not be restricted.
		 */
		if ( defined( 'REST_REQUEST' ) && 33 == $group_id ) {
			remove_filter( 'posts_where', [ 'Groups_Post_Access', 'posts_where' ] );
		}
		$cache_key = sprintf(
			'method=get_products_visible_to_group&group_id=%d&status[]=%s',
			intval( $group_id ),
			$status
		);

		$products = get_transient( $cache_key );
		if ( false === $products ) {
			$products = ProductUtils::get_products_visible_to_group( $group_id, [ $status ] );
			set_transient( $cache_key, $products, HOUR_IN_SECONDS );
		}
		return $products;
	}

	public static function get_products_visible_to_current_user( WP_REST_Request $req ): array {
		$products = ProductUtils::get_visible_products();
		return array_map( fn( $product ) => $product->get_data(), $products );
	}

	public static function get_products_with_status( WP_REST_Request $req ): array {
		$status   = trim( $req->get_param( 'status' ) );
		$products = ProductUtils::get_products_with_status( $status );
		return array_map( fn( $product ) => $product->get_data(), $products );
	}

	public static function get_product_by_id( WP_REST_Request $req ): array {
		$product_id = intval( $req->get_param( 'product_id' ) );
		$product    = wc_get_product( $product_id );
		return [
			'id'                      => $product_id,
			'name'                    => html_entity_decode( $product->get_name() ),
			'reserved_stock_quantity' => wc_get_held_stock_quantity( $product ),
			'price'                   => $product->get_price(),
			'stock_quantity'          => $product->get_stock_quantity(),
		];
	}

	/**
	 * @return Empty string means no quota
	 */
	public static function get_product_group_quota( WP_REST_Request $req ): string {
		$product_id = intval( $req->get_param( 'product_id' ) );
		$group_id   = intval( $req->get_param( 'group_id' ) );
		$quota      = QuotaUtils::remaining_quota( $product_id, $group_id );
		return $quota;
	}
}
