<?php

defined('ABSPATH') || exit;

/**
 * WooCommerce payment gateway for ZevPay Checkout.
 *
 * Supports two checkout modes:
 * - Inline: opens the ZevPay modal on the order-pay page.
 * - Standard: redirects the customer to the ZevPay hosted checkout page.
 */
class WC_Gateway_ZevPay_Checkout extends WC_Payment_Gateway {

	/**
	 * API public key.
	 *
	 * @var string
	 */
	public $public_key;

	/**
	 * API secret key.
	 *
	 * @var string
	 */
	protected $secret_key;

	/**
	 * Webhook signing secret.
	 *
	 * @var string
	 */
	protected $webhook_secret;

	/**
	 * Checkout mode: 'inline' or 'standard'.
	 *
	 * @var string
	 */
	public $checkout_mode;

	/**
	 * Allowed payment methods.
	 *
	 * @var array
	 */
	public $payment_methods;

	/**
	 * Whether to pass order details in standard mode.
	 *
	 * @var bool
	 */
	protected $pass_order_details;

	/**
	 * Whether logging is enabled.
	 *
	 * @var bool
	 */
	protected $logging_enabled;

	/**
	 * Logger instance.
	 *
	 * @var WC_Logger
	 */
	protected $logger;

	/**
	 * ZevPay PHP SDK client instance.
	 *
	 * @var \ZevPay\ZevPay|null
	 */
	protected $zevpay_client;

	/**
	 * Supported webhook completion events.
	 *
	 * @var string[]
	 */
	protected $webhook_completion_events = array(
		'charge.success',
		'payment.virtual_payid.completed',
		'payment.checkout_payid.completed',
		'payment.virtual_account.completed',
		'payment.checkout_virtual_account.completed',
	);

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                 = 'zevpay_checkout';
		$this->method_title       = __( 'ZevPay Checkout', 'zevpay-checkout-for-woocommerce' );
		$this->method_description = __( 'Accept bank transfer, PayID, and card payments with ZevPay Checkout.', 'zevpay-checkout-for-woocommerce' );
		$this->has_fields         = false;
		$this->supports           = array( 'products' );
		$this->icon               = ZEVPAY_CHECKOUT_URL . '/assets/images/zevpay.png';

		$this->init_form_fields();
		$this->init_settings();

		$this->title              = $this->get_option( 'title' );
		$this->description        = $this->get_option( 'description' );
		$this->enabled            = $this->get_option( 'enabled' );
		$this->public_key         = trim( $this->get_option( 'public_key' ) );
		$this->secret_key         = trim( $this->get_option( 'secret_key' ) );
		$this->webhook_secret     = trim( $this->get_option( 'webhook_secret' ) );
		$this->checkout_mode      = $this->get_option( 'checkout_mode', 'inline' );
		$this->payment_methods    = $this->get_option( 'payment_methods', array() );
		$this->pass_order_details = 'yes' === $this->get_option( 'pass_order_details', 'yes' );
		$this->logging_enabled    = 'yes' === $this->get_option( 'enable_logging', 'no' );
		$this->logger             = wc_get_logger();

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
		add_action( 'woocommerce_api_wc_gateway_zevpay_checkout', array( $this, 'handle_checkout_callback' ) );
		add_action( 'woocommerce_api_zevpay_checkout_webhook', array( $this, 'handle_webhook' ) );
		add_action( 'woocommerce_api_zevpay_checkout_standard_callback', array( $this, 'handle_standard_callback' ) );

		// Hide WooCommerce default order summary on pay-for-order page for our gateway.
		add_action( 'wp', array( $this, 'hide_default_order_summary' ) );
	}

	/**
	 * Render admin options page.
	 */
	public function admin_options() {
		$webhook_url = WC()->api_request_url( 'zevpay_checkout_webhook' );
		?>
		<h2>
			<?php echo esc_html( $this->get_method_title() ); ?>
			<?php
			if ( function_exists( 'wc_back_link' ) ) {
				wc_back_link( __( 'Return to payments', 'zevpay-checkout-for-woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) );
			}
			?>
		</h2>

		<p style="margin-top:0;">
			<a href="https://zevpay.ng" target="_blank"
				rel="noopener noreferrer"><?php esc_html_e( 'ZevPay', 'zevpay-checkout-for-woocommerce' ); ?></a>
			<span style="margin: 0 8px;">|</span>
			<a href="https://docs.zevpaycheckout.com" target="_blank"
				rel="noopener noreferrer"><?php esc_html_e( 'API Docs', 'zevpay-checkout-for-woocommerce' ); ?></a>
			<span style="margin: 0 8px;">|</span>
			<a href="https://dashboard.zevpaycheckout.com" target="_blank"
				rel="noopener noreferrer"><?php esc_html_e( 'Dashboard', 'zevpay-checkout-for-woocommerce' ); ?></a>
		</p>

		<?php if ( $this->method_description ) : ?>
			<p><?php echo wp_kses_post( $this->method_description ); ?></p>
		<?php endif; ?>

		<div class="notice notice-info" style="margin: 15px 0;">
			<p>
				<strong><?php esc_html_e( 'Webhook configuration', 'zevpay-checkout-for-woocommerce' ); ?></strong><br />
				<?php esc_html_e( 'Copy the URL below into your ZevPay Dashboard so payment notifications are delivered. Your endpoint must return HTTP 200 when the payload is accepted.', 'zevpay-checkout-for-woocommerce' ); ?>
			</p>
			<code style="display: inline-block; padding: 6px 10px; background: #fff; border: 1px solid #ccd0d4; border-radius: 3px;">
				<?php echo esc_html( $webhook_url ); ?>
			</code>
		</div>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>
		<?php
	}

	/**
	 * Gateway settings fields.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled'              => array(
				'title'       => __( 'Enable/Disable', 'zevpay-checkout-for-woocommerce' ),
				'label'       => __( 'Enable ZevPay Checkout', 'zevpay-checkout-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable ZevPay Checkout as a payment option.', 'zevpay-checkout-for-woocommerce' ),
				'default'     => 'no',
			),
			'title'                => array(
				'title'       => __( 'Title', 'zevpay-checkout-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Controls the payment method title seen during checkout.', 'zevpay-checkout-for-woocommerce' ),
				'default'     => __( 'ZevPay Checkout', 'zevpay-checkout-for-woocommerce' ),
			),
			'description'          => array(
				'title'       => __( 'Description', 'zevpay-checkout-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => __( 'Payment method description shown on checkout forms.', 'zevpay-checkout-for-woocommerce' ),
				'default'     => __( 'Pay securely using ZevPay Checkout.', 'zevpay-checkout-for-woocommerce' ),
			),
			'public_key'           => array(
				'title'       => __( 'Public API Key', 'zevpay-checkout-for-woocommerce' ),
				'type'        => 'text',
				'description' => __( 'Your ZevPay public key (pk_live_* or pk_test_*).', 'zevpay-checkout-for-woocommerce' ),
				'default'     => '',
			),
			'secret_key'           => array(
				'title'       => __( 'Secret API Key', 'zevpay-checkout-for-woocommerce' ),
				'type'        => 'password',
				'description' => __( 'Your ZevPay secret key (sk_live_* or sk_test_*).', 'zevpay-checkout-for-woocommerce' ),
				'default'     => '',
			),
			'webhook_secret'       => array(
				'title'       => __( 'Webhook Secret', 'zevpay-checkout-for-woocommerce' ),
				'type'        => 'password',
				'description' => __( 'Your webhook signing secret from the ZevPay Dashboard. Used to verify incoming webhook events.', 'zevpay-checkout-for-woocommerce' ),
				'default'     => '',
			),
			'checkout_mode'        => array(
				'title'       => __( 'Checkout Mode', 'zevpay-checkout-for-woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Inline opens a payment modal on your site. Standard redirects the customer to the ZevPay hosted checkout page.', 'zevpay-checkout-for-woocommerce' ),
				'default'     => 'inline',
				'options'     => array(
					'inline'   => __( 'Inline Modal', 'zevpay-checkout-for-woocommerce' ),
					'standard' => __( 'Standard (Redirect)', 'zevpay-checkout-for-woocommerce' ),
				),
			),
			'payment_methods'      => array(
				'title'       => __( 'Payment Methods', 'zevpay-checkout-for-woocommerce' ),
				'type'        => 'multiselect',
				'description' => __( 'Select which payment methods to offer. Leave empty to use your ZevPay Dashboard settings.', 'zevpay-checkout-for-woocommerce' ),
				'default'     => array(),
				'class'       => 'wc-enhanced-select',
				'css'         => 'width: 400px;',
				'options'     => array(
					'bank_transfer' => __( 'Bank Transfer', 'zevpay-checkout-for-woocommerce' ),
					'payid'         => __( 'PayID', 'zevpay-checkout-for-woocommerce' ),
					'card'          => __( 'Card', 'zevpay-checkout-for-woocommerce' ),
				),
			),
			'pass_order_details'   => array(
				'title'       => __( 'Pass Order Details', 'zevpay-checkout-for-woocommerce' ),
				'label'       => __( 'Send order details to ZevPay hosted checkout (Standard mode)', 'zevpay-checkout-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'When using Standard mode, forward line items, customer name, and metadata to the hosted checkout page.', 'zevpay-checkout-for-woocommerce' ),
				'default'     => 'yes',
			),
			'enable_logging'       => array(
				'title'       => __( 'Logging', 'zevpay-checkout-for-woocommerce' ),
				'label'       => __( 'Enable debug logging', 'zevpay-checkout-for-woocommerce' ),
				'type'        => 'checkbox',
				'description' => __( 'Logs ZevPay Checkout events to WooCommerce > Status > Logs.', 'zevpay-checkout-for-woocommerce' ),
				'default'     => 'no',
			),
		);
	}

	/**
	 * Display additional information on the checkout page.
	 */
	public function payment_fields() {
		if ( $this->description ) {
			echo wp_kses_post( wpautop( wptexturize( $this->description ) ) );
		}
	}

	/**
	 * Show admin warnings.
	 */
	public function admin_notices() {
		if ( 'yes' !== $this->enabled ) {
			return;
		}

		if ( ! $this->public_key || ! $this->secret_key ) {
			printf(
				'<div class="error"><p>%s</p></div>',
				esc_html__( 'ZevPay Checkout: Enter your public and secret keys to start accepting payments.', 'zevpay-checkout-for-woocommerce' )
			);
		}

		if ( ZEVPAY_CHECKOUT_SUPPORTED_CURRENCY !== get_woocommerce_currency() ) {
			printf(
				'<div class="error"><p>%s</p></div>',
				esc_html__( 'ZevPay Checkout only supports Nigerian Naira (NGN). Update your store currency to enable the gateway.', 'zevpay-checkout-for-woocommerce' )
			);
		}
	}

	/**
	 * Check if gateway can be used.
	 *
	 * @return bool
	 */
	public function is_available() {
		$currency    = get_woocommerce_currency();
		$currency_ok = ZEVPAY_CHECKOUT_SUPPORTED_CURRENCY === $currency;
		$keys_ok     = ! empty( $this->public_key ) && ! empty( $this->secret_key );
		$enabled     = 'yes' === $this->enabled;

		$is_available = $enabled && $currency_ok && $keys_ok;

		if ( ! $is_available ) {
			$reasons = array();
			if ( ! $enabled ) {
				$reasons[] = 'gateway disabled';
			}
			if ( ! $currency_ok ) {
				$reasons[] = sprintf( 'unsupported currency (%s)', $currency );
			}
			if ( ! $keys_ok ) {
				$reasons[] = 'missing keys';
			}
			$this->log( 'Gateway unavailable: ' . implode( ', ', $reasons ) );
		}

		return $is_available;
	}

	/**
	 * Process the payment.
	 *
	 * Inline mode: redirect to pay-for-order page where the modal opens.
	 * Standard mode: initialize a session via SDK and redirect to ZevPay hosted page.
	 *
	 * @param int $order_id Order ID.
	 * @return array
	 */
	public function process_payment( $order_id ) {
		$order = wc_get_order( $order_id );

		$this->log( sprintf( 'process_payment called for order %d. checkout_mode=%s', $order_id, $this->checkout_mode ) );

		if ( 'standard' === $this->checkout_mode ) {
			return $this->process_standard_payment( $order );
		}

		return $this->process_inline_payment( $order );
	}

	/**
	 * Inline mode: redirect to pay-for-order page.
	 *
	 * @param WC_Order $order Order instance.
	 * @return array
	 */
	protected function process_inline_payment( WC_Order $order ) {
		$this->ensure_order_reference( $order );
		$order->save();

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url( true ),
		);
	}

	/**
	 * Standard mode: initialize session via PHP SDK and redirect.
	 *
	 * @param WC_Order $order Order instance.
	 * @return array
	 */
	protected function process_standard_payment( WC_Order $order ) {
		$this->log( sprintf( 'process_standard_payment: order %d, secret_key=%s', $order->get_id(), substr( $this->secret_key, 0, 10 ) . '...' ) );

		$reference = $this->ensure_order_reference( $order );
		$order->save();

		$callback_url = WC()->api_request_url( 'zevpay_checkout_standard_callback' );

		$amount_kobo = (int) round( $order->get_total() * 100 );

		$params = array(
			'amount'       => $amount_kobo,
			'email'        => $order->get_billing_email(),
			'currency'     => 'NGN',
			'reference'    => $reference,
			'callback_url' => add_query_arg(
				array(
					'order_id'  => $order->get_id(),
					'order_key' => $order->get_order_key(),
				),
				$callback_url
			),
		);

		if ( ! empty( $this->payment_methods ) ) {
			$params['payment_methods'] = $this->payment_methods;
		}

		if ( $this->pass_order_details ) {
			$params['customer_name'] = $order->get_formatted_billing_full_name();
			$params['metadata']      = array(
				'order_id'  => $order->get_id(),
				'order_key' => $order->get_order_key(),
				'source'    => 'woocommerce',
			);
		}

		try {
			$client  = $this->get_zevpay_client();
			$session = $client->checkout->initialize( $params );

			$this->log( 'Standard session initialized: ' . wp_json_encode( $session ) );

			$checkout_url = isset( $session['checkout_url'] ) ? $session['checkout_url'] : '';
			$session_id   = isset( $session['session_id'] ) ? $session['session_id'] : '';

			if ( ! $checkout_url ) {
				wc_add_notice( __( 'Unable to initialize checkout session. Please try again.', 'zevpay-checkout-for-woocommerce' ), 'error' );
				return array( 'result' => 'fail' );
			}

			$order->update_meta_data( '_zevpay_checkout_session_id', sanitize_text_field( $session_id ) );
			$order->save();

			return array(
				'result'   => 'success',
				'redirect' => $checkout_url,
			);
		} catch ( \Exception $e ) {
			$this->log( 'Standard session initialization failed: ' . $e->getMessage() );
			wc_add_notice( __( 'Payment initialization failed. Please try again.', 'zevpay-checkout-for-woocommerce' ), 'error' );
			return array( 'result' => 'fail' );
		}
	}

	/**
	 * Enqueue scripts on the pay-for-order page.
	 */
	public function payment_scripts() {
		if ( ! $this->public_key || 'yes' !== $this->enabled ) {
			return;
		}

		// Only enqueue for inline mode.
		if ( 'inline' !== $this->checkout_mode ) {
			return;
		}

		if ( is_checkout_pay_page() ) {
			$this->enqueue_order_pay_scripts();
		}
	}

	/**
	 * Enqueue scripts for pay-for-order page (inline mode).
	 */
	protected function enqueue_order_pay_scripts() {
		$order_id = absint( get_query_var( 'order-pay' ) );

		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			return;
		}

		$reference  = $this->ensure_order_reference( $order );
		$order->save();

		$amount_kobo = (int) round( $order->get_total() * 100 );

		$metadata = array(
			'order_id'  => $order->get_id(),
			'order_key' => $order->get_order_key(),
			'source'    => 'woocommerce',
			'reference' => $reference,
		);

		$params = array(
			'publicKey'      => $this->public_key,
			'amount'         => $amount_kobo,
			'currency'       => $order->get_currency(),
			'reference'      => $reference,
			'email'          => $order->get_billing_email(),
			'firstName'      => $order->get_billing_first_name(),
			'lastName'       => $order->get_billing_last_name(),
			'orderId'        => $order->get_id(),
			'nonce'          => wp_create_nonce( 'zevpay_checkout_verify_' . $order->get_id() ),
			'metadata'       => wp_json_encode( $metadata ),
			'logoUrl'        => ZEVPAY_CHECKOUT_URL . '/assets/images/zevpay.png',
			'ajaxUrl'        => WC()->api_request_url( 'wc_gateway_zevpay_checkout' ),
			'orderUrl'       => $this->get_return_url( $order ),
			'cancelUrl'      => $order->get_cancel_order_url(),
			'payButtonText'  => __( 'Pay with ZevPay', 'zevpay-checkout-for-woocommerce' ),
			'paymentMethods' => ! empty( $this->payment_methods ) ? $this->payment_methods : 'all',
			'amountText'     => wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ),
		);

		wp_enqueue_script( 'jquery' );
		wp_enqueue_script(
			'wc-zevpay-checkout',
			ZEVPAY_CHECKOUT_URL . '/assets/js/zevpay-checkout.js',
			array( 'jquery' ),
			ZEVPAY_CHECKOUT_VERSION,
			true
		);
		wp_localize_script( 'wc-zevpay-checkout', 'wc_zevpay_checkout_params', $params );
	}

	/**
	 * Output the payment button on the order pay page (inline mode).
	 *
	 * @param int $order_id Order ID.
	 */
	public function receipt_page( $order_id ) {
		$order = wc_get_order( $order_id );

		// Standard mode: redirect to ZevPay hosted checkout instead of showing the pay button.
		if ( 'standard' === $this->checkout_mode ) {
			try {
				$result = $this->process_standard_payment( $order );
				if ( 'success' === $result['result'] && ! empty( $result['redirect'] ) ) {
					wp_redirect( $result['redirect'] );
					exit;
				}
			} catch ( \Exception $e ) {
				$this->log( 'Receipt page standard redirect failed: ' . $e->getMessage() );
			}
			// If redirect failed, show a message.
			echo '<p>' . esc_html__( 'Unable to redirect to payment page. Please try placing your order again.', 'zevpay-checkout-for-woocommerce' ) . '</p>';
			echo '<p><a href="' . esc_url( wc_get_checkout_url() ) . '">' . esc_html__( 'Return to checkout', 'zevpay-checkout-for-woocommerce' ) . '</a></p>';
			return;
		}

		wp_enqueue_style(
			'zevpay-checkout-style',
			ZEVPAY_CHECKOUT_URL . '/assets/css/zevpay-checkout.css',
			array(),
			ZEVPAY_CHECKOUT_VERSION
		);

		?>
		<div class="zevpay-checkout-container">
			<div class="zevpay-order-summary">
				<h3><?php esc_html_e( 'Order Summary', 'zevpay-checkout-for-woocommerce' ); ?></h3>
				<div class="zevpay-order-details">
					<div class="zevpay-order-item">
						<span class="zevpay-order-item-label"><?php esc_html_e( 'Order Number', 'zevpay-checkout-for-woocommerce' ); ?></span>
						<span class="zevpay-order-item-value">#<?php echo esc_html( $order->get_order_number() ); ?></span>
					</div>
					<div class="zevpay-order-item">
						<span class="zevpay-order-item-label"><?php esc_html_e( 'Date', 'zevpay-checkout-for-woocommerce' ); ?></span>
						<span class="zevpay-order-item-value"><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?></span>
					</div>
					<div class="zevpay-order-item">
						<span class="zevpay-order-item-label"><?php esc_html_e( 'Total', 'zevpay-checkout-for-woocommerce' ); ?></span>
						<span class="zevpay-order-item-value zevpay-amount"><?php echo wp_kses_post( wc_price( $order->get_total(), array( 'currency' => $order->get_currency() ) ) ); ?></span>
					</div>
					<div class="zevpay-order-item">
						<span class="zevpay-order-item-label"><?php esc_html_e( 'Payment Method', 'zevpay-checkout-for-woocommerce' ); ?></span>
						<span class="zevpay-order-item-value"><?php esc_html_e( 'ZevPay Checkout', 'zevpay-checkout-for-woocommerce' ); ?></span>
					</div>
				</div>
			</div>

			<div class="zevpay-payment-instructions">
				<img src="<?php echo esc_url( ZEVPAY_CHECKOUT_URL . '/assets/images/zevpay.png' ); ?>" alt="ZevPay" />
				<p><?php esc_html_e( 'Click the button below to complete your payment with ZevPay Checkout.', 'zevpay-checkout-for-woocommerce' ); ?></p>
			</div>

			<div class="zevpay-action-buttons">
				<button class="zevpay-pay-button" id="zevpay-checkout-button">
					<span class="zevpay-loading"></span>
					<?php esc_html_e( 'Pay with ZevPay', 'zevpay-checkout-for-woocommerce' ); ?>
				</button>
				<a class="zevpay-cancel-button" href="<?php echo esc_url( $order->get_cancel_order_url() ); ?>">
					<?php esc_html_e( 'Cancel order & restore cart', 'zevpay-checkout-for-woocommerce' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Handle AJAX verification from inline checkout.
	 */
	public function handle_checkout_callback() {
		$payload = file_get_contents( 'php://input' );
		$data    = json_decode( $payload, true );

		if ( json_last_error() !== JSON_ERROR_NONE || ! is_array( $data ) ) {
			$data = wp_unslash( $_REQUEST );
		}

		$session_id = isset( $data['session_id'] ) ? sanitize_text_field( $data['session_id'] ) : '';
		$reference  = isset( $data['reference'] ) ? sanitize_text_field( $data['reference'] ) : '';
		$order_id   = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : 0;
		$nonce      = isset( $data['nonce'] ) ? sanitize_text_field( $data['nonce'] ) : '';

		if ( ! $order_id || ! wp_verify_nonce( $nonce, 'zevpay_checkout_verify_' . $order_id ) ) {
			$this->log( 'Inline callback rejected: invalid nonce.' );
			wp_send_json_error( array( 'message' => __( 'Invalid verification request.', 'zevpay-checkout-for-woocommerce' ) ), 400 );
		}

		$order = wc_get_order( $order_id );

		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			$this->log( 'Inline callback rejected: order mismatch.' );
			wp_send_json_error( array( 'message' => __( 'Unable to verify this payment.', 'zevpay-checkout-for-woocommerce' ) ), 404 );
		}

		if ( ! $session_id && ! $reference ) {
			$this->log( 'Inline callback rejected: no session_id or reference.' );
			wp_send_json_error( array( 'message' => __( 'Missing payment reference.', 'zevpay-checkout-for-woocommerce' ) ), 400 );
		}

		// Verify the session via the PHP SDK.
		try {
			$client   = $this->get_zevpay_client();
			$response = $client->checkout->verify( $session_id );

			$this->log( 'Inline verification response: ' . wp_json_encode( $response ) );

			$status = isset( $response['status'] ) ? $response['status'] : '';

			if ( 'completed' !== $status ) {
				$this->log( sprintf( 'Inline verification: session not completed. status=%s', $status ) );
				wp_send_json_error( array( 'message' => __( 'Payment not completed. Please try again.', 'zevpay-checkout-for-woocommerce' ) ), 402 );
			}

			$finalize = $this->finalize_order_payment( $order, $response );

			if ( is_wp_error( $finalize ) ) {
				$this->log( 'Order finalization error: ' . $finalize->get_error_message() );
				wp_send_json_error( array( 'message' => __( 'Payment verified but the order could not be updated. Contact support.', 'zevpay-checkout-for-woocommerce' ) ), 500 );
			}

			$this->log( sprintf( 'Payment verified for order %d via inline callback.', $order_id ) );

			wp_send_json_success( array( 'redirect' => $this->get_return_url( $order ) ) );
		} catch ( \Exception $e ) {
			$this->log( 'Inline verification error: ' . $e->getMessage() );
			wp_send_json_error( array( 'message' => __( 'We could not verify the payment. Please try again.', 'zevpay-checkout-for-woocommerce' ) ), 502 );
		}
	}

	/**
	 * Handle standard checkout callback (customer returning from hosted page).
	 */
	public function handle_standard_callback() {
		$order_id  = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
		$order_key = isset( $_GET['order_key'] ) ? sanitize_text_field( wp_unslash( $_GET['order_key'] ) ) : '';

		if ( ! $order_id || ! $order_key ) {
			$this->log( 'Standard callback: missing order_id or order_key.' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}

		$order = wc_get_order( $order_id );

		if ( ! $order || $order->get_order_key() !== $order_key ) {
			$this->log( 'Standard callback: order key mismatch.' );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}

		// If order is already processed, redirect to thank you page.
		if ( in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
			wp_safe_redirect( $this->get_return_url( $order ) );
			exit;
		}

		$session_id = $order->get_meta( '_zevpay_checkout_session_id' );

		if ( $session_id ) {
			try {
				$client   = $this->get_zevpay_client();
				$response = $client->checkout->verify( $session_id );

				$this->log( 'Standard callback verification: ' . wp_json_encode( $response ) );

				$status = isset( $response['status'] ) ? $response['status'] : '';

				if ( 'completed' === $status ) {
					$this->finalize_order_payment( $order, $response );
					$this->log( sprintf( 'Payment verified for order %d via standard callback.', $order_id ) );
				} else {
					$this->log( sprintf( 'Standard callback: session not completed. status=%s', $status ) );
					$order->add_order_note(
						/* translators: %s: session status. */
						sprintf( __( 'ZevPay standard checkout returned with status: %s. Awaiting webhook confirmation.', 'zevpay-checkout-for-woocommerce' ), $status )
					);
					$order->save();
				}
			} catch ( \Exception $e ) {
				$this->log( 'Standard callback verification failed: ' . $e->getMessage() );
			}
		}

		wp_safe_redirect( $this->get_return_url( $order ) );
		exit;
	}

	/**
	 * Handle incoming webhook requests.
	 */
	public function handle_webhook() {
		$payload   = file_get_contents( 'php://input' );
		$signature = isset( $_SERVER['HTTP_X_ZEVPAY_SIGNATURE'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_ZEVPAY_SIGNATURE'] ) )
			: '';

		if ( ! $this->webhook_secret ) {
			$this->log( 'Webhook rejected: no webhook secret configured.' );
			http_response_code( 401 );
			exit;
		}

		// Verify signature via PHP SDK.
		try {
			$event = \ZevPay\Webhook::constructEvent( $payload, $signature, $this->webhook_secret );
		} catch ( \Exception $e ) {
			$this->log( 'Webhook rejected: ' . $e->getMessage() );
			http_response_code( 401 );
			exit;
		}

		$this->log( 'Webhook event received: ' . wp_json_encode( $event ) );

		$event_type = isset( $event['event'] ) ? sanitize_text_field( $event['event'] ) : '';

		if ( ! in_array( $event_type, $this->webhook_completion_events, true ) ) {
			$this->log( sprintf( 'Webhook: ignoring event "%s".', $event_type ) );
			http_response_code( 200 );
			echo 'Ignored event';
			exit;
		}

		$transaction = isset( $event['data'] ) && is_array( $event['data'] ) ? $event['data'] : array();
		$metadata    = $this->parse_metadata( isset( $transaction['metadata'] ) ? $transaction['metadata'] : array() );

		$order_id = isset( $metadata['order_id'] ) ? absint( $metadata['order_id'] ) : 0;
		$order    = $order_id ? wc_get_order( $order_id ) : false;

		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			$this->log( sprintf( 'Webhook: order %d not found or payment method mismatch.', $order_id ) );
			http_response_code( 200 );
			echo 'Order not found';
			exit;
		}

		// Verify the session via SDK for extra safety.
		$session_id = $order->get_meta( '_zevpay_checkout_session_id' );

		if ( $session_id ) {
			try {
				$client   = $this->get_zevpay_client();
				$response = $client->checkout->verify( $session_id );

				$status = isset( $response['status'] ) ? $response['status'] : '';

				if ( 'completed' !== $status ) {
					$this->log( sprintf( 'Webhook: session verification returned status=%s for order %d.', $status, $order_id ) );
					http_response_code( 200 );
					echo 'Session not completed';
					exit;
				}

				$transaction = $response;
			} catch ( \Exception $e ) {
				$this->log( 'Webhook: session verification failed: ' . $e->getMessage() );
				// Fall back to the webhook payload.
			}
		}

		$finalize = $this->finalize_order_payment( $order, $transaction );

		if ( is_wp_error( $finalize ) ) {
			$this->log( 'Webhook: order update failed: ' . $finalize->get_error_message() );
			http_response_code( 500 );
			echo 'Order update failed';
			exit;
		}

		$this->log( sprintf( 'Payment verified for order %d via webhook.', $order->get_id() ) );

		http_response_code( 200 );
		echo 'OK';
		exit;
	}

	/**
	 * Ensure the order has a unique reference stored.
	 *
	 * @param WC_Order $order Order instance.
	 * @return string
	 */
	protected function ensure_order_reference( WC_Order $order ) {
		$reference = $order->get_meta( '_zevpay_checkout_reference' );

		if ( $reference ) {
			return $reference;
		}

		$merchant_id = '';
		if ( ! empty( $this->public_key ) ) {
			$merchant_id = substr( md5( $this->public_key ), 0, 8 );
		} else {
			$site_url    = function_exists( 'home_url' ) ? home_url() : 'default';
			$merchant_id = substr( md5( $site_url ), 0, 8 );
		}

		$microtime_int = (int) ( microtime( true ) * 10000 );
		$unique_id     = str_replace( '.', '', uniqid( '', true ) );
		$random_string = strtolower( wp_generate_password( 10, false, false ) );

		$reference = sprintf( '%s_%d_%d_%s_%s', $merchant_id, $order->get_id(), $microtime_int, $unique_id, $random_string );

		$order->update_meta_data( '_zevpay_checkout_reference', $reference );

		return $reference;
	}

	/**
	 * Finalize the WooCommerce order after a successful payment.
	 *
	 * @param WC_Order $order       Order instance.
	 * @param array    $transaction Transaction or session data.
	 * @return true|WP_Error
	 */
	protected function finalize_order_payment( WC_Order $order, $transaction ) {
		// Idempotent — skip if already processed.
		if ( in_array( $order->get_status(), array( 'processing', 'completed' ), true ) ) {
			return true;
		}

		$reference = isset( $transaction['reference'] ) ? sanitize_text_field( $transaction['reference'] ) : '';

		// Extract paid amount (the API may return it in different fields).
		$paid_amount = 0;
		foreach ( array( 'amount', 'amount_paid', 'amountKobo' ) as $field ) {
			if ( ! empty( $transaction[ $field ] ) ) {
				$paid_amount = (int) $transaction[ $field ];
				break;
			}
		}

		$order_amount = (int) round( $order->get_total() * 100 );

		if ( ! $paid_amount ) {
			$this->log( sprintf( 'No amount returned for order %d. Using order total.', $order->get_id() ) );
			$paid_amount = $order_amount;
		}

		// Normalize: if paid_amount is in naira (1/100 of expected), convert to kobo.
		if ( $paid_amount && $order_amount && $paid_amount !== $order_amount ) {
			if ( $paid_amount * 100 === $order_amount ) {
				$paid_amount *= 100;
			}
		}

		$order->set_transaction_id( $reference );

		if ( $paid_amount < $order_amount ) {
			$this->log( sprintf( 'Amount mismatch for order %d. paid=%d expected=%d', $order->get_id(), $paid_amount, $order_amount ) );
			$order->update_status( 'on-hold', __( 'ZevPay payment completed with a lower amount than expected.', 'zevpay-checkout-for-woocommerce' ) );
			$order->save();
			return new WP_Error( 'zevpay_amount_mismatch', __( 'Payment amount mismatch.', 'zevpay-checkout-for-woocommerce' ) );
		}

		if ( $order->get_currency() !== ZEVPAY_CHECKOUT_SUPPORTED_CURRENCY ) {
			$order->update_status( 'on-hold', __( 'ZevPay payment currency differs from the order currency.', 'zevpay-checkout-for-woocommerce' ) );
			$order->save();
			return new WP_Error( 'zevpay_currency_mismatch', __( 'Payment currency mismatch.', 'zevpay-checkout-for-woocommerce' ) );
		}

		$order->update_meta_data( '_zevpay_checkout_transaction', wp_json_encode( $transaction ) );
		/* translators: %s: ZevPay transaction reference. */
		$order->add_order_note( sprintf( __( 'ZevPay Checkout payment completed. Reference: %s', 'zevpay-checkout-for-woocommerce' ), $reference ) );
		$order->payment_complete( $reference );

		if ( function_exists( 'WC' ) && isset( WC()->cart ) && WC()->cart ) {
			WC()->cart->empty_cart();
		}

		return true;
	}

	/**
	 * Get or create a ZevPay PHP SDK client instance.
	 *
	 * @return \ZevPay\ZevPay
	 * @throws \Exception If secret key is missing.
	 */
	protected function get_zevpay_client() {
		if ( null === $this->zevpay_client ) {
			if ( empty( $this->secret_key ) ) {
				throw new \Exception( __( 'Missing ZevPay secret key.', 'zevpay-checkout-for-woocommerce' ) );
			}

			$this->zevpay_client = new \ZevPay\ZevPay( $this->secret_key );
		}

		return $this->zevpay_client;
	}

	/**
	 * Parse metadata field.
	 *
	 * @param mixed $metadata Raw metadata.
	 * @return array
	 */
	protected function parse_metadata( $metadata ) {
		if ( is_array( $metadata ) ) {
			return $metadata;
		}

		if ( is_string( $metadata ) ) {
			$decoded = json_decode( $metadata, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				return $decoded;
			}
		}

		return array();
	}

	/**
	 * Log data when enabled.
	 *
	 * @param string $message Log message.
	 */
	protected function log( $message ) {
		if ( ! $this->logging_enabled ) {
			return;
		}

		$this->logger->info( $message, array( 'source' => $this->id ) );
	}

	/**
	 * Hide WooCommerce default order summary on pay-for-order page for our gateway.
	 */
	public function hide_default_order_summary() {
		if ( ! is_checkout_pay_page() ) {
			return;
		}

		$order_id = absint( get_query_var( 'order-pay' ) );
		if ( ! $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order || $order->get_payment_method() !== $this->id ) {
			return;
		}

		remove_action( 'woocommerce_pay_order_before_payment', 'woocommerce_order_details_table', 10 );
		remove_action( 'woocommerce_pay_order_before_payment', 'woocommerce_order_details_table', 20 );
	}
}
