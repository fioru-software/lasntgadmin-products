<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;
use Lasntg\Admin\Orders\OrderUtils;

use Groups_Group;
use WC_Product, DateTime, WP_Query;

/**
 * ProductUtils
 */
class ProductUtils {

	public static $publish_status = 'open_for_enrollment';
	public static $statuses       = [
		'template'            => 'Template',
		'open_for_enrollment' => 'Open for Enrolment',
		'enrollment_closed'   => 'Enrolment Closed',
		'date_passed'         => 'Date Passed',
		'closed'              => 'Closed',
		'cancelled'           => 'Cancelled',
		'archived'            => 'Archived',
	];


	public static function is_open_for_enrollment( WC_Product $product ): bool {
		return $product->get_status() === self::$publish_status || 'date_passed' === $product->get_status();
	}
	public static function is_open_for_enrollment_by_product_id( int $product_id ): bool {
		$product = wc_get_product( $product_id );
		return $product->get_status() === self::$publish_status || 'date_passed' === $product->get_status();
	}

	public static function is_funded( WC_Product $product ): bool {
		$funding_source_slugs = $product->get_meta( 'funding_sources', true );
		$grant_year           = $product->get_meta( 'grant_year', true );
		return empty( $funding_source_slugs ) || empty( $grant_year ) ? false : true;
	}

	public static function get_status_name( $status ) {
		return isset( self::$statuses[ $status ] ) ? self::$statuses[ $status ] : $status;
	}

	public static function get_total_capacity( WC_Product $product ): int {
		$order_ids = self::get_orders_ids_by_product_id( $product->get_id(), [ 'wc-completed', 'wc-on-hold', 'wc-processing' ] );
		$sales     = self::get_total_items( $order_ids );
		$total     = $product->get_stock_quantity() + $sales;
		return $total;
	}

	/**
	 * Redirect back
	 *
	 * @return void
	 */
	public static function redirect_back(): void {
		$redirect = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : home_url( '/' );
		$redirect = esc_url( $redirect );
		exit( esc_attr( wp_redirect( $redirect ) ) );
	}

	/**
	 * Check current user has products capabilities.
	 *
	 * @return bool
	 */
	public static function is_allowed_products_edit(): bool {
		return current_user_can( 'publish_products' )
		|| current_user_can( 'read_product' )
		|| current_user_can( 'delete_products' )
		|| current_user_can( 'edit_products' )
		|| current_user_can( 'edit_product' );
	}

	/**
	 * Get cached products with specific group membership.
	 *
	 * @param int      $group_id The group id.
	 * @param string[] $status The course status eg. [ 'open_for_enrollment' ].
	 * @return WC_Product[]
	 */
	public static function get_cached_minimal_products_visible_to_group( int $group_id, array $status ): array {

		$cache_key = sprintf(
			'method=get_products_visible_to_group&group_id=%d&status[]=%s',
			intval( $group_id ),
			join( '&status[]=', $status )
		);

		$minimal_products = get_transient( $cache_key );

		if ( false === $minimal_products ) {
			$args     = [
				'status'   => $status,
				'limit'    => -1,
				'group_id' => $group_id,
			];
			$products = wc_get_products( $args );
			/**
			 * Returning the least required information
			 */
			$minimal_products = array_map(
				function ( $product ) {
					return (object) [
						'name'      => $product->get_name(),
						'meta_data' => [
							'start_date'      => $product->get_meta( 'start_date' ),
							'duration'        => $product->get_meta( 'duration' ),
							'training_group'  => $product->get_meta( 'training_group' ),
							'training_centre' => $product->get_meta( 'training_centre' ),
						],
					];
				},
				$products
			);
			unset( $products );
			set_transient( $cache_key, $minimal_products, HOUR_IN_SECONDS );
		}//end if
		return $minimal_products;
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/54042
	 * @see https://github.com/WordPress/WordPress-Coding-Standards/blob/dc2f21771cb2b5336a7e6bb6616abcdfa691d7de/WordPress/Tests/DB/PreparedSQLPlaceholdersUnitTest.inc#L70-L124
	 */
	public static function get_product_ids_with_status( array $status ): array {
		global $wpdb;
		// Get all product ids with post statuses.
		$sql         = $wpdb->prepare(
			sprintf(
				"SELECT ID FROM `%s` WHERE post_type = 'product' AND post_status IN ( %s )",
				$wpdb->posts,
				implode( ',', array_fill( 0, count( $status ), '%s' ) )
			),
			$status
		);
		$product_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( 'intval', $product_ids );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/54042
	 * @see https://github.com/WordPress/WordPress-Coding-Standards/blob/dc2f21771cb2b5336a7e6bb6616abcdfa691d7de/WordPress/Tests/DB/PreparedSQLPlaceholdersUnitTest.inc#L70-L124
	 */
	public static function get_limited_product_ids_for_grant_year( int $grant_year, array $product_ids ): array {
		global $wpdb;
		$sql         = $wpdb->prepare(
			sprintf(
				"SELECT DISTINCT post_id FROM `%s` WHERE meta_key = 'grant_year' AND meta_value = %d AND post_id IN ( %s )",
				$wpdb->postmeta,
				$grant_year,  // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				implode( ',', array_fill( 0, count( $product_ids ), '%d' ) )
			),
			$product_ids
		);
		$product_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( 'intval', $product_ids );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/54042
	 * @see https://github.com/WordPress/WordPress-Coding-Standards/blob/dc2f21771cb2b5336a7e6bb6616abcdfa691d7de/WordPress/Tests/DB/PreparedSQLPlaceholdersUnitTest.inc#L70-L124
	 */
	public static function get_limited_product_ids_with_any_group_restriction( array $product_ids ): array {
		global $wpdb;
		$sql         = $wpdb->prepare(
			sprintf(
				"SELECT DISTINCT post_id FROM `%s` WHERE meta_key = 'groups-read' AND post_id IN ( %s )",
				$wpdb->postmeta,
				implode( ',', array_fill( 0, count( $product_ids ), '%d' ) )
			),
			$product_ids
		);
		$product_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( 'intval', $product_ids );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/54042
	 * @see https://github.com/WordPress/WordPress-Coding-Standards/blob/dc2f21771cb2b5336a7e6bb6616abcdfa691d7de/WordPress/Tests/DB/PreparedSQLPlaceholdersUnitTest.inc#L70-L124
	 */
	public static function get_limited_product_ids_visible_to_all_groups( array $product_ids_for_grant_year ): array {
		$product_ids_with_any_group_restriction = self::get_limited_product_ids_with_any_group_restriction( $product_ids_for_grant_year );
		return array_diff( $product_ids_for_grant_year, $product_ids_with_any_group_restriction );
	}

	/**
	 * @see https://core.trac.wordpress.org/ticket/54042
	 * @see https://github.com/WordPress/WordPress-Coding-Standards/blob/dc2f21771cb2b5336a7e6bb6616abcdfa691d7de/WordPress/Tests/DB/PreparedSQLPlaceholdersUnitTest.inc#L70-L124
	 */
	public static function get_limited_product_ids_visible_to_groups( array $group_ids, array $product_ids ): array {
		global $wpdb;

		$sql = $wpdb->prepare(
			sprintf(
				"SELECT DISTINCT post_id FROM `%s` WHERE meta_key = 'groups-read' AND meta_value IN ( %s ) AND post_id IN ( %s )",
				$wpdb->postmeta,
				implode( ',', array_fill( 0, count( $group_ids ), '%d' ) ),
				implode( ',', array_fill( 0, count( $product_ids ), '%d' ) ),
			),
			array_merge( $group_ids, $product_ids )
		);

		$product_ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_map( 'intval', $product_ids );
	}

	/**
	 * Get all course ids that are:
	 *  - open to anyone
	 *  - has the same local authority group as the order or it's training centre
	 *  - has the same training centre group as the order
	 *  - has status in [ 'open_for_enrollment', 'enrollment_closed', 'date_passed' ]
	 *  - for a grant year
	 *
	 * @return int[] Product ids.
	 */
	public static function get_product_ids_visible_to_group( int $group_id, int $grant_year, array $status ): array {

		global $wpdb;

		$group = ( new Groups_Group( $group_id ) )->group;

		// Include training centre id when applicable.
		$group_ids = ! empty( $group->parent_id ) ? [ $group->group_id, $group->parent_id ] : [ $group->group_id ];

		// Limit products to statuses.
		$product_ids_with_status = self::get_product_ids_with_status( $status );
		if ( ! $product_ids_with_status ) {
			return [];
		}

		// Further limit products to grant year.
		$product_ids_for_grant_year = self::get_limited_product_ids_for_grant_year( $grant_year, $product_ids_with_status );
		if ( ! $product_ids_for_grant_year ) {
			return [];
		}

		// Products without group visibility restriction with status and for grant year.
		$product_ids_visible_to_all = self::get_limited_product_ids_visible_to_all_groups( $product_ids_for_grant_year );

		// Products with a group visibilty restriction with status and grant year.
		$product_ids_visible_to_group = self::get_limited_product_ids_visible_to_groups( $group_ids, $product_ids_for_grant_year );

		$product_ids = array_merge( $product_ids_visible_to_all, $product_ids_visible_to_group );

		return array_map( 'intval', $product_ids );
	}

	/**
	 * @return int[] Course ids.
	 */
	public static function get_product_ids_for_training_centre( int $training_centre_group_id, string $grant_year, array $status ): array {
		$options  = [
			'fields'         => 'ids',
			'post_status'    => $status,
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'meta_query'     => [
				[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found, Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey
					'key'     => 'training_centre',
					'compare' => '=',
					'type'    => 'NUMERIC',
					// Training centre group id needs to be added for local authorities.
					'value'   => $training_centre_group_id,
				],
				[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found, Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey
					'key'     => 'grant_year',
					'compare' => '=',
					'type'    => 'NUMERIC',
					// Training centre group id needs to be added for local authorities.
					'value'   => $grant_year,
				],
			],
		];
		$post_ids = get_posts( $options );
		return $post_ids;
	}

	public static function get_product_ids_for_courses_closed_in_grant_year_and_month_for_training_centre( int $grant_year, int $month, int $training_centre_group_id ): array {
		$status         = [ 'closed' ];
		$start_datetime = DateTime::createFromFormat( 'j/n/Y H:i', sprintf( '1/%d/%d 00:00', $month, $grant_year ), wp_timezone() );
		$end_datetime   = DateTime::createFromFormat( 'j/n/Y H:i', sprintf( '31/%d/%d 23:59', $month, $grant_year ), wp_timezone() );
		$course_ids     = self::get_product_ids_for_training_centre( $training_centre_group_id, $grant_year, $status );

		/**
		 * Passing an empty array to post__in will return has_posts() as true (and all posts will be returned). Logic should be used before hand to determine if WP_Query should be used in the event that the array being passed to post__in is empty.
		 *
		 * @see https://core.trac.wordpress.org/ticket/28099
		 */
		if ( empty( $course_ids ) ) {
			return [];
		}

		// The order of options seem to matter.
		$options  = [
			'post__in'       => $course_ids,
			'post_status'    => $status,
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				'relation' => 'AND',
				[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found, Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey
					'key'     => 'course_closed_timestamp',
					'compare' => '>=',
					'type'    => 'NUMERIC',
					'value'   => $start_datetime->format( 'U' ),
				],
				[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found
					'key'     => 'course_closed_timestamp',
					'compare' => '<=',
					'type'    => 'NUMERIC',
					'value'   => $end_datetime->format( 'U' ),
				],
			],
		];
		$post_ids = get_posts( $options );
		return $post_ids;
	}

	/**
	 * @deprecated Renamed to be more descriptive.
	 * @see self::get_product_ids_for_courses_closed_in_grant_year_and_month_visible_to_group()
	 */
	public static function get_product_ids_for_courses_closed_in_grant_year_and_month( int $grant_year, int $month, int $group_id = 0 ): array {
		return self::get_product_ids_for_courses_closed_in_grant_year_and_month_visible_to_group( $grant_year, $month, $group_id );
	}

	public static function get_product_ids_for_courses_closed_in_grant_year_and_month_visible_to_group( int $grant_year, int $month, int $group_id = 0 ): array {
		$status         = [ 'closed' ];
		$start_datetime = DateTime::createFromFormat( 'j/n/Y H:i', sprintf( '1/%d/%d 00:00', $month, $grant_year ), wp_timezone() );
		$end_datetime   = DateTime::createFromFormat( 'j/n/Y H:i', sprintf( '31/%d/%d 23:59', $month, $grant_year ), wp_timezone() );

		// The order of options seem to matter.
		$options = [
			'post_status'    => $status,
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [
				'relation' => 'AND',
				[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found, Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey
					'key'     => 'course_closed_timestamp',
					'compare' => '>=',
					'type'    => 'NUMERIC',
					'value'   => $start_datetime->format( 'U' ),
				],
				[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found
					'key'     => 'course_closed_timestamp',
					'compare' => '<=',
					'type'    => 'NUMERIC',
					'value'   => $end_datetime->format( 'U' ),
				],
			],
		];

		if ( $group_id > 0 ) {
			$course_ids          = self::get_product_ids_visible_to_group( $group_id, $grant_year, $status );
			$options['post__in'] = $course_ids;
		}

		$post_ids = get_posts( $options );
		return $post_ids;
	}

	public static function get_visible_product_ids_for_current_user(): array {
		$product_ids = get_posts(
			[
				'fields'         => 'ids',
				'post_status'    => [ self::$publish_status, 'date_passed' ],
				'post_type'      => 'product',
				'posts_per_page' => -1,
				'meta_query'     => [
					'relation' => 'OR',
					[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found, Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey
						'key'     => 'groups-read',
						'compare' => 'IN',
						'type'    => 'NUMERIC',
						'value'   => GroupUtils::get_current_users_group_ids_deep(),
					],
					[ // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found
						'key'     => 'groups-read',
						'compare' => 'NOT EXISTS',
					],
				],
			]
		);
		return $product_ids;
	}

	/**
	 * Get products with the same group memberships as my user.
	 *
	 * @see https://github.com/woocommerce/woocommerce/wiki/wc_get_products-and-WC_Product_Query
	 * @return WC_Product[]
	 */
	public static function get_visible_products(): array {
		$post_ids = self::get_visible_product_ids_for_current_user();
		$products = array_map(
			function ( $post_id ) {

				$product = wc_get_product( $post_id );
				$product->update_meta_data( 'reserved_stock_quantity', wc_get_held_stock_quantity( $product ) );
				return $product;
			},
			$post_ids
		);
		return $products;
	}

	/**
	 * Get products with status
	 *
	 * @return WC_Product[]
	 */
	public static function get_products_with_status( string $status ): array {
		require_once '/var/www/html/wp-admin/includes/post.php';
		$statuses = get_available_post_statuses( 'product' );
		if ( in_array( $status, get_available_post_statuses( 'product' ) ) ) {
			$post_ids = get_posts(
				[
					'fields'         => 'ids',
					'post_status'    => trim( $status ),
					'post_type'      => 'product',
					'posts_per_page' => -1,
				]
			);
			$products = array_map( fn( $post_id ) => wc_get_product( $post_id ), $post_ids );
			return $products;
		}
		return [];
	}

	/**
	 * @deprecated
	 */
	public static function get_orders_ids_by_product_id( int $product_id, array $order_status = [ 'wc-processing', 'wc-completed', 'wc-on-hold' ] ): array {
		return OrderUtils::get_order_ids_by_product_id( $product_id, 0, $order_status );
	}

	public static function get_total_items( $order_ids ) {
		$total = 0;
		foreach ( $order_ids as $order_id ) {
			$order  = wc_get_order( $order_id );
			$total += $order->get_item_count();
		}

		return $total;
	}
}
