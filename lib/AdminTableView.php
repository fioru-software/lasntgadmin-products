<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;
use Lasntg\Admin\Products\QuotaUtils;

use WP_User;

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
	}


	public static function modify_list_row_actions( $actions, $post ) {
		if ( 'product' === $post->post_type ) {
			unset( $actions['view'] );
			$actions['attendees'] = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( sprintf( 'edit.php?post_type=attendee&product_id=%d', $post->ID ) ) ),
				esc_html( __( 'Attendees', 'lasntgadmin' ) )
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
		$columns['sku'] = __( 'Course Code', 'lasntgadmin' );
		return $columns;
	}

	/**
	 * Add the custom columns to the attendee custom post type.
	 */
	public static function add_order_create_column( array $columns ): array {
		if ( current_user_can( 'publish_shop_orders' ) ) {
			$columns['create_order'] = __( 'Order', 'lasntgadmin' );
		}
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
		return sprintf( "<a href='/wp-admin/post-new.php?post_type=shop_order&product_id=$product_id' class='button button-primary'>%s</a>", __( 'Book Now', 'lasntgadmin' ) );
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
