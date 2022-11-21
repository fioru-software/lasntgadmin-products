<?php
/**
 * WP Actions also includes woocommerce actions
 */

add_filter( 'woocommerce_product_data_tabs', 'lasntgadmin_quotas_product_tab', 10, 1 );

/**
 * Show tab
 *
 * @param  array $default_tabs the default woocommerce tabs.
 * @return array
 */
function lasntgadmin_quotas_product_tab( array $default_tabs ): array {
	$default_tabs['quotas_tab'] = array(
		'label'    => __( 'Quotas', 'lasntgadmin' ),
		'target'   => 'lasntgadmin_quotas_product_tab_data',
		'priority' => 20,
		'class'    => array(),
	);
	return $default_tabs;
}

add_action( 'woocommerce_product_data_panels', 'lasntgadmin_quotas_product_tab_data', 10, 1 );
/**
 * Show tab data
 *
 * @return void
 */
function lasntgadmin_quotas_product_tab_data(): void {
	global $post, $wpdb;

	$prefix = $wpdb->prefix;
	$table  = $prefix . 'groups_group';

	$results = $wpdb->get_results( "SELECT name,group_id FROM $table ORDER BY name" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

	echo '<div id="lasntgadmin_quotas_product_tab_data" class="panel woocommerce_options_panel">';
	// Get action. For new product it's add.
	$action = get_current_screen()->action;

	foreach ( $results as $group ) {
		$groups[ $group->group_id ] = $group->name;
		woocommerce_wp_text_input(
			array(
				'id'                => '_quotas_field_' . $group->group_id,
				'label'             => $group->name,
				'placeholder'       => "Enter the quotas for {$group->name}.",
				'desc_tip'          => 'true',
				'description'       => "Enter the quotas for {$group->name}.",
				'value'             => 'add' !== $action ? get_post_meta( $post->ID, '_quotas_field_' . $group->group_id, true ) : 0,
				'type'              => 'number',
				'custom_attributes' => array(
					'step' => '1',
					'min'  => '0',
					'type' => 'number',
				),
			)
		);
	}

	echo '</div>';
}

add_action( 'woocommerce_process_product_meta', 'lasntgadmin_quotas_add_quotas_save' );
/**
 * Save Quotas
 *
 * @param  mixed $post_id the product id.
 * @return void
 */
function lasntgadmin_quotas_add_quotas_save( $post_id ): void {
	global $wpdb;
	$prefix = $wpdb->prefix;
	$table  = $prefix . 'groups_group';

	$results = $wpdb->get_results( "SELECT name,group_id FROM $table ORDER BY name" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	// fields.
	foreach ( $results as $group ) {
		if ( ! isset( $_POST[ '_quotas_field_' . $group->group_id ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			continue;
		}
		$field = sanitize_text_field( wp_unslash( $_POST[ '_quotas_field_' . $group->group_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		update_post_meta( $post_id, '_quotas_field_' . $group->group_id, esc_attr( $field ) );
	}
}
