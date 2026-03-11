<?php
/**
 * Plugin Name:       ZevPay Checkout for WooCommerce
 * Plugin URI:        https://docs.zevpaycheckout.com/sdks/woocommerce
 * Description:       Accept payments via bank transfer, PayID, and card with ZevPay Checkout. Supports inline modal and standard (redirect) checkout flows.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            ZevPay
 * Author URI:        https://zevpay.ng
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       zevpay-checkout-for-woocommerce
 * Domain Path:       /languages
 * WC requires at least: 7.0
 * WC tested up to:   9.6
 */

defined('ABSPATH') || exit;

define('ZEVPAY_CHECKOUT_VERSION', '1.0.0');
define('ZEVPAY_CHECKOUT_MAIN_FILE', __FILE__);
define('ZEVPAY_CHECKOUT_PATH', plugin_dir_path(__FILE__));
define('ZEVPAY_CHECKOUT_URL', plugins_url('', __FILE__));
define('ZEVPAY_CHECKOUT_SUPPORTED_CURRENCY', 'NGN');

/**
 * Initialize the gateway after all plugins are loaded.
 */
add_action('plugins_loaded', 'zevpay_checkout_init', 99);

function zevpay_checkout_init() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'zevpay_checkout_woocommerce_missing_notice' );
		return;
	}

	// Load the PHP SDK autoloader.
	$autoloader = ZEVPAY_CHECKOUT_PATH . 'vendor/autoload.php';
	if ( file_exists( $autoloader ) ) {
		require_once $autoloader;
	}

	require_once ZEVPAY_CHECKOUT_PATH . 'includes/class-wc-gateway-zevpay-checkout.php';

	add_filter( 'woocommerce_payment_gateways', 'zevpay_checkout_register_gateway' );
}

/**
 * Register the gateway with WooCommerce.
 *
 * @param array $gateways Existing gateways.
 * @return array
 */
function zevpay_checkout_register_gateway( $gateways ) {
	$gateways[] = 'WC_Gateway_ZevPay_Checkout';
	return $gateways;
}

/**
 * Admin notice when WooCommerce is not active.
 */
function zevpay_checkout_woocommerce_missing_notice() {
	printf(
		'<div class="error"><p><strong>%s</strong> %s</p></div>',
		esc_html__( 'ZevPay Checkout for WooCommerce', 'zevpay-checkout-for-woocommerce' ),
		esc_html__( 'requires WooCommerce to be installed and active.', 'zevpay-checkout-for-woocommerce' )
	);
}

/**
 * Declare compatibility with WooCommerce HPOS (High-Performance Order Storage).
 */
add_action( 'before_woocommerce_init', function () {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

/**
 * Declare compatibility with WooCommerce Blocks.
 */
add_action( 'woocommerce_blocks_loaded', function () {
	if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		return;
	}

	require_once ZEVPAY_CHECKOUT_PATH . 'includes/class-wc-gateway-zevpay-blocks-support.php';

	add_action(
		'woocommerce_blocks_payment_method_type_registration',
		function ( \Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $registry ) {
			$registry->register( new WC_Gateway_ZevPay_Blocks_Support() );
		}
	);
} );

/**
 * Add a "Settings" link on the Plugins page.
 */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	$settings_url = admin_url( 'admin.php?page=wc-settings&tab=checkout&section=zevpay_checkout' );
	array_unshift( $links, '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'zevpay-checkout-for-woocommerce' ) . '</a>' );
	return $links;
} );
