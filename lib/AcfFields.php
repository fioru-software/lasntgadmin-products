<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\PaymentGateway\GrantFunded\{ GrantYearUtils, FundingSourceUtils };
use Lasntg\Admin\Group\GroupUtils;
use stdClass;

class AcfFields {

	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	private static function add_filters() {
		add_filter( 'acf/load_field', [ self::class, 'set_course_template_fields_optional' ] );
		add_filter( 'acf/validate_value', [ self::class, 'validate_product_template' ], 10, 4 );
		add_filter( 'acf/load_field/name=funding_sources', [ self::class, 'populate_funding_source_select_options' ], 10 );
		add_filter( 'acf/load_field/name=grant_year', [ self::class, 'populate_grant_year_select_options' ], 10 );
		add_filter( 'acf/load_field/name=training_centre', [ self::class, 'populate_training_centre_select_options' ], 10 );
	}

	private static function add_actions() {
	}

	/**
	 * When editing a course template
	 * then all fields are optional
	 */
	public static function validate_product_template( $valid, $value, $field, $input_name ) {
		if ( is_admin() ) {
			if ( isset( $_POST['post_type'] ) && isset( $_POST['post_id'] ) && isset( $_POST['post_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( 'product' === $_POST['post_type'] && 'template' === $_POST['post_status'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$valid = true;
				}
			}
		}
		return $valid;
	}

	/**
	 * When editing a course template
	 * then all fields are optional
	 */
	public static function set_course_template_fields_optional( array $field ): array {
		if ( is_admin() && function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( ! is_null( $screen ) ) {
				if ( 'product' === $screen->post_type && 'edit' === $screen->parent_base && 'product' === $screen->id ) {
					if ( isset( $_GET['post'] ) ) {
						$post_id = intval( $_GET['post'] );
						$post    = get_post( $post_id );
						if ( 'template' === $post->post_status ) {
							$field['required'] = 0;
						}
					}
				}
			}
		}
		return $field;
	}

	public static function get_awarding_body_acf_field_group_id( string $acf_field_value ): string {
		$field_group_id  = '';
		$awarding_bodies = [
			'Solas CSCS'     => 'group_638ae5f1ef0b7',
			'Solas Safepass' => 'group_638ae6c17fe42',
			'TTM'            => 'group_638ae836b826c',
			'ATU Sligo'      => 'group_638aea32eb29f',
			'RSA'            => 'group_638ae8b651fba',
			'SETU Carlow'    => 'group_638aeb7c2b825',
			'QQI'            => 'group_6388299addcf9',
		];
		if ( isset( $awarding_bodies[ $acf_field_value ] ) ) {
			$field_group_id = $awarding_bodies[ $acf_field_value ];
		}
		return $field_group_id;
	}

	public static function get_water_grant_acf_field_group_id(): string {
		return 'group_638aec109ab55';
	}


	/**
	 * Fetch local authority options from the groups table.
	 */
	public static function populate_training_centre_select_options( array $field ): array {
		$training_centres = GroupUtils::get_all_training_centre_groups();

		$field['choices'] = [];
		foreach ( $training_centres as $training_centre ) {
			$field['choices'][ $training_centre->group_id ] = $training_centre->name;
		}
		return $field;
	}

	/**
	 * Fetch local authority options from the groups table.
	 */
	public static function populate_funding_source_select_options( array $field ): array {
		$funding_sources = FundingSourceUtils::get_all();

		$field['choices'] = [];
		foreach ( $funding_sources as $funding_source ) {
			$field['choices'][ $funding_source->slug ] = $funding_source->name;
		}
		return $field;
	}

	public static function populate_grant_year_select_options( array $field ): array {
		$grant_years = GrantYearUtils::get_all();

		$field['choices']         = [];
		$field['choices'][ null ] = 'Not funded';
		foreach ( $grant_years as $grant_year ) {
			$field['choices'][ $grant_year ] = $grant_year;
		}
		return $field;
	}
}
