<?php
/**
 * All related Actions and filters for Quota
 */

namespace Lasntg\Admin\Quotas;

use Lasntg\Admin\Quotas\QuotaUtils;
/**
 * Handles Actions and Filters related to Quota functions
 */
class QuotasActionsFilters {
	/**
	 * Initiates the filtesr and actions for Quota related functions
	 *
	 * @return void
	 */
	public static function init(): void {
		// filters.
		add_filter( 'woocommerce_is_purchasable', [ self::class, 'product_is_in_stock' ], 10, 2 );
		add_filter( 'woocommerce_product_data_tabs', [ self::class, 'product_tab' ], 10, 1 );
		add_filter( 'woocommerce_product_meta_start', [ self::class, 'show_out_of_stock_message' ], 1 );
		add_filter( 'woocommerce_get_availability_text', [ self::class, 'stock_filter' ], 10, 2 );
		add_filter( 'woocommerce_add_to_cart_validation', [ self::class, 'add_to_cart_validation' ], 10, 6 );
		add_filter( 'woocommerce_update_cart_validation', [ self::class, 'update_cart_validation' ], 10, 6 );

		// actions.
		add_action( 'woocommerce_product_data_panels', [ self::class, 'product_tab_data' ], 10, 1 );
		add_action( 'woocommerce_process_product_meta', [ self::class, 'add_quotas_save' ], 10, 1 );
		add_action( 'woocommerce_before_cart_contents', [ self::class, 'show_quota_to_client' ], 10, 0 );
		add_action( 'woocommerce_checkout_order_processed', [ self::class, 'add_checkout_orders_to_private' ], 10, 1 );
	}
	/**
	 * Show tab
	 *
	 * @param  array $default_tabs the default woocommerce tabs.
	 * @return array
	 */
	public static function product_tab( array $default_tabs ): array {
		$default_tabs['quotas_tab'] = array(
			'label'    => __( 'Quotas', 'lasntgadmin' ),
			'target'   => 'product_tab_data',
			'priority' => 20,
			'class'    => array(),
		);
		return $default_tabs;
	}

	/**
	 * Show tab data
	 *
	 * @return void
	 */
	public static function product_tab_data(): void {
		global $post, $wpdb;

		$results = $wpdb->get_results(
			"SELECT name,group_id FROM {$wpdb->prefix}groups_group ORDER BY name"
		);

		echo '<div id="product_tab_data" class="panel woocommerce_options_panel">';
		// Get action. For new product it's add.
		$action = get_current_screen()->action;

		foreach ( $results as $group ) {
			$groups[ $group->group_id ] = $group->name;
			woocommerce_wp_text_input(
				array(
					'id'                => '_quotas_field_' . $group->group_id,
					'label'             => $group->name,
					'placeholder'       => __( 'Leave blank for unlimited quota.', 'lasntgadmin' ),
					'desc_tip'          => 'true',
					'description'       => __( 'Leave blank for unlimited quota.', 'lasntgadmin' ),
					'value'             => 'add' !== $action ? get_post_meta( $post->ID, '_quotas_field_' . $group->group_id, true ) : 0,
					'type'              => 'number',
					'custom_attributes' => array(
						'step' => '1',
						'min'  => '0',
						'type' => 'number',
					),
				)
			);
		}

		echo '</div>';
	}

	/**
	 * Save Quotas
	 *
	 * @param  mixed $post_id the product id.
	 * @return void
	 */
	public static function add_quotas_save( $post_id ): void {
		global $wpdb;
		$results = $wpdb->get_results(
			"SELECT name,group_id FROM {$wpdb->prefix}groups_group ORDER BY name"
		);
		// fields.
		foreach ( $results as $group ) {
			if ( ! isset( $_POST[ '_quotas_field_' . $group->group_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				continue;
			}
			$field = sanitize_text_field( wp_unslash( $_POST[ '_quotas_field_' . $group->group_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

			update_post_meta( $post_id, '_quotas_field_' . $group->group_id, esc_attr( $field ) );
		}
	}

	public static function show_out_of_stock_message() {
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();

		$product_id = get_the_ID();
		$orders     = QuotaUtils::get_product_quota( $product_id );
		if ( 0 == $orders ) {
			echo '<p class="stock out-of-stock">Not in stock</p>';
		}
		foreach ( $items as $values ) {
			$orders = QuotaUtils::get_product_quota( $values['data']->get_id() );
			if ( $values['data']->get_id() !== $product_id ) {
				echo '<p class="woocommerce-error">You can only add one course to cart.</p>';
			}
		}
	}

	public static function show_quota_to_client() {
		global $woocommerce;
		$items = $woocommerce->cart->get_cart();

		// this assumes the cart can only have one item.
		if ( ! count( $items ) ) {
			return;
		}
		$orders = null;
		foreach ( $items as $item => $values ) {
			$orders = QuotaUtils::get_product_quota( $values['data']->get_id() );
			break;
		}

		if ( $orders ) {
			echo wp_kses_post( "<p class='woocommerce-info'>Available quota remaining: $orders</p>" );
		}
	}

	public static function stock_filter( $available_text, $product ) {
		$post_id = $product->get_ID();
		$orders  = QuotaUtils::get_product_quota( $post_id );
		if ( $orders < 1 ) {
			return __( 'Not in stock', 'lasntgadmin' );
		}
		return $orders . ' in stock';
	}

	public static function product_is_in_stock( $is_in_stock, $product ) {
		global $woocommerce;

		$product_id = $product->get_ID();
		$orders     = QuotaUtils::get_product_quota( $product_id );
		$items      = $woocommerce->cart->get_cart();
		foreach ( $items as $values ) {
			$orders = QuotaUtils::get_product_quota( $values['data']->get_id() );
			if ( $values['data']->get_id() !== $product_id ) {
				return false;
			}
		}
		return $orders > 0;
	}

	public static function add_to_cart_validation( $add, $product_id, $product_quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {
		$orders = QuotaUtils::get_product_quota( $product_id );
		if ( 0 == $orders ) {
			wc_add_notice( __( 'You do not have a quota for this course.', 'lasntgadmin' ), 'error' );
			return false;
		}
		if ( $product_quantity > $orders ) {
			wc_add_notice( 'The max quantity you can enter is ' . $orders, 'error' );
			return false;
		}
		return true;
	}

	public static function update_cart_validation( $passed, $cart_item_key, $values, $quantity ) {
		$product_id = $values['product_id'];
		$orders     = QuotaUtils::get_product_quota( $product_id, false );

		if ( 0 == $orders ) {
			wc_add_notice( __( 'You do not have a quota for this course.', 'lasntgadmin' ), 'error' );
			return false;
		}
		if ( $quantity > $orders ) {
			wc_add_notice( 'The max quantity you can enter is ' . $orders, 'error' );
			return false;
		}
		return true;
	}


	public static function add_checkout_orders_to_private( $id ) {
		error_log( 'Order Checkout for order ' . $id );
		QuotaUtils::lasntgadmin_add_group( $id );
	}
}
