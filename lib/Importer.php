<?php

namespace Lasntg\Admin\Products;

use WC_Product;

/**
 * @see https://github.com/woocommerce/woocommerce/wiki/Product-CSV-Importer-&-Exporter#adding-custom-import-columns-developers
 */
class Importer {

	public static function init(): void {
		self::add_filters();
	}

	public static function add_filters(): void {
		add_filter( 'woocommerce_csv_product_import_mapping_options', [ self::class, 'add_column_to_importer' ] );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns', [ self::class, 'add_column_to_mapping_screen' ] );
		add_filter( 'woocommerce_product_import_pre_insert_product_object', [ self::class, 'process_import' ], 10, 2 );
	}

	private static function get_columns(): array {
		return [
			'groups-read'              => 'Groups',
			'event_type'               => 'Event Type',
			'grant_year'               => 'Grant Year',
			'funding_sources'          => 'Funding Sources',
			'road_grant_job_types'     => 'Job Types',
			'awarding_body'            => 'Awarding Body',
			'award'                    => 'Award',
			'start_date'               => 'Start Date',
			'start_time'               => 'Start Time',
			'end_date'                 => 'End Date',
			'end_time'                 => 'End Time',
			'duration'                 => 'Duration',
			'location'                 => 'Location',
			'onsiteoffsite'            => 'Onsite/Offsite',
			'training_centre'          => 'Training Centre',
			'training_group'           => 'Training Group',
			'course_trainer_name'      => 'Course Trainer Name',
			'training_provider'        => 'Training Provider',
			'course_trainer_email'     => 'Course Trainer Email',
			'entry_requirements_file'  => 'Entry Requirements File',
			'training_aim'             => 'Training Aim',
			'applicable_regulation'    => 'Applicable Regulation',
			'primary_target_grade'     => 'Primary Target Grade',
			'other_grades_applicable'  => 'Other Grades Applicable',
			'expiry_period'            => 'Expiry Period',
			'renewal_method'           => 'Renewal Method',
			'internal_external'        => 'Internal/External',
			'documentation'            => 'Documentation',
			'link_to_more_information' => 'Link To More Information',
			'course_order'             => 'Course Order',
			'post_status'              => 'Status',
		];
	}

	/**
	 * Register the 'Custom Column' column in the importer.
	 */
	public static function add_column_to_importer( array $options ): array {
		$cols = self::get_columns();
		foreach ( $cols as $key => $name ) {
			$options[ $key ] = $name;
		}
		return $options;
	}

	/**
	 * Add automatic mapping support for 'Custom Column'.
	 * This will automatically select the correct mapping for columns named 'Custom Column' or 'custom column'.
	 */
	public static function add_column_to_mapping_screen( array $columns ): array {
		$cols = self::get_columns();
		foreach ( $cols as $key => $name ) {
			$columns[ $name ] = $key;
		}
		return $columns;
	}

	/**
	 * Process the data read from the CSV file.
	 * This just saves the value in meta data, but you can do anything you want here with the data.
	 */
	public static function process_import( WC_Product $object, array $data ): WC_Product {
		$cols = self::get_columns();

		foreach ( $cols as $key => $name ) {
			if ( ! empty( $data[ $key ] ) ) {
				switch ( $key ) {

					// checkboxes
					case 'road_grant_job_types':
						$list = explode( ',', $data[ $key ] );
						$object->update_meta_data( $key, $list );
						break;

					case 'post_status':
						$object->set_status( $data[ $key ] );
						break;

					// itthinx groups plugin.
					case 'groups-read':
						$list = explode( ',', $data[ $key ] );
						$object->delete_meta_data( $key );
						foreach ( $list as $value ) {
							$object->add_meta_data( $key, $value );
						}
						break;

					// multiple select.
					case 'training_centre':
					case 'funding_sources':
					case 'primary_target_grade':
					case 'other_grades_applicable':
						$list = explode( ',', $data[ $key ] );
						$object->update_meta_data( $key, $list );
						break;

					default:
						$object->update_meta_data( $key, $data[ $key ] );
				}//end switch
			}//end if
		}//end foreach
		return $object;
	}
}
