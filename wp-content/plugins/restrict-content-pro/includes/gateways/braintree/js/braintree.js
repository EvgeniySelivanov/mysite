/* global rcp_braintree_script_options */

jQuery( function( $ ) {

	/**
	 * Braintree registration
	 */
	var RCP_Braintree_Registration = {

		/**
		 * Braintree drop-in UI instance
		 */
		dropinInstance: false,

		/**
		 * Whether or not card details have been entered
		 */
		hasCardDetails: false,

		/**
		 * Initialize
		 */
		init: function () {

			$( 'body' ).on( 'rcp_gateway_loaded', RCP_Braintree_Registration.mountUI );
			$( '#rcp_submit' ).on( 'click', RCP_Braintree_Registration.maybeBlockSubmit );
			$( 'body' ).on( 'rcp_registration_form_processed', RCP_Braintree_Registration.tokenizePayment );

		},

		/**
		 * Mount the drop-in UI when the gateway is loaded
		 *
		 * @param e
		 * @param gateway
		 */
		mountUI: function( e, gateway ) {

			if ( ! document.getElementById( 'rcp-braintree-client-token' ) ) {
				return;
			}

			rcp_braintree_script_options.dropin_ui_config.authorization = $( '#rcp-braintree-client-token' ).val();

			braintree.dropin.create( rcp_braintree_script_options.dropin_ui_config ).then( function( dropinInstance ) {
				RCP_Braintree_Registration.dropinInstance = dropinInstance;

				// Flag as having payment details or not.
				if ( dropinInstance.isPaymentMethodRequestable() ) {
					RCP_Braintree_Registration.hasCardDetails = true;
				}
				dropinInstance.on( 'paymentMethodRequestable', function ( requestableEvent ) {
					RCP_Braintree_Registration.hasCardDetails = true;
				} );
				dropinInstance.on( 'noPaymentMethodRequestable', function ( requestableEvent ) {
					RCP_Braintree_Registration.hasCardDetails = false;
				} );

			} ).catch( function( error ) {
				// Handle errors from creating drop-in.
				rcpBraintreeHandleError( error );
			} );

		},

		/**
		 * Prevent form submission if card details haven't been filled out yet
		 *
		 * @param e
		 */
		maybeBlockSubmit: function ( e ) {

			if ( 'braintree' === rcp_get_gateway().val() && document.getElementById( 'rcp-braintree-client-token' ) && ! RCP_Braintree_Registration.hasCardDetails ) {
				e.stopPropagation();
				rcpBraintreeHandleError( rcp_script_options.enter_card_details );
				return false;
			}

		},

		/**
		 * Collect card details, handle 3D secure if available, and tokenize the payment method
		 *
		 * @param event
		 * @param form
		 * @param response
		 */
		tokenizePayment: function( event, form, response ) {

			if ( ! document.getElementById( 'rcp-braintree-client-token' ) || 'braintree' !== rcp_get_gateway().val() ) {
				return;
			}

			let paymentMethodOptions = rcp_braintree_script_options.payment_method_options;

			// Set email address(es) for logged out customers.
			if ( 'undefined' !== typeof( paymentMethodOptions.threeDSecure ) && '' === paymentMethodOptions.threeDSecure.email ) {
				paymentMethodOptions.threeDSecure.email = $( '#rcp_user_email' ).val();
			}
			if ( 'undefined' !== typeof( paymentMethodOptions.threeDSecure ) && 'undefined' !== typeof( paymentMethodOptions.threeDSecure.additionalInformation ) && '' === paymentMethodOptions.threeDSecure.additionalInformation.deliveryEmail ) {
				paymentMethodOptions.threeDSecure.additionalInformation.deliveryEmail = $( '#rcp_user_email' ).val();
			}

			// Set authorization amount.
			if ( 'undefined' !== typeof paymentMethodOptions.threeDSecure ) {
				paymentMethodOptions.threeDSecure.amount = (response.total > 0) ? response.total : response.recurring_total;
			}

			RCP_Braintree_Registration.dropinInstance.requestPaymentMethod( paymentMethodOptions ).then( function( payload ) {
				if ( payload.liabilityShiftPossible && ! payload.liabilityShifted ) {
					// 3D secure was possible, but failed.

					// Clear the payment method.
					RCP_Braintree_Registration.dropinInstance.clearSelectedPaymentMethod();

					// Display error message.
					rcpBraintreeHandleError( rcp_braintree_script_options.try_new_payment );
				} else {
					// Payment was successfully tokenized. Set up the nonce so we can use it for processing transactions server-side.
					$( form ).find( '#rcp_submit_wrap' ).append( '<input type="hidden" name="payment_method_nonce" value="' + payload.nonce + '"/>' );

					// Submit registration.
					rcp_submit_registration_form( form, response );
				}
			} ).catch( function( error ) {
				// Handle errors from payment method request.
				rcpBraintreeHandleError( error );
			} );

		}

	};

	RCP_Braintree_Registration.init();

	/**
	 * Update card details
	 */
	let RCP_Braintree_Update_Card = {

		container: false,

		recurringAmount: 0.00,

		hasCardDetails: false,

		init: function () {
			RCP_Braintree_Update_Card.container = $( '#rcp_update_card_form' );

			if ( ! RCP_Braintree_Update_Card.container.length ) {
				return;
			}

			RCP_Braintree_Update_Card.mountUI();

			RCP_Braintree_Update_Card.container.on( 'submit', RCP_Braintree_Update_Card.tokenizePayment );
		},

		/**
		 * Mount the drop-in UI
		 */
		mountUI: function() {
			if ( ! document.getElementById( 'rcp-braintree-client-token' ) ) {
				return;
			}

			rcp_braintree_script_options.dropin_ui_config.authorization = $( '#rcp-braintree-client-token' ).val();

			let dropinArgs = rcp_braintree_script_options.dropin_ui_config;

			/*
			 * Enabling this would allow customers to delete their saved payment methods. I've commented it out for now
			 * because if the customer deletes their CURRENT payment method then Braintree will automatically cancel
			 * the subscription, which is a bit annoying.
			 */
			//dropinArgs.vaultManager = true;

			/*
			 * We set `preselectVaultedPaymentMethod` to false because we can't yet configure which one is pre-selected
			 * and we don't want to confuse anyone by having the wrong payment method pre-selected.
			 */
			dropinArgs.preselectVaultedPaymentMethod = false;

			braintree.dropin.create( dropinArgs ).then( function( dropinInstance ) {
				RCP_Braintree_Update_Card.dropinInstance = dropinInstance;

				// Flag as having payment details or not.
				if ( dropinInstance.isPaymentMethodRequestable() ) {
					RCP_Braintree_Update_Card.hasCardDetails = true;
				}
				dropinInstance.on( 'paymentMethodRequestable', function ( requestableEvent ) {
					RCP_Braintree_Update_Card.hasCardDetails = true;
				} );
				dropinInstance.on( 'noPaymentMethodRequestable', function ( requestableEvent ) {
					RCP_Braintree_Update_Card.hasCardDetails = false;
				} );

			} ).catch( function( error ) {
				// Handle errors from creating drop-in.
				rcpBraintreeHandleError( error );
			} );
		},

		/**
		 * Disable the submit button and change the text to "Please wait..."
		 */
		disableButton: function() {
			let button = RCP_Braintree_Update_Card.container.find( '#rcp_submit' );

			button.prop( 'disabled', true ).data( 'text', button.val() ).val( rcp_braintree_script_options.please_wait );
		},

		/**
		 * Enable the submit button and re-set the text back to the original value
		 */
		enableButton: function() {
			let button = RCP_Braintree_Update_Card.container.find( '#rcp_submit' );

			button.prop( 'disabled', false ).val( button.data( 'text' ) );
		},

		/**
		 * Tokenize the payment method
		 * @param e
		 */
		tokenizePayment: function ( e ) {

			e.preventDefault();

			if ( ! RCP_Braintree_Update_Card.hasCardDetails ) {
				rcpBraintreeHandleError( rcp_script_options.enter_card_details );

				return false;
			}

			// Clear errors.
			$( '#rcp-braintree-dropin-errors' ).empty();

			RCP_Braintree_Update_Card.disableButton();

			let paymentMethodOptions = rcp_braintree_script_options.payment_method_options;

			// Set authorization amount.
			paymentMethodOptions.threeDSecure.amount = $( '#rcp-braintree-recurring-amount' ).val();

			RCP_Braintree_Update_Card.dropinInstance.requestPaymentMethod( paymentMethodOptions ).then( function( payload ) {
				if ( payload.liabilityShiftPossible && ! payload.liabilityShifted ) {
					// 3D secure was possible, but failed.

					// Clear the payment method.
					RCP_Braintree_Update_Card.dropinInstance.clearSelectedPaymentMethod();

					// Display error message.
					throw rcp_braintree_script_options.try_new_payment;
				} else {
					// Payment was successfully tokenized. Set up the nonce so we can use it for processing transactions server-side.
					RCP_Braintree_Update_Card.container.append( '<input type="hidden" name="payment_method_nonce" value="' + payload.nonce + '"/>' );

					RCP_Braintree_Update_Card.container.off( 'submit', RCP_Braintree_Update_Card.tokenizePayment ).submit();
				}
			} ).catch( function( error ) {
				// Handle errors from payment method request.
				rcpBraintreeHandleError( error );
				RCP_Braintree_Update_Card.enableButton();
				return false;
			} );

		}

	};
	RCP_Braintree_Update_Card.init();

} );

/**
 * Handle Braintree errors
 * @param {string} error Error message.
 */
function rcpBraintreeHandleError( error ) {
	let $ = jQuery;
	let form = $( '#rcp_registration_form' );
	let errorWrapper = $( '#rcp-braintree-dropin-errors' );

	errorWrapper.empty().append( '<div class="rcp_message error" role="list"><p class="rcp_error" role="listitem">' + error + '</p>' );

	if ( form.length > 0 ) {
		form.unblock();
		$( '#rcp_submit' ).val( rcp_script_options.register );
	}

	rcp_processing = false;
}
