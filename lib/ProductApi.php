<?php

namespace Lasntg\Admin\Products;

use WP_REST_Request, WP_Error;
use Lasntg\Admin\Products\ProductUtils;

/**
 * ProductApi
 */
class ProductApi {

	protected static $instance = null;

	const PATH_PREFIX = 'lasntgadmin/products/v1';

	protected function __construct() {
		register_rest_route(
			self::PATH_PREFIX,
			'/products',
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
		if ( ! wp_verify_nonce( $req->get_header( 'X-WP-Nonce' ), 'wp_rest' ) ) {
			return new WP_Error( 'invalid_nonce', 'Invalid nonce', array( 'status' => 403 ) );
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
		$products = ProductUtils::get_visible_products();
		return array_map( fn( $product) => $product->get_data(), $products );
	}

}
