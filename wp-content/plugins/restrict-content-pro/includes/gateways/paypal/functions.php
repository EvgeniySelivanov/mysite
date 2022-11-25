<?php
/**
 * PayPal Functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Gateways/PayPal/Functions
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Determine if a member is a PayPal subscriber
 *
 * @deprecated 3.0 Use `rcp_is_paypal_membership()` instead.
 * @see rcp_is_paypal_membership()
 *
 * @param int $user_id The ID of the user to check
 *
 * @since       2.0
 * @access      public
 * @return      bool
*/
function rcp_is_paypal_subscriber( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret = false;

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( ! empty( $customer ) ) {
		$membership = rcp_get_customer_single_membership( $customer->get_id() );

		if ( ! empty( $membership ) ) {
			$ret = rcp_is_paypal_membership( $membership );
		}
	}

	return (bool) apply_filters( 'rcp_is_paypal_subscriber', $ret, $user_id );
}

/**
 * Determines if a membership is a PayPal subscription.
 *
 * @param int|RCP_Membership $membership_object_or_id Membership ID or object.
 *
 * @since 3.0
 * @return bool
 */
function rcp_is_paypal_membership( $membership_object_or_id ) {

	if ( ! is_object( $membership_object_or_id ) ) {
		$membership = rcp_get_membership( $membership_object_or_id );
	} else {
		$membership = $membership_object_or_id;
	}

	$is_paypal = false;

	if ( ! empty( $membership ) && $membership->get_id() > 0 ) {
		$subscription_id = $membership->get_gateway_subscription_id();

		if ( false !== strpos( $subscription_id, 'I-' ) ) {
			$is_paypal = true;
		}
	}

	/**
	 * Filters whether or not the membership is a PayPal subscription.
	 *
	 * @param bool           $is_paypal
	 * @param RCP_Membership $membership
	 *
	 * @since 3.0
	 */
	return (bool) apply_filters( 'rcp_is_paypal_membership', $is_paypal, $membership );

}

/**
 * Determine if PayPal API access is enabled
 *
 * @access      public
 * @since       2.1
 * @return      bool
 */
function rcp_has_paypal_api_access() {
	global $rcp_options;

	$ret    = false;
	$prefix = 'live_';

	if( rcp_is_sandbox() ) {
		$prefix = 'test_';
	}

	$username  = $prefix . 'paypal_api_username';
	$password  = $prefix . 'paypal_api_password';
	$signature = $prefix . 'paypal_api_signature';

	if( ! empty( $rcp_options[ $username ] ) && ! empty( $rcp_options[ $password ] ) && ! empty( $rcp_options[ $signature ] ) ) {

		$ret = true;

	}

	return $ret;
}

/**
 * Retrieve PayPal API credentials
 *
 * @access      public
 * @since       2.1
 * @return      array Array of credentials.
 */
function rcp_get_paypal_api_credentials() {
	global $rcp_options;

	$ret    = false;
	$prefix = 'live_';

	if( rcp_is_sandbox() ) {
		$prefix = 'test_';
	}

	$creds = array(
		'username'  => $rcp_options[ $prefix . 'paypal_api_username' ],
		'password'  => $rcp_options[ $prefix . 'paypal_api_password' ],
		'signature' => $rcp_options[ $prefix . 'paypal_api_signature' ]
	);

	return apply_filters( 'rcp_get_paypal_api_credentials', $creds );
}

/**
 * Process an update card form request
 *
 * @deprecated 3.0 Use `rcp_paypal_update_membership_billing_card()` instead.
 * @see rcp_paypal_update_membership_billing_card()
 *
 * @param int        $member_id  ID of the member.
 * @param RCP_Member $member_obj Member object.
 *
 * @access      private
 * @since       2.6
 * @return      void
 */
function rcp_paypal_update_billing_card( $member_id, $member_obj ) {

	if( empty( $member_id ) ) {
		return;
	}

	if( ! is_a( $member_obj, 'RCP_Member' ) ) {
		return;
	}

	$customer = rcp_get_customer_by_user_id( $member_id );

	if ( empty( $customer ) ) {
		return;
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	if ( empty( $membership ) ) {
		return;
	}

	rcp_paypal_update_membership_billing_card( $membership );

}
//add_action( 'rcp_update_billing_card', 'rcp_paypal_update_billing_card', 10, 2 );

/**
 * Update the billing card for a given membership.
 *
 * @param RCP_Membership $membership
 *
 * @since 3.0
 * @return void
 */
function rcp_paypal_update_membership_billing_card( $membership ) {

	if ( ! is_a( $membership, 'RCP_Membership' ) ) {
		return;
	}

	if ( ! rcp_is_paypal_membership( $membership ) ) {
		return;
	}

	if( rcp_is_sandbox() ) {
		$api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';
	} else {
		$api_endpoint = 'https://api-3t.paypal.com/nvp';
	}

	$error           = '';
	$subscription_id = $membership->get_gateway_subscription_id();
	$credentials     = rcp_get_paypal_api_credentials();

	$card_number    = isset( $_POST['rcp_card_number'] )    && is_numeric( $_POST['rcp_card_number'] )    ? $_POST['rcp_card_number']    : '';
	$card_exp_month = isset( $_POST['rcp_card_exp_month'] ) && is_numeric( $_POST['rcp_card_exp_month'] ) ? $_POST['rcp_card_exp_month'] : '';
	$card_exp_year  = isset( $_POST['rcp_card_exp_year'] )  && is_numeric( $_POST['rcp_card_exp_year'] )  ? $_POST['rcp_card_exp_year']  : '';
	$card_cvc       = isset( $_POST['rcp_card_cvc'] )       && is_numeric( $_POST['rcp_card_cvc'] )       ? $_POST['rcp_card_cvc']       : '';
	$card_zip       = isset( $_POST['rcp_card_zip'] ) ? sanitize_text_field( $_POST['rcp_card_zip'] ) : '' ;

	if ( empty( $card_number ) || empty( $card_exp_month ) || empty( $card_exp_year ) || empty( $card_cvc ) || empty( $card_zip ) ) {
		$error = __( 'Please enter all required fields.', 'rcp' );
	}

	if ( empty( $error ) ) {

		$args = array(
			'USER'                => $credentials['username'],
			'PWD'                 => $credentials['password'],
			'SIGNATURE'           => $credentials['signature'],
			'VERSION'             => '124',
			'METHOD'              => 'UpdateRecurringPaymentsProfile',
			'PROFILEID'           => $subscription_id,
			'ACCT'                => $card_number,
			'EXPDATE'             => $card_exp_month . $card_exp_year,
			// needs to be in the format 062019
			'CVV2'                => $card_cvc,
			'ZIP'                 => $card_zip,
			'BUTTONSOURCE'        => 'EasyDigitalDownloads_SP',
		);

		$request = wp_remote_post( $api_endpoint, array(
			'timeout'     => 45,
			'sslverify'   => false,
			'body'        => $args,
			'httpversion' => '1.1',
		) );

		$body    = wp_remote_retrieve_body( $request );
		$code    = wp_remote_retrieve_response_code( $request );
		$message = wp_remote_retrieve_response_message( $request );

		if ( is_wp_error( $request ) ) {

			$error = $request->get_error_message();

		} elseif ( 200 == $code && 'OK' == $message ) {

			if( is_string( $body ) ) {
				wp_parse_str( $body, $body );
			}

			if ( 'failure' === strtolower( $body['ACK'] ) ) {

				$error = $body['L_ERRORCODE0'] . ': ' . $body['L_LONGMESSAGE0'];

			} else {

				// Request was successful, but verify the profile ID that came back matches
				if ( $subscription_id !== $body['PROFILEID'] ) {
					$error = __( 'Error updating subscription', 'rcp' );

					rcp_log( sprintf( 'Invalid PayPal subscription ID. Expected: %s; Provided: %s.', $subscription_id, $body['PROFILEID'] ), true );
				}

			}

		} else {

			$error = __( 'Something has gone wrong, please try again', 'rcp' );

		}

	}

	if( ! empty( $error ) ) {

		wp_redirect( add_query_arg( array( 'card' => 'not-updated', 'msg' => urlencode( $error ) ) ) ); exit;

	}

	wp_redirect( add_query_arg( array( 'card' => 'updated', 'msg' => '' ) ) ); exit;

}
add_action( 'rcp_update_membership_billing_card', 'rcp_paypal_update_membership_billing_card' );

/**
 * Log the start of a valid IPN request
 *
 * @param array $payment_data Payment information to be stored in the DB.
 * @param int   $user_id      ID of the user the payment is for.
 * @param array $posted       Data sent via the IPN.
 *
 * @since 2.9
 * @return void
 */
function rcp_log_valid_paypal_ipn( $payment_data, $user_id, $posted ) {

	rcp_log( sprintf( 'Started processing valid PayPal IPN request for user #%d. Payment Data: %s', $user_id, var_export( $payment_data, true ) ) );

}
add_action( 'rcp_valid_ipn', 'rcp_log_valid_paypal_ipn', 10, 3 );

/**
 * Cancel a PayPal membership by profile ID.
 *
 * @param string $payment_profile_id Gateway payment profile ID.
 *
 * @since 3.0
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function rcp_paypal_cancel_membership( $payment_profile_id ) {

	global $rcp_options;

	if ( ! rcp_has_paypal_api_access() ) {
		return new WP_Error( 'paypal_cancel_failed_no_api', __( 'PayPal cancellation failed - no API access.', 'rcp' ) );
	}

	// Set PayPal API key credentials.
	$api_username  = rcp_is_sandbox() ? 'test_paypal_api_username' : 'live_paypal_api_username';
	$api_password  = rcp_is_sandbox() ? 'test_paypal_api_password' : 'live_paypal_api_password';
	$api_signature = rcp_is_sandbox() ? 'test_paypal_api_signature' : 'live_paypal_api_signature';
	$api_endpoint  = rcp_is_sandbox() ? 'https://api-3t.sandbox.paypal.com/nvp' : 'https://api-3t.paypal.com/nvp';

	$args = array(
		'USER'      => trim( $rcp_options[$api_username] ),
		'PWD'       => trim( $rcp_options[$api_password] ),
		'SIGNATURE' => trim( $rcp_options[$api_signature] ),
		'VERSION'   => '124',
		'METHOD'    => 'ManageRecurringPaymentsProfileStatus',
		'PROFILEID' => $payment_profile_id,
		'ACTION'    => 'Cancel'
	);

	$error_msg = '';
	$request   = wp_remote_post( $api_endpoint, array( 'body' => $args, 'timeout' => 30, 'httpversion' => '1.1' ) );

	if ( is_wp_error( $request ) ) {

		$success   = false;
		$error_msg = $request->get_error_message();

	} else {

		$body    = wp_remote_retrieve_body( $request );
		$code    = wp_remote_retrieve_response_code( $request );
		$message = wp_remote_retrieve_response_message( $request );

		if ( is_string( $body ) ) {
			wp_parse_str( $body, $body );
		}

		if ( 200 !== (int) $code ) {
			$success = false;
		}

		if ( 'OK' !== $message ) {
			$success = false;
		}

		if ( isset( $body['ACK'] ) && 'success' === strtolower( $body['ACK'] ) ) {
			$success = true;
		} else {
			$success = false;
			if ( isset( $body['L_LONGMESSAGE0'] ) ) {
				$error_msg = $body['L_LONGMESSAGE0'];
			}
		}

	}

	if ( ! $success ) {
		$success = new WP_Error( 'paypal_cancel_fail', $error_msg );
	}

	return $success;

}