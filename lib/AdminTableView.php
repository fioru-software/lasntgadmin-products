<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;
use Lasntg\Admin\Products\{ QuotaUtils, AdminTableUtils };

use DOMDocument;

use WP_User, WP_Query;

class AdminTableView {

	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	private static function add_actions() {
		add_action( 'manage_product_posts_custom_column', [ self::class, 'render_product_column' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );
	}

	private static function add_filters() {
		add_filter( 'manage_product_posts_columns', [ self::class, 'modify_existing_columns' ], 15 );
		add_filter( 'manage_product_posts_columns', [ self::class, 'add_group_quota_column' ], 15 );
		add_filter( 'manage_product_posts_columns', [ self::class, 'add_order_create_column' ], 20 );
		add_filter( 'post_row_actions', [ self::class, 'modify_product_row_actions' ] );
		add_filter( 'login_redirect', [ self::class, 'redirect_to_product_list' ], 10, 3 );
		add_filter( 'post_row_actions', [ self::class, 'modify_list_row_actions' ], 10, 2 );
		add_filter( 'woocommerce_duplicate_product_capability', [ self::class, 'woocommerce_duplicate_product_capability' ] );

		add_filter( 'parse_query', [ self::class, 'handle_filter_request' ] );
		add_filter( 'views_edit-product', [ self::class, 'views_edit_product' ] );
	}


	/**
	 * Remove "All" link and make "Open for Enrolment" the default.
	 */
	public static function views_edit_product( array $views ): array {
		unset( $views['all'] );

		if ( isset( $views[ ProductUtils::$publish_status ] ) ) {
			$first = $views[ ProductUtils::$publish_status ];
			unset( $views[ ProductUtils::$publish_status ] );

			if ( AdminTableUtils::is_base_request() ) {
				$dom = new DOMDocument();
				$dom->loadHTML( $first );
				$a = ( $dom->getElementsByTagName( 'a' ) )->item( 0 );
				$a->setAttribute( 'class', 'current' );
				$first = $dom->saveHTML( $a );
			}

			$views = array_merge(
				[ ProductUtils::$publish_status => $first ],
				$views
			);
		}
		return $views;
	}

	/**
	 * Default filter is post_status = open_for_enrollment
	 */
	public static function handle_filter_request( WP_Query $query ): WP_Query {
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen ) {
				if ( 'product' === $screen->post_type && 'edit-product' === $screen->id && 'product' === $query->query_vars['post_type'] && AdminTableUtils::is_base_request() ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$query->query_vars['post_status'] = [ ProductUtils::$publish_status ];
				}
			}
		}//end if
		return $query;
	}

	public static function woocommerce_duplicate_product_capability() {
		return 'woocommerce_duplicate_product_capability';
	}

	public static function modify_list_row_actions( $actions, $post ) {
		if ( 'product' === $post->post_type ) {
			unset( $actions['view'] );
			$actions['attendees'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( sprintf( 'edit.php?post_type=attendee&product_id=%d', $post->ID ) ) ),
				esc_html( __( 'Attendees', 'lasntgadmin' ) )
			);
			$actions['orders']    = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( sprintf( 'edit.php?post_type=shop_order&product_id=%d', $post->ID ) ) ),
				esc_html( __( 'Orders', 'lasntgadmin' ) )
			);
		}
		return $actions;
	}

	/**
	 * @param string           $redirect_to URL to redirect to.
	 * @param string           $requested_redirect_to URL request came from.
	 * @param WP_User|WP_Error $user User object.
	 */
	public static function redirect_to_product_list( string $redirect_to, string $requested_redirect_to, $user ): string {
		if ( $user instanceof WP_User && $user->has_cap( 'view_admin_dashboard' ) ) {
			return admin_url( 'edit.php?post_type=product' );
		}
		return $redirect_to;
	}

	public static function modify_existing_columns( array $columns ): array {
		$columns['_minimum_capacity'] = __( 'Min Capacity', 'lasntgadmin' );
		// hide unwanted columns.
		unset( $columns['sku'] );
		unset( $columns['product_tag'] );
		unset( $columns['featured'] );
		unset( $columns['thumb'] );
		unset( $columns['date'] );
		return $columns;
	}

	/**
	 * Add the custom columns to the attendee custom post type.
	 */
	public static function add_order_create_column( array $columns ): array {
		if ( current_user_can( 'publish_shop_orders' ) ) {
			$columns['create_order'] = __( 'Order', 'lasntgadmin' );
		}
		$columns['product_cat'] = __( 'Category', 'lasntgadmin' );
		return $columns;
	}

	/**
	 * Add the custom columns to the attendee custom post type.
	 */
	public static function add_group_quota_column( array $columns ): array {
		if ( current_user_can( 'publish_shop_orders' ) ) {
			$user_groups = GroupUtils::get_current_users_groups();
			if ( ! empty( $user_groups ) ) {
				$columns['group_quota'] = __( 'Quota', 'lasntgadmin' );
				unset( $columns['groups-read'] );
				// Remove groups column when user is only a member of a single group.
			}
		}
		return $columns;
	}

	public static function modify_product_row_actions( array $actions ): array {
		unset( $actions['inline hide-if-no-js'] );
		return $actions;
	}

	public static function render_product_column( string $column, int $post_id ): void {
		if ( current_user_can( 'publish_shop_orders' ) && 'group_quota' === $column ) {
			echo self::render_group_quota( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		if ( '_minimum_capacity' === $column ) {
			echo esc_attr( get_post_meta( $post_id, '_minimum_capacity', true ) );
		}
		if ( 'entry_requirements' === $column ) {
			$media_id = get_post_meta( $post_id, 'entry_requirements', true );
			echo esc_attr( get_attached_file( $media_id ) );
		}
		if ( 'attendee_requirements' === $column ) {
			$media_id = get_post_meta( $post_id, 'attendee_requirements', true );
			if ( $media_id ) {
				$media = wp_get_attachment_url( $media_id );
				echo sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					esc_url( $media ),
					esc_html( __( 'File', 'lasntgadmin' ) )
				);
			} else {
				echo '-';
			}
		}
		if ( 'entry_requirements' === $column ) {
			$media_id = get_post_meta( $post_id, 'entry_requirements_file', true );
			if ( $media_id ) {
				$media = wp_get_attachment_url( $media_id );
				echo sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					esc_url( $media ),
					esc_html( __( 'File', 'lasntgadmin' ) )
				);
			} else {
				echo '-';
			}
		}
		if ( current_user_can( 'publish_shop_orders' ) && 'create_order' === $column ) {
			echo self::render_create_order_button( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	private static function render_group_quota( int $product_id ): string {
		$post_groups = GroupUtils::get_read_group_ids( $product_id );
		if ( count( $post_groups ) > 1 ) {
			return __( 'Multiple Quotas', 'lasntgadmin' );
		}
		if ( ! count( $post_groups ) ) {
			return __( 'Limited by spaces available', 'lasntgadmin' );
		}
		$quota = QuotaUtils::remaining_quota( $product_id, $post_groups[0] );

		return ! is_numeric( $quota ) ? __( 'Limited by spaces available', 'lasntgadmin' ) : $quota;
	}

	private static function render_create_order_button( int $product_id ): string {
		$product = wc_get_product( $product_id );
		return sprintf(
			"<a href='%s' %s class='button button-primary'>%s</a>",
			ProductUtils::is_open_for_enrollment( $product ) ? "/wp-admin/post-new.php?post_type=shop_order&product_id=$product_id" : '#',
			ProductUtils::is_open_for_enrollment( $product ) ? '' : 'disabled',
			__( 'Book Now', 'lasntgadmin' )
		);
	}

	public static function enqueue_assets( string $hook ): void {
		$post_type = property_exists( get_current_screen(), 'post_type' ) ? get_current_screen()->post_type : false;
		$name      = sprintf( '%s', PluginUtils::get_kebab_case_name() );

		if ( ! in_array( $hook, [ 'edit.php' ] ) || 'product' !== $post_type ) {
			return;
		}
		$plugin_data = get_plugin_data( PluginUtils::get_absolute_plugin_filepath() );
		// Load our style.css.
		wp_register_style(
			$name,
			plugins_url( sprintf( '%s/style.css', PluginUtils::get_kebab_case_name() ) ),
			array(),
			$plugin_data['Version']
		);
		wp_enqueue_style( $name );
	}
}
