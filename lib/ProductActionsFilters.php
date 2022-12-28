<?php
/**
 * Product Actions Filter
 */

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;

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
		add_action( 'rest_api_init', [ ProductApi::class, 'get_instance' ] );
		add_action( 'admin_notices', [ self::class, 'admin_notice_errors' ], 500 );
		add_filter( 'wp_insert_post_data', [ self::class, 'filter_post_data' ], 99, 2 );
		add_filter( 'post_updated_messages', [ self::class, 'post_updated_messages_filter' ], 500 );

		add_action( 'add_meta_boxes', [ self::class, 'check_roles' ], 100 );

		add_action( 'woocommerce_product_data_tabs', [ self::class, 'remove_unwanted_tabs' ], 999 );
		add_action( 'admin_menu', [ self::class, 'remove_woocommerce_products_taxonomy' ], 99 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'admin_enqueue_scripts' ], 99 );
	}

	public static function admin_enqueue_scripts(): void {
		$assets_dir = untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/../assets/';
		wp_enqueue_script( 'lasntgadmin-users-admin-js', ( $assets_dir . 'js/lasntgadmin-admin.js' ), array( 'jquery' ), '1.4', true );
	}
	/**
	 * Remove unwanted woocommerce admin links
	 *
	 * @return void
	 */
	public static function remove_woocommerce_products_taxonomy(): void {
		// make sure administrator can see woocommerce menu items.
		if ( current_user_can( 'edit_product_terms' ) ) {
			return;
		}
		remove_submenu_page( 'edit.php?post_type=product', 'product_attributes' );
		remove_submenu_page( 'edit.php?post_type=product', 'product-reviews' );

		$ptype = 'product';
		remove_submenu_page( "edit.php?post_type={$ptype}", "edit-tags.php?taxonomy=product_tag&amp;post_type={$ptype}" );
		remove_submenu_page( "edit.php?post_type={$ptype}", "edit-tags.php?taxonomy=product_cat&amp;post_type={$ptype}" );
	}


	/**
	 * Remove unwanted woocommerce tabs in product.
	 *
	 * @param  mixed $tabs tabs.
	 * @return array tabs.
	 */
	public static function remove_unwanted_tabs( $tabs ): array {
		$unwanted_tabs = [
			'marketplace-suggestions',
			'shipping',
			'linked_product',
			'attribute',
			'advanced',
			'inventory',
			'general',
		];

		foreach ( $unwanted_tabs as $tab ) {
			if ( isset( $tabs[ $tab ] ) ) {
				unset( $tabs[ $tab ] );
			}
		}

		return $tabs;
	}

	/**
	 * Checks if role can edit products. If not redirects back.
	 *
	 * @return void
	 */
	public static function check_roles(): void {
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
		if ( '0' === $postarr['_stock'] || empty( $postarr['_stock'] ) ) {
			$errors[] = __( 'Capacity is required.', 'lasntgadmin' );
		}

		if ( empty( $postarr['tax_input'] ) ) {
			$errors[] = __( 'Category is required.', 'lasntgadmin' );
		} else {
			$sum = array_sum( array_values( $postarr['tax_input']['product_cat'] ) );

			if ( ! $sum ) {
				$errors[] = __( 'Category is required.', 'lasntgadmin' );
			}
		}

		if ( empty( $postarr['_sku'] ) ) {
			$errors[] = __( 'Course code is required.', 'lasntgadmin' );
		}

		if ( empty( $postarr['post_title'] ) ) {
			$errors[] = __( 'Name is required.', 'lasntgadmin' );
		}

		if ( '0' === $postarr['_regular_price'] || empty( $postarr['_regular_price'] ) ) {
			$errors[] = __( 'Course cost is required.', 'lasntgadmin' );
		}

		if ( ! isset( $postarr['groups-read'] ) || ! $postarr['groups-read'] ) {
			$errors[] = __( 'Groups is required.', 'lasntgadmin' );
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
			$messages['product'][6] = '';
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
