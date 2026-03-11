( function() {
	var wpI18n    = window.wp && window.wp.i18n ? window.wp.i18n : null;
	var wpElement = window.wp && window.wp.element ? window.wp.element : null;
	var wc        = window.wc || {};
	var registry  = wc.wcBlocksRegistry || null;
	var settingsStore = wc.wcSettings || null;

	if ( ! wpElement || ! registry || ! settingsStore ) {
		return;
	}

	var __ = wpI18n ? wpI18n.__ : function( text ) { return text; };
	var el = wpElement.createElement;
	var Fragment = wpElement.Fragment;
	var registerPaymentMethod = registry.registerPaymentMethod;
	var settings = settingsStore.getSetting( 'zevpay_checkout_data', {} );

	var title       = settings.title || __( 'ZevPay Checkout', 'zevpay-checkout-for-woocommerce' );
	var description = settings.description || '';
	var isEnabled   = typeof settings.isEnabled === 'undefined' ? true : settings.isEnabled;

	var Icon = function() {
		return el( 'img', {
			src: settings.logoUrl,
			alt: title,
			style: { height: '20px', width: '20px', marginRight: '8px' },
		} );
	};

	var Label = function() {
		return el(
			'span',
			{ style: { display: 'flex', alignItems: 'center', gap: '8px' } },
			settings.logoUrl ? el( Icon ) : null,
			el( 'span', null, title )
		);
	};

	var Content = function() {
		return el( Fragment, null, description ? el( 'p', null, description ) : null );
	};

	registerPaymentMethod( {
		name: 'zevpay_checkout',
		label: el( Label ),
		content: el( Content ),
		edit: el( Content ),
		canMakePayment: function() {
			return Boolean( isEnabled );
		},
		ariaLabel: title,
		supports: {
			showSavedCards: false,
			showSaveOption: false,
			features: settings.supports || [],
		},
	} );
} )();
