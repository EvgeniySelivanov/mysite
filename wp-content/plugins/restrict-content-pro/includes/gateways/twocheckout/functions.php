<?php
/**
 * Checkout Functions
 *
 * @package     Restrict Content Pro
 * @subpackage  Gateways/2Checkout/Functions
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/**
 * Cancel a 2Checkout membership, given a gateway payment profile ID.
 *
 * @param string $payment_profile_id Membership payment profile ID.
 *
 * @since 3.0
 * @return true|WP_Error True on success, WP_Error on failure.
 */
function rcp_2checkout_cancel_membership( $payment_profile_id ) {

	global $rcp_options;

	$user_name = defined( 'TWOCHECKOUT_ADMIN_USER' ) ? TWOCHECKOUT_ADMIN_USER : '';
	$password  = defined( 'TWOCHECKOUT_ADMIN_PASSWORD' ) ? TWOCHECKOUT_ADMIN_PASSWORD : '';

	if( empty( $user_name ) || empty( $password ) ) {
		return new WP_Error( 'missing_username_or_password', __( 'The 2Checkout API username and password must be defined', 'rcp' ) );
	}

	if( ! class_exists( 'Twocheckout' ) ) {
		require_once RCP_PLUGIN_DIR . 'includes/libraries/twocheckout/Twocheckout.php';
	}

	$secret_word = rcp_is_sandbox() ? trim( $rcp_options['twocheckout_secret_word'] ) : '';;
	$test_mode   = rcp_is_sandbox();

	if( $test_mode ) {

		$secret_key      = isset( $rcp_options['twocheckout_test_private'] )     ? trim( $rcp_options['twocheckout_test_private'] )     : '';
		$publishable_key = isset( $rcp_options['twocheckout_test_publishable'] ) ? trim( $rcp_options['twocheckout_test_publishable'] ) : '';
		$seller_id       = isset( $rcp_options['twocheckout_test_seller_id'] )   ? trim( $rcp_options['twocheckout_test_seller_id'] )   : '';
		$environment     = 'sandbox';

	} else {

		$secret_key      = isset( $rcp_options['twocheckout_live_private'] )     ? trim( $rcp_options['twocheckout_live_private'] )     : '';
		$publishable_key = isset( $rcp_options['twocheckout_live_publishable'] ) ? trim( $rcp_options['twocheckout_live_publishable'] ) : '';
		$seller_id       = isset( $rcp_options['twocheckout_live_seller_id'] )   ? trim( $rcp_options['twocheckout_live_seller_id'] )   : '';
		$environment     = 'production';

	}

	try {

		Twocheckout::privateKey( $secret_key );
		Twocheckout::sellerId( $seller_id );
		Twocheckout::username( TWOCHECKOUT_ADMIN_USER );
		Twocheckout::password( TWOCHECKOUT_ADMIN_PASSWORD );
		Twocheckout::sandbox( $test_mode );

		$sale_id   = str_replace( '2co_', '', $payment_profile_id );
		$cancelled = Twocheckout_Sale::stop( array( 'sale_id' => $sale_id ) );

		if( $cancelled['response_code'] == 'OK' ) {
			return true;
		}

	} catch ( Twocheckout_Error $e) {

		return new WP_Error( '2checkout_cancel_failed', $e->getMessage() );

	}

	return new WP_Error( '2checkout_cancel_failed', __( 'Unexpected error cancelling 2Checkout payment profile.', 'rcp' ) );

}

/**
 * Cancel a 2checkout subscriber
 *
 * @deprecated 3.0 Use `rcp_2checkout_cancel_membership()` instead.
 * @see rcp_2checkout_cancel_membership()
 *
 * @param int $member_id ID of the member to cancel.
 *
 * @access      private
 * @since       2.4
 * @return      bool|WP_Error
 */
function rcp_2checkout_cancel_member( $member_id = 0 ) {

	$customer = rcp_get_customer_by_user_id( $member_id );

	if ( empty( $customer ) ) {
		return new WP_Error( '2checkout_cancel_failed', __( 'Unable to find customer from member ID.', 'rcp' ) );
	}

	$membership = rcp_get_customer_single_membership( $customer->get_id() );

	if ( empty( $membership ) ) {
		return new WP_Error( '2checkout_cancel_failed', __( 'Invalid membership.', 'rcp' ) );
	}

	$payment_profile = $membership->get_gateway_subscription_id();

	if ( empty( $payment_profile ) ) {
		return new WP_Error( '2checkout_cancel_failed', __( 'Invalid membership.', 'rcp' ) );
	}

	return rcp_2checkout_cancel_membership( $payment_profile );

}


/**
 * Determine if a member is a 2Checkout Customer
 *
 * @deprecated 3.0 Use `rcp_is_2checkout_membership()` instead.
 * @see rcp_is_2checkout_membership()
 *
 * @param int $user_id The ID of the user to check
 *
 * @since       2.4
 * @access      public
 * @return      bool
*/
function rcp_is_2checkout_subscriber( $user_id = 0 ) {

	if( empty( $user_id ) ) {
		$user_id = get_current_user_id();
	}

	$ret = false;

	$customer = rcp_get_customer_by_user_id( $user_id );

	if ( ! empty( $customer ) ) {
		$membership = rcp_get_customer_single_membership( $customer->get_id() );

		if ( ! empty( $membership ) ) {
			$ret = rcp_is_2checkout_membership( $membership );
		}
	}

	return (bool) apply_filters( 'rcp_is_2checkout_subscriber', $ret, $user_id );
}

/**
 * Determines if a membership is a 2Checkout subscription.
 *
 * @param int|RCP_Membership $membership_object_or_id Membership ID or object.
 *
 * @since 3.0
 * @return bool
 */
function rcp_is_2checkout_membership( $membership_object_or_id ) {

	if ( ! is_object( $membership_object_or_id ) ) {
		$membership = rcp_get_membership( $membership_object_or_id );
	} else {
		$membership = $membership_object_or_id;
	}

	$is_2checkout = false;

	if ( ! empty( $membership ) && $membership->get_id() > 0 ) {
		$subscription_id = $membership->get_gateway_subscription_id();

		if ( false !== strpos( $subscription_id, '2co_' ) ) {
			$is_2checkout = true;
		}
	}

	/**
	 * Filters whether or not the membership is a 2Checkout subscription.
	 *
	 * @param bool           $is_2checkout
	 * @param RCP_Membership $membership
	 *
	 * @since 3.0
	 */
	return (bool) apply_filters( 'rcp_is_2checkout_membership', $is_2checkout, $membership );

}