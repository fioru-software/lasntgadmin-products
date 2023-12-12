<?php
/**
 * All related Actions and filters for Quota
 */

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Products\QuotaUtils;
use Groups_Utility;
use Groups_Group;
use Lasntg\Admin\Group\GroupUtils;
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
		add_filter( 'woocommerce_add_to_cart_validation', [ self::class, 'add_to_cart_validation' ], 10, 3 );
		add_filter( 'woocommerce_update_cart_validation', [ self::class, 'update_cart_validation' ], 10, 6 );

		// actions.
		add_action( 'woocommerce_product_data_panels', [ self::class, 'product_tab_data' ], 10, 1 );
		add_action( 'woocommerce_product_data_panels', [ self::class, 'course_capacity_tab_data' ], 10, 1 );
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
		$default_tabs['quotas_tab']      = array(
			'label'    => __( 'Quotas', 'lasntgadmin' ),
			'target'   => 'product_tab_data',
			'priority' => 20,
			'class'    => array(),
		);
		$default_tabs['course_capacity'] = array(
			'label'    => __( 'Course Data', 'lasntgadmin' ),
			'target'   => 'course_capacity_tab_data',
			'priority' => 19,
			'class'    => array(),
		);
		return $default_tabs;
	}

	public static function course_capacity_tab_data() {
		global $post;
		$product = wc_get_product( $post->ID );

		echo '<div id="course_capacity_tab_data" class="panel woocommerce_options_panel">';
		echo '<input type="hidden" name="_manage_stock" value="yes"/>';
		woocommerce_wp_text_input(
			array(
				'id'                => '_regular_price',
				'label'             => __( 'Course Cost', 'lasntgadmin' ),
				'placeholder'       => __( 'Course Cost.', 'lasntgadmin' ),
				'desc_tip'          => 'true',
				'value'             => $product ? $product->get_regular_price() : 0,
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
					'type' => 'number',
				),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => '_stock',
				'label'             => __( 'Course Capacity', 'lasntgadmin' ),
				'placeholder'       => __( 'Course Capacity', 'lasntgadmin' ),
				'desc_tip'          => 'true',
				'value'             => $product ? $product->get_stock_quantity() : 0,
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
					'type' => 'number',
				),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => '_minimum_capacity',
				'label'             => __( 'Minimum Capacity', 'lasntgadmin' ),
				'placeholder'       => __( 'Minimum Capacity', 'lasntgadmin' ),
				'desc_tip'          => 'true',
				'value'             => $product ? get_post_meta( $product->get_ID(), '_minimum_capacity', true ) : 0,
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
					'type' => 'number',
				),
			)
		);

		if ( $product ) {
			$order_ids = ProductUtils::get_orders_ids_by_product_id( $post->ID, [ 'wc-completed', 'wc-on-hold', 'wc-processing' ] );
			$sales     = ProductUtils::get_total_items( $order_ids );
			$total     = $product->get_stock_quantity() + $sales;

			woocommerce_wp_text_input(
				array(
					'id'                => 'total_capacity',
					'label'             => __( 'Course Total Capacity', 'lasntgadmin' ),
					'placeholder'       => __( 'Course Total Capacity', 'lasntgadmin' ),
					'desc_tip'          => 'true',
					'value'             => $total,
					'type'              => 'number',
					'custom_attributes' => array(
						'step'     => '1',
						'min'      => '0',
						'type'     => 'number',
						'readonly' => true,
					),
				)
			);
		}//end if

		echo '</div>';
	}

	/**
	 * Show tab data
	 *
	 * @return void
	 */
	public static function product_tab_data(): void {
		global $post;

		echo '<div id="product_tab_data" class="panel woocommerce_options_panel" style="padding-left: 5px">';
		// Get action. For new product it's add.
		$action = get_current_screen()->action;
		$tree   = Groups_Utility::get_group_tree();
		unset( $tree[1] );
		unset( $tree[33] );

		$allowed_groups = GroupUtils::formatted_current_user_tree();

		foreach ( $allowed_groups as $parent_id => $groups ) {
			if ( ! $groups ) {
				continue;
			}
			$group      = new Groups_Group( $parent_id );
			$group_name = esc_attr( $group->name );
			echo "<h3>$group_name</h3>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			$parent_id = (int) $parent_id;
			if ( count( $groups ) > 1 ) {
				echo "<div style='display: flex;justify-content: space-evenly;'>"
				. "<button type='button' data-id='$parent_id' class='set-all-zero button button-small hide-if-no-js'>" . __( 'Set All to 0', 'lasntgadmin' ) . '</button>' //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				. "<button type='button' data-id='$parent_id' class='set-all-unlimited button button-small hide-if-no-js'>" . __( 'Set All to unlimited', 'lasntgadmin' ) . '</button>' //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				. '</div>';
			}

			foreach ( $groups as $group ) {
				$groups[ $group->group_id ] = $group->name;
				woocommerce_wp_text_input(
					array(
						'id'                => '_quotas_field_' . $group->group_id,
						'label'             => $group->name,
						'placeholder'       => __( 'Leave blank for unlimited quota.', 'lasntgadmin' ),
						'desc_tip'          => 'true',
						'description'       => __( 'Leave blank for unlimited quota.', 'lasntgadmin' ),
						'value'             => 'add' !== $action ? get_post_meta( $post->ID, '_quotas_field_' . $group->group_id, true ) : '',
						'type'              => 'number',
						'custom_attributes' => array(
							'step'        => '1',
							'min'         => '0',
							'type'        => 'number',
							'data-parent' => $parent_id,
						),
					)
				);
			}
		}//end foreach
		$user_groups = GroupUtils::get_current_users_group_ids();
		if ( in_array( 33, $user_groups ) ) {
			$group = new \Groups_Group( 33 );
			echo '<h3>Private Client</h3>';
			woocommerce_wp_text_input(
				array(
					'id'                => '_quotas_field_' . $group->group_id,
					'label'             => $group->name,
					'placeholder'       => __( 'Leave blank for unlimited quota.', 'lasntgadmin' ),
					'desc_tip'          => 'true',
					'description'       => __( 'Leave blank for unlimited quota.', 'lasntgadmin' ),
					'value'             => 'add' !== $action ? get_post_meta( $post->ID, '_quotas_field_' . $group->group_id, true ) : '',
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
			$field     = sanitize_text_field( wp_unslash( $_POST[ '_quotas_field_' . $group->group_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$old_value = get_post_meta( $post_id, '_quotas_field_' . $group->group_id, true );
			$quota     = QuotaUtils::get_product_quota( $post_id, false, $group->group_id );
			update_post_meta( $post_id, '_quotas_field_' . $group->group_id, esc_attr( $field ) );

			if ( $old_value != $field && 0 === $quota ) {
				do_action( 'lasntgadmin-products_quotas_field_changed', $post_id, $group->group_id, $old_value, $field );
			}
		}

		if ( isset( $_POST['_minimum_capacity'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$field = sanitize_text_field( wp_unslash( $_POST['_minimum_capacity'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $post_id, '_minimum_capacity', esc_attr( $field ) );
		}
	}

	public static function show_out_of_stock_message() {
		global $woocommerce;
		if ( ! is_null( $woocommerce->cart ) ) {
			$items = $woocommerce->cart->get_cart();

			$product_id = get_the_ID();
			$orders     = QuotaUtils::get_product_quota( $product_id );
			if ( 0 == $orders ) {
				printf(
					'<p class="stock out-of-stock">%s</p>',
					__( 'Not in stock', 'lasntgadmin' ) //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				);
			}
			foreach ( $items as $values ) {
				$orders = QuotaUtils::get_product_quota( $values['data']->get_id() );
				if ( $values['data']->get_id() !== $product_id ) {
					printf(
						'<p class="woocommerce-error">%s</p>',
						__( 'You can only add one course to cart.', 'lasntgadmin' ) //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					);
				}
			}
		}//end if
	}

	public static function show_quota_to_client() {
		global $woocommerce;
		if ( ! is_null( $woocommerce->cart ) ) {
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
				echo ( sprintf( "<p class='woocommerce-info'>%s %d</p>", __( 'Available quota remaining:', 'lasntgadmin' ), esc_attr( $orders ) ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}
	}

	/**
	 * Show if in stock and how many for private client.
	 *
	 * @param  mixed $available_text ignored.
	 * @param  mixed $product WC_Product.
	 * @return string
	 */
	public static function stock_filter( $available_text, $product ): string {
		$post_id = $product->get_ID();
		$orders  = QuotaUtils::get_product_quota( $post_id );
		if ( $orders < 1 ) {
			return __( 'Not in stock', 'lasntgadmin' );
		}
		// translators: in stock.
		return sprintf( __( '%d in stock', 'lasntgadmin' ), $orders );
	}

	/**
	 * Check if product is in stock for private client.
	 *
	 * @param  bool   $is_in_stock Ignored.
	 * @param  object $product WC_Product.
	 * @return bool
	 */
	public static function product_is_in_stock( $is_in_stock, $product ): bool {
		global $woocommerce;
		if ( ! $is_in_stock ) {
			return $is_in_stock;
		}
		$product_id = $product->get_ID();
		$orders     = QuotaUtils::get_product_quota( $product_id );
		if ( ! is_null( $woocommerce->cart ) ) {
			$items = $woocommerce->cart->get_cart();
			foreach ( $items as $values ) {
				$orders = QuotaUtils::get_product_quota( $values['data']->get_id() );
				if ( $values['data']->get_id() !== $product_id ) {
					return false;
				}
			}
		}
		return $orders > 0;
	}

	/**
	 * Shold the product be added to cart
	 *
	 * @param  bool    $add Should it be added.
	 * @param  integer $product_id Product ID.
	 * @param  integer $product_quantity Product Quantity.
	 * @return bool
	 */
	public static function add_to_cart_validation( $add, $product_id, $product_quantity ): bool {
		$orders = QuotaUtils::get_product_quota( $product_id );
		if ( 0 == $orders ) {
			wc_add_notice( __( 'You do not have a quota for this course.', 'lasntgadmin' ), 'error' );
			return false;
		}
		if ( $product_quantity > $orders ) {
			wc_add_notice(
				// translators: max quantity.
				sprintf( __( 'The max quantity you can enter is %d', 'lasntgadmin' ), $orders ),
				'error'
			);
			return false;
		}
		return $add;
	}

	/**
	 * Update cart validation
	 *
	 * @param  bool  $passed if it has already passed/failed.
	 * @param  mixed $cart_item_key ignored.
	 * @param  array $values Array of products.
	 * @param  int   $quantity Quantity of items.
	 * @return bool
	 */
	public static function update_cart_validation( $passed, $cart_item_key, $values, $quantity ): bool {
		$product_id = $values['product_id'];
		$orders     = QuotaUtils::get_product_quota( $product_id, false );

		if ( 0 == $orders ) {
			wc_add_notice( __( 'You do not have a quota for this course.', 'lasntgadmin' ), 'error' );
			return false;
		}
		if ( $quantity > $orders ) {
			wc_add_notice(
				// translators: max quantity.
				sprintf( __( 'The max quantity you can enter is %d', 'lasntgadmin' ), $orders ),
				'error'
			);
			return false;
		}
		return $passed;
	}


	/**
	 * Add checkout orders to private.
	 *
	 * @param  integer $id ID.
	 * @return void
	 */
	public static function add_checkout_orders_to_private( $id ): void {
		QuotaUtils::lasntgadmin_add_group( $id );
	}
}
