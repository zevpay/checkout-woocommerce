<?php

defined( 'ABSPATH' ) || exit;

/**
 * Minimal ZevPay Checkout API client built on the WordPress HTTP API.
 *
 * Covers the three operations the gateway needs: initializing a
 * checkout session, verifying a session, and verifying webhook
 * signatures. Uses wp_remote_request() so hosts' proxy/SSL settings
 * and the WP_HTTP filters all apply.
 */
class ZevPay_Checkout_API_Client {

	/**
	 * Default API base URL.
	 */
	const DEFAULT_BASE_URL = 'https://api.zevpaycheckout.com';

	/**
	 * Request timeout in seconds.
	 */
	const TIMEOUT = 30;

	/**
	 * API secret key.
	 *
	 * @var string
	 */
	protected $secret_key;

	/**
	 * API base URL.
	 *
	 * @var string
	 */
	protected $base_url;

	/**
	 * Constructor.
	 *
	 * @param string $secret_key ZevPay secret key.
	 */
	public function __construct( $secret_key ) {
		$this->secret_key = $secret_key;

		/**
		 * Filters the ZevPay Checkout API base URL.
		 *
		 * @param string $base_url API base URL.
		 */
		$this->base_url = rtrim( apply_filters( 'zevpay_checkout_api_base_url', self::DEFAULT_BASE_URL ), '/' );
	}

	/**
	 * Initialize a checkout session.
	 *
	 * @param array $params Session parameters (amount, email, reference, ...).
	 * @return array Session data (checkout_url, session_id, ...).
	 * @throws Exception When the API returns an error or is unreachable.
	 */
	public function initialize_session( array $params ) {
		return $this->request( 'POST', '/v1/checkout/session/initialize', $params );
	}

	/**
	 * Verify a checkout session payment.
	 *
	 * @param string $session_id Session ID.
	 * @return array Session data including payment status.
	 * @throws Exception When the API returns an error or is unreachable.
	 */
	public function verify_session( $session_id ) {
		return $this->request( 'GET', '/v1/checkout/session/' . rawurlencode( $session_id ) . '/verify' );
	}

	/**
	 * Verify a webhook signature and parse the event payload.
	 *
	 * @param string $payload   Raw request body.
	 * @param string $signature Value of the x-zevpay-signature header.
	 * @param string $secret    Webhook signing secret.
	 * @return array Parsed event data.
	 * @throws Exception When the signature or payload is invalid.
	 */
	public static function construct_webhook_event( $payload, $signature, $secret ) {
		$expected = hash_hmac( 'sha256', $payload, $secret );

		if ( ! is_string( $signature ) || '' === $signature || ! hash_equals( $expected, $signature ) ) {
			throw new Exception( 'Invalid webhook signature.' );
		}

		$event = json_decode( $payload, true );

		if ( ! is_array( $event ) ) {
			throw new Exception( 'Invalid webhook payload.' );
		}

		return $event;
	}

	/**
	 * Perform an API request with one retry on connection errors / 5xx.
	 *
	 * @param string $method HTTP method.
	 * @param string $path   API path beginning with '/'.
	 * @param array  $body   Request body for POST/PUT requests.
	 * @return array Decoded response data.
	 * @throws Exception When the API returns an error or is unreachable.
	 */
	protected function request( $method, $path, array $body = array() ) {
		$args = array(
			'method'  => $method,
			'timeout' => self::TIMEOUT,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->secret_key,
				'Content-Type'  => 'application/json',
				'Accept'        => 'application/json',
				'User-Agent'    => 'zevpay-checkout-for-woocommerce/' . ZEVPAY_CHECKOUT_VERSION . '; ' . home_url(),
			),
		);

		if ( ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$url      = $this->base_url . $path;
		$response = wp_remote_request( $url, $args );

		// Retry once on network failure or server error.
		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) >= 500 ) {
			$response = wp_remote_request( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			throw new Exception( 'Connection error: ' . esc_html( $response->get_error_message() ) );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		if ( $status >= 200 && $status < 300 ) {
			return isset( $data['data'] ) && is_array( $data['data'] ) ? $data['data'] : $data;
		}

		$error   = isset( $data['error'] ) && is_array( $data['error'] ) ? $data['error'] : $data;
		$message = isset( $error['message'] ) && is_string( $error['message'] ) ? $error['message'] : 'Unknown error';

		throw new Exception( esc_html( sprintf( 'ZevPay API error (HTTP %d): %s', $status, $message ) ) );
	}
}
