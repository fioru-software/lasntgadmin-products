<?php
/**
 * Product Actions Filter
 */

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Products\ProductApi;

/**
 * Handle Actions anf filters for products
 */
class ProductActionsFilters {
	/**
	 * Iniates actions and filters regarding Product
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_notices', [ self::class, 'admin_notice_errors' ], 500 );
		add_filter( 'wp_insert_post_data', [ self::class, 'filter_post_data' ], 10, 2 );
		add_filter( 'post_updated_messages', [ self::class, 'post_updated_messages_filter' ], 500 );

		add_action( 'add_meta_boxes', [ self::class, 'check_roles' ], 100 );
	}

	public static function check_roles() {
		$screen = get_current_screen();
		// disable creating courses for other users except admin, national manager and training manager.
		if ( $screen &&
			'product' === $screen->post_type
		 ) {
			if ( ! ProductUtils::is_allowed_products_edit() ) {
				ProductUtils::redirect_back();
			}
		}
	}


	/**
	 * Check to see if groups are set for post_type product
	 *
	 * @param  mixed $data the data.
	 * @param  mixed $postarr postarray.
	 * @return array
	 */
	public static function filter_post_data( $data, $postarr ): array {
		if ( ! isset( $postarr['_stock'] ) || 'product' !== $postarr['post_type'] ) {
			return $data;
		}
		$errors = [];
		if ( '0' === $postarr['_stock'] ) {
			$errors[] = __( 'Course needs to be in stock.', 'lasntgadmin' );
		}
		if ( ! isset( $postarr['groups-read'] ) || ! $postarr['groups-read'] ) {
			$errors[] = __( 'Course groups required.', 'lasntgadmin' );
		}
		if ( $errors ) {
			$data['post_status'] = 'draft';
			set_transient( 'lasntg_post_error', wp_json_encode( $errors ) );
		}
		return $data;
	}

	public static function post_updated_messages_filter( $messages ) {
		if ( get_transient( 'lasntg_post_error' ) ) {
			$messages['product'][8] = '';
		}
		return $messages;
	}


	/**
	 * Admin notice errors
	 *
	 * @return void
	 */
	public static function admin_notice_errors(): void {
		$message = get_transient( 'lasntg_post_error' );
		if ( ! $message ) {
			return;
		}
		$class = 'notice notice-error';

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( 'Product saved as draft.' ) );
		$msgs = json_decode( $message );
		foreach ( $msgs as $msg ) {
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $msg ) );
		}
		delete_transient( 'lasntg_post_error' );
	}

}
