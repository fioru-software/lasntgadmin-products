<?php
/**
 * Product Actions Filter
 */

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;

use WP_Post;

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
		// actions.
		add_action( 'rest_api_init', [ ProductApi::class, 'get_instance' ] );
		add_action( 'admin_notices', [ self::class, 'admin_notice_errors' ], 500 );

		add_action( 'add_meta_boxes', [ self::class, 'check_roles' ], 100 );
		add_action( 'woocommerce_product_data_tabs', [ self::class, 'remove_unwanted_tabs' ], 999 );
		add_action( 'admin_menu', [ self::class, 'remove_woocommerce_products_taxonomy' ], 99 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'admin_enqueue_scripts' ], 99 );
		add_action( 'edit_form_after_title', [ self::class, 'hidden_status' ] );
		add_action( 'init', [ self::class, 'register_custom_product_statuses' ], 0 );

		// filters.
		add_filter( 'wp_insert_post_data', [ self::class, 'filter_post_data' ], 99, 2 );
		add_filter( 'wp_insert_post', [ self::class, 'cancel_orders' ], 10, 3 );
		add_filter( 'post_updated_messages', [ self::class, 'post_updated_messages_filter' ], 500 );
		add_filter( 'post_row_actions', [ self::class, 'remove_quick_edit' ], 10, 1 );
		add_filter( 'manage_product_posts_columns', [ self::class, 'rename_sku_column' ], 11 );

		add_filter( 'woocommerce_product_meta_start', [ self::class, 'woocommerce_get_availability_text' ], 10, 2 );
		add_filter( 'woocommerce_is_purchasable', [ self::class, 'product_is_in_stock' ], 15, 2 );

		add_filter( 'woocommerce_product_query', [ self::class, 'woocommerce_product_query' ], 15, 1 );
	}

	/**
	 * Only show products that have $publish_status in the shops page.
	 *
	 * @param  WP_Query $q Query.
	 * @return void
	 */
	public static function woocommerce_product_query( $q ): void {
		$q->set( 'post_status', ProductUtils::$publish_status );

		// check the product has private group.
		$args = array(
			array(
				'key'     => 'groups-read',
				'value'   => 33,
				// private client group id is 33.
				'compare' => '=',
				'type'    => 'numeric',
			),
		);

		$q->set( 'meta_query', $args );
	}


	/**
	 * Determine if product is in stock depending on the course status.
	 *
	 * @param  bool       $is_in_stock In stock.
	 * @param  WC_Product $product WC_product.
	 * @return bool
	 */
	public static function product_is_in_stock( $is_in_stock, $product ): bool {
		$group_ids = GroupUtils::get_read_group_ids( $product->get_id() );

		if ( ! in_array( 33, $group_ids ) ) {
			return false;
		}
		if ( ProductUtils::$publish_status === $product->get_status() ) {
			return $is_in_stock;
		}
		return false;
	}

	/**
	 * Show if the product is available depending on the status.
	 *
	 * @return void
	 */
	public static function woocommerce_get_availability_text(): void {
		$product_id = get_the_ID();
		$product    = wc_get_product( $product_id );
		if ( ProductUtils::$publish_status !== $product->get_status() ) {
			$status = ProductUtils::get_status_name( $product->get_status() );
			echo '<p class="stock out-of-stock">Course not available: ' . esc_attr( $status ) . '</p>';
		}

		$group_ids = GroupUtils::get_read_group_ids( $product_id );

		if ( ! in_array( 33, $group_ids ) ) {
			echo '<p class="stock out-of-stock">Course is not available.</p>';
		}
	}

	/**
	 * Show out of stock depending on course status.
	 *
	 * @param  string            $available_text the available text.
	 * @param  object WC_Product $product wc_product.
	 * @return string
	 */
	public static function stock_filter( $available_text, $product ): string {
		if ( 'publish' !== $product->get_status() ) {
			$status = ProductUtils::get_status_name( $product->get_status() );
			return '<p class="stock out-of-stock">Course not available: ' . esc_attr( $status ) . '</p>';
		}

		return $available_text;
	}

	/**
	 * Hidden status input.
	 * I added this input to be updated when OK button is clicked before publish.
	 * Since the status field was being overridden by Woocommerce.
	 *
	 * @return void
	 */
	public static function hidden_status(): void {
		global $post;
		if ( 'product' !== $post->post_type ) {
			return;
		}
		print '<input type="hidden" name="lasntgadmin_status" id="lasntgadmin_status" size="30" tabindex="1" value="' . esc_attr( htmlspecialchars( $post->post_status ) ) . '" id="title" autocomplete="off" />';
	}

	/**
	 * Register the needed custom codes.
	 *
	 * @return void
	 */
	public static function register_custom_product_statuses(): void {
		foreach ( ProductUtils::$statuses as $tag => $status ) {
			register_post_status(
				$tag,
				array(
					'label'                     => $status,
					// translators: $status status.
					'label_count'               => _n_noop( $status . ' <span class="count">(%s)</span>', $status . ' <span class="count">(%s)</span>', 'lasntgadmin' ), // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural,WordPress.WP.I18n.NonSingularStringLiteralSingle
					'public'                    => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'post_type'                 => [ 'product' ],
				)
			);
		}
	}

	/**
	 * Rename sku column to course code.
	 *
	 * @param  array $columns Columns.
	 * @return array
	 */
	public static function rename_sku_column( $columns ): array {
		$columns['sku'] = __( 'Course Code', 'lasntgadmin' );
		return $columns;
	}

	/**
	 * Remove quick edit
	 *
	 * @param  array $actions post row actions array.
	 * @return array
	 */
	public static function remove_quick_edit( $actions ): array {
		unset( $actions['inline hide-if-no-js'] );
		return $actions;
	}

	/**
	 * Admin enqueue scripts
	 *
	 * @return void
	 */
	public static function admin_enqueue_scripts(): void {
		global $post;
		$post_type = property_exists( get_current_screen(), 'post_type' ) ? get_current_screen()->post_type : false;
		if ( 'product' !== $post_type ) {
			return;
		}
		$assets_dir = untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/../assets/';
		wp_enqueue_script( 'lasntgadmin-products-admin-js', ( $assets_dir . 'js/lasntgadmin-admin.js' ), array( 'jquery' ), '1.6', true );

		wp_localize_script(
			'lasntgadmin-products-admin-js',
			'lasntgadmin_products_admin_localize',
			array(
				'adminurl'      => admin_url() . 'admin-ajax.php',
				'lasntg_status' => $post ? $post->post_status : false,
				'statuses'      => ProductUtils::$statuses,
				'post_type'     => $post_type,
			)
		);
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

		if ( ! ProductUtils::is_allowed_products_edit() ) {
			$errors[] = __( 'You are not allowed to edit products.', 'lasntgadmin' );
			return $errors;
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

		$data['post_status'] = $postarr['lasntgadmin_status'];
		if ( $errors ) {
			$data['post_status'] = 'draft';
		}

		set_transient(
			'lasntg_post_error',
			wp_json_encode(
				[
					'errors'      => $errors,
					'post_status' => ProductUtils::get_status_name( $data['post_status'] ),
				]
			)
		);

		return $data;
	}

	public static function cancel_orders( int $post_ID, WP_Post $post_after, $post_before ) {
		if ( ! is_a( $post_before, 'WP_Post' ) || 'product' !== $post_after->post_type ) {
			return;
		}
		if ( $post_after->post_status !== $post_before->post_status ) {
			$order_ids = ProductUtils::get_orders_ids_by_product_id( $post_ID );
			foreach ( $order_ids as $order_id ) {
				$order = wc_get_order( $order_id );
				$order->update_status( 'wc-cancelled' );
			}
		}
	}
	/**
	 * Overide the Woocommerce success message if there's an error.
	 *
	 * @param  array $messages Messages.
	 * @return array
	 */
	public static function post_updated_messages_filter( $messages ): array {
		if ( get_transient( 'lasntg_post_error' ) || get_transient( 'lasntg_clear_woocommerce' ) ) {
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

		$msgs = json_decode( $message, true );
		if ( $msgs['errors'] ) {
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( 'Course saved as draft.' ) );
			foreach ( $msgs['errors'] as $msg ) {
				printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $msg ) );
			}
		} else {
			printf( '<div class="notice notice-success"><p>Course saved with status %1$s</p></div>', esc_html( $msgs['post_status'] ) );
		}
		delete_transient( 'lasntg_post_error' );
	}

}
