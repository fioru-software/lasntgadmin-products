<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;
use Lasntg\Admin\Products\{ QuotaUtils, AdminTableUtils };

use DOMDocument, stdClass;

use WP_User, WP_Query, WP_Post;

/**
 * Product list page.
 */
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

		add_filter( 'manage_product_posts_columns', [ self::class, 'add_columns_css' ], 1 );
		add_filter( 'manage_product_posts_columns', [ self::class, 'add_order_create_column' ], 20 );
		add_filter( 'manage_product_posts_columns', [ self::class, 'add_group_quota_column' ], 15 );
		add_filter( 'manage_product_posts_columns', [ self::class, 'modify_existing_columns' ], 15 );
		add_filter( 'post_row_actions', [ self::class, 'modify_product_row_actions' ], 99, 2 );
		add_filter( 'login_redirect', [ self::class, 'redirect_to_product_list' ], 10, 3 );
		add_filter( 'woocommerce_duplicate_product_capability', [ self::class, 'woocommerce_duplicate_product_capability' ] );

		/**
		 * Training officers require the edit_others_products cap, when removing attendee from paid order,
		 * which also increments product stock, but we don't want them to edit products via the UI.
		 */
		add_filter( 'post_row_actions', [ self::class, 'modify_list_row_actions' ], 10, 2 );
		add_filter( 'bulk_actions-edit-product', [ self::class, 'modify_product_bulk_actions' ], 99, 1 );
		add_filter( 'get_edit_post_link', [ self::class, 'modify_edit_product_link' ], 10, 3 );

		/**
		 * RTC Managers need the Group plugin's Administer Groups permission, so that they can assign groups when creating users.
		 * This permission also allows them to see all products.
		 * These two filters work together to show products with same group membership as the RTC manager or products authored by the RTC Manager.
		 */
		add_filter( 'groups_post_access_posts_where_apply', [ self::class, 'apply_default_product_list_filter_by_group_membership' ], 10, 3 );
		add_filter( 'groups_post_access_posts_where', [ self::class, 'filter_product_list_for_regional_training_centre_managers' ], 10, 2 );

		// Product list page groups filter.
		add_filter( 'groups_admin_posts_restrict_manage_posts_get_groups_options', [ self::class, 'get_group_options_for_product_list_page_filter' ] );

		add_filter( 'parse_query', [ self::class, 'handle_filter_request' ] );
		add_filter( 'views_edit-product', [ self::class, 'views_edit_product' ] );

		add_filter( 'wp_dropdown_cats', [ self::class, 'dropdown_cats' ], 10, 2 );

		add_filter( 'manage_edit-product_sortable_columns', [ self::class, 'sortable_venue' ] );
		add_filter( 'views_edit-product', [ self::class, 'hide_unwanted_views' ] );
		add_filter( 'pre_get_posts', [ self::class, 'only_training_centre' ] );
	}

	public static function only_training_centre( $query ) {
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen ) {
				if ( 'product' === $screen->post_type && 'edit-product' === $screen->id && 'product' === $query->query_vars['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( isset( $query->query_vars['post_type'] ) && 'product' == $query->query_vars['post_type'] ) {
						$query->set(
							'meta_query',
							array(
								array(
									'key'     => 'training_centre',
									'value'   => GroupUtils::get_current_users_group_ids_deep(),
									'compare' => 'IN',
								),
							)
						);
					}
				}
			}
		}
	}

	public static function hide_unwanted_views( $views ) {
		if ( ( wc_current_user_has_role( 'training_officer' ) || wc_current_user_has_role( 'fire_training_officer' ) ) ) {
			$remove_views = [ 'draft', 'template' ];

			foreach ( (array) $remove_views as $view ) {
				if ( isset( $views[ $view ] ) ) {
					unset( $views[ $view ] );
				}
			}
		}

		return $views;
	}

	public static function modify_product_row_actions( array $actions, WP_Post $post ): array {
		if ( 'product' === get_post_type() ) {
			unset( $actions['inline hide-if-no-js'] );
			if ( 'template' !== $post->post_status ) {
				unset( $actions['duplicate'] );
			}
		}
		return $actions;
	}

	public static function add_columns_css( $defaults ) {
		$assets_dir = untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/../assets/';
		wp_enqueue_style( 'admin-columns', $assets_dir . 'styles/admin-column.css', [], PluginUtils::get_version() );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_enqueue_style( 'non-admin-table-view', $assets_dir . 'styles/non-admin-table-view.css', [], 123 );
		}
		return $defaults;
	}

	public static function sortable_venue( $columns ) {
		$columns['venue']      = __( 'Venue', 'lasntgadmin' );
		$columns['start_date'] = __( 'Start Date', 'lasntgadmin' );

		return $columns;
	}
	public static function modify_edit_product_link( string $link, int $post_id, string $context ): string {
		if ( 'product' === get_post_type() && ( wc_current_user_has_role( 'training_officer' ) || wc_current_user_has_role( 'fire_training_officer' ) ) ) {
			$link = '#';
		}
		return $link;
	}

	public static function modify_product_bulk_actions( array $actions ): array {
		/**
		 * Completely remove the edit as it does quick edit.
		 */
		if ( 'product' === get_post_type() ) {
			unset( $actions['edit'] );
		}
		return $actions;
	}

	/**
	 * Remove incorrect row count from category filter dropdowns.
	 *
	 * @see https://developer.wordpress.org/reference/hooks/wp_dropdown_cats/
	 */
	public static function dropdown_cats( string $output, array $parsed_args ): string {
		return preg_replace( '/(&nbsp;){2}(\(\d+\))/', '', $output );
	}

	/**
	 * Default filter is post_status = open_for_enrollment
	 */
	public static function handle_filter_request( WP_Query $query ): WP_Query {
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( $screen ) {
				if ( 'product' === $screen->post_type && 'edit-product' === $screen->id && 'product' === $query->query_vars['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					if ( ! is_search() && AdminTableUtils::is_base_request() ) {
						$query->set( 'post_status', [ ProductUtils::$publish_status ] );
					}
				}
			}
		}//end if
		return $query;
	}

	/**
	 * Product list page groups filter dropdown.
	 *
	 * @see https://github.com/fioru-software/lasntgadmin-itthinx-groups/blob/master/lib/admin/class-groups-admin-posts.php#L166
	 */
	public static function get_group_options_for_product_list_page_filter( array $options ): array {
		// When altering options here, please ensure to limit to products only.
		return $options;
	}


	/**
	 * Product list page, should we apply the default product list filtered by group membership?
	 * Don't apply when role is regional_training_centre_manager
	 *
	 * @see https://github.com/fioru-software/lasntgadmin-itthinx-groups/blob/master/lib/access/class-groups-post-access.php#L223
	 */
	public static function apply_default_product_list_filter_by_group_membership( bool $apply, string $where, WP_Query $query ): bool {
		if ( is_admin() && function_exists( 'get_current_screen' ) && wc_current_user_has_role( 'regional_training_centre_manager' ) ) {
			$screen = get_current_screen();
			if ( ! is_null( $screen ) ) {
				if ( 'product' === $screen->post_type && 'edit-product' === $screen->id && 'product' === $query->query_vars['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$apply = false;
				}
			}
		}
		return $apply;
	}

	/**
	 * Product list page, filter product list by group membership or author.
	 *
	 * @see https://github.com/fioru-software/lasntgadmin-itthinx-groups/blob/master/lib/access/class-groups-post-access.php#L352
	 */
	public static function filter_product_list_for_regional_training_centre_managers( string $where, WP_Query $query ): string {

		if ( is_admin() && is_archive() && function_exists( 'get_current_screen' ) && wc_current_user_has_role( 'regional_training_centre_manager' ) ) {
			$screen = get_current_screen();

			if ( ! is_null( $screen ) ) {
				if ( 'product' === $screen->post_type && 'edit-product' === $screen->id && 'product' === $query->query_vars['post_type'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

					// Filter by group membership.
					$where .= GroupUtils::append_to_posts_where(
						'product',
						GroupUtils::get_current_users_group_ids_deep()
					);

					$post_status = $query->get( 'post_status' );

					if ( ! is_search() ) {
						if ( empty( $post_status ) ) {
							// Not filtered.
							$where .= sprintf(
								" OR ( post_type = 'product' AND post_status NOT IN ( 'auto-draft', 'template', 'trash' ) AND post_author = %d )",
								get_current_user_id()
							);
						} else {
							// Filtered by clicking post status link.
							$where .= sprintf(
								" OR ( post_type = 'product' AND post_status IN ( '%s' ) AND post_author = %d )",
								is_array( $post_status ) ? implode( "','", $post_status ) : esc_sql( $post_status ),
								get_current_user_id()
							);
						}
					}

					/**
					 * Filtered by category dropdown
					 */
					if ( is_search() && is_tax() ) {
						$taxonomy_id = get_queried_object_id();
						if ( empty( $post_status ) ) {
							// Post status is all.
							$where .= sprintf(
								" OR ( wp_term_relationships.term_taxonomy_id IN (%d) && post_type = 'product' AND post_author = %d )",
								$taxonomy_id,
								get_current_user_id()
							);
						} else {
							// Post status is something other than all.
							$where .= sprintf(
								" OR ( wp_term_relationships.term_taxonomy_id IN (%d) && post_type = 'product'  AND post_status IN ( '%s' ) AND post_author = %d )",
								$taxonomy_id,
								is_array( $post_status ) ? implode( "','", $post_status ) : esc_sql( $post_status ),
								get_current_user_id()
							);
						}
					}
				}//end if
			}//end if
		}//end if
		return $where;
	}


	/**
	 * Remove "All" link and make "Open for Enrolment" the default.
	 */
	public static function views_edit_product( array $views ): array {
		unset( $views['all'] );
		$statuses = ProductUtils::$statuses;

		$statuses['draft'] = 'Draft';
		$keys              = array_keys( $statuses );
		// remove unwanted statuses from the table view.
		foreach ( $views as $name => $view ) {
			if ( ! in_array( $name, $keys ) ) {
				unset( $views[ $name ] );
			}
		}
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

	public static function woocommerce_duplicate_product_capability() {
		return 'woocommerce_duplicate_product_capability';
	}

	public static function modify_list_row_actions( $actions, $post ) {
		if ( 'product' === $post->post_type ) {

			/**
			 * Training officers require the edit_others_products cap, when removing attendee from paid order,
			 * which also increments product stock, but we don't want them to edit products via the UI.
			 */
			if ( wc_current_user_has_role( 'training_officer' ) || wc_current_user_has_role( 'fire_training_officer' ) ) {
				unset( $actions['edit'] );
			}

			unset( $actions['view'] );

			if ( 'template' === $post->post_status ) {
				if ( wc_current_user_has_role( 'regional_training_centre_manager' ) ) {
					unset( $actions['view'] );
					unset( $actions['trash'] );
					unset( $actions['edit'] );
				}
			}

			if ( 'template' !== $post->post_status ) {
				$actions['attendees'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( admin_url( sprintf( 'edit.php?post_type=attendee&product_id=%d', $post->ID ) ) ),
					esc_html( __( 'Attendees', 'lasntgadmin' ) )
				);

				$actions['orders'] = sprintf(
					'<a href="%1$s">%2$s</a>',
					esc_url( admin_url( sprintf( 'edit.php?post_type=shop_order&product_id=%d', $post->ID ) ) ),
					esc_html( __( 'Orders', 'lasntgadmin' ) )
				);
			}
		}//end if
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
		if ( ! wc_current_user_has_role( 'training_officer' ) ) {
			$columns['places_available'] = __( 'Places Available', 'lasntgadmin' );
			$columns['places_booked']    = __( 'Places Booked', 'lasntgadmin' );
			$columns['reserved_stock']   = __( 'Places Reserved', 'lasntgadmin' );
		}
		$columns['venue'] = __( 'Venue', 'lasntgadmin' );

		$columns['start_date']      = __( 'Start Date', 'lasntgadmin' );
		$columns['course_duration'] = __( 'Duration', 'lasntgadmin' );

		$columns['organizer'] = __( 'Organiser', 'lasntgadmin' );

		$columns['entry_requirements'] = __( 'Course Brochures', 'lasntgadmin' );

		$columns['_minimum_capacity'] = __( 'Min Capacity', 'lasntgadmin' );
		$columns['course_info']       = __( 'Info', 'lasntgadmin' );
		$columns['groups-read']       = __( 'Available to', 'lasntgadmin' );
		// hide unwanted columns.
		unset( $columns['sku'] );
		unset( $columns['product_tag'] );
		unset( $columns['featured'] );
		unset( $columns['thumb'] );
		unset( $columns['date'] );

		unset( $columns['product_cat'] );
		unset( $columns['_minimum_capacity'] );
		unset( $columns['group_quota'] );
		unset( $columns['groups-read'] );
		unset( $columns['is_in_stock'] );
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


	public static function render_product_column( string $column, int $post_id ): void {

		if ( 'reserved_stock' === $column ) {
			echo esc_attr( self::render_reserved_stock( $post_id ) );
		}

		if ( current_user_can( 'publish_shop_orders' ) && 'group_quota' === $column ) {
			echo self::render_group_quota( $post_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		if ( 'course_info' === $column ) {
			echo esc_attr( get_post_meta( $post_id, 'course_info', true ) );
		}
		if ( 'course_duration' === $column ) {
			$duration = esc_attr( get_field( 'field_63881b63798a4', $post_id ) );
			if ( $duration ) {
				$duration .= ' Days';
			}
			echo esc_attr( $duration );
		}
		if ( 'minimum_capacity' === $column ) {
			echo esc_attr( get_post_meta( $post_id, 'minimum_capacity', true ) );
		}
		if ( 'entry_requirements' === $column ) {
			$media_id = get_post_meta( $post_id, 'entry_requirements', true );
			echo esc_attr( get_attached_file( $media_id ) );
		}
		if ( 'attendee_requirements' === $column ) {
			$media_id = get_post_meta( $post_id, 'attendee_requirements', true );
			if ( $media_id ) {
				$media = wp_get_attachment_url( $media_id );
				printf(
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
				printf(
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

		if ( 'venue' === $column ) {
			echo get_field( 'field_63881b84798a5', $post_id ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'start_date' === $column ) {
			echo get_field( 'field_63881aee31478', $post_id ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo ' ' . get_field( 'field_63881b0531479', $post_id ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'organizer' === $column ) {
			$centres = get_field( 'field_63881beb798a7', $post_id ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( is_array( $centres ) && count( $centres ) ) {
				echo implode( ', ', $centres ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} elseif ( is_string( $centres ) ) {
				echo esc_attr( $centres );
			}
		} elseif ( 'places_available' === $column ) {
			$product = wc_get_product( $post_id );
			$total   = $product->get_stock_quantity() - wc_get_held_stock_quantity( $product );
			echo $total . ( 0 === $total ? ' <span class="text-red">(Full)</span>' : '' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'places_booked' === $column ) {
			$order_ids = ProductUtils::get_orders_ids_by_product_id( $post_id, [ 'wc-completed', 'wc-on-hold', 'wc-processing' ] );
			$sales     = ProductUtils::get_total_items( $order_ids );
			echo "<span class='text-green'>$sales</span>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}//end if
	}

	private static function render_reserved_stock( int $product_id ): string {
		return wc_get_held_stock_quantity( wc_get_product( $product_id ) );
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
		$name      = sprintf( '%s-admin-table-view', PluginUtils::get_kebab_case_name() );

		if ( ! in_array( $hook, [ 'edit.php' ] ) || 'product' !== $post_type ) {
			return;
		}
		$plugin_data = get_plugin_data( PluginUtils::get_absolute_plugin_filepath() );

		wp_register_script(
			$name,
			plugins_url( sprintf( '%s/assets/js/admin-table-view.js', PluginUtils::get_kebab_case_name() ) ),
			[ 'jquery' ],
			$plugin_data['Version']
		);
		wp_enqueue_script( $name );
		wp_localize_script( $name, 'table_view', array( 'post_status_isset' => isset( $_GET['post_status'] ) ? 1 : 0 ) );

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
