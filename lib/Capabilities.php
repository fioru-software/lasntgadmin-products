<?php

namespace Lasntg\Admin\Order;

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
		return array();
	}

	/**
	 * Regional training officer capabilities
	 */
	public static function get_regional_training_centre_manager_capabilities(): array {
		return [];
	}

	/**
	 * Training officer capabilities
	 */
	public static function get_training_officer_capabilities(): array {
		return [];
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
