<?php
/**
 * WP Actions also includes woocommerce actions
 */

use Lasntg\Admin\Quotas\QuotaUtil;

add_filter( 'woocommerce_product_data_tabs', 'lasntgadmin_quotas_product_tab', 10, 1 );

/**
 * Show tab
 *
 * @param  array $default_tabs the default woocommerce tabs.
 * @return array
 */
function lasntgadmin_quotas_product_tab( array $default_tabs ): array {
	$default_tabs['quotas_tab'] = array(
		'label'    => __( 'Quotas', 'lasntgadmin' ),
		'target'   => 'lasntgadmin_quotas_product_tab_data',
		'priority' => 20,
		'class'    => array(),
	);
	return $default_tabs;
}

add_action( 'woocommerce_product_data_panels', 'lasntgadmin_quotas_product_tab_data', 10, 1 );

/**
 * Show tab data
 *
 * @return void
 */
function lasntgadmin_quotas_product_tab_data(): void {
	global $post, $wpdb;

	$prefix = $wpdb->prefix;
	$table  = $prefix . 'groups_group';

	$results = $wpdb->get_results( "SELECT name,group_id FROM $table ORDER BY name" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

	echo '<div id="lasntgadmin_quotas_product_tab_data" class="panel woocommerce_options_panel">';
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

add_action( 'woocommerce_process_product_meta', 'lasntgadmin_quotas_add_quotas_save' );
/**
 * Save Quotas
 *
 * @param  mixed $post_id the product id.
 * @return void
 */
function lasntgadmin_quotas_add_quotas_save( $post_id ): void {
	global $wpdb;
	$prefix = $wpdb->prefix;
	$table  = $prefix . 'groups_group';

	$results = $wpdb->get_results( "SELECT name,group_id FROM $table ORDER BY name" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	// fields.
	foreach ( $results as $group ) {
		if ( ! isset( $_POST[ '_quotas_field_' . $group->group_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			continue;
		}
		$field = sanitize_text_field( wp_unslash( $_POST[ '_quotas_field_' . $group->group_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		update_post_meta( $post_id, '_quotas_field_' . $group->group_id, esc_attr( $field ) );
	}
}

add_filter( 'woocommerce_product_meta_start', 'lasntgadmin_show_out_of_stock_message' );
function lasntgadmin_show_out_of_stock_message() {
	$product_id = get_the_ID();
	$orders     = QuotaUtil::get_product_quota( $product_id );
	if ( 0 == $orders ) {
		echo '<p class="stock out-of-stock">Not in stock</p>';
	}
}
add_action( 'woocommerce_before_cart_contents', 'lasntgadmin_show_quota_to_client', 10 );

function lasntgadmin_show_quota_to_client() {
	global $woocommerce;
	$items = $woocommerce->cart->get_cart();

	// this assumes the cart can only have one item.
	if ( ! count( $items ) ) {
		return;
	}
	$orders = null;
	foreach ( $items as $item => $values ) {
		$orders = QuotaUtil::get_product_quota( $values['data']->get_id() );
		break;
	}

	if ( $orders ) {
		echo wp_kses_post( "<h3>Available quota remaining: $orders</h3>" );
	}
}


function lasntgadmin_stock_filter( $available_text, $product ) {
	$post_id = $product->get_ID();
	$orders  = QuotaUtil::get_product_quota( $post_id );
	if ( $orders < 1 ) {
		return __( 'Not in stock', 'lasntgadmin' );
	}
	return $orders . ' in stock';
}
add_filter( 'woocommerce_get_availability_text', 'lasntgadmin_stock_filter', 10, 2 );
add_filter( 'woocommerce_is_purchasable', 'lasntgadmin_product_is_in_stock', 10, 2 );
function lasntgadmin_product_is_in_stock( $is_in_stock, $product ) {
	$post_id = $product->get_ID();
	$orders  = QuotaUtil::get_product_quota( $post_id );
	return $orders > 0;
}

add_filter( 'woocommerce_add_to_cart_validation', 'lasntgadmin_quotas_add_to_cart_validation', 10, 6 );
add_filter( 'woocommerce_update_cart_validation', 'lasntgadmin_quotas_update_cart_validation', 10, 6 );

function lasntgadmin_quotas_add_to_cart_validation( $add, $product_id, $product_quantity, $variation_id = '', $variations = array(), $cart_item_data = array() ) {
	$orders = QuotaUtil::get_product_quota( $product_id );
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

function lasntgadmin_quotas_update_cart_validation( $passed, $cart_item_key, $values, $quantity ) {
	$product_id = $values['product_id'];
	$orders     = QuotaUtil::get_product_quota( $product_id, false );

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
