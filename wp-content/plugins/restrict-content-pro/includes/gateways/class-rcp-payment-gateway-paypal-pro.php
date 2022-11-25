<?php
/**
 * PayPal Pro Gateway class
 *
 * @package     Restrict Content Pro
 * @subpackage  Classes/Gateways/PayPal Pro
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.1
*/

class RCP_Payment_Gateway_PayPal_Pro extends RCP_Payment_Gateway {

	private $api_endpoint;
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

		$this->supports[]  = 'one-time';
		$this->supports[]  = 'recurring';
		$this->supports[]  = 'fees';
		$this->supports[]  = 'trial';
		$this->supports[]  = 'card-updates';

		if( $this->test_mode ) {

			$this->api_endpoint = 'https://api-3t.sandbox.paypal.com/nvp';

		} else {

			$this->api_endpoint = 'https://api-3t.paypal.com/nvp';

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

		/**
		 * @var RCP_Payments $rcp_payments_db
		 */
		global $rcp_payments_db;

		if ( is_user_logged_in() ) {
			$user_data  = get_userdata( $this->user_id );
			$first_name = $user_data->first_name;
			$last_name  = $user_data->last_name;
		} else {
			$first_name = $_POST['rcp_user_first'];
			$last_name  = $_POST['rcp_user_last'];
		}

		$descriptor = get_bloginfo( 'name' ) . ' - ' . $this->subscription_name;
		if ( strlen( $descriptor ) > 23 ) {
			$descriptor = substr( $descriptor, 0, 23 );
		}

		$descriptor_city = get_bloginfo( 'admin_email' );
		if ( strlen( $descriptor_city ) > 13 ) {
			$descriptor_city = substr( $descriptor_city, 0, 13 );
		}

		$args = array(
			'USER'               => $this->username,
			'PWD'                => $this->password,
			'SIGNATURE'          => $this->signature,
			'VERSION'            => '124',
			'METHOD'             => $this->auto_renew ? 'CreateRecurringPaymentsProfile' : 'DoDirectPayment',
			'AMT'                => $this->auto_renew ? $this->amount : $this->initial_amount,
			'CURRENCYCODE'       => strtoupper( $this->currency ),
			'SHIPPINGAMT'        => 0,
			'TAXAMT'             => 0,
			'DESC'               => $this->subscription_name,
			'SOFTDESCRIPTOR'     => $descriptor,
			'SOFTDESCRIPTORCITY' => $descriptor_city,
			'CUSTOM'             => $this->user_id . '|' . $this->membership->get_id(),
			'NOTIFYURL'          => add_query_arg( 'listener', 'EIPN', home_url( 'index.php' ) ),
			'EMAIL'              => $this->email,
			'FIRSTNAME'          => sanitize_text_field( $first_name ),
			'LASTNAME'           => sanitize_text_field( $last_name ),
			'STREET'             => sanitize_text_field( $_POST['rcp_card_address'] ),
			'CITY'               => sanitize_text_field( $_POST['rcp_card_city'] ),
			'STATE'              => sanitize_text_field( $_POST['rcp_card_state'] ),
			'COUNTRYCODE'        => sanitize_text_field( $_POST['rcp_card_country'] ),
			'CREDITCARDTYPE'     => '',
			'ACCT'               => sanitize_text_field( $_POST['rcp_card_number'] ),
			'EXPDATE'            => sanitize_text_field( $_POST['rcp_card_exp_month'] . $_POST['rcp_card_exp_year'] ), // needs to be in the format 062019
			'CVV2'               => sanitize_text_field( $_POST['rcp_card_cvc'] ),
			'ZIP'                => sanitize_text_field( $_POST['rcp_card_zip'] ),
			'BUTTONSOURCE'       => 'EasyDigitalDownloads_SP',
			'PROFILESTARTDATE'   => date( 'Y-m-d\TH:i:s', strtotime( '+' . $this->length . ' ' . $this->length_unit, time() ) ),
			'BILLINGPERIOD'      => ucwords( $this->length_unit ),
			'BILLINGFREQUENCY'   => $this->length,
			'FAILEDINITAMTACTION'=> 'CancelOnFailure',
			'TOTALBILLINGCYCLES' => $this->auto_renew ? 0 : 1
		);

		if ( $this->auto_renew ) {

			if ( $this->initial_amount >= 0 ) {
				$args['INITAMT'] = $this->initial_amount;
			}

		}

		if ( $this->auto_renew && ! empty( $this->subscription_start_date ) ) {
			// Set profile start date.
			$args['PROFILESTARTDATE'] = date( 'Y-m-d\TH:i:s', strtotime( $this->subscription_start_date, current_time( 'timestamp' ) ) );
			unset( $args['INITAMT'] );
		}

		$request = wp_remote_post( $this->api_endpoint, array( 'timeout' => 45, 'sslverify' => false, 'httpversion' => '1.1', 'body' => apply_filters( 'rcp_paypal_pro_args', $args, $this ) ) );
		$body    = wp_remote_retrieve_body( $request );
		$code    = wp_remote_retrieve_response_code( $request );
		$message = wp_remote_retrieve_response_message( $request );

		if( is_wp_error( $request ) ) {

			$this->error_message = $request->get_error_message();
			do_action( 'rcp_registration_failed', $this );
			do_action( 'rcp_paypal_pro_signup_payment_failed', $request, $this );

			$error = '<p>' . __( 'An unidentified error occurred.', 'rcp' ) . '</p>';
			$error .= '<p>' . $request->get_error_message() . '</p>';

			wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

		} elseif ( 200 == $code && 'OK' == $message ) {

			if( is_string( $body ) ) {
				wp_parse_str( $body, $body );
			}

			if( false !== strpos( strtolower( $body['ACK'] ), 'failure' ) ) {

				$this->error_message = $body['L_LONGMESSAGE0'];
				do_action( 'rcp_registration_failed', $this );
				do_action( 'rcp_paypal_pro_signup_payment_failed', $request, $this );

				$error = '<p>' . __( 'PayPal subscription creation failed.', 'rcp' ) . '</p>';
				$error .= '<p>' . __( 'Error message:', 'rcp' ) . ' ' . $body['L_LONGMESSAGE0'] . '</p>';
				$error .= '<p>' . __( 'Error code:', 'rcp' ) . ' ' . $body['L_ERRORCODE0'] . '</p>';

				wp_die( $error, __( 'Error', 'rcp' ), array( 'response' => '401' ) );

			} else {

				// Successful signup

				if ( isset( $body['PROFILEID'] ) ) {
					$this->membership->set_gateway_subscription_id( $body['PROFILEID'] );
				}

				if ( isset( $body['TRANSACTIONID'] ) && false !== strpos( strtolower( $body['ACK'] ), 'success' ) ) {
					// Confirm a one-time payment. Updating the payment activates the account.
					$payment_data = array(
						'payment_type'   => 'Credit Card One Time',
						'transaction_id' => sanitize_text_field( $body['TRANSACTIONID'] ),
						'status'         => 'complete'
					);

					$rcp_payments_db->update( $this->payment->id, $payment_data );
				}

				wp_redirect( esc_url_raw( rcp_get_return_url() ) ); exit;
				exit;

			}

		} else {

			do_action( 'rcp_registration_failed', $this );
			wp_die( __( 'Something has gone wrong, please try again', 'rcp' ), __( 'Error', 'rcp' ), array( 'back_link' => true, 'response' => '401' ) );

		}

	}

	/**
	 * Add credit card form
	 *
	 * @since 2.1
	 * @return string
	 */
	public function fields() {

		ob_start();
		rcp_get_template_part( 'card-form', 'full' );
		return ob_get_clean();
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
			$this->add_error( 'no_paypal_api', __( 'You have not configured PayPal API access. Please configure it in Restrict &rarr; Settings', 'rcp' ) );
		}

		if( empty( $_POST['rcp_card_address'] ) ) {
			$this->add_error( 'missing_card_address', __( 'The address you have entered is invalid', 'rcp' ) );
		}

		if( empty( $_POST['rcp_card_city'] ) ) {
			$this->add_error( 'missing_card_city', __( 'The city you have entered is invalid', 'rcp' ) );
		}

		if( empty( $_POST['rcp_card_state'] ) ) {
			$this->add_error( 'missing_card_state', __( 'The state you have entered is invalid', 'rcp' ) );
		}

		if( empty( $_POST['rcp_card_country'] ) ) {
			$this->add_error( 'missing_card_country', __( 'The country you have entered is invalid', 'rcp' ) );
		}

		if( empty( $_POST['rcp_card_number'] ) ) {
			$this->add_error( 'missing_card_number', __( 'The card number you have entered is invalid', 'rcp' ) );
		}

		if( empty( $_POST['rcp_card_cvc'] ) ) {
			$this->add_error( 'missing_card_code', __( 'The security code you have entered is invalid', 'rcp' ) );
		}

		if( empty( $_POST['rcp_card_zip'] ) ) {
			$this->add_error( 'missing_card_zip', __( 'The zip / postal code you have entered is invalid', 'rcp' ) );
		}

		if( empty( $_POST['rcp_card_name'] ) ) {
			$this->add_error( 'missing_card_name', __( 'The card holder name you have entered is invalid', 'rcp' ) );
		}

		if( empty( $_POST['rcp_card_exp_month'] ) ) {
			$this->add_error( 'missing_card_exp_month', __( 'The card expiration month you have entered is invalid', 'rcp' ) );
		}

		if( empty( $_POST['rcp_card_exp_year'] ) ) {
			$this->add_error( 'missing_card_exp_year', __( 'The card expiration year you have entered is invalid', 'rcp' ) );
		}

	}

	/**
	 * Process webhooks
	 *
	 * @access public
	 * @since  2.1
	 * @return void
	 */
	public function process_webhooks() {

		// These are processed through PayPal Express gateway

	}

}
