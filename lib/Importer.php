<?php

namespace Lasntg\Admin\Products;

/**
 * @see https://github.com/woocommerce/woocommerce/wiki/Product-CSV-Importer-&-Exporter#adding-custom-import-columns-developers
 */
class Importer {

    public static function init() {
        self::add_filters();
    }

    public static function add_filters() {
        add_filter( 'woocommerce_csv_product_import_mapping_options', [ self::class, 'add_column_to_importer' ] );
        add_filter( 'woocommerce_csv_product_import_mapping_default_columns', [ self::class, 'add_column_to_mapping_screen' ] );
        add_filter( 'woocommerce_product_import_pre_insert_product_object', [ self::class, 'process_import' ], 10, 2 );
    }

    private static function getColumns() {
        return [
            'event_type'=> 'Event Type',
            'grant_year'=> 'Grant Year',
            'funding_sources'=> 'Funding Sources',
            'road_grant_job_types'=> 'Job Types',
            'awarding_body'=> 'Awarding Body',
            'award'=> 'Award',
            'start_date'=> 'Start Date',
            'start_time'=> 'Start Time',
            'end_date'=> 'End Date',
            'end_time'=> 'End Time',
            'duration'=> 'Duration',
            'location'=> 'Location',
            'onsiteoffsite'=> 'Onsite/Offsite',
            'training_centre'=> 'Training Centre',
            'training_group'=> 'Training Group',
            'course_trainer_name'=> 'Course Trainer Name',
            'training_provider'=> 'Training Provider',
            'course_trainer_email'=> 'Course Trainer Email',
            'entry_requirements_file'=> 'Entry Requirements File',
            'training_aim'=> 'Training Aim',
            'applicable_regulation'=> 'Applicable Regulation',
            'primary_target_grade'=> 'Primary Target Grade',
            'other_grades_applicable'=> 'Other Grades Applicable',
            'expiry_period'=> 'Expiry Period',
            'renewal_method'=> 'Renewal Method',
            'internal_external'=> 'Internal/External',
            'documentation'=> 'Documentation',
            'link_to_more_information'=> 'Link To More Information',
            'course_order'=> 'Course Order',
        ];
    }

    /**
     * Register the 'Custom Column' column in the importer.
     *
     * @param array $options
     * @return array $options
     */
    public static function add_column_to_importer( $options ) {

        $cols = self::getColumns();
        foreach( $cols as $key => $name ) {
            $options[$key] = $name;
        }
        return $options;
    }

    /**
     * Add automatic mapping support for 'Custom Column'.
     * This will automatically select the correct mapping for columns named 'Custom Column' or 'custom column'.
     *
     * @param array $columns
     * @return array $columns
     */
    public static function add_column_to_mapping_screen( $columns ) {

        $cols = self::getColumns();
        foreach( $cols as $key => $name ) {
            $columns[$name] = $key;
        }
        return $columns;
    }

    /**
     * Process the data read from the CSV file.
     * This just saves the value in meta data, but you can do anything you want here with the data.
     *
     * @param WC_Product $object - Product being imported or updated.
     * @param array $data - CSV data read for the product.
     * @return WC_Product $object
     */
    public static function process_import( $object, $data ) {

        $cols = self::getColumns();
        foreach( $cols as $key => $name ) {
            if ( ! empty( $data[$key] ) ) {
                $object->update_meta_data( $key, $data[$key] );
            }
        }
        return $object;

    }
}
