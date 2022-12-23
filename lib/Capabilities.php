<?php

namespace Lasntg\Admin\Products;

/**
 * Plugin capabilities assigned to roles
 */
class Capabilities {

	/**
	 * Admin capabilities
	 */
	public static function get_administrator_capabilities(): array {
		return array();
	}

	/**
	 * National manager capabilities
	 */
	public static function get_national_manager_capabilities(): array {
		return array(
			'view_admin_dashboard',
			'read',

			'read_product',
			'edit_product',
			'edit_product_terms',
			'delete_product_terms',
			'delete_product',
			'edit_product',
			'assign_product_terms',
			'publish_products',

			'delete_private_products',
			'delete_products',
			'delete_published_products',
			'edit_others_products',
			'edit_private_products',
			'edit_products',
			'publish_products',
			'edit_published_products',
			'read_private_products',
		);
	}

	/**
	 * Regional training officer capabilities
	 */
	public static function get_regional_training_centre_manager_capabilities(): array {
		return [
			'view_admin_dashboard',
			'read',

			'read_product',
			'edit_product',
			'edit_product_terms',
			'delete_product_terms',
			'delete_product',
			'edit_product',
			'assign_product_terms',
			'publish_products',

			'delete_private_products',
			'delete_products',
			'delete_published_products',
			'edit_others_products',
			'edit_private_products',
			'edit_products',
			'publish_products',
			'edit_published_products',
			'read_private_products',
		];
	}

	/**
	 * Training officer capabilities
	 */
	public static function get_training_officer_capabilities(): array {
		return [
			'view_admin_dashboard',
			'read',
		];
	}

	/**
	 * Fire training officer capabilities
	 */
	public static function get_fire_training_officer_capabilities(): array {
		return [];
	}

	public static function add(): void {
		$role = get_role( 'administrator' );
		$caps = self::get_administrator_capabilities();
		array_walk(
			$caps,
			function( $cap ) use ( $role ) {
				$role->add_cap( $cap );
			}
		);

		$role = get_role( 'national_manager' );
		$caps = self::get_national_manager_capabilities();
		array_walk(
			$caps,
			function( $cap ) use ( $role ) {
				$role->add_cap( $cap );
			}
		);

		$role = get_role( 'regional_training_centre_manager' );
		$caps = self::get_regional_training_centre_manager_capabilities();
		array_walk(
			$caps,
			function( $cap ) use ( $role ) {
				$role->add_cap( $cap );
			}
		);

		$role = get_role( 'training_officer' );
		$caps = self::get_training_officer_capabilities();
		array_walk(
			$caps,
			function( $cap ) use ( $role ) {
				$role->add_cap( $cap );
			}
		);

		$role = get_role( 'fire_training_officer' );
		$caps = self::get_fire_training_officer_capabilities();
		array_walk(
			$caps,
			function( $cap ) use ( $role ) {
				$role->add_cap( $cap );
			}
		);
	}

	public static function remove(): void {
		$role = get_role( 'administrator' );
		$caps = self::get_administrator_capabilities();
		array_walk(
			$caps,
			function( $cap ) use ( $role ) {
				$role->remove_cap( $cap );
			}
		);

		$role = get_role( 'national_manager' );
		$caps = self::get_national_manager_capabilities();
		array_walk(
			$caps,
			function( $cap ) use ( $role ) {
				$role->remove_cap( $cap );
			}
		);

		$role = get_role( 'regional_training_centre_manager' );
		$caps = self::get_regional_training_centre_manager_capabilities();
		array_walk(
			$caps,
			function( $cap ) use ( $role ) {
				$role->remove_cap( $cap );
			}
		);

		$role = get_role( 'training_officer' );
		$caps = self::get_training_officer_capabilities();
		array_walk(
			$caps,
			function( $cap ) use ( $role ) {
				$role->remove_cap( $cap );
			}
		);

		$role = get_role( 'fire_training_officer' );
		$caps = self::get_fire_training_officer_capabilities();
		array_walk(
			$caps,
			function( $cap ) use ( $role ) {
				$role->remove_cap( $cap );
			}
		);
	}

}
