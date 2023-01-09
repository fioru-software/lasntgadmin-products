<?php

namespace Lasntg\Admin\Products;

use WP_REST_Request, WP_Error;
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
			'/products/(?P<group_id>\d+)',
			[
				'methods'             => 'GET',
				'callback'            => [ self::class, 'get' ],
				'permission_callback' => [ self::class, 'auth_get' ],
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
		return sprintf( '/%s/products', self::PATH_PREFIX );
	}

	public static function auth_get( WP_REST_Request $req ) {

		/**
		 * Verify nonce
		 */
		if ( ! wp_verify_nonce( $req->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', 'Invalid nonce', array( 'status' => 403 ) );
		}

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
	public static function get( WP_REST_Request $req ): array {
		$group_id = $req->get_param( 'group_id' );
		$products = ProductUtils::get_products_visible_to_group( $group_id );
		return array_map( fn( $product) => $product->get_data(), $products );
	}

}
