<?php
/**
 * All related Actions and filters for Waiting List
 */

namespace Lasntg\Admin\Products;

use wc_order;
/**
 * Handle all Filters and actions for Waiting List
 */
class WaitingListActionsFilters {

	/**
	 * Add Actions and Filters
	 *
	 * @return void
	 */
	public static function init(): void {
		add_filter( 'wc_order_statuses', [ self::class, 'custom_order_status' ] );
		add_filter( 'woocommerce_product_meta_start', [ self::class, '_form' ], 10 );

		add_action( 'init', [ self::class, 'register_whishlist_order_status' ], 1 );
		add_action( 'wp_enqueue_scripts', [ self::class, '_enqueue_scripts' ], 500 );
		add_action( 'wp_ajax_lasntgadmin_wl', [ self::class, 'create_wl_order' ] );
		add_action( 'wp_ajax_nopriv_lasntgadmin_wl', [ self::class, 'create_wl_order' ] );
		add_action( 'user_register', [ self::class, 'associate_order_with_new_customer' ], 10, 2 );
	}

	/**
	 * Enqueue required scripts
	 *
	 * @return void
	 */
	public static function _enqueue_scripts(): void {
		wp_enqueue_script( 'lasntgadmin-add-to-wishlist' );
		$wl_nonce   = wp_create_nonce( 'lasntgadmin-wl-nonce' );
		$assets_dir = untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/../assets/';
		wp_enqueue_script( 'lasntgadmin-wl-js', ( $assets_dir . 'js/lasntgadmin-wl.js' ), array( 'jquery' ), '1.4', true );
		wp_localize_script(
			'lasntgadmin-wl-js',
			'lasntgadmin_ws_localize',
			array(
				'adminurl' => admin_url() . 'admin-ajax.php',
				'wl_nonce' => $wl_nonce,
			)
		);
	}

	/**
	 * Register Whishlist
	 *
	 * @return void
	 */
	public static function register_whishlist_order_status(): void {
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

	/**
	 * Waiting list order status
	 *
	 * @param  mixed $order_statuses Order Statuses.
	 * @return array Order Statuses.
	 */
	public static function custom_order_status( $order_statuses ): array {
		$order_statuses['wc-waiting_list'] = _x( 'Waiting list', 'Order status', 'lasntgadmin' );
		return $order_statuses;
	}

	/**
	 * _form
	 *
	 * @return void
	 */
	public static function _form(): void {
		global $product;
		$product_id = $product->get_id();
		$orders     = QuotaUtils::get_product_quota( $product_id );

		if ( $orders > 0 ) {
			return;
		}
		$check = WaitingListUtils::check_already_in_whishlist( $product_id );

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

	/**
	 * Create Whishlist Order
	 *
	 * @return void
	 */
	public static function create_wl_order(): void {
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
		$confirmed  = isset( $_POST['confirmed'] ) ? sanitize_text_field( wp_unslash( $_POST['confirmed'] ) ) : '';
		$email      = isset( $_POST['email'] ) ? sanitize_text_field( wp_unslash( $_POST['email'] ) ) : false;

		$check = ! $email ? WaitingListUtils::check_already_in_whishlist( $product_id, true ) : false;
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

		// associate order by email.
		// if it's guest.
		if ( ! $user ) {
			$key    = WaitingListUtils::LASNTGADMIN_EMAIL_META_KEY . '_' . $product_id;
			$exists = WaitingListUtils::check_order_meta( $key, $email );
			if ( ! $exists ) {
				$order = wc_create_order();
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
				WaitingListUtils::remove_guest( $email, $product_id );
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
					'customer_id' => (int) get_current_user_id(),
				];
			$order      = wc_create_order( $order_args );
		}//end if
		QuotaUtils::lasntgadmin_add_group( $order->get_id() );
		// add product and update status.
		$order->add_product( wc_get_product( $product_id ), 1 );
		$order->update_status( 'wc-waiting_list' );
		$order->save();
		wp_send_json(
			[
				'status' => 1,
				'msg'    => __( 'Added to Waiting list.', 'lasntgadmin' ),
			]
		);
		wp_die();
	}

	/**
	 * Associate previous whishlist orders to new customer.
	 *
	 * @param  integer $user_id User ID.
	 * @return void
	 */
	public static function associate_order_with_new_customer( int $user_id ): void {
		$user  = get_user_by( 'id', $user_id );
		$metas = WaitingListUtils::get_orders_by_meta( $user->user_email );

		if ( ! $metas ) {
			return;
		}
		foreach ( $metas as $meta ) {
			$order = new wc_order( $meta->post_id );
			$order->set_customer_id( $user_id );
			$order->save();
		}
	}
}
