<?php
/**
 * Report Actions
 *
 * @package   restrict-content-pro
 * @copyright Copyright (c) 2019, Sandhills Development, LLC
 * @license   GPL2+
 * @since     3.3
 */

/**
 * Get the membership counts report data
 *
 * @since 3.3
 * @return void
 */
function rcp_get_membership_counts_report_data() {

	check_ajax_referer( 'rcp_load_reports', 'nonce' );

	$membership_level_id = ! empty( $_POST['level_id'] ) ? absint( $_POST['level_id'] ) : 0;
	$status              = ! empty( $_POST['membership_status'] ) ? $_POST['membership_status'] : '';

	$config = array(
		'type'    => 'line',
		'data'    => array(
			'labels'   => array(), // Date intervals
			'datasets' => array(
				'active'    => array(
					'label'           => __( 'Active Memberships', 'rcp' ),
					'backgroundColor' => 'rgba(12, 194, 25, 0.1)',
					'borderColor'     => 'rgb(12, 194, 25)',
					//'fill' => false
				),
				'expired'   => array(
					'label'           => __( 'Expired Memberships', 'rcp' ),
					'backgroundColor' => 'rgba(255, 99, 132, 0.1)',
					'borderColor'     => 'rgb(255, 99, 132)',
					//'fill' => false
				),
				'cancelled' => array(
					'label'           => __( 'Cancelled Memberships', 'rcp' ),
					'backgroundColor' => 'rgba(178, 178, 178, 0.1)',
					'borderColor'     => 'rgb(178, 178, 178)',
					//'fill' => false
				),
				'pending'   => array(
					'label'           => __( 'Pending Memberships', 'rcp' ),
					'backgroundColor' => 'rgba(59, 168, 245, 0.1)',
					'borderColor'     => 'rgb(59, 168, 245)',
					//'fill' => false
				)
			)
		),
		'options' => array(
			'scales' => array(
				'xAxes' => array(
					array(
						'ticks' => array(
							'autoSkipPadding' => 10,
							'maxLabels'       => 52
						)
					)
				),
				'yAxes' => array(
					array(
						'ticks' => array(
							'min' => 0
						)
					)
				),
			)
		)
	);

	if ( ! empty( $status ) ) {
		foreach ( $config['data']['datasets'] as $status_key => $status_name ) {
			if ( $status_key !== $status ) {
				unset( $config['data']['datasets'][ $status_key ] );
			}
		}
	}

	$statuses = array_keys( $config['data']['datasets'] );

	$dates  = rcp_get_report_dates();
	$ranges = rcp_get_graph_dates_by_range();
	$from   = date_create_from_format( 'j n Y', sprintf( '%d %d %d', $dates['day'], $dates['m_start'], $dates['year'] ) );
	$to     = date_create_from_format( 'j n Y', sprintf( '%d %d %d', $dates['day_end'], $dates['m_end'], $dates['year_end'] ) );

	$config['data']['labels'] = $ranges;

	$membership_counts_table_name = restrict_content_pro()->membership_counts_table->get_table_name();

	global $wpdb;

	$where = $wpdb->prepare( "WHERE date_created >= %s AND date_created <= %s", $from->format( 'Y-m-d 00:00:00' ), $to->format( 'Y-m-d 23:59:59' ) );

	if ( ! empty( $membership_level_id ) ) {
		$where .= $wpdb->prepare( " AND level_id = %d ", $membership_level_id );
	}

	$query             = "SELECT SUM(active_count) AS active, SUM(pending_count) AS pending, SUM(cancelled_count) AS cancelled, SUM(expired_count) AS expired, DATE_FORMAT(date_created, '%Y-%m-%d') as date FROM {$membership_counts_table_name} {$where} GROUP BY date";
	$results           = $wpdb->get_results( $query );
	$formatted_results = array();

	foreach ( $results as $result ) {
		$formatted_results[ $result->date ] = array(
			'active'    => $result->active,
			'pending'   => $result->pending,
			'cancelled' => $result->cancelled,
			'expired'   => $result->expired
		);
	}

	foreach ( $ranges as $range ) {
		$exists = false;
		if ( array_key_exists( $range, $formatted_results ) ) {
			$exists = true;
		}
		foreach ( $statuses as $status ) {
			$config['data']['datasets'][ $status ]['data'][] = $exists ? absint( $formatted_results[ $range ][ $status ] ) : 0;
		}
	}

	$config['data']['datasets'] = array_values( $config['data']['datasets'] );

	wp_send_json_success( $config );

	exit;

}

add_action( 'wp_ajax_rcp_get_membership_counts_report_data', 'rcp_get_membership_counts_report_data' );
