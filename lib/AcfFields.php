<?php

namespace Lasntg\Admin\Products;

use Lasntg\Admin\PaymentGateway\GrantFunded\{ GrantYearUtils, FundingSourceUtils };

class AcfFields {

	public static function init() {
		self::add_actions();
		self::add_filters();
	}

	private static function add_filters() {
	}

	private static function add_actions() {
		// Apply to all fields.
		add_action( 'acf/load_field/name=funding_sources', [ self::class, 'populate_funding_source_select_options' ], 10 );
		add_action( 'acf/load_field/name=grant_year', [ self::class, 'populate_grant_year_select_options' ], 10 );
	}

	/**
	 * Fetch local authority options from the groups table.
	 */
	public static function populate_funding_source_select_options( array $field ): array {
		$funding_sources = FundingSourceUtils::get_all();

		foreach ( $funding_sources as $funding_source ) {
			$field['choices'][ $funding_source->slug ] = $funding_source->name;
		}
		return $field;
	}

    public static function populate_grant_year_select_options( array $field ): array {
		$grant_years = GrantYearUtils::get_all();

        $field['choices'][ null ] = 'Not funded';
		foreach ( $grant_years as $grant_year ) {
			$field['choices'][ $grant_year ] = $grant_year;
		}
		return $field;
    }

}

