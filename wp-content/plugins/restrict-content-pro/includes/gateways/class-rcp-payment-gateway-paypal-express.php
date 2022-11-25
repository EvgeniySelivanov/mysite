<?php

use RCP\Membership_Level;

/**
 * PayPal Express Gateway class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Gateways/PayPal Express
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_PayPal_Express extends RCP_Payment_Gateway {

	private $api_endpoint;
	private $checkout_url;
	protected $username;
	protected $password;
	protected $signature;

	/**
	 * Get things going
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function init() {

		global $rcp_options;

		$this->supports[]  = 'one-time';
		$this->supports[]  = 'recurring';
		$this->supports[]  = 'fees';
		$this->supports[]  = 'trial';
		$this->supports[]  = 'expiration-extension-on-renewals'; // @link https://github.com/restrictcontentpro/restrict-content-pro/issues/1259

		if( $this->test_mode ) {

			$this->api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
			$this->checkout_url = 'https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=';

		} else {

			$this->api_endpoint = 'https://api-3t.paypal.com/nvp';
			$this->checkout_url = 'https://www.paypal.com/webscr&cmd=_express-checkout&token=';

		}

		if( rcp_has_paypal_api_access() ) {

			$creds = rcp_get_paypal_api_credentials();

			$this->username  = $creds['username'];
			$this->password  = $creds['password'];
			$this->signature = $creds['signature'];

		}

	}

	/**
	 * Process registration
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function process_signup() {

		global $rcp_options;

		if( $this->auto_renew ) {
			$amount = $this->amount;
		} else {
			$amount = $this->initial_amount;
		}

		$cancel_url = wp_get_referer();
		if ( empty( $cancel_url ) ) {
			$cancel_url = get_permalink( $rcp_options['registration_page'] );
		}

		$args = array(
			'USER'                           => $this->username,
			'PWD'                            => $this->password,
			'SIGNATURE'                      => $this->signature,
			'VERSION'                        => '124',
			'METHOD'                         => 'SetExpressCheckout',
			'PAYMENTREQUEST_0_AMT'           => $amount,
			'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
			'PAYMENTREQUEST_0_CURRENCYCODE'  => strtoupper( $this->currency ),
			'PAYMENTREQUEST_0_ITEMAMT'       => $amount,
			'PAYMENTREQUEST_0_SHIPPINGAMT'   => 0,
			'PAYMENTREQUEST_0_TAXAMT'        => 0,
			'PAYMENTREQUEST_0_DESC'          => html_entity_decode( substr( $this->subscription_name, 0, 127 ), ENT_COMPAT, 'UTF-8' ),
			'PAYMENTREQUEST_0_CUSTOM'        => $this->user_id . '|' . absint( $this->membership->get_id() ),
			'PAYMENTREQUEST_0_NOTIFYURL'     => add_query_arg( 'listener', 'EIPN', home_url( 'index.php' ) ),
			'EMAIL'                          => $this->email,
			'RETURNURL'                      => add_query_arg( array( 'rcp-confirm' => 'paypal_express', 'membership_id' => urlencode( $this->membership->get_id() ) ), get_permalink( $rcp_options['registration_page'] ) ),
			'CANCELURL'                      => $cancel_url,
			'REQCONFIRMSHIPPING'             => 0,
			'NOSHIPPING'                     => 1,
			'ALLOWNOTE'                      => 0,
			'ADDROVERRIDE'                   => 0,
			'PAGESTYLE'                      => ! empty( $rcp_options['paypal_page_style'] ) ? trim( $rcp_options['paypal_page_style'] ) : '',
			'SOLUTIONTYPE'                   => 'Sole',
			'LANDINGPAGE'                    => 'Billing',
		);

		if( $this->auto_renew && ! empty( $this->length ) ) {
			$args['L_BILLINGAGREEMENTDESCRIPTION0'] = html_entity_decode( substr( $this->subscription_name, 0, 127 ), ENT_COMPAT, 'UTF-8' );
			$args['L_BILLINGTYPE0']                 = 'RecurringPayments';
			$args['RETURNURL']                      = add_query_arg( array( 'rcp-recurring' => '1' ), $args['RETURNURL'] );
		}

		$request = wp_remote_post( $this->api_endpoint, array(
			'timeout' => 45,
			'httpversion' => '1.1',
			'body' => $args
		) );
		$body    = wp_remote_retrieve_body( $request );
		$code    = wp_remote_retrieve_response_code( $request );
		$message = wp_remote_retrieve_response_message( $request );

		if( is_wp_error( $request ) ) {

			$this->error_message = $request->get_error_message();
			do_action( 'rcp_registration_failed', $this );
			do_action( 'rcp_paypal_express_signup_payment_failed', $request, $this );

			$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
			$error .= '<p>' . $request->get_error_message() . '</p>';

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

		} elseif ( 200 == $code && 'OK' == $message ) {

			if( is_string( $body ) ) {
				wp_parse_str( $body, $body );
			}

			if( 'failure' === strtolower( $body['ACK'] ) ) {

				$this->error_message = $body['L_LONGMESSAGE0'];
				do_action( 'rcp_registration_failed', $this );

				$error = '<p>' . __( 'PayPal token creation failed.', 'rcp' ) . '</p>';
				$error .= '<p>' . __( 'Error message:', 'rcp' ) . ' ' . $body['L_LONGMESSAGE0'] . '</p>';
				$error .= '<p>' . __( 'Error code:', 'rcp' ) . ' ' . $body['L_ERRORCODE0'] . '</p>';

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

			} else {

				// Successful token
				wp_redirect( $this->checkout_url . $body['TOKEN'] );
				exit;

			}

		} else {

			do_action( 'rcp_registration_failed', $this );
			wp_die( __( 'Something has gone wrong, please try again', 'rcp' ), __( 'Error', 'rcp' ), array( 'back_link' => true, 'response' => '401' ) );

		}

	}

	/**
	 * Validate additional fields during registration submission
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function validate_fields() {

		if( ! rcp_has_paypal_api_access() ) {
			rcp_errors()->add( 'no_paypal_api', __( 'You have not configured PayPal API access. Please configure it in Restrict &rarr; Settings', 'rcp' ), 'register' );
		}

	}

	/**
	 * Process payment confirmation after returning from PayPal
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function process_confirmation() {

		if ( isset( $_POST['rcp_ppe_confirm_nonce'] ) && wp_verify_nonce( $_POST['rcp_ppe_confirm_nonce'], 'rcp-ppe-confirm-nonce' ) ) {

			$details    = $this->get_checkout_details( $_POST['token'] );
			$membership = rcp_get_membership( absint( $details['membership_id'] ) );

			/**
			 * Always process a one-time payment if the initial amount is > 0.
			 */
			if ( $details['initial_amount'] > 0 ) {

				$args = array(
					'USER'                           => $this->username,
					'PWD'                            => $this->password,
					'SIGNATURE'                      => $this->signature,
					'VERSION'                        => '124',
					'METHOD'                         => 'DoExpressCheckoutPayment',
					'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',
					'TOKEN'                          => $_POST['token'],
					'PAYERID'                        => $_POST['payer_id'],
					'PAYMENTREQUEST_0_AMT'           => $details['initial_amount'],
					'PAYMENTREQUEST_0_ITEMAMT'       => $details['initial_amount'],
					'PAYMENTREQUEST_0_SHIPPINGAMT'   => 0,
					'PAYMENTREQUEST_0_TAXAMT'        => 0,
					'PAYMENTREQUEST_0_CURRENCYCODE'  => $details['CURRENCYCODE'],
					'BUTTONSOURCE'                   => 'EasyDigitalDownloads_SP'
				);

				$request = wp_remote_post( $this->api_endpoint, array(
					'timeout' => 45,
					'httpversion' => '1.1',
					'body' => $args
				) );
				$body    = wp_remote_retrieve_body( $request );
				$code    = wp_remote_retrieve_response_code( $request );
				$message = wp_remote_retrieve_response_message( $request );

				try {

					if ( is_wp_error( $request ) ) {
						$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
						$error .= '<p>' . $request->get_error_message() . '</p>';

						throw new Exception( $error );
					}

					if ( 200 != $code || 'OK' != $message ) {
						throw new Exception( __( 'Something has gone wrong, please try again', 'rcp' ) );
					}

					if ( is_string( $body ) ) {
						wp_parse_str( $body, $body );
					}

					if ( 'failure' === strtolower( $body['ACK'] ) ) {
						$error = '<p>' . __( 'PayPal payment processing failed.', 'rcp' ) . '</p>';
						$error .= '<p>' . __( 'Error message:', 'rcp' ) . ' ' . $body['L_LONGMESSAGE0'] . '</p>';
						$error .= '<p>' . __( 'Error code:', 'rcp' ) . ' ' . $body['L_ERRORCODE0'] . '</p>';

						throw new Exception( $error );
					}

					// At this point we know we're successful!
					$payment_data = array(
						'date'             => date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ),
						'subscription'     => $membership->get_membership_level_name(),
						'payment_type'     => 'PayPal Express One Time',
						'subscription_key' => $membership->get_subscription_key(),
						'amount'           => $body['PAYMENTINFO_0_AMT'],
						'user_id'          => $membership->get_user_id(),
						'transaction_id'   => $body['PAYMENTINFO_0_TRANSACTIONID'],
						'status'           => 'complete'
					);

					$rcp_payments = new RCP_Payments;

					$pending_payment_id = rcp_get_membership_meta( $membership->get_id(), 'pending_payment_id', true );

					if ( ! empty( $pending_payment_id ) ) {
						$rcp_payments->update( $pending_payment_id, $payment_data );
					}

					// Membership is activated via rcp_complete_registration()

					// If we're non-recurring, bail now.
					if ( empty( $_GET['rcp-recurring'] ) ) {
						wp_redirect( esc_url_raw( rcp_get_return_url() ) );
						exit;
					}

				} catch ( Exception $e ) {
					wp_die( $e->getMessage(), __( 'Error', 'rcp' ), array( 'response' => '401' ) );
				}

			}


			/**
			 * Successful initial payment, now create the recurring profile
			 */

			// Fetch new membership object to refresh expiration date.
			$this->membership = rcp_get_membership( $membership->get_id() );

			// Get expiration date to use as subscription start date.
			$base_date = $this->membership->get_expiration_date( false );
			$timezone = get_option( 'timezone_string' );
			$timezone = ! empty( $timezone ) ? $timezone : 'UTC';
			$datetime = new DateTime( $base_date, new DateTimeZone( $timezone ) );
			$current_time = getdate();
			$datetime->setTime( $current_time['hours'], $current_time['minutes'], $current_time['seconds'] );

			$args = array(
				'USER'                => $this->username,
				'PWD'                 => $this->password,
				'SIGNATURE'           => $this->signature,
				'VERSION'             => '124',
				'TOKEN'               => $_POST['token'],
				'METHOD'              => 'CreateRecurringPaymentsProfile',
				'PROFILESTARTDATE'    => date( 'Y-m-d\TH:i:s', $datetime->getTimestamp() ),
				'BILLINGPERIOD'       => ucwords( $details['subscription']['duration_unit'] ),
				'BILLINGFREQUENCY'    => $details['subscription']['duration'],
				'AMT'                 => $details['AMT'],
				'CURRENCYCODE'        => $details['CURRENCYCODE'],
				'FAILEDINITAMTACTION' => 'CancelOnFailure',
				'L_BILLINGTYPE0'      => 'RecurringPayments',
				'DESC'                => html_entity_decode( substr( $details['subscription']['name'], 0, 127 ), ENT_COMPAT, 'UTF-8' ),
				'BUTTONSOURCE'        => 'EasyDigitalDownloads_SP'
			);

			$request = wp_remote_post( $this->api_endpoint, array(
				'timeout' => 45,
				'httpversion' => '1.1',
				'body' => $args
			) );
			$body    = wp_remote_retrieve_body( $request );
			$code    = wp_remote_retrieve_response_code( $request );
			$message = wp_remote_retrieve_response_message( $request );

			try {

				if ( is_wp_error( $request ) ) {
					$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
					$error .= '<p>' . $request->get_error_message() . '</p>';

					throw new Exception( $error );
				}

				if ( 200 != $code || 'OK' != $message ) {
					throw new Exception( __( 'Something has gone wrong, please try again', 'rcp' ) );
				}

				if ( is_string( $body ) ) {
					wp_parse_str( $body, $body );
				}

				if ( 'failure' === strtolower( $body['ACK'] ) ) {
					$error = '<p>' . __( 'PayPal payment processing failed.', 'rcp' ) . '</p>';
					$error .= '<p>' . __( 'Error message:', 'rcp' ) . ' ' . $body['L_LONGMESSAGE0'] . '</p>';
					$error .= '<p>' . __( 'Error code:', 'rcp' ) . ' ' . $body['L_ERRORCODE0'] . '</p>';

					throw new Exception( $error );
				}

				if ( empty( $body['PROFILEID'] ) ) {
					$error = '<p>' . __( 'PayPal payment processing failed.', 'rcp' ) . '</p>';
					$error .= '<p>' . __( 'Error message: Unable to retrieve subscription ID', 'rcp' ) . '</p>';

					throw new Exception( $error );
				}

				// At this point, we know it was successful!
				$this->membership->set_gateway_subscription_id( $body['PROFILEID'] );

			} catch ( Exception $e ) {

				// Only show the customer an error if the initial amount was 0.
				if ( empty( $details['initial_amount'] ) ) {
					wp_die( $e->getMessage(), __( 'Error', 'rcp' ), array( 'response' => '401' ) );
				} else {
					// Initial payment was successful, it's just the subscription that failed, so let's unset auto renew.
					$this->membership->set_recurring( false );
					$this->membership->add_note( sprintf( __( 'PayPal Standard Gateway: An error occurred while creating the subscription: %s', 'rcp'), wp_strip_all_tags( $e->getMessage() ) ) );
				}

			}

			wp_redirect( esc_url_raw( rcp_get_return_url() ) ); exit;

		} elseif ( ! empty( $_GET['token'] ) && ! empty( $_GET['PayerID'] ) ) {

			/**
			 * Show confirmation page.
			 */

			add_filter( 'the_content', array( $this, 'confirmation_form' ), 9999999 );

		}

	}

	/**
	 * Display the confirmation form
	 *
	 * @since 2.1
	 * @return string
	 */
	public function confirmation_form() {

		global $rcp_checkout_details;

		$token                = sanitize_text_field( $_GET['token'] );
		$rcp_checkout_details = $this->get_checkout_details( $token );

		if ( ! is_array( $rcp_checkout_details ) ) {
			$error = is_wp_error( $rcp_checkout_details ) ? $rcp_checkout_details->get_error_message() : __( 'Invalid response code from PayPal', 'rcp' );
			return '<p>' . sprintf( __( 'An unexpected PayPal error occurred. Error message: %s.', 'rcp' ), $error ) . '</p>';
		}

		ob_start();
		rcp_get_template_part( 'paypal-express-confirm' );
		return ob_get_clean();
	}

	/**
	 * Process PayPal IPN
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function process_webhooks() {

		if( ! isset( $_GET['listener'] ) || strtoupper( $_GET['listener'] ) != 'EIPN' ) {
			return;
		}

		rcp_log( 'Starting to process PayPal Express IPN.' );

		$user_id    = 0;
		$posted     = apply_filters('rcp_ipn_post', $_POST ); // allow $_POST to be modified
		$membership = false;
		$custom     = ! empty( $posted['custom'] ) ? explode( '|', $posted['custom'] ) : false;

		if( ! empty( $posted['recurring_payment_id'] ) ) {
			$membership = rcp_get_membership_by( 'gateway_subscription_id', $posted['recurring_payment_id'] );
		}

		if( empty( $membership ) && ! empty( $custom[1] ) ) {
			$membership = rcp_get_membership( absint( $custom[1] ) );
		}

		if( empty( $membership ) || ! $membership->get_id() > 0 ) {
			rcp_log( 'Exiting PayPal Express IPN - membership ID not found.', true );

			die( 'no membership found' );
		}

		$this->membership = $membership;

		rcp_log( sprintf( 'Processing IPN for membership #%d.', $membership->get_id() ) );

		if ( empty( $user_id ) ) {
			$user_id = $membership->get_user_id();
		}

		$member = new RCP_Member( $membership->get_user_id() ); // for backwards compatibility

		$membership_level_id = $membership->get_object_id();

		if( ! $membership_level_id ) {
			rcp_log( 'Exiting PayPal Express IPN - no membership level ID.', true );

			die( 'no membership level found' );
		}

		$membership_level = rcp_get_membership_level( $membership_level_id );

		if ( ! $membership_level instanceof Membership_Level ) {
			rcp_log( 'Exiting PayPal Express IPN - no membership level found.', true );

			die( 'no membership level found' );
		}

		$amount = isset( $posted['mc_gross'] ) ? number_format( (float) $posted['mc_gross'], 2, '.', '' ) : false;

		$membership_gateway = $membership->get_gateway();

		// setup the payment info in an array for storage
		$payment_data = array(
			'subscription'     => $membership_level->get_name(),
			'payment_type'     => $posted['txn_type'],
			'subscription_key' => $membership->get_subscription_key(),
			'user_id'          => $user_id,
			'customer_id'      => $membership->get_customer()->get_id(),
			'membership_id'    => $membership->get_id(),
			'status'           => 'complete',
			'gateway'          => ! empty( $membership_gateway ) && 'paypal_pro' == $membership_gateway ? 'paypal_pro' : 'paypal_express'
		);

		if ( false !== $amount ) {
			$payment_data['amount'] = $amount;
		}

		if ( ! empty( $posted['payment_date'] ) ) {
			$payment_data['date'] = date( 'Y-m-d H:i:s', strtotime( $posted['payment_date'] ) );
		}

		if ( ! empty( $posted['txn_id'] ) ) {
			$payment_data['transaction_id'] = sanitize_text_field( $posted['txn_id'] );
		}

		do_action( 'rcp_valid_ipn', $payment_data, $user_id, $posted );

		/* now process the kind of subscription/payment */

		$rcp_payments       = new RCP_Payments();
		$pending_payment_id = rcp_get_membership_meta( $membership->get_id(), 'pending_payment_id', true );

		// Subscriptions
		switch ( $posted['txn_type'] ) :

			case "recurring_payment_profile_created":

				rcp_log( 'Processing PayPal Express recurring_payment_profile_created IPN.' );

				if ( isset( $posted['initial_payment_txn_id'] ) ) {
					$transaction_id = ( 'Completed' == $posted['initial_payment_status'] ) ? $posted['initial_payment_txn_id'] : '';
				} else {
					$transaction_id = $posted['ipn_track_id'];
				}

				if ( empty( $transaction_id ) || $rcp_payments->payment_exists( $transaction_id ) ) {
					rcp_log( sprintf( 'Breaking out of PayPal Express IPN recurring_payment_profile_created. Transaction ID not given or payment already exists. TXN ID: %s', $transaction_id ), true );

					break;
				}

				// setup the payment info in an array for storage
				$payment_data['date']           = date( 'Y-m-d H:i:s', strtotime( $posted['time_created'] ) );
				$payment_data['amount']         = number_format( (float) $posted['initial_payment_amount'], 2, '.', '' );
				$payment_data['transaction_id'] = sanitize_text_field( $transaction_id );

				if ( ! empty( $pending_payment_id ) ) {

					$payment_id = $pending_payment_id;

					// This activates the membership.
					$rcp_payments->update( $pending_payment_id, $payment_data );

				} elseif( floatval( $payment_data['amount'] ) > 0 ) {

					$payment_data['subtotal'] = $payment_data['amount'];

					$payment_id = $rcp_payments->insert( $payment_data );

					$expiration = date( 'Y-m-d 23:59:59', strtotime( $posted['next_payment_date'] ) );
					$membership->renew( $membership->is_recurring(), 'active', $expiration );

				}

				do_action( 'rcp_webhook_recurring_payment_profile_created', $member, $this );

				if ( isset( $payment_id ) ) {
					do_action( 'rcp_gateway_payment_processed', $member, $payment_id, $this );
				}

				break;
			case "recurring_payment" :

				rcp_log( 'Processing PayPal Express recurring_payment IPN.' );

				// when a user makes a recurring payment
				update_user_meta( $user_id, 'rcp_paypal_subscriber', $posted['payer_id'] );

				$membership->set_gateway_subscription_id( $posted['recurring_payment_id'] );

				if ( 'failed' == strtolower( $posted['payment_status'] ) ) {

					// Recurring payment failed.
					$membership->add_note( sprintf( __( 'Transaction ID %s failed in PayPal.', 'rcp' ), $posted['txn_id'] ) );

					die( 'Subscription payment failed' );

				} elseif ( 'pending' == strtolower( $posted['payment_status'] ) ) {

					// Recurring payment pending (such as echeck).
					$pending_reason = ! empty( $posted['pending_reason'] ) ? $posted['pending_reason'] : __( 'unknown', 'rcp' );
					$membership->add_note( sprintf( __( 'Transaction ID %s is pending in PayPal for reason: %s', 'rcp' ), $posted['txn_id'], $pending_reason ) );

					die( 'Subscription payment pending' );

				}

				// Recurring payment succeeded.

				$membership->renew( true );

				$payment_data['transaction_type'] = 'renewal';

				// record this payment in the database
				$payment_id = $rcp_payments->insert( $payment_data );

				do_action( 'rcp_ipn_subscr_payment', $user_id );
				do_action( 'rcp_webhook_recurring_payment_processed', $member, $payment_id, $this );
				do_action( 'rcp_gateway_payment_processed', $member, $payment_id, $this );

				die( 'successful recurring_payment' );

				break;

			case "recurring_payment_profile_cancel" :

				rcp_log( 'Processing PayPal Express recurring_payment_profile_cancel IPN.' );

				if( ! $member->just_upgraded() ) {

					if( isset( $posted['initial_payment_status'] ) && 'Failed' == $posted['initial_payment_status'] ) {
						// Initial payment failed, so set the user back to pending.
						$membership->set_status( 'pending' );
						$membership->add_note( __( 'Initial payment failed in PayPal Express.', 'rcp' ) );

						$this->error_message = __( 'Initial payment failed.', 'rcp' );
						do_action( 'rcp_registration_failed', $this );
						do_action( 'rcp_paypal_express_initial_payment_failed', $member, $posted, $this );
					} else {
						// If this is a completed payment plan, we can skip any cancellation actions. This is handled in renewals.
						if ( $membership->has_payment_plan() && $membership->at_maximum_renewals() ) {
							rcp_log( sprintf( 'Membership #%d has completed its payment plan - not cancelling.', $membership->get_id() ) );
							die( 'membership payment plan completed' );
						}

						// user is marked as cancelled but retains access until end of term
						$membership->cancel();
						$membership->add_note( __( 'Membership cancelled via PayPal Express IPN.', 'rcp' ) );

						// set the use to no longer be recurring
						delete_user_meta( $user_id, 'rcp_paypal_subscriber' );

						do_action( 'rcp_ipn_subscr_cancel', $user_id );
						do_action( 'rcp_webhook_cancel', $member, $this );
					}

					die( 'successful recurring_payment_profile_cancel' );

				}

				break;

			case "recurring_payment_failed" :
			case "recurring_payment_suspended_due_to_max_failed_payment" :

			rcp_log( 'Processing PayPal Express recurring_payment_failed or recurring_payment_suspended_due_to_max_failed_payment IPN.' );

				if( ! in_array( $membership->get_status(), array( 'cancelled', 'expired' ) ) ) {

					$membership->set_status( 'expired' );

				}

				if ( ! empty( $posted['txn_id'] ) ) {

					$this->webhook_event_id = sanitize_text_field( $posted['txn_id'] );

				} elseif ( ! empty( $posted['ipn_track_id'] ) ) {

					$this->webhook_event_id = sanitize_text_field( $posted['ipn_track_id'] );
				}

				do_action( 'rcp_ipn_subscr_failed' );

				do_action( 'rcp_recurring_payment_failed', $member, $this );

				die( 'successful recurring_payment_failed or recurring_payment_suspended_due_to_max_failed_payment' );

				break;

			case "web_accept" :

				rcp_log( sprintf( 'Processing PayPal Express web_accept IPN. Payment status: %s', $posted['payment_status'] ) );

				switch ( strtolower( $posted['payment_status'] ) ) :

					case 'completed' :

						if ( empty( $payment_data['transaction_id'] ) || $rcp_payments->payment_exists( $payment_data['transaction_id'] ) ) {
							rcp_log( sprintf( 'Not inserting PayPal Express web_accept payment. Transaction ID not given or payment already exists. TXN ID: %s', $payment_data['transaction_id'] ), true );
						} else {
							$rcp_payments->insert( $payment_data );
						}

						// Member was already activated.

						break;

					case 'denied' :
					case 'expired' :
					case 'failed' :
					case 'voided' :
						if ( $membership->is_active() ) {
							$membership->cancel();
						} else {
							rcp_log( sprintf( 'Membership #%d is not active - not cancelling account.', $membership->get_id() ) );
						}
						break;

				endswitch;


				die( 'successful web_accept' );

			break;

		endswitch;

	}

	/**
	 * Get checkout details
	 *
	 * @param string $token
	 *
	 * @return array|bool|string|WP_Error
	 */
	public function get_checkout_details( $token = '' ) {

		$args = array(
			'USER'      => $this->username,
			'PWD'       => $this->password,
			'SIGNATURE' => $this->signature,
			'VERSION'   => '124',
			'METHOD'    => 'GetExpressCheckoutDetails',
			'TOKEN'     => $token
		);

		$request = wp_remote_post( $this->api_endpoint, array(
			'timeout'     => 45,
			'httpversion' => '1.1',
			'body'        => $args
		) );
		$body    = wp_remote_retrieve_body( $request );
		$code    = wp_remote_retrieve_response_code( $request );
		$message = wp_remote_retrieve_response_message( $request );

		if( is_wp_error( $request ) ) {

			return $request;

		} elseif ( 200 == $code && 'OK' == $message ) {

			if( is_string( $body ) ) {
				wp_parse_str( $body, $body );
			}

			$payments = new RCP_Payments();

			$membership          = rcp_get_membership( absint( $_GET['membership_id'] ) );
			$membership_level_id = $membership->get_object_id();
			$pending_payment_id  = rcp_get_membership_meta( $membership->get_id(), 'pending_payment_id', true );
			$pending_payment     = ! empty( $pending_payment_id ) ? $payments->get_payment( $pending_payment_id ) : false;

			if ( ! empty( $pending_payment ) ) {
				$pending_amount = $pending_payment->amount;
			} elseif ( 0 == $membership->get_times_billed() ) {
				$pending_amount = $membership->get_initial_amount();
			} else {
				$pending_amount = $membership->get_recurring_amount();
			}

			$membership_level = rcp_get_membership_level( $membership_level_id );

			$body['subscription']   = $membership_level instanceof Membership_Level ? $membership_level->export_vars() : array();
			$body['initial_amount'] = $pending_amount;

			$custom = explode( '|', $body['PAYMENTREQUEST_0_CUSTOM'] );

			$body['membership_id'] = ! empty( $custom[1] ) ? absint( $custom[1] ) : 0;

			return $body;

		}

		return false;

	}

}
