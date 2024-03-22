<?php
/**
 * Product Actions Filter
 */

namespace Lasntg\Admin\Products;

use WP_Post;
use Groups_Group;
use WP_Query;
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
		self::add_actions();
		self::add_filters();
	}

	public static function add_actions(): void {
		add_action( 'rest_api_init', [ ProductApi::class, 'get_instance' ] );

		add_action( 'admin_notices', [ self::class, 'admin_notice_errors' ], 500 );

		add_action( 'add_meta_boxes', [ self::class, 'check_roles' ], 100 );
		add_action( 'woocommerce_product_data_tabs', [ self::class, 'remove_unwanted_tabs' ], 999 );
		add_action( 'admin_menu', [ self::class, 'remove_woocommerce_products_taxonomy' ], 99 );
		add_action( 'admin_enqueue_scripts', [ self::class, 'admin_enqueue_scripts' ], 99 );
		add_action( 'edit_form_after_title', [ self::class, 'hidden_status' ] );

		add_action( 'wp_enqueue_media', [ self::class, 'wp_enqueue_media' ] );

		add_action( 'pre_get_posts', [ self::class, 'sort_custom_columns_query' ], 99, 1 );
		add_action( 'add_meta_boxes', [ self::class, 'remove_short_description' ], 999 );
		add_action( 'posts_where', [ self::class, 'remove_template_products' ], 10, 2 );
		add_action( 'init', [ self::class, 'remove_editor' ] );
		add_action( 'init', [ self::class, 'register_custom_product_statuses' ], 0 );

		add_action( 'init', [ self::class, 'disable_course_add_button' ] );
		add_action( 'add_meta_boxes', array( self::class, 'add_product_boxes_sort_order' ), 99 );
		add_action( 'load-post.php', [ self::class, 'edit_product' ] );

		// Overrides code styling to accommodate for a third dropdown filter.
		add_action(
			'admin_footer',
			[ self::class, 'admin_footer' ]
		);
	}

	public static function add_filters(): void {
		if ( is_admin() ) {
			add_filter( 'wp_insert_post_data', [ self::class, 'filter_post_data' ], 99, 2 );
			add_filter( 'wp_insert_post', [ self::class, 'cancel_orders' ], 10, 3 );
			add_filter( 'post_updated_messages', [ self::class, 'post_updated_messages_filter' ], 500 );
			add_filter( 'woocommerce_products_admin_list_table_filters', [ self::class, 'remove_products_filter' ] );

			// media library.
			add_filter( 'ajax_query_attachments_args', [ self::class, 'show_groups_attachments' ] );
		}

		if ( ! is_admin() ) {
			// show products in private client group to anonymous shoppers.
			add_filter( 'groups_post_access_posts_where_apply', [ self::class, 'filter_products_apply' ], 20, 3 );
			add_filter( 'woocommerce_product_is_visible', [ self::class, 'product_is_visible' ], 11, 2 );

			add_filter( 'do_meta_boxes', [ self::class, 'wpse33063_move_meta_box' ] );
			add_filter( 'user_has_cap', [ self::class, 'temporarily_disable_cap_administer_group' ], 10, 3 );
			add_filter( 'woocommerce_product_tabs', [ self::class, 'remove_product_tab' ], 9999 );

			add_filter( 'woocommerce_product_meta_start', [ self::class, 'woocommerce_get_availability_text' ], 10, 2 );
			add_filter( 'woocommerce_is_purchasable', [ self::class, 'product_is_in_stock' ], 15, 2 );
			add_filter( 'woocommerce_is_purchasable', [ self::class, 'set_product_to_purchasable' ], 1, 2 );
			add_filter( 'woocommerce_product_query', [ self::class, 'woocommerce_product_query' ], 15, 1 );
		}
		add_filter( 'use_block_editor_for_post', [ self::class, 'remove_block_editor' ], 50, 2 );
	}

	public static function remove_block_editor( bool $use_block_editor, WP_Post $post ) {
		if ( 'product' === $post->post_type ) {
			return false;
		}
		return $use_block_editor;
	}

	public static function temporarily_disable_cap_administer_group( $allcaps, $caps, $args ) {
		if ( in_array( 'groups_admin_groups', $args ) !== true ) {
			return $allcaps;
		}
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( ! is_null( $screen ) ) {
				if ( ! is_search() ) {
					if ( current_user_can( 'manage_options' ) ) {
						return $allcaps;
					}
					if ( 'product' === $screen->post_type && 'product' === $screen->id && 'edit' === $screen->parent_base ) {
						$allcaps['groups_admin_groups'] = false;
					}
				}
			}
		}
		return $allcaps;
	}
	public static function set_product_to_purchasable( $is_in_stock, $product ): bool {
		if ( ProductUtils::$publish_status === $product->get_status() ) {
			return true;
		}
		return false;
	}
	public static function edit_product() {
		if ( ! wc_current_user_has_role( 'regional_training_centre_manager' ) ) {
			return;
		}
		if ( ! isset( $_GET['post'] ) ) {
			return;
		}
		$post_ID = (int) $_GET['post'];

		if ( 'product' !== get_post_type( $post_ID ) ) {
			return;
		}

		$disabled = [
			'field_638786be96777',
			'field_63881c1ff4453',
			'field_63881c1ff4453',
			'field_63878925d6a26',
			'field_6387890fd6a25',
			'field_63878939d6a27',
			'field_63881f7f3e5af',
			'field_638820173e5b0',
			'field_63882047beae3',
			'field_638820d1beae4',
			'field_63f764087f8c9',
		];
		foreach ( $disabled as $key ) {
			add_filter( "acf/load_field/key=$key", [ self::class, 'disable_field' ], 999 );
			add_filter( "acf/render_field/key=$key", [ self::class, 'add_hidden_field' ], 999 );
		}
		add_filter( 'acf/load_field/key=field_63881beb798a7', [ self::class, 'acf_training_centre' ] );
	}

	public static function acf_training_centre( $field ) {
		$centres    = GroupUtils::formatted_current_user_tree();
		$centre_ids = array_keys( $centres );
		$choices    = [];
		foreach ( $field['choices'] as $id  => $choice ) {
			if ( in_array( $id, $centre_ids ) === true ) {
				$choices[ $id ] = $choice;
			}
		}
		$field['choices'] = $choices;

		return $field;
	}

	/**
	 * Values of disabled fields are not included in POST data, whereas values of readonly fields are.
	 * Unfortunately, the readonly attribute is not supported or relevant to <select> or input types that are already not mutable.
	 * Hence we need to add a duplicate hidden field, so the values are included in the POST data.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/readonly
	 * @see https://www.advancedcustomfields.com/resources/acf-render_field/
	 */
	public static function add_hidden_field( array $field ): void {
		if ( in_array( $field['type'], [ 'select', 'checkbox', 'radio', 'true_false' ] ) ) {
			if ( 1 === $field['multiple'] ) {
				foreach ( $field['value'] as $value ) {
					printf( "<input type='hidden' name='%s[]' value='%s' />", esc_attr( $field['name'] ), esc_attr( $value ) );
				}
			} else {
				printf( "<input type='hidden' name='%s' value='%s' />", esc_attr( $field['name'] ), esc_attr( $field['value'] ) );
			}
		}
	}

	/**
	 * Values of disabled fields are not included in POST data, whereas values of readonly fields are.
	 * Unfortunately, the readonly attribute is not supported or relevant to <select> or input types that are already not mutable.
	 *
	 * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/readonly
	 * @see https://www.advancedcustomfields.com/resources/acf-load_field/
	 */
	public static function disable_field( array $field ): array {
		if ( in_array( $field['type'], [ 'select', 'checkbox', 'radio', 'true_false' ] ) ) {
			$field['disabled'] = 1;
		}
		$field['readonly']          = 1;
		$field['conditional_logic'] = [];
		return $field;
	}

	public static function remove_editor() {
		remove_post_type_support( 'product', 'editor' );
	}

	public static function add_product_boxes_sort_order() {
		update_user_meta(
			get_current_user_id(),
			'meta-box-order_product',
			array(
				'side'     => 'postimagediv,woocommerce-product-images,product_catdiv,tagsdiv-product_tag,submitdiv',
				'normal'   => 'woocommerce-product-data,postcustom,slugdiv,postexcerpt',
				'advanced' => '',
			)
		);
	}

	public static function wpse33063_move_meta_box() {
		remove_meta_box( 'submitdiv', 'product', 'side' );
		add_meta_box( 'submitdiv', __( 'Course Status', 'lasntgadmin' ), 'post_submit_meta_box', 'product', 'normal', 'high' );
	}
	public static function disable_course_add_button() {
		global $wp_post_types;
		if ( ! current_user_can( 'create_products' ) ) {
			$wp_post_types['product']->map_meta_cap      = true;
			$wp_post_types['product']->cap->create_posts = false;
		}
	}
	public static function remove_template_products( string $where, WP_Query $query ) {
		if ( $query->is_admin && $query->get( 'post_type' ) === 'product' && ! current_user_can( 'publish_products' ) ) {
			$where .= sprintf( " AND post_status != '%s' ", 'template' );
		}

		return $where;
	}
	public static function remove_short_description() {
		remove_meta_box( 'postcustom', 'product', 'normal' );
		remove_meta_box( 'postexcerpt', 'product', 'normal' );
		remove_meta_box( 'commentsdiv', 'product', 'normal' );
		remove_meta_box( 'tagsdiv-product_tag', 'product', 'normal' );

		remove_meta_box( 'woocommerce-product-images', 'product', 'normal' );
		remove_meta_box( 'woocommerce-product-images', 'product', 'side' );
		remove_meta_box( 'woocommerce-order-note', 'product', 'normal' );

		remove_meta_box( 'authordiv', 'product', 'normal' );
		// Author Metabox.
		remove_meta_box( 'authordiv', 'product', 'normal' );
		// Author Metabox.
		remove_meta_box( 'postimagediv', 'product', 'normal' );
		// Featured Image Metabox.
		remove_meta_box( 'postimagediv', 'product', 'side' );
	}

	public static function remove_product_tab( $tabs ) {
		unset( $tabs['description'] );
		unset( $tabs['additional_information'] );
		unset( $tabs['reviews'] );
		return $tabs;
	}
	public static function remove_products_filter( $filters ) {
		if ( isset( $filters['product_type'] ) ) {
			$filters['product_type'] = [ self::class, 'product_filter_type_callback' ];
		}
		return $filters;
	}
	public static function product_filter_type_callback() {
		// extend our capability to control product permissions.
		add_filter( 'woocommerce_register_post_type_product', [ self::class, 'register_post_type_product' ] );
	}


	public static function sort_custom_columns_query( $query ) {
		$orderby = $query->get( 'orderby' );

		if ( 'venue' == $orderby ) {
			$meta_query = array(
				'relation' => 'OR',
				array( //phpcs:ignore Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey, Universal.Arrays.MixedKeyedUnkeyedArray.Found
					'key'     => 'location',
					'compare' => 'NOT EXISTS',
				),
				array( //phpcs:ignore Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey, Universal.Arrays.MixedKeyedUnkeyedArray.Found
					'key' => 'location',
				),
			);

			$query->set( 'meta_query', $meta_query );
			$query->set( 'orderby', 'meta_value' );
		}
		if ( is_string( $orderby ) && 'start date' == strtolower( $orderby ) ) {
			$meta_query = array(
				'relation' => 'OR',
				array( //phpcs:ignore Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey, Universal.Arrays.MixedKeyedUnkeyedArray.Found
					'key'     => 'start_date',
					'compare' => 'NOT EXISTS',
				),
				array( //phpcs:ignore Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey, Universal.Arrays.MixedKeyedUnkeyedArray.Found
					'key' => 'start_date',
				),
			);

			$query->set( 'meta_query', $meta_query );
			$query->set( 'orderby', 'meta_value' );
		}
	}


	/**
	 * @todo Escape the output.
	 */
	public static function add_venue_custom( $column_name, $post_id ) {
		if ( 'venue' === $column_name ) {
			echo get_field( 'field_63881b84798a5', $post_id ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'start_date' === $column_name ) {
			echo get_field( 'field_63881aee31478', $post_id ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo ' ' . get_field( 'field_63881b0531479', $post_id ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'organizer' === $column_name ) {
			$centres = get_field( 'field_63881beb798a7', $post_id ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			if ( is_array( $centres ) && count( $centres ) ) {
				echo implode( ', ', $centres ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		} elseif ( 'places_available' === $column_name ) {
			$product = wc_get_product( $post_id );

			$order_ids = ProductUtils::get_orders_ids_by_product_id( $post_id, [ 'wc-completed', 'wc-on-hold', 'wc-processing' ] );
			$sales     = ProductUtils::get_total_items( $order_ids );
			$total     = $product->get_stock_quantity();
			echo $total . ( 0 === $total ? ' <span class="text-red">(Full)</span>' : '' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} elseif ( 'places_booked' === $column_name ) {
			$order_ids = ProductUtils::get_orders_ids_by_product_id( $post_id, [ 'wc-completed', 'wc-on-hold', 'wc-processing' ] );
			$sales     = ProductUtils::get_total_items( $order_ids );
			echo "<span class='text-green'>$sales</span>"; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}//end if
	}

	public static function rename_groups_column( $defaults ) {
		$defaults['groups-read'] = __( 'Available to', 'lasntgadmin' );

		return $defaults;
	}

	/**
	 * Override group plugin's product visibility override.
	 *
	 * @see https://github.com/itthinx/groups/blob/master/lib/extra/class-groups-extra.php#L52
	 */
	public static function product_is_visible( $visible, $product_id ) {
		return true;
	}

	/**
	 * Do not apply groups filter for products in the shop.
	 *
	 * @see https://github.com/fioru-software/lasntgadmin-products/blob/master/lib/ProductActionsFilters.php#L191
	 * @see https://github.com/itthinx/groups/blob/master/lib/access/class-groups-post-access.php#L223
	 */
	public static function filter_products_apply( $bool, $where, $query ) {
		if ( ! is_admin() && ( ( is_singular() && $query->get( 'post_type' ) === 'product' ) || is_shop() ) ) {
			$bool = false;
		}
		return $bool;
	}

	public static function wp_enqueue_media() {
		$post_type = property_exists( get_current_screen(), 'post_type' ) ? get_current_screen()->post_type : false;
		if ( 'product' !== $post_type ) {
			return;
		}
		$assets_dir = untrailingslashit( plugin_dir_url( __FILE__ ) ) . '/../assets/';
		wp_enqueue_script( 'media-library-taxonomy-filter', $assets_dir . '/js/collection-filter.js', array( 'media-editor', 'media-views' ), PluginUtils::get_version() );
		$user_groups = GroupUtils::get_groups_by_user_id( get_current_user_id() );

		// Load 'terms' into a JavaScript variable that collection-filter.js has access to.
		wp_localize_script(
			'media-library-taxonomy-filter',
			'MediaLibraryTaxonomyFilterData',
			array(
				'terms' => $user_groups,
			)
		);
	}

	public static function admin_footer() {
		?>
			<style>
				.media-modal-content .media-frame select.attachment-filters {
					max-width: -webkit-calc(33% - 12px);
					max-width: calc(33% - 12px);
				}

				#edit-slug-box {
					display: none !important;
				}
			</style>
				<?php
	}

	public static function show_groups_attachments( $query ) {
		if ( ! isset( $_POST['query'] ) || ! isset( $_POST['post_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $query;
		}
		$user_id = get_current_user_id();
		$post_id = sanitize_text_field( wp_unslash( $_POST['post_id'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post    = get_post( $post_id );
		if ( 'product' !== $post->post_type ) {
			return $query;
		}
		$selected_group_id = '';
		if ( isset( $_POST['query']['lasntg_group'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$selected_group_id = sanitize_text_field( wp_unslash( $_POST['query']['lasntg_group'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		$user_groups = GroupUtils::get_groups_by_user_id( get_current_user_id() );

		// if empty show all groups current user has.
		// admin has access to all.
		if ( empty( $selected_group_id ) ) {
			// assumption that national_manager will be a member of all groups.
			if ( current_user_can( 'manage_options' ) || wc_current_user_has_role( 'national_manager' ) ) {
				return $query;
			}
			$user_ids = [];
			foreach ( $user_groups as $user_group ) {
				$group = new \Groups_Group( $user_group->group_id );
				$users = $group->users;

				$users    = array_map(
					function ( $user ) {
						return $user->ID;
					},
					$users
				);
				$user_ids = array_merge( $user_ids, $users );
			}
			$user_ids            = array_unique( $user_ids );
			$query['author__in'] = $users;
			return $query;
		}//end if
		$user_groups = GroupUtils::get_group_ids_by_user_id( $user_id );

		if ( current_user_can( 'manage_options' ) || in_array( $selected_group_id, $user_groups ) !== false ) {
			$group = new \Groups_Group( $selected_group_id );

			$users = array_map(
				function ( $user ) {
					return $user->ID;
				},
				$group->users
			);
			if ( $users ) {
				$query['author__in'] = $users;
				return $query;
			}
		}
		// if current user is not an admin and doesn't have the group return an absurd user id.
		// if it's blank it returns all users media.
		$query['author'] = '10000000';
		return $query;
	}

	public static function register_post_type_product( array $args ): array {
		self::enable_create_products_capability( $args );
		self::enable_edit_others_products_capability( $args );
		return $args;
	}

	/**
	 * Enable create_products capability.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_post_type/#capabilities
	 */
	private static function enable_create_products_capability( array &$args ) {
		if ( ! array_key_exists( 'capabilities', $args ) ) {
			$args['capabilities'] = [];
		}
		if ( ! array_key_exists( 'create_posts', $args['capabilities'] ) ) {
			$args['capabilities']['create_posts'] = 'create_products';
		}
	}

	/**
	 * Enable edit_others_products capability.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_post_type/#capabilities
	 */
	private static function enable_edit_others_products_capability( array &$args ) {
		if ( ! in_array( 'author', $args['supports'] ) ) {
			$args['supports'] = array_merge( $args['supports'], [ 'author' ] );
		}
	}

	/**
	 * Only show products that have $publish_status in the shops page.
	 *
	 * @param  WP_Query $q Query.
	 * @return void
	 */
	public static function woocommerce_product_query( WP_Query $q ): void {
		$q->set( 'post_status', ProductUtils::$publish_status );

		// check the product has private group or it's blank.
		$args = array(
			'relation' => 'OR',
			array( //phpcs:ignore Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey, Universal.Arrays.MixedKeyedUnkeyedArray.Found
				'key'     => 'groups-read',
				'value'   => 33,
				// private client group id is 33.
				'compare' => '=',
				'type'    => 'NUMERIC',
			),
			array( //phpcs:ignore Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey, Universal.Arrays.MixedKeyedUnkeyedArray.Found
				'key'     => 'groups-read',
				'compare' => 'NOT EXISTS',
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

		if ( false === in_array( 33, $group_ids, false ) ) {
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

		if ( count( $group_ids ) > 1 && ! in_array( 33, $group_ids ) ) {
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
		if ( ProductUtils::$publish_status !== $product->get_status() ) {
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
					'label_count'               => _n_noop( $status . ' <span class="count">(%s)</span>', $status . ' <span class="count">(%s)</span>', 'lasntgadmin' ), //phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralPlural, WordPress.WP.I18n.NonSingularStringLiteralSingle, WordPress.WP.I18n.NonSingularStringLiteralSingular
					'public'                    => true,
					'publicly_queryable'        => true,
					'exclude_from_search'       => false,
					'show_in_admin_all_list'    => true,
					'show_in_admin_status_list' => true,
					'post_type'                 => [ 'product' ],
				)
			);
		}
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
		wp_enqueue_script( 'lasntgadmin-products-admin-js', ( $assets_dir . 'js/lasntgadmin-admin.js' ), array( 'jquery' ), PluginUtils::get_version(), true );

		wp_enqueue_style( 'product-css', $assets_dir . 'styles/product-admin.css', [], PluginUtils::get_version() );
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
		if (
			$screen &&
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
		if ( '' === $postarr['_stock'] ) {
			$errors[] = __( 'Capacity is required.', 'lasntgadmin' );
		} elseif ( ! empty( $postarr['_stock'] ) && 0 > (int) $postarr['_stock'] ) {
			$errors[] = __( 'Capacity cannot be negative.', 'lasntgadmin' );
		}

		if ( empty( $postarr['tax_input'] ) ) {
			$errors[] = __( 'Category is required.', 'lasntgadmin' );
		} else {
			$sum = array_sum( array_values( $postarr['tax_input']['product_cat'] ) );

			if ( ! $sum ) {
				$errors[] = __( 'Category is required.', 'lasntgadmin' );
			}
		}

		if ( empty( $postarr['post_title'] ) ) {
			$errors[] = __( 'Name is required.', 'lasntgadmin' );
		}

		if ( '0' === $postarr['_regular_price'] || empty( $postarr['_regular_price'] ) ) {
			$errors[] = __( 'Course cost is required.', 'lasntgadmin' );
		} elseif ( $postarr['_regular_price'] > 5000 ) {
			$errors[] = __( 'Course cost can not be higher than €5,000.', 'lasntgadmin' );
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
