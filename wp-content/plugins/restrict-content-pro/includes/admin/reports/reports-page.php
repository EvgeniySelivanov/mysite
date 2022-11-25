<?php
/**
 * Reports Page
 *
 * @package     Restrict Content Pro
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2017, Restrict Content Pro
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Renders the reports page
 *
 * @access  public
 * @since   1.8
 * @return  void
 */
function rcp_reports_page() {
	$current_page = admin_url( 'admin.php?page=rcp-reports' );
	$active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'earnings';
	?>
	<div id="rcp-reports-wrap" class="wrap" data-nonce="<?php echo esc_attr( wp_create_nonce( 'rcp_load_reports' ) ); ?>">
		<h2 class="nav-tab-wrapper">
			<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'earnings' ), $current_page ) ); ?>" class="nav-tab <?php echo $active_tab == 'earnings' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Earnings', 'rcp' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'refunds' ), $current_page ) ); ?>" class="nav-tab <?php echo $active_tab == 'refunds' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Refunds', 'rcp' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'signups' ), $current_page ) ); ?>" class="nav-tab <?php echo $active_tab == 'signups' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Signups', 'rcp' ); ?></a>
			<a href="<?php echo esc_url( add_query_arg( array( 'tab' => 'membership_counts' ), $current_page ) ); ?>" class="nav-tab <?php echo $active_tab == 'membership_counts' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Membership Counts', 'rcp' ); ?></a>
			<?php do_action( 'rcp_reports_tabs' ); ?>
		</h2>

		<?php
		do_action( 'rcp_reports_page_top' );
		do_action( 'rcp_reports_tab_' . $active_tab );
		do_action( 'rcp_reports_page_bottom' );
		?>
	</div><!-- .wrap -->
	<?php
}

/**
 * Displays the earnings graph.
 *
 * @uses rcp_get_report_dates()
 *
 * @access  public
 * @since   1.8
 * @return  void
*/
function rcp_earnings_graph() {
	global $rcp_options, $wpdb;

	// Retrieve the queried dates
	$dates = rcp_get_report_dates();

	// Determine graph options
	switch ( $dates['range'] ) :
		case 'today' :
			$time_format 	= '%d/%b';
			$tick_size		= 'hour';
			$day_by_day		= true;
			break;
		case 'last_year' :
			$time_format 	= '%b';
			$tick_size		= 'month';
			$day_by_day		= false;
			break;
		case 'this_year' :
			$time_format 	= '%b';
			$tick_size		= 'month';
			$day_by_day		= false;
			break;
		case 'last_quarter' :
			$time_format	= '%b';
			$tick_size		= 'month';
			$day_by_day 	= false;
			break;
		case 'this_quarter' :
			$time_format	= '%b';
			$tick_size		= 'month';
			$day_by_day 	= false;
			break;
		case 'other' :
			if( $dates['m_end'] - $dates['m_start'] >= 2 || $dates['year_end'] > $dates['year'] ) {
				$time_format	= '%b';
				$tick_size		= 'month';
				$day_by_day 	= false;
			} else {
				$time_format 	= '%d/%b';
				$tick_size		= 'day';
				$day_by_day 	= true;
			}
			break;
		default:
			$time_format 	= '%d/%b'; 	// Show days by default
			$tick_size		= 'day'; 	// Default graph interval
			$day_by_day 	= true;
			break;
	endswitch;


	$time_format 	= apply_filters( 'rcp_graph_timeformat', $time_format );
	$tick_size 		= apply_filters( 'rcp_graph_ticksize', $tick_size );
	$earnings 		= (float) 0.00; // Total earnings for time period shown
	$subscription   = isset( $_GET['subscription'] ) ? absint( $_GET['subscription'] ) : false;
	$payments_db    = new RCP_Payments;

	$args = array(
		'subscription' => rcp_get_subscription_name( $subscription ),
		'date' => array()
	);

	ob_start(); ?>
	<script type="text/javascript">
	   jQuery( document ).ready( function($) {
			$.plot(
				$("#rcp_earnings_graph"),
				[{
					data: [
						<?php

						if( $dates['range'] == 'this_week' || $dates['range'] == 'last_week'  ) {

							//Day by day
							$day     = $dates['day'];
							$day_end = $dates['day_end'];
							$month   = $dates['m_start'];

							while ( $day <= $day_end ) :

								$args = array(
									'date' => array(
										'day'   => $day,
										'month' => $month,
										'year'  => $dates['year']
									),
									'fields' => 'amount'
								);

								$args['date'] = array( 'day' => $day, 'month' => $month, 'year' => $dates['year'] );

								$payments = $payments_db->get_earnings( $args );
								$earnings += $payments;
								$date = mktime( 0, 0, 0, $month, $day, $dates['year'] ); ?>
								[<?php echo $date * 1000; ?>, <?php echo $payments; ?>],
								<?php
								$day++;
							endwhile;

						} else {

							$y = $dates['year'];
							while( $y <= $dates['year_end'] ) :

								if( $dates['year'] == $dates['year_end'] ) {
									$month_start = $dates['m_start'];
									$month_end   = $dates['m_end'];
								} elseif( $y == $dates['year'] ) {
									$month_start = $dates['m_start'];
									$month_end   = 12;
								} elseif ( $y == $dates['year_end'] ) {
									$month_start = 1;
									$month_end   = $dates['m_end'];
								} else {
									$month_start = 1;
									$month_end   = 12;
								}

								$i = $month_start;
								while ( $i <= $month_end ) :
									if ( $day_by_day ) :
										$num_of_days 	= cal_days_in_month( CAL_GREGORIAN, $i, $y );
										$d 				= 1;
										while ( $d <= $num_of_days ) :
											$args['date'] = array( 'day' => $d, 'month' => $i, 'year' => $y );
											$payments = $payments_db->get_earnings( $args );
											$earnings += $payments;
											$date = mktime( 0, 0, 0, $i, $d, $y ); ?>
											[<?php echo $date * 1000; ?>, <?php echo $payments; ?>],
										<?php
										$d++;
										endwhile;
									else :

										$args['date'] = array( 'day' => null, 'month' => $i, 'year' => $y );
										$payments = $payments_db->get_earnings( $args );
										$earnings += $payments;
										$date = mktime( 0, 0, 0, $i, 1, $y );
										?>
										[<?php echo $date * 1000; ?>, <?php echo $payments; ?>],
									<?php
									endif;
									$i++;
								endwhile;

								$y++;
							endwhile;

						}

						?>,
					],
					yaxis: 2,
					label: "<?php _e( 'Earnings', 'rcp' ); ?>",
					id: 'sales'
				}],
			{
				series: {
				   lines: { show: true },
				   points: { show: true }
				},
				grid: {
					show: true,
					aboveData: false,
					color: '#ccc',
					backgroundColor: '#fff',
					borderWidth: 2,
					borderColor: '#ccc',
					clickable: false,
					hoverable: true
				},
				xaxis: {
					mode: "time",
					timeFormat: "<?php echo $time_format; ?>",
					minTickSize: [1, "<?php echo $tick_size; ?>"]
				},
				yaxis: {
					min: 0,
					minTickSize: 1,
					tickDecimals: 0
				}

			});

			function rcp_flot_tooltip(x, y, contents) {
				$('<div id="rcp-flot-tooltip">' + contents + '</div>').css( {
					position: 'absolute',
					display: 'none',
					top: y + 5,
					left: x + 5,
					border: '1px solid #fdd',
					padding: '2px',
					'background-color': '#fee',
					opacity: 0.80
				}).appendTo("body").fadeIn(200);
			}

			var previousPoint = null;
			$("#rcp_earnings_graph").bind("plothover", function (event, pos, item) {
				if (item) {
					if (previousPoint != item.dataIndex) {
						previousPoint = item.dataIndex;
						$("#rcp-flot-tooltip").remove();
						var x = item.datapoint[0].toFixed(2),
						y = item.datapoint[1].toFixed(2);
						if( rcp_vars.currency_pos == 'before' ) {
							rcp_flot_tooltip( item.pageX, item.pageY, item.series.label + ' ' + rcp_vars.currency_sign + y );
						} else {
							rcp_flot_tooltip( item.pageX, item.pageY, item.series.label + ' ' + y + rcp_vars.currency_sign );
						}
					}
				} else {
					$("#rcp-flot-tooltip").remove();
					previousPoint = null;
				}
			});
	   });
	</script>
	<h1><?php _e( 'Earnings Report', 'rcp' ); ?></h1>
	<div class="metabox-holder" style="padding-top: 0;">
		<div class="postbox">
			<div class="inside">
				<?php rcp_reports_graph_controls(); ?>
				<div id="rcp_earnings_graph" style="height: 300px;"></div>
				<p class="rcp_graph_totals"><strong><?php _e( 'Total earnings for period shown: ', 'rcp' ); echo rcp_currency_filter( $earnings ); ?></strong></p>
			</div>
		</div>
	</div>
	<?php
	echo ob_get_clean();
}
add_action( 'rcp_reports_tab_earnings', 'rcp_earnings_graph' );

/**
 * Displays the refunds graph.
 *
 * @access  public
 * @since   2.5
 * @return  void
 */
function rcp_refunds_graph() {
	global $rcp_options, $wpdb;

	// Retrieve the queried dates
	$dates = rcp_get_report_dates();

	// Determine graph options
	switch ( $dates['range'] ) :
		case 'today' :
			$time_format 	= '%d/%b';
			$tick_size		= 'hour';
			$day_by_day		= true;
			break;
		case 'last_year' :
			$time_format 	= '%b';
			$tick_size		= 'month';
			$day_by_day		= false;
			break;
		case 'this_year' :
			$time_format 	= '%b';
			$tick_size		= 'month';
			$day_by_day		= false;
			break;
		case 'last_quarter' :
			$time_format	= '%b';
			$tick_size		= 'month';
			$day_by_day 	= false;
			break;
		case 'this_quarter' :
			$time_format	= '%b';
			$tick_size		= 'month';
			$day_by_day 	= false;
			break;
		case 'other' :
			if( ( $dates['m_end'] - $dates['m_start'] ) >= 2 ) {
				$time_format	= '%b';
				$tick_size		= 'month';
				$day_by_day 	= false;
			} else {
				$time_format 	= '%d/%b';
				$tick_size		= 'day';
				$day_by_day 	= true;
			}
			break;
		default:
			$time_format 	= '%d/%b'; 	// Show days by default
			$tick_size		= 'day'; 	// Default graph interval
			$day_by_day 	= true;
			break;
	endswitch;

	$time_format 	= apply_filters( 'rcp_graph_timeformat', $time_format );
	$tick_size 		= apply_filters( 'rcp_graph_ticksize', $tick_size );
	$refunds 		= (float) 0.00; // Total refunds for time period shown
	$subscription   = isset( $_GET['subscription'] ) ? absint( $_GET['subscription'] ) : false;
	$payments_db    = new RCP_Payments;

	$args = array(
		'subscription' => rcp_get_subscription_name( $subscription ),
		'date' => array()
	);

	ob_start(); ?>
	<script type="text/javascript">
	   jQuery( document ).ready( function($) {
	   		$.plot(
	   			$("#rcp_refunds_graph"),
	   			[{
   					data: [
	   					<?php

	   					if( $dates['range'] == 'this_week' || $dates['range'] == 'last_week'  ) {

							//Day by day
							$day     = $dates['day'];
							$day_end = $dates['day_end'];
	   						$month   = $dates['m_start'];

							while ( $day <= $day_end ) :

		   						$args = array(
		   							'date' => array(
		   								'day'   => $day,
		   								'month' => $month,
		   								'year'  => $dates['year']
		   							),
		   							'fields' => 'amount'
									/*'status' => 'complete'*/
		   						);

		   						$args['date'] = array( 'day' => $day, 'month' => $month, 'year' => $dates['year'] );

								$payments = $payments_db->get_refunds( $args );
								$refunds += $payments;
								$date = mktime( 0, 0, 0, $month, $day, $dates['year'] ); ?>
								[<?php echo $date * 1000; ?>, <?php echo $payments; ?>],
								<?php
								$day++;
							endwhile;

						} else {

							$y = $dates['year'];
							while( $y <= $dates['year_end'] ) :

								if( $dates['year'] == $dates['year_end'] ) {
									$month_start = $dates['m_start'];
									$month_end   = $dates['m_end'];
								} elseif( $y == $dates['year'] ) {
									$month_start = $dates['m_start'];
									$month_end   = 12;
								} else {
									$month_start = 1;
									$month_end   = 12;
								}

								$i = $month_start;
								while ( $i <= $month_end ) :
									if ( $day_by_day ) :
										$num_of_days 	= cal_days_in_month( CAL_GREGORIAN, $i, $y );
										$d 				= 1;
										while ( $d <= $num_of_days ) :
											$args['date'] = array( 'day' => $d, 'month' => $i, 'year' => $y );
											$payments = $payments_db->get_refunds( $args );
											$refunds += $payments;
											$date = mktime( 0, 0, 0, $i, $d, $y ); ?>
											[<?php echo $date * 1000; ?>, <?php echo $payments; ?>],
										<?php
										$d++;
										endwhile;
									else :

										$args['date'] = array( 'day' => null, 'month' => $i, 'year' => $y );
										$payments = $payments_db->get_refunds( $args );
										$refunds += $payments;
										$date = mktime( 0, 0, 0, $i, 1, $y );
										?>
										[<?php echo $date * 1000; ?>, <?php echo $payments; ?>],
									<?php
									endif;
									$i++;
								endwhile;

								$y++;
							endwhile;

	   					}

	   					?>,
	   				],
	   				yaxis: 2,
   					label: "<?php _e( 'Refunds', 'rcp' ); ?>",
   					id: 'sales'
   				}],
	   		{
               	series: {
                   lines: { show: true },
                   points: { show: true }
            	},
            	grid: {
           			show: true,
					aboveData: false,
					color: '#ccc',
					backgroundColor: '#fff',
					borderWidth: 2,
					borderColor: '#ccc',
					clickable: false,
					hoverable: true
           		},
            	xaxis: {
	   				mode: "time",
	   				timeFormat: "<?php echo $time_format; ?>",
	   				minTickSize: [1, "<?php echo $tick_size; ?>"]
   				},
   				yaxis: {
   					min: 0,
   					minTickSize: 1,
   					tickDecimals: 0
   				}

            });

			function rcp_flot_tooltip(x, y, contents) {
		        $('<div id="rcp-flot-tooltip">' + contents + '</div>').css( {
		            position: 'absolute',
		            display: 'none',
		            top: y + 5,
		            left: x + 5,
		            border: '1px solid #fdd',
		            padding: '2px',
		            'background-color': '#fee',
		            opacity: 0.80
		        }).appendTo("body").fadeIn(200);
		    }

		    var previousPoint = null;
		    $("#rcp_refunds_graph").bind("plothover", function (event, pos, item) {
	            if (item) {
	                if (previousPoint != item.dataIndex) {
	                    previousPoint = item.dataIndex;
	                    $("#rcp-flot-tooltip").remove();
	                    var x = item.datapoint[0].toFixed(2),
                        y = item.datapoint[1].toFixed(2);
                    	if( rcp_vars.currency_pos == 'before' ) {
							rcp_flot_tooltip( item.pageX, item.pageY, item.series.label + ' ' + rcp_vars.currency_sign + y );
                    	} else {
							rcp_flot_tooltip( item.pageX, item.pageY, item.series.label + ' ' + y + rcp_vars.currency_sign );
                    	}
	                }
	            } else {
	                $("#rcp-flot-tooltip").remove();
	                previousPoint = null;
	            }
		    });
	   });
    </script>
	<h1><?php _e( 'Refunds Report', 'rcp' ); ?></h1>
	<div class="metabox-holder" style="padding-top: 0;">
		<div class="postbox">
			<div class="inside">
				<?php rcp_reports_graph_controls(); ?>
				<div id="rcp_refunds_graph" style="height: 300px;"></div>
				<p class="rcp_graph_totals"><strong><?php _e( 'Total refunds for period shown: ', 'rcp' ); echo rcp_currency_filter( $refunds ); ?></strong></p>
			</div>
		</div>
	</div>
	<?php
	echo ob_get_clean();
}
add_action( 'rcp_reports_tab_refunds', 'rcp_refunds_graph' );

/**
 * Displays the signups graph.
 *
 * @access  public
 * @since   1.8
 * @return  void
 */
function rcp_signups_graph() {
	global $rcp_options, $wpdb;

	// Retrieve the queried dates
	$dates = rcp_get_report_dates();

	// Determine graph options
	switch ( $dates['range'] ) :
		case 'today' :
			$time_format 	= '%d/%b';
			$tick_size		= 'hour';
			$day_by_day		= true;
			break;
		case 'last_year' :
			$time_format 	= '%b';
			$tick_size		= 'month';
			$day_by_day		= false;
			break;
		case 'this_year' :
			$time_format 	= '%b';
			$tick_size		= 'month';
			$day_by_day		= false;
			break;
		case 'last_quarter' :
			$time_format	= '%b';
			$tick_size		= 'month';
			$day_by_day 	= false;
			break;
		case 'this_quarter' :
			$time_format	= '%b';
			$tick_size		= 'month';
			$day_by_day 	= false;
			break;
		case 'other' :
			if( $dates['m_end'] - $dates['m_start'] >= 2 || $dates['year_end'] > $dates['year'] ) {
				$time_format	= '%b';
				$tick_size		= 'month';
				$day_by_day 	= false;
			} else {
				$time_format 	= '%d/%b';
				$tick_size		= 'day';
				$day_by_day 	= true;
			}
			break;
		default:
			$time_format 	= '%d/%b'; 	// Show days by default
			$tick_size		= 'day'; 	// Default graph interval
			$day_by_day 	= true;
			break;
	endswitch;

	$time_format 	= apply_filters( 'rcp_graph_timeformat', $time_format );
	$tick_size 		= apply_filters( 'rcp_graph_ticksize', $tick_size );
	$signups 		= 0; // Total signups for time period shown

	$payments_db = new RCP_Payments;

	ob_start(); ?>
	<script type="text/javascript">
	   jQuery( document ).ready( function($) {
			$.plot(
				$("#rcp_signups_graph"),
				[{
					data: [
						<?php

						if( $dates['range'] == 'this_week' || $dates['range'] == 'last_week'  ) {

							//Day by day
							$day     = $dates['day'];
							$day_end = $dates['day_end'];
							$month   = $dates['m_start'];

							while ( $day <= $day_end ) :

								$args = array(
									'date' => array(
										'day'   => $day,
										'month' => $month,
										'year'  => $dates['year']
									),
									'fields' => 'amount'
								);

								$users = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->users WHERE (%d = MONTH ( user_registered ) AND %d = YEAR ( user_registered ) AND %d = DAY ( user_registered ))", $month, $dates['year'], $day ) );
								$signups += $users;
								$date = mktime( 0, 0, 0, $month, $day, $dates['year'] ); ?>
								[<?php echo $date * 1000; ?>, <?php echo $users; ?>],
								<?php
								$day++;
							endwhile;

						} else {

							$y = $dates['year'];
							while( $y <= $dates['year_end'] ) :

								if( $dates['year'] == $dates['year_end'] ) {
									$month_start = $dates['m_start'];
									$month_end   = $dates['m_end'];
								} elseif( $y == $dates['year'] ) {
									$month_start = $dates['m_start'];
									$month_end   = 12;
								} elseif ( $y == $dates['year_end'] ) {
									$month_start = 1;
									$month_end   = $dates['m_end'];
								} else {
									$month_start = 1;
									$month_end   = 12;
								}

								$i = $month_start;
								while ( $i <= $month_end ) :
									if ( $day_by_day ) :
										$num_of_days 	= cal_days_in_month( CAL_GREGORIAN, $i, $y );
										$d 				= 1;
										while ( $d <= $num_of_days ) :
											$users = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->users WHERE (%d = MONTH ( user_registered ) AND %d = YEAR ( user_registered ) AND %d = DAY ( user_registered ))", $i, $y, $d ) );
											$signups += $users;
											$date = mktime( 0, 0, 0, $i, $d, $y ); ?>
											[<?php echo $date * 1000; ?>, <?php echo $users; ?>],
										<?php
										$d++;
										endwhile;
									else :
										$users = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(ID) FROM $wpdb->users WHERE (%d = MONTH ( user_registered ) AND %d = YEAR ( user_registered ) )", $i, $y ) );
										$signups += $users;
										$date = mktime( 0, 0, 0, $i, 1, $y );
										?>
										[<?php echo $date * 1000; ?>, <?php echo $users; ?>],
									<?php
									endif;
									$i++;
								endwhile;

								$y++;
							endwhile;
						}

						?>,
					],
					yaxis: 2,
					label: "<?php _e( 'Signups', 'rcp' ); ?>",
					id: 'sales'
				}],
			{
				series: {
				   lines: { show: true },
				   points: { show: true }
				},
				grid: {
					show: true,
					aboveData: false,
					color: '#ccc',
					backgroundColor: '#fff',
					borderWidth: 2,
					borderColor: '#ccc',
					clickable: false,
					hoverable: true
				},
				xaxis: {
					mode: "time",
					timeFormat: "<?php echo $time_format; ?>",
					minTickSize: [1, "<?php echo $tick_size; ?>"]
				},
				yaxis: [
					{ min: 0, tickSize: 1, tickDecimals: 2 },
					{ min: 0, tickDecimals: 0 }
				]

			});

			function rcp_flot_tooltip(x, y, contents) {
				$('<div id="rcp-flot-tooltip">' + contents + '</div>').css( {
					position: 'absolute',
					display: 'none',
					top: y + 5,
					left: x + 5,
					border: '1px solid #fdd',
					padding: '2px',
					'background-color': '#fee',
					opacity: 0.80
				}).appendTo("body").fadeIn(200);
			}

			var previousPoint = null;
			$("#rcp_signups_graph").bind("plothover", function (event, pos, item) {
				if (item) {
					if (previousPoint != item.dataIndex) {
						previousPoint = item.dataIndex;
						$("#rcp-flot-tooltip").remove();
						var x = item.datapoint[0].toFixed(2),
							y = item.datapoint[1].toFixed(2);
						rcp_flot_tooltip( item.pageX, item.pageY, item.series.label + ' ' + y.replace( '.00', '' ) );
					}
				} else {
					$("#rcp-flot-tooltip").remove();
					previousPoint = null;
				}
			});

	   });
	</script>
	<h1><?php _e( 'Signups Report', 'rcp' ); ?></h1>
	<div class="metabox-holder" style="padding-top: 0;">
		<div class="postbox">
			<div class="inside">
				<?php rcp_reports_graph_controls(); ?>
				<div id="rcp_signups_graph" style="height: 300px;"></div>
				<p class="rcp_graph_totals"><strong><?php _e( 'Total signups for period shown: ', 'rcp' ); echo $signups; ?></strong></p>
			</div>
		</div>
	</div>
	<?php
	echo ob_get_clean();
}
add_action( 'rcp_reports_tab_signups', 'rcp_signups_graph' );

/**
 * Displays the membership counts graph
 *
 * @since 3.3
 */
function rcp_membership_counts_graph() {

	?>
	<h1><?php _e( 'Membership Counts Report', 'rcp' ); ?></h1>
	<div class="metabox-holder" style="padding-top: 0;">
		<div class="postbox">
			<div class="inside">
				<?php rcp_reports_graph_controls(); ?>
				<div id="rcp-membership-counts-graph">
					<canvas id="rcp-membership-counts-graph-canvas"></canvas>
				</div>
			</div>
		</div>
	</div>
	<?php

}
add_action( 'rcp_reports_tab_membership_counts', 'rcp_membership_counts_graph' );

/**
 * Show report graph date filters
 *
 * @since 1.8
 * @return void
 */
function rcp_reports_graph_controls() {
	$date_options = apply_filters( 'rcp_report_date_options', array(
		'this_week' 	=> __( 'This Week', 'rcp' ),
		'last_week' 	=> __( 'Last Week', 'rcp' ),
		'this_month' 	=> __( 'This Month', 'rcp' ),
		'last_month' 	=> __( 'Last Month', 'rcp' ),
		'this_quarter'	=> __( 'This Quarter', 'rcp' ),
		'last_quarter'	=> __( 'Last Quarter', 'rcp' ),
		'this_year'		=> __( 'This Year', 'rcp' ),
		'last_year'		=> __( 'Last Year', 'rcp' ),
		'other'			=> __( 'Custom', 'rcp' )
	) );

	$dates           = rcp_get_report_dates();
	$display         = $dates['range'] == 'other' ? '' : 'style="display:none;"';
	$active_tab      = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'earnings';
	$selected_level  = isset( $_GET['subscription'] ) ? absint( $_GET['subscription'] ) : false;
	$selected_status = isset( $_GET['membership_status'] ) ? wp_strip_all_tags( $_GET['membership_status'] ) : '';
	?>
	<form id="rcp-graphs-filter" method="get">
		<div class="tablenav top">
			<div class="alignleft actions">

				<input type="hidden" name="page" value="rcp-reports"/>

				<select id="rcp-graphs-date-options" name="range">
					<?php
					foreach ( $date_options as $key => $option ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $dates['range'] ) . '>' . esc_html( $option ) . '</option>';
					}
					?>
				</select>

				<div id="rcp-date-range-options" <?php echo $display; ?>>
					<span><?php _e( 'From', 'rcp' ); ?>&nbsp;</span>
					<select id="rcp-graphs-month-start" name="m_start">
						<?php for ( $i = 1; $i <= 12; $i++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['m_start'] ); ?>><?php echo rcp_get_month_name( $i ); ?></option>
						<?php endfor; ?>
					</select>
					<select id="rcp-graphs-year-start" name="year">
						<?php for ( $i = 2007; $i <= date( 'Y' ); $i++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['year'] ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
					<span><?php _e( 'To', 'rcp' ); ?>&nbsp;</span>
					<select id="rcp-graphs-month-end" name="m_end">
						<?php for ( $i = 1; $i <= 12; $i++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['m_end'] ); ?>><?php echo rcp_get_month_name( $i ); ?></option>
						<?php endfor; ?>
					</select>
					<select id="rcp-graphs-year-end" name="year_end">
						<?php for ( $i = 2007; $i <= date( 'Y' ); $i++ ) : ?>
							<option value="<?php echo absint( $i ); ?>" <?php selected( $i, $dates['year_end'] ); ?>><?php echo $i; ?></option>
						<?php endfor; ?>
					</select>
				</div>

				<?php if( in_array( $active_tab, array( 'earnings', 'membership_counts' ) ) ) : $levels = rcp_get_membership_levels( array( 'number' => 999 ) ); ?>
					<select id="rcp-graphs-subscriptions" name="subscription">
						<option value="0"><?php _e( 'All Membership Levels', 'rcp' ); ?></option>
						<?php foreach( $levels as $level ) : ?>
							<option value="<?php echo esc_attr( $level->get_id() ); ?>"<?php selected( $selected_level, $level->get_id() ); ?>><?php echo esc_html( $level->get_name() ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>

				<?php if( in_array( $active_tab, array( 'membership_counts' ) ) ) :
					$statuses = array( 'active', 'expired', 'cancelled', 'pending' ); ?>
					<label for="rcp-graphs-membership-status" class="screen-reader-text"><?php _e( 'Filter by membership status', 'rcp' ); ?></label>
					<select id="rcp-graphs-membership-status" name="membership_status">
						<option value=""><?php _e( 'All Statuses', 'rcp' ); ?></option>
						<?php foreach( $statuses as $status ) : ?>
							<option value="<?php echo esc_attr( $status ); ?>"<?php selected( $selected_status, $status ); ?>><?php echo esc_html( rcp_get_status_label( $status ) ); ?></option>
						<?php endforeach; ?>
					</select>
				<?php endif; ?>

				<input type="hidden" name="rcp_action" value="" />
				<input type="hidden" name="tab" value="<?php echo $active_tab ?>" />
				<input type="submit" class="button-secondary" value="<?php _e( 'Filter', 'rcp' ); ?>"/>
			</div>
		</div>
	</form>
	<?php
}

/**
 * Sets up the dates used to filter graph data
 *
 * Date sent via $_GET is read first and then modified (if needed) to match the
 * selected date-range (if any)
 *
 * @since 1.8
 * @return array
 */
function rcp_get_report_dates( $args = array() ) {

	$dates = array();

	// Make sure the reports are based off of the correct timezone
	date_default_timezone_set( rcp_get_timezone_id() );

	$current_time = current_time( 'timestamp' );

	$dates['range']      = isset( $_REQUEST['range'] )   ? $_REQUEST['range']   : 'this_month';
	$dates['year']       = isset( $_REQUEST['year'] )    ? $_REQUEST['year']    : date( 'Y' );
	$dates['year_end']   = isset( $_REQUEST['year_end'] )? $_REQUEST['year_end']: date( 'Y' );
	$dates['m_start']    = isset( $_REQUEST['m_start'] ) ? $_REQUEST['m_start'] : 1;
	$dates['m_end']      = isset( $_REQUEST['m_end'] )   ? $_REQUEST['m_end']   : 12;
	$dates['day']        = isset( $_REQUEST['day'] )     ? $_REQUEST['day']     : 1;
	$dates['day_end']    = isset( $_REQUEST['day_end'] ) ? $_REQUEST['day_end'] : cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );

	$dates = wp_parse_args( $args, $dates );

	// Modify dates based on predefined ranges
	switch ( $dates['range'] ) :

		case 'this_month' :
			$dates['m_start']  = date( 'n', $current_time );
			$dates['m_end']    = date( 'n', $current_time );
			$dates['day']      = 1;
			$dates['day_end']  = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
			$dates['year']     = date( 'Y' );
			$dates['year_end'] = date( 'Y' );
		break;

		case 'last_month' :
			if( date( 'n' ) == 1 ) {
				$dates['m_start']  = 12;
				$dates['m_end']    = 12;
				$dates['year']     = date( 'Y', $current_time ) - 1;
				$dates['year_end'] = date( 'Y', $current_time ) - 1;
			} else {
				$dates['m_start']  = date( 'n' ) - 1;
				$dates['m_end']    = date( 'n' ) - 1;
				$dates['year_end'] = $dates['year'];
			}
			$dates['day_end'] = cal_days_in_month( CAL_GREGORIAN, $dates['m_end'], $dates['year'] );
		break;

		case 'today' :
			$dates['day']     = date( 'd', $current_time );
			$dates['m_start'] = date( 'n', $current_time );
			$dates['m_end']   = date( 'n', $current_time );
			$dates['year']    = date( 'Y', $current_time );
		break;

		case 'yesterday' :

			$year  = date( 'Y', $current_time );
			$month = date( 'n', $current_time );
			$day   = date( 'd', $current_time );

			if ( $month == 1 && $day == 1 ) {

				$year  -= 1;
				$month = 12;
				$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );

			} elseif ( $month > 1 && $day == 1 ) {

				$month -= 1;
				$day   = cal_days_in_month( CAL_GREGORIAN, $month, $year );

			} else {

				$day -= 1;

			}

			$dates['day']       = $day;
			$dates['m_start']   = $month;
			$dates['m_end']     = $month;
			$dates['year']      = $year;
			$dates['year_end']  = $year;
		break;

		case 'this_week' :
		case 'last_week' :
			$base_time = $dates['range'] === 'this_week' ? current_time( 'mysql' ) : date( 'Y-m-d h:i:s', current_time( 'timestamp' ) - WEEK_IN_SECONDS );
			$start_end = get_weekstartend( $base_time, get_option( 'start_of_week' ) );

			$dates['day']      = date( 'd', $start_end['start'] );
			$dates['m_start']  = date( 'n', $start_end['start'] );
			$dates['year']     = date( 'Y', $start_end['start'] );

			$dates['day_end']  = date( 'd', $start_end['end'] );
			$dates['m_end']    = date( 'n', $start_end['end'] );
			$dates['year_end'] = date( 'Y', $start_end['end'] );
		break;

		case 'this_quarter' :
			$month_now = date( 'n', $current_time );

			if ( $month_now <= 3 ) {

				$dates['m_start'] = 1;
				$dates['m_end']   = 4;
				$dates['year']    = date( 'Y', $current_time );

			} else if ( $month_now <= 6 ) {

				$dates['m_start'] = 4;
				$dates['m_end']   = 7;
				$dates['year']    = date( 'Y', $current_time );

			} else if ( $month_now <= 9 ) {

				$dates['m_start'] = 7;
				$dates['m_end']   = 10;
				$dates['year']    = date( 'Y', $current_time );

			} else {

				$dates['m_start']  = 10;
				$dates['m_end']    = 1;
				$dates['year']     = date( 'Y', $current_time );
				$dates['year_end'] = date( 'Y', $current_time ) + 1;

			}
		break;

		case 'last_quarter' :
			$month_now = date( 'n' );

			if ( $month_now <= 3 ) {

				$dates['m_start']  = 10;
				$dates['m_end']    = 12;
				$dates['year']     = date( 'Y', $current_time ) - 1; // Previous year
				$dates['year_end'] = date( 'Y', $current_time ) - 1; // Previous year

			} else if ( $month_now <= 6 ) {

				$dates['m_start'] = 1;
				$dates['m_end']   = 3;
				$dates['year']    = date( 'Y', $current_time );

			} else if ( $month_now <= 9 ) {

				$dates['m_start'] = 4;
				$dates['m_end']   = 6;
				$dates['year']    = date( 'Y', $current_time );

			} else {

				$dates['m_start'] = 7;
				$dates['m_end']   = 9;
				$dates['year']    = date( 'Y', $current_time );

			}
		break;

		case 'this_year' :
			$dates['m_start']  = 1;
			$dates['m_end']    = 12;
			$dates['year']     = date( 'Y', $current_time );
			$dates['year_end'] = date( 'Y', $current_time );
		break;

		case 'last_year' :
			$dates['m_start']  = 1;
			$dates['m_end']    = 12;
			$dates['year']     = date( 'Y', $current_time ) - 1;
			$dates['year_end'] = date( 'Y', $current_time ) - 1;
		break;

	endswitch;

	return apply_filters( 'rcp_report_dates', $dates );
}

/**
 * Grabs all of the selected date info and then redirects appropriately
 *
 * @since 1.8
 * @return void
 */
function rcp_parse_report_dates() {

	if( ! isset( $_GET['rcp_action'] ) )
		return;

	if( 'filter_reports' != $_GET['rcp_action'] )
		return;

	$dates = rcp_get_report_dates();

	wp_safe_redirect( add_query_arg( array_map( 'urlencode', $dates ), admin_url( 'admin.php?page=rcp-reports' ) ) ); exit;
}
add_action( 'admin_init', 'rcp_parse_report_dates' );

/**
 * Get an array of dates to use in report queries based on the selected date range.
 *
 * @param array $args
 *
 * @since 3.3
 * @return array()
 */
function rcp_get_graph_dates_by_range() {

	$dates = array();

	$date_parameters = rcp_get_report_dates();

	/**
	 * If `$daily` is false, then monthly interval is used.
	 */
	$daily = false;

	$daily_ranges = array( 'today', 'this_week', 'last_week', 'this_month', 'last_month' );

	// Determine if we want daily or monthly increments.
	if ( in_array( $date_parameters['range'], $daily_ranges ) ) {
		$daily = true;
	} elseif ( 'other' === $date_parameters['range'] ) {
		if ( $date_parameters['m_end'] - $date_parameters['m_start'] < 2 && $date_parameters['year_end'] <= $date_parameters['year'] ) {
			$daily = true;
		}
	}

	$interval = $daily ? 'P1D' : 'P1M';
	$interval = 'P1D';

	try {
		$start_date = new DateTime( sprintf( '%d-%d-%d', $date_parameters['year'], $date_parameters['m_start'], $date_parameters['day'] ) );
		$end_date   = new DateTime( sprintf( '%d-%d-%d', $date_parameters['year_end'], $date_parameters['m_end'], $date_parameters['day_end'] ) );

		// Include the last date.
		$end_date->modify( '+1 day' );

		$period = new DatePeriod( $start_date, new DateInterval( $interval ), $end_date );

		foreach ( $period as $datetime ) {
			$dates[] = $datetime->format( 'Y-m-d' );
		}
	} catch ( \Exception $e ) {

	}

	return $dates;

}
