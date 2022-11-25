<?php

/**
 * Waiting list functions
 */

use Lasntg\Admin\Quotas\QuotaUtil;

/**
 * Add new custom order status: wishlist
 */
function lasntgadmin_register_whishlist_order_status() {
	register_post_status(
		'wc-waiting_list',
		array(
			'label'                     => 'Waiting list',
			'public'                    => false,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			/* translators: %s: waiting list count */
			'label_count'               => _n_noop( 'Waiting list <span class="count">(%s)</span>', 'Waiting list <span class="count">(%s)</span>', 'lasntgadmin' ),
		)
	);
}
add_action( 'init', 'lasntgadmin_register_whishlist_order_status' );
const LASNTGADMIN_EMAIL_META_KEY     = '_LASNTG_ADMIN_EMAIL';
const LASNTGADMIN_EMAIL_META_PRODUCT = '_LASNTG_ADMIN_PRODUCT';

add_filter( 'wc_order_statuses', 'lasntgadmin_custom_order_status' );
function lasntgadmin_custom_order_status( $order_statuses ) {
	$order_statuses['wc-waiting_list'] = _x( 'Waiting list', 'Order status', 'lasntgadmin' );
	return $order_statuses;
}

function lasntgadmin_wl_form() {
	global $product;
	$product_id = $product->get_id();
	$orders     = QuotaUtil::get_product_quota( $product_id );

	if ( $orders > 0 ) {
		return;
	}
	$check = lasntgadmin_check_already_in_whishlist( $product_id );

	echo '<div class="lasntgadmin-wl-info">';
	if ( $check ) {
		echo '<div class="woocommerce-info">Already in whitelist.</div>';
	}
	echo '</div>';
	if ( ! get_current_user_id() ) {
		echo '<input type="email" id="lasntgadmin-guest-email" class="input-text" placeholder="Your email" /><br/><br/>';
	}
	$btn_msg = ! $check ? 'Join Waiting list' : 'Remove Waiting list';
	$html    = '<button class="lasntgadmin-wl-btn button" data-id=' . $product_id . '>' . $btn_msg . '</button>';
	echo wp_kses_post( $html );
}
add_filter( 'woocommerce_product_meta_start', 'lasntgadmin_wl_form', 10 );


function lasntgadmin_wl_enqueu_scripts() {
	wp_enqueue_script( 'lasntgadmin-add-to-wishlist' );

	$wl_nonce = wp_create_nonce( 'lasntgadmin-wl-nonce' );
	wp_enqueue_script( 'lasntgadmin-wl-js', ( LASNTGADMIN_QUOTAS_ASSETS_DIR_PATH . 'js/lasntgadmin-wl.js' ), array( 'jquery' ), '1.4', true );
	wp_localize_script(
		'lasntgadmin-wl-js',
		'lasntgadmin_ws_localize',
		array(
			'adminurl' => admin_url() . 'admin-ajax.php',
			'wl_nonce' => $wl_nonce,
		)
	);
}


add_action( 'wp_enqueue_scripts', 'lasntgadmin_wl_enqueu_scripts', 500 );

function lasntgadmin_check_already_in_whishlist( $current_product_id, $delete = false ) {
	if ( ! get_current_user_id() ) {
		return;
	}
	$args = array(
		'customer_id' => get_current_user_id(),
		'limit'       => -1,
		// to retrieve _all_ orders by this user.
		'status'      => 'wc-waiting_list',

	);
	$orders  = wc_get_orders( $args );
	$removed = false;
	foreach ( $orders as $order ) {
		$items = $order->get_items();
		foreach ( $items as $item ) {
			$product_id = $item->get_product_id();
			if ( $product_id == $current_product_id ) {
				if ( ! $delete ) {
					return true;
				}
				$removed = true;
				wp_delete_post( $order->get_id(), true );
			}
			// assumes the order has one item.
			break;
		}
	}
	return $removed;
}

function lasntgadmin_check_order_meta( $key, $email ) {
	global $wpdb;
	$results = $wpdb->get_results( $wpdb->prepare( "select * from $wpdb->postmeta where meta_key = %s and meta_value = %s", $key, $email ) );
	return $results;
}

function lasntgadmin_get_orders_by_meta( $email ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( "select * from $wpdb->postmeta where meta_key like %s and meta_value = %s ", '%' . LASNTGADMIN_EMAIL_META_KEY . '_%', $email ) );
}

function lasntgadmin_create_wl_order() {
	check_ajax_referer( 'lasntgadmin-wl-nonce', 'security' );
	if ( ! isset( $_POST['product_id'] ) ) {
		wp_send_json(
			[
				'status' => 0,
				'msg'    => __( 'No product id', 'lasntgadmin' ),
			]
		);
		wp_die();
	}
	$product_id = sanitize_text_field( wp_unslash( $_POST['product_id'] ) );
	$confirmed  = sanitize_text_field( wp_unslash( $_POST['confirmed'] ) );
	$email      = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : false;

	$check = ! $email ? lasntgadmin_check_already_in_whishlist( $product_id, true ) : false;
	if ( $check ) {
		// product already in the wishlist.
		wp_send_json(
			[
				'status' => 2,
				'msg'    => __( 'Removed from waiting list', 'lasntgadmin' ),
			]
		);
		wp_die();
	}
	$user = get_current_user_id();
	if ( $email ) {
		$user = get_user_by( 'email', $email );
	}

	$order_args = [];

	// associate order by email.
	// if it's guest.
	if ( ! $user ) {
		$key    = LASNTGADMIN_EMAIL_META_KEY . '_' . $product_id;
		$exists = lasntgadmin_check_order_meta( $key, $email );
		if ( ! $exists ) {
			$order = wc_create_order( $order_args );
			$order->update_meta_data( $key, $email );
		} elseif ( $exists && ! $confirmed ) {
			wp_send_json(
				[
					'status' => -3,
					'msg'    => __( 'Already joined waiting list', 'lasntgadmin' ),
				]
			);
			wp_die();
		} elseif ( $exists && $confirmed ) {
			// to remove.
			lasntgadmin_remove_guest( $email, $product_id );
			wp_send_json(
				[
					'status' => 2,
					'msg'    => __( 'Removed from waiting list', 'lasntgadmin' ),
				]
			);
			wp_die();
		}//end if
	} else {
		$order_args =
			[
				'customer_id' => get_current_user_id(),
			];
		$order      = wc_create_order( $order_args );
	}//end if

	QuotaUtil::lasntgadmin_add_group( $order->get_id() );
	// create order.

	$order->add_product( wc_get_product( $product_id ), 1 );
	$order->update_status( 'wc-waiting_list' );

	wp_send_json(
		[
			'status' => 1,
			'msg'    => __( 'Added to Waiting list.', 'lasntgadmin' ),
		]
	);
	wp_die();
}

add_action( 'wp_ajax_lasntgadmin_wl', 'lasntgadmin_create_wl_order' );
add_action( 'wp_ajax_nopriv_lasntgadmin_wl', 'lasntgadmin_create_wl_order' );

function lasntgadmin_remove_guest( $email, $product_id ) {
	global $wpdb;
	$order_id = $wpdb->get_var( $wpdb->prepare( "select post_id from $wpdb->postmeta where meta_key = %s and meta_value = %s ", LASNTGADMIN_EMAIL_META_KEY . '_' . $product_id, $email ) );
	if ( ! $order_id ) {
		return false;
	}
	wp_delete_post( $order_id, true );
	return true;
}

function lasntgadmin_associate_order_with_new_customer( $user_id ) {
	error_log( '===== lasntgadmin_associate_order_with_new_customer =====' );
	error_log( "Customer Id : $user_id " );
	error_log( 'Customer Data' );
	error_log( '===== END lasntgadmin_associate_order_with_new_customer =====' );
	$user  = get_user_by( 'id', $user_id );
	$metas = lasntgadmin_get_orders_by_meta( $user->user_email );

	if ( ! $metas ) {
		return;
	}
	foreach ( $metas as $meta ) {
		$order = new wc_order( $meta->post_id );
		$order->set_customer_id( $user_id );
		$order->save();
	}
}

add_action( 'user_register', 'lasntgadmin_associate_order_with_new_customer', 10, 2 );

