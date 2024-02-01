<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;

/**
 * Product edit page.
 */
class AdminEditView {

	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	private static function add_actions() {
	}

	private static function add_filters() {

		// Product edit page groups metabox filter.
		add_filter( 'groups_access_meta_boxes_groups_get_groups_options', [ self::class, 'get_group_options_for_product_visbility_restriction_metabox' ] );
	}

	/**
	 * Product edit page groups metabox filter dropdown.
	 *
	 * @see https://github.com/fioru-software/lasntgadmin-itthinx-groups/blob/master/lib/access/class-groups-access-meta-boxes.php#L211
	 */
	public static function get_group_options_for_product_visbility_restriction_metabox( array $options ): array {
		if ( is_admin() && ! is_search() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( ! is_null( $screen ) ) {
				if ( 'product' === $screen->post_type && 'product' === $screen->id && 'edit' === $screen->parent_base ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$user_groups = GroupUtils::get_all_group_ids();
					$options     = [
						'order_by' => 'parent_id',
						'order'    => 'ASC',
						'include'  => $user_groups,
					];
				}
			}
		}
		return $options;
	}
}
