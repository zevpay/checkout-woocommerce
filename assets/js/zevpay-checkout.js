/**
 * ZevPay Checkout for WooCommerce — Inline Modal Handler
 *
 * Loads the ZevPay inline checkout SDK and opens the payment modal
 * on the WooCommerce pay-for-order page.
 *
 * @package ZevPay_Checkout_For_WooCommerce
 */

/* global jQuery, wc_zevpay_checkout_params */
( function( $ ) {
	'use strict';

	if ( typeof wc_zevpay_checkout_params === 'undefined' ) {
		return;
	}

	var params    = wc_zevpay_checkout_params;
	var sdkLoaded = false;
	var sdkUrl    = 'https://js.zevpaycheckout.com/v1/inline.js';

	/**
	 * Load the ZevPay inline checkout SDK script.
	 */
	function loadSdk( callback ) {
		if ( sdkLoaded ) {
			callback();
			return;
		}

		var script  = document.createElement( 'script' );
		script.src  = sdkUrl;
		script.async = true;

		script.onload = function() {
			sdkLoaded = true;
			callback();
		};

		script.onerror = function() {
			setButtonState( false );
			alert( 'Unable to load the ZevPay payment SDK. Please refresh the page and try again.' );
		};

		document.head.appendChild( script );
	}

	/**
	 * Toggle the pay button between loading and ready states.
	 */
	function setButtonState( loading ) {
		var $button = $( '#zevpay-checkout-button' );

		if ( loading ) {
			$button.addClass( 'loading' ).prop( 'disabled', true );
		} else {
			$button.removeClass( 'loading' ).prop( 'disabled', false );
		}
	}

	/**
	 * Block the page while processing.
	 */
	function blockPage() {
		if ( typeof $.fn.block === 'function' ) {
			$( '.zevpay-checkout-container' ).block( {
				message: null,
				overlayCSS: { background: '#fff', opacity: 0.6 }
			} );
		}
	}

	/**
	 * Unblock the page.
	 */
	function unblockPage() {
		if ( typeof $.fn.unblock === 'function' ) {
			$( '.zevpay-checkout-container' ).unblock();
		}
	}

	/**
	 * Open the ZevPay checkout modal.
	 */
	function openCheckout() {
		if ( typeof window.ZevPay === 'undefined' || typeof window.ZevPay.ZevPayCheckout === 'undefined' ) {
			alert( 'ZevPay SDK not loaded. Please refresh the page.' );
			return;
		}

		var checkoutConfig = {
			apiKey: params.publicKey,
			email: params.email,
			amount: parseInt( params.amount, 10 ),
			currency: params.currency || 'NGN',
			reference: params.reference || '',
			firstName: params.firstName || '',
			lastName: params.lastName || '',
			metadata: params.metadata ? JSON.parse( params.metadata ) : {},
			onSuccess: function( response ) {
				handleSuccess( response );
			},
			onClose: function() {
				setButtonState( false );
				unblockPage();
			}
		};

		// Add payment methods if configured.
		if ( params.paymentMethods && params.paymentMethods !== 'all' ) {
			checkoutConfig.paymentMethods = params.paymentMethods;
		}

		try {
			var handler = new window.ZevPay.ZevPayCheckout();
			handler.checkout( checkoutConfig );
		} catch ( e ) {
			setButtonState( false );
			alert( 'Error opening checkout: ' + e.message );
		}
	}

	/**
	 * Handle a successful payment from the modal.
	 */
	function handleSuccess( response ) {
		blockPage();

		var sessionId = response.session_id || response.sessionId || '';
		var reference = response.reference || '';

		$.ajax( {
			url: params.ajaxUrl,
			method: 'POST',
			contentType: 'application/json',
			data: JSON.stringify( {
				session_id: sessionId,
				reference: reference,
				order_id: params.orderId,
				nonce: params.nonce
			} ),
			success: function( result ) {
				if ( result.success && result.data && result.data.redirect ) {
					window.location.href = result.data.redirect;
				} else {
					var msg = ( result.data && result.data.message ) ? result.data.message : 'Verification failed.';
					alert( msg );
					unblockPage();
					setButtonState( false );
				}
			},
			error: function( xhr ) {
				var msg = 'Payment verification failed.';
				try {
					var resp = JSON.parse( xhr.responseText );
					if ( resp.data && resp.data.message ) {
						msg = resp.data.message;
					}
				} catch ( e ) {
					// Use default message.
				}
				alert( msg );
				unblockPage();
				setButtonState( false );
			}
		} );
	}

	/**
	 * Initialize on DOM ready.
	 */
	$( function() {
		var $button = $( '#zevpay-checkout-button' );

		if ( ! $button.length ) {
			return;
		}

		$button.on( 'click', function( e ) {
			e.preventDefault();
			setButtonState( true );

			loadSdk( function() {
				openCheckout();
			} );
		} );
	} );

} )( jQuery );
