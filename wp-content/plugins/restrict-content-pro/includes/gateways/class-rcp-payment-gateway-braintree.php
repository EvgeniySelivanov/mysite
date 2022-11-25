<?php
/**
 * Braintree Payment Gateway Class
 *
 * @package    Restrict Content Pro
 * @subpackage Classes/Gateways/Braintree
 * @copyright  Copyright (c) 2017, Sandhills Development
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      2.8
 */

class RCP_Payment_Gateway_Braintree extends RCP_Payment_Gateway {

	/**
	 * @var Braintree\Gateway
	 */
	protected $braintree;
	protected $merchantId;
	protected $publicKey;
	protected $privateKey;
	protected $encryptionKey;
	protected $environment;

	/**
	 * Initializes the gateway configuration.
	 *
	 * @since 2.8
	 * @return void
	 */
	public function init() {

		if ( version_compare( PHP_VERSION, '5.4.0', '<' ) ) {
			return;
		}

		global $rcp_options;

		$this->supports[] = 'one-time';
		$this->supports[] = 'recurring';
		$this->supports[] = 'fees';
		$this->supports[] = 'trial';
		$this->supports[] = 'gateway-submits-form';
		$this->supports[] = 'card-updates';
		$this->supports[] = 'expiration-extension-on-renewals'; // @link https://github.com/restrictcontentpro/restrict-content-pro/issues/1259

		if ( $this->test_mode ) {
			$this->merchantId    = ! empty( $rcp_options['braintree_sandbox_merchantId'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_merchantId'] ) : '';
			$this->publicKey     = ! empty( $rcp_options['braintree_sandbox_publicKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_publicKey'] ) : '';
			$this->privateKey    = ! empty( $rcp_options['braintree_sandbox_privateKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_privateKey'] ) : '';
			$this->encryptionKey = ! empty( $rcp_options['braintree_sandbox_encryptionKey'] ) ? sanitize_text_field( $rcp_options['braintree_sandbox_encryptionKey'] ) : '';
			$this->environment   = 'sandbox';
		} else {
			$this->merchantId    = ! empty( $rcp_options['braintree_live_merchantId'] ) ? sanitize_text_field( $rcp_options['braintree_live_merchantId'] ) : '';
			$this->publicKey     = ! empty( $rcp_options['braintree_live_publicKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_publicKey'] ) : '';
			$this->privateKey    = ! empty( $rcp_options['braintree_live_privateKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_privateKey'] ) : '';
			$this->encryptionKey = ! empty( $rcp_options['braintree_live_encryptionKey'] ) ? sanitize_text_field( $rcp_options['braintree_live_encryptionKey'] ) : '';
			$this->environment   = 'production';
		}

		if ( ! class_exists( 'Braintree\\Gateway' ) ) {
			require_once RCP_PLUGIN_DIR . 'includes/libraries/braintree/lib/Braintree.php';
		}

		$this->braintree = new Braintree\Gateway( array(
			'environment' => $this->environment,
			'merchantId'  => $this->merchantId,
			'publicKey'   => $this->publicKey,
			'privateKey'  => $this->privateKey
		) );

	}

	/**
	 * Validates the form fields.
	 * If there are any errors, it creates a new WP_Error instance
	 * via the rcp_errors() function.
	 *
	 * @see WP_Error::add()
	 * @uses rcp_errors()
	 * @return void
	 */
	public function validate_fields() {}

	/**
	 * Processes a registration payment.
	 *
	 * @return void
	 */
	public function process_signup() {

		if ( empty( $_POST['payment_method_nonce'] ) ) {
			$this->handle_processing_error(
				new Exception(
					__( 'Missing Braintree payment nonce. Please try again. Contact support if the issue persists.', 'rcp' )
				)
			);
		}

		$payment_method_nonce = $_POST['payment_method_nonce'];

		/**
		 * @var RCP_Payments $rcp_payments_db
		 */
		global $rcp_payments_db;

		$txn_args = array();
		$member   = new RCP_Member( $this->user_id ); // For backwards compatibility only.
		$user     = get_userdata( $this->user_id );

		/**
		 * Set up the customer object.
		 *
		 * Get the customer record from Braintree if it already exists,
		 * otherwise create a new customer record.
		 */
		$customer = false;
		$payment_profile_id = rcp_get_customer_gateway_id( $this->membership->get_customer_id(), 'braintree' );


		if ( $payment_profile_id ) {
			try {
				$customer = $this->braintree->customer()->find( $payment_profile_id );
			} catch ( Braintree_Exception_NotFound $e ) {
				$customer = false;
			} catch ( Exception $e ) {
				$this->handle_processing_error( $e );
			}
		}

		if ( ! $customer ) {
			// Search for existing customer by ID.
			$collection = $this->braintree->customer()->search( array(
				Braintree_CustomerSearch::id()->is( 'bt_' . $this->user_id )
			) );

			if ( $collection ) {
				foreach ( $collection as $record ) {
					if ( $record->id === 'bt_' . $this->user_id ) {
						$customer = $record;
						break;
					}
				}
			}
		}

		if ( ! $customer ) {

			try {
				$result = $this->braintree->customer()->create( array(
					'id'                 => 'bt_' . $this->user_id,
					'firstName'          => ! empty( $user->first_name ) ? sanitize_text_field( $user->first_name ) : '',
					'lastName'           => ! empty( $user->last_name ) ? sanitize_text_field( $user->last_name ) : '',
					'email'              => $user->user_email,
					'riskData'           => array(
						'customerBrowser' => $_SERVER['HTTP_USER_AGENT'],
						'customerIp'      => rcp_get_ip()
					)
				) );

				if ( $result->success && $result->customer ) {
					$customer = $result->customer;
				}

			} catch ( Exception $e ) {
				// Customer lookup/creation failed
				$this->handle_processing_error( $e );
			}
		}

		if ( empty( $customer ) ) {
			$this->handle_processing_error( new Exception( __( 'Unable to locate or create customer record. Please try again. Contact support if the problem persists.', 'rcp' ) ) );
		}

		// Set the customer ID.
		$this->membership->set_gateway_customer_id( $customer->id );

		$payment_method_token = false;

		if ( $this->initial_amount > 0 ) {

			/**
			 * Always process a one-time payment for the first transaction.
			 */
			try {
				$single_payment = $this->braintree->transaction()->sale( array(
					'amount'             => $this->initial_amount,
					'customerId'         => $customer->id,
					'paymentMethodNonce' => $payment_method_nonce,
					'options'            => array(
						'submitForSettlement'   => true,
						'storeInVaultOnSuccess' => true
					)
				) );

				if ( $single_payment->success ) {

					$payment_method_token = $single_payment->transaction->creditCardDetails->token;

					$rcp_payments_db->update( $this->payment->id, array(
						'date'           => date( 'Y-m-d H:i:s', time() ),
						'payment_type'   => __( 'Braintree Credit Card Initial Payment', 'rcp' ),
						'transaction_id' => $single_payment->transaction->id,
						'status'         => 'complete'
					) );

					/**
					 * Triggers when a gateway payment is completed.
					 *
					 * @param RCP_Member                    $member     Deprecated member object.
					 * @param int                           $payment_id ID of the payment record in RCP.
					 * @param RCP_Payment_Gateway_Braintree $this       Gateway object.
					 */
					do_action( 'rcp_gateway_payment_processed', $member, $this->payment->id, $this );

				} else {
					throw new Exception( sprintf( __( 'There was a problem processing your payment. Message: %s', 'rcp' ), $single_payment->message ) );
				}

			} catch ( Exception $e ) {
				$this->handle_processing_error( $e );
			}

		} elseif ( empty( $this->initial_amount ) && $this->auto_renew ) {

			/**
			 * Vault the payment method.
			 *
			 * Setting up a subscription requires a vaulted payment method first.
			 * This is done automatically when doing a one-time transaction, so we only need to do this
			 * separately if we haven't done a one-time charge.
			 */
			try {
				$vaulted_payment_method = $this->braintree->paymentMethod()->create( array(
					'customerId'         => $customer->id,
					'paymentMethodNonce' => $payment_method_nonce
				) );

				if ( $vaulted_payment_method->success && isset( $vaulted_payment_method->paymentMethod->token ) ) {
					$payment_method_token = $vaulted_payment_method->paymentMethod->token;
				}

			} catch ( Exception $e ) {
				$error = sprintf( 'Braintree Gateway: Error occurred while vaulting the payment method. Message: %s', $e->getMessage() );
				rcp_log( $error, true );
				$this->membership->add_note( $error );
			}

			// Complete the pending payment.
			$rcp_payments_db->update( $this->payment->id, array(
				'date'           => date( 'Y-m-d H:i:s', time() ),
				'payment_type'   => __( 'Braintree Credit Card Initial Payment', 'rcp' ),
				'status'         => 'complete'
			) );

		}

		/**
		 * Set up the subscription values and create the subscription.
		 */
		if ( $this->auto_renew ) {

			try {

				// Failure if we don't have a token.
				if ( empty( $payment_method_token ) ) {
					throw new Exception( __( 'Missing payment method token.', 'rcp' ) );
				}

				$txn_args['planId'] = $this->subscription_data['subscription_id'];
				$txn_args['price']  = $this->amount;

				if ( $this->is_3d_secure_enabled() ) {
					// If 3D secure is enabled, we need a nonce from the vaulted payment method.
					$nonce_result                   = $this->braintree->paymentMethodNonce()->create( $payment_method_token );
					$txn_args['paymentMethodNonce'] = $nonce_result->paymentMethodNonce->nonce;
				} else {
					// Otherwise we can use a token, which doesn't have 3D secure data.
					$txn_args['paymentMethodToken'] = $payment_method_token;
				}

				/**
				 * Start the subscription at the end of the trial period (if applicable) or the end of the first billing period.
				 */
				if ( ! empty( $this->subscription_start_date ) ) {
					$txn_args['firstBillingDate'] = $this->subscription_start_date;
				} else {
					// Now set the firstBillingDate to the expiration date of the membership, modified to current time instead of 23:59.
					$timezone     = get_option( 'timezone_string' );
					$timezone     = ! empty( $timezone ) ? $timezone : 'UTC';
					$datetime     = new DateTime( $this->membership->get_expiration_date( false ), new DateTimeZone( $timezone ) );
					$current_time = getdate( current_time( 'timestamp' ) );
					$datetime->setTime( $current_time['hours'], $current_time['minutes'], $current_time['seconds'] );
					$txn_args['firstBillingDate'] = $datetime->format( 'Y-m-d H:i:s' );
				}

				rcp_log( sprintf( 'Braintree Gateway: Creating subscription with start date: %s', $txn_args['firstBillingDate'] ) );

				$result = $this->braintree->subscription()->create( $txn_args );

				if ( $result->success ) {
					$this->membership->set_gateway_subscription_id( $result->subscription->id );
				} else {
					throw new Exception( sprintf( __( 'Failed to create the subscription. Message: %s.', 'rcp' ), esc_html( $result->message ) ) );
				}

			} catch ( Exception $e ) {
				$error = sprintf( 'Braintree Gateway: Error occurred while creating the subscription. Message: %s', $e->getMessage() );
				rcp_log( $error, true );
				$this->membership->add_note( $error );
				$this->membership->set_recurring( false );
			}

		}

		wp_redirect( $this->return_url ); exit;

	}

	/**
	 * Processes the Braintree webhooks.
	 *
	 * @return void
	 */
	public function process_webhooks() {

		if ( isset( $_GET['bt_challenge'] ) ) {
			try {
				$verify = $this->braintree->webhookNotification()->verify( $_GET['bt_challenge'] );
				die( $verify );
			} catch ( Exception $e ) {
				rcp_log( 'Exiting Braintree webhook - verification failed.', true );

				wp_die( 'Verification failed' );
			}
		}

		if ( ! isset( $_POST['bt_signature'] ) || ! isset( $_POST['bt_payload'] ) ) {
			return;
		}

		rcp_log( 'Starting to process Braintree webhook.' );

		$data = false;

		try {
			$data = $this->braintree->webhookNotification()->parse( $_POST['bt_signature'], $_POST['bt_payload'] );
		} catch ( Exception $e ) {
			rcp_log( 'Exiting Braintree webhook - invalid signature.', true );

			die( 'Invalid signature' );
		}

		if ( empty( $data->kind ) ) {
			rcp_log( 'Exiting Braintree webhook - invalid webhook.', true );

			die( 'Invalid webhook' );
		}

		/**
		 * Return early if this is a test webhook.
		 */
		if ( 'check' === $data->kind ) {
			rcp_log( 'Exiting Braintree webhook - this is a test webhook.' );

			die( 200 );
		}

		/**
		 * Get the membership from the subscription ID.
		 * @todo is subscription ID unique enough?? Should check for customer ID too.
		 */
		if ( empty( $user_id ) && ! empty( $data->subscription->id ) ) {
			$this->membership = rcp_get_membership_by( 'gateway_subscription_id', $data->subscription->id );

		}

		if ( ! empty( $data->subscription->transactions ) ) {
			$transaction = $data->subscription->transactions[0];
		}

		/**
		 * For backwards compatibility with the old Braintree add-on,
		 * find a user with this subscription ID stored in the meta
		 * `rcp_recurring_payment_id`.
		 */
		if ( empty( $this->membership ) && ! empty( $data->subscription->id ) ) {

			global $wpdb;

			$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'rcp_recurring_payment_id' AND meta_value = %s LIMIT 1", $data->subscription->id ) );

			if ( ! empty( $user_id ) ) {
				$customer = rcp_get_customer_by_user_id( $user_id );

				if ( ! empty( $customer ) ) {
					/*
					 * We can only use this function if:
					 * 		- Multiple memberships is disabled; or
					 * 		- The customer only has one membership anyway.
					 */
					if ( ! rcp_multiple_memberships_enabled() || 1 === count( $customer->get_memberships() ) ) {
						$this->membership = rcp_get_customer_single_membership( $customer->get_id() );
					}
				}
			}

		}

		if ( empty( $this->membership ) ) {
			rcp_log( 'Exiting Braintree webhook - membership not found.', true );

			die( 'no membership found' );
		}

		$member = new RCP_Member( $this->membership->get_user_id() ); // for backwards compat

		rcp_log( sprintf( 'Processing webhook for membership #%d.', $this->membership->get_id() ) );

		if ( empty( $this->membership->get_object_id() ) ) {
			rcp_log( 'Exiting Braintree webhook - no membership level associated with membership.', true );

			die( 'no membership level found' );
		}

		$pending_payment_id = rcp_get_membership_meta( $this->membership->get_id(), 'pending_payment_id', true );

		$rcp_payments = new RCP_Payments;

		/**
		 * Process the webhook.
		 *
		 * Descriptions of the webhook kinds below come from the Braintree developer docs.
		 * @see https://developers.braintreepayments.com/reference/general/webhooks/subscription/php
		 */
		switch ( $data->kind ) {

			/**
			 * A subscription is canceled.
			 */
			case 'subscription_canceled':

				rcp_log( 'Processing Braintree subscription_canceled webhook.' );

				// If this is a completed payment plan, we can skip any cancellation actions. This is handled in renewals.
				if ( $this->membership->has_payment_plan() && $this->membership->at_maximum_renewals() ) {
					rcp_log( sprintf( 'Membership #%d has completed its payment plan - not cancelling.', $this->membership->get_id() ) );
					die( 'membership payment plan completed' );
				}

				if ( $this->membership->is_active() ) {
					$this->membership->cancel();
				} else {
					rcp_log( sprintf( 'Membership #%d is not active - not cancelling.', $this->membership->get_id() ) );
				}

				/**
				 * There won't be a paidThroughDate if a trial user cancels,
				 * so we need to check that it exists.
				 */
				if ( ! empty( $data->subscription->paidThroughDate ) ) {
					$this->membership->set_expiration_date( $data->subscription->paidThroughDate->format( 'Y-m-d 23:59:59' ) );
				}

				$this->membership->add_note( __( 'Subscription cancelled in Braintree via webhook.', 'rcp' ) );

				do_action( 'rcp_webhook_cancel', $member, $this );

				die( 'braintree subscription cancelled' );

				break;

			/**
			 * A subscription successfully moves to the next billing cycle.
			 * This occurs if a new transaction is created. It will also occur
			 * when a billing cycle is skipped due to the presence of a
			 * negative balance that covers the cost of the subscription.
			 */
			case 'subscription_charged_successfully':

				rcp_log( 'Processing Braintree subscription_charged_successfully webhook.' );

				if ( $rcp_payments->payment_exists( $transaction->id ) ) {
					do_action( 'rcp_ipn_duplicate_payment', $transaction->id, $member, $this );

					die( 'duplicate payment found' );
				}

				if ( ! empty( $pending_payment_id ) ) {

					// First payment on a new membership.

					$rcp_payments->update( $pending_payment_id, array(
						'date'             => date( $transaction->createdAt->format( 'Y-m-d H:i:s' ) ),
						'payment_type'     => 'Braintree Credit Card',
						'transaction_id'   => $transaction->id,
					    'status'           => 'complete'
					) );

					$this->membership->add_note( __( 'Subscription started in Braintree', 'rcp' ) );

					$payment_id = $pending_payment_id;

				} else {

					// Renewing an existing membership.

					$this->membership->renew( true, 'active', $data->subscription->paidThroughDate->format( 'Y-m-d 23:59:59' ) );

					$payment_id = $rcp_payments->insert( array(
						'date'             => date( $transaction->createdAt->format( 'Y-m-d H:i:s' ) ),
						'payment_type'     => 'Braintree Credit Card',
						'transaction_type' => 'renewal',
						'user_id'          => $this->membership->get_user_id(),
						'customer_id'      => $this->membership->get_customer_id(),
						'membership_id'    => $this->membership->get_id(),
						'amount'           => $transaction->amount,
						'subtotal'         => $transaction->subtotal,
						'transaction_id'   => $transaction->id,
						'subscription'     => $this->membership->get_membership_level_name(),
						'subscription_key' => $member->get_subscription_key(),
						'object_type'      => 'subscription',
						'object_id'        => $this->membership->get_object_id(),
						'gateway'          => 'braintree'
					) );

					$member->add_note( sprintf( __( 'Payment %s collected in Braintree', 'rcp' ), $payment_id ) );

					do_action( 'rcp_webhook_recurring_payment_processed', $member, $payment_id, $this );
				}

				do_action( 'rcp_gateway_payment_processed', $member, $payment_id, $this );

				die( 'braintree payment recorded' );
				break;

			/**
			 * A subscription already exists and fails to create a successful charge.
			 * This will not trigger on manual retries or if the attempt to create a
			 * subscription fails due to an unsuccessful transaction.
			 */
			case 'subscription_charged_unsuccessfully':
				rcp_log( 'Processing Braintree subscription_charged_unsuccessfully webhook.' );

				do_action( 'rcp_recurring_payment_failed', $member, $this );

				die( 'subscription_charged_unsuccessfully' );
				break;

			/**
			 * A subscription reaches the specified number of billing cycles and expires.
			 */
			case 'subscription_expired':

				rcp_log( 'Processing Braintree subscription_expired webhook.' );

				$this->membership->set_status( 'expired' );

				$this->membership->set_expiration_date( $data->subscription->paidThroughDate->format( 'Y-m-d H:i:s' ) );

				$this->membership->add_note( __( 'Subscription expired in Braintree', 'rcp' ) );

				die( 'member expired' );
				break;

			/**
			 * A subscription's trial period ends.
			 */
			case 'subscription_trial_ended':

				rcp_log( 'Processing Braintree subscription_trial_ended webhook.' );

				$this->membership->renew( $member->is_recurring(), '', $data->subscription->billingPeriodEndDate->format( 'Y-m-d H:i:s' ) );
				$this->membership->add_note( __( 'Trial ended in Braintree', 'rcp' ) );
				die( 'subscription_trial_ended processed' );
				break;

			/**
			 * A subscription's first authorized transaction is created.
			 * Subscriptions with trial periods will never trigger this notification.
			 */
			case 'subscription_went_active':

				rcp_log( 'Processing Braintree subscription_went_active webhook.' );

				if ( ! empty( $pending_payment_id ) ) {
					$rcp_payments->update( $pending_payment_id, array(
						'date'             => $transaction->createdAt->format( 'Y-m-d H:i:s' ),
						'payment_type'     => 'Braintree Credit Card',
						'transaction_id'   => $transaction->id,
					    'status'           => 'complete'
					) );

					$this->membership->add_note( sprintf( __( 'Subscription %s started in Braintree', 'rcp' ), $pending_payment_id ) );
				}

				do_action( 'rcp_webhook_recurring_payment_profile_created', $member, $this );

				die( 'subscription went active' );
				break;

			/**
			 * A subscription has moved from the active status to the past due status.
			 * This occurs when a subscription’s initial transaction is declined.
			 */
			case 'subscription_went_past_due':

				rcp_log( 'Processing Braintree subscription_went_past_due webhook.' );

				$this->membership->set_status( 'pending' );
				$this->membership->add_note( __( 'Subscription went past due in Braintree', 'rcp' ) );
				die( 'subscription past due: member pending' );
				break;

			default:
				die( 'unrecognized webhook kind' );
				break;
		}
	}

	/**
	 * Handles the error processing.
	 *
	 * @param Exception $exception
	 */
	protected function handle_processing_error( $exception ) {

		$this->error_message = $exception->getMessage();

		do_action( 'rcp_registration_failed', $this );

		wp_die( $exception->getMessage(), __( 'Error', 'rcp' ), array( 'response' => 401 ) );

	}

	/**
	 * Load the registration fields
	 *
	 * Outputs a placeholder for the Drop-in UI and a hidden field for the client token.
	 *
	 * @return string
	 */
	public function fields() {
		ob_start();

		$args     = array();
		$customer = rcp_get_customer();

		if ( ! empty( $customer ) ) {
			$braintree_customer_id = rcp_get_customer_gateway_id( $customer->get_id(), 'braintree' );

			if ( ! empty( $braintree_customer_id ) ) {
				$args['customerId'] = $braintree_customer_id;
			}
		}

		try {
			$token = $this->braintree->clientToken()->generate( $args );
		} catch ( Exception $e ) {
			return __( 'Failed to create client token.', 'rcp' );
		}
		?>
		<div id="rcp-braintree-dropin-container"></div>
		<div id="rcp-braintree-dropin-errors"></div>
		<input type="hidden" id="rcp-braintree-client-token" name="rcp-braintree-client-token" value="<?php echo esc_attr( $token ); ?>" />
		<?php
		return ob_get_clean();
	}

	/**
	 * Load fields for the Update Billing Card form
	 *
	 * Outputs a placeholder for the Drop-in UI and a hidden field for the client token.
	 *
	 * @access public
	 * @since  3.3
	 * @return void
	 */
	public function update_card_fields() {

		global $rcp_membership;

		$args = array();

		if ( ! $rcp_membership instanceof RCP_Membership ) {
			return; // @todo message?
		}

		$braintree_customer_id     = $rcp_membership->get_gateway_customer_id();
		$braintree_subscription_id = $rcp_membership->get_gateway_subscription_id();

		if ( empty( $braintree_customer_id ) || empty( $braintree_subscription_id ) ) {
			echo '<p>' . __( 'You do not have an active subscription.', 'rcp' ) . '</p>';
			return;
		}

		if ( ! empty( $braintree_customer_id ) ) {
			$args['customerId'] = $braintree_customer_id;
		}

		try {
			$token = $this->braintree->clientToken()->generate( $args );
		} catch ( Exception $e ) {
			echo '<p>' . sprintf( __( 'An unexpected error occurred: %s', 'rcp' ), esc_html( $e->getMessage() ) ) . '</p>';
			return;
		}
		?>
		<div id="rcp-braintree-dropin-container"></div>
		<div id="rcp-braintree-dropin-errors"></div>
		<input type="hidden" id="rcp-braintree-client-token" name="rcp-braintree-client-token" value="<?php echo esc_attr( $token ); ?>"/>
		<input type="hidden" id="rcp-braintree-recurring-amount" value="<?php echo esc_attr( $rcp_membership->get_recurring_amount() ); ?>"/>
		<?php

	}

	/**
	 * Loads the Braintree javascript library.
	 */
	public function scripts() {
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		wp_enqueue_script( 'rcp-braintree-dropin', 'https://js.braintreegateway.com/web/dropin/1.20.0/js/dropin.min.js' );
		wp_enqueue_script( 'rcp-braintree-3d-secure', 'https://js.braintreegateway.com/web/3.50.1/js/three-d-secure.min.js' );

		wp_enqueue_script(
			'rcp-braintree',
			RCP_PLUGIN_URL . 'includes/gateways/braintree/js/braintree' . $suffix . '.js',
			array(
				'jquery',
				'rcp-braintree-dropin',
				'rcp-braintree-3d-secure'
			),
			RCP_PLUGIN_VERSION
		);

		wp_localize_script( 'rcp-braintree', 'rcp_braintree_script_options', array(
			'dropin_ui_config'       => $this->get_dropin_ui_config(),
			'payment_method_options' => $this->get_payment_method_options(),
			'please_wait'            => esc_html__( 'Please wait...', 'rcp' ),
			'user_email'             => is_user_logged_in() ? wp_get_current_user()->user_email : '',
			'try_new_payment'        => __( 'Please try a new payment method.', 'rcp' )
		) );
	}

	/**
	 * Determines whether or not 3D secure is enabled on the merchant account.
	 *
	 * @since 3.3
	 * @return bool
	 */
	protected function is_3d_secure_enabled() {

		try {
			$token = $this->braintree->clientToken()->generate();

			if ( empty( $token ) ) {
				throw new Exception();
			}

			$data = json_decode( base64_decode( $token ) );

			if ( empty( $data ) || empty( $data->threeDSecureEnabled ) ) {
				throw new Exception();
			}

			$enabled = true;
		} catch ( Exception $e ) {
			$enabled = false;
		}

		return $enabled;

	}

	/**
	 * Get drop-in UI default configuration.
	 *
	 * @link  https://braintree.github.io/braintree-web-drop-in/docs/current/module-braintree-web-drop-in.html#.create
	 *
	 * @since 3.3
	 * @return array
	 */
	protected function get_dropin_ui_config() {

		$config = array(
			'container'    => '#rcp-braintree-dropin-container',
			'locale'       => get_locale(),
			'threeDSecure' => $this->is_3d_secure_enabled()
		);

		/**
		 * Filters the default drop-in UI configuration.
		 *
		 * @since 3.3
		 */
		$config = apply_filters( 'rcp_braintree_dropin_ui_config', $config );

		return $config;

	}

	/**
	 * Get default options for `requestPaymentMethod()` call.
	 *
	 * @link  https://braintree.github.io/braintree-web-drop-in/docs/current/Dropin.html#requestPaymentMethod
	 *
	 * @since 3.3
	 * @return array
	 */
	protected function get_payment_method_options() {

		$options = array();

		if ( $this->is_3d_secure_enabled() ) {
			$options['threeDSecure'] = array(
				'amount'                => 0.00,
				// This gets set in the JavaScript.
				'email'                 => is_user_logged_in() ? wp_get_current_user()->user_email : '',
				// If user is not logged in, JS will set this.
				'additionalInformation' => array(
					'productCode'       => 'DIG',
					// Digital product
					'deliveryTimeframe' => '01',
					// Immediate delivery
					'deliveryEmail'     => is_user_logged_in() ? wp_get_current_user()->user_email : '',
					// If user is not logged in, JS will set this.
				)
			);
		}

		/**
		 * Filters the payment method options.
		 *
		 * @since 3.3
		 */
		$options = apply_filters( 'rcp_braintree_payment_method_options', $options );

		return $options;

	}

}
