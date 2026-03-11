=== ZevPay Checkout for WooCommerce ===
Contributors: zevpay
Tags: payment, gateway, nigeria, bank transfer, woocommerce
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept payments via bank transfer, PayID, and card with ZevPay Checkout.

== Description ==

ZevPay Checkout for WooCommerce lets your customers pay using bank transfer, PayID, and card — directly from your WooCommerce store.

**Two checkout modes:**

* **Inline Modal** — Opens the ZevPay payment form in a modal overlay on your checkout page. Customers never leave your site.
* **Standard (Redirect)** — Redirects customers to the ZevPay hosted checkout page. Optionally forwards order details (line items, customer info) to the hosted page.

**Features:**

* Simple setup — just enter your API keys
* Supports bank transfer, PayID, and card payments
* Choose which payment methods to offer, or use your ZevPay Dashboard settings
* WooCommerce Blocks support
* HPOS (High-Performance Order Storage) compatible
* Webhook verification for reliable payment confirmation
* Debug logging for troubleshooting
* Fully translatable

== Installation ==

1. Upload the `zevpay-checkout-for-woocommerce` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **WooCommerce > Settings > Payments > ZevPay Checkout**
4. Enter your Public Key and Secret Key from your [ZevPay Dashboard](https://dashboard.zevpaycheckout.com)
5. Copy the webhook URL shown on the settings page into your ZevPay Dashboard webhook settings
6. Choose your preferred checkout mode (Inline or Standard)

== Frequently Asked Questions ==

= What currencies are supported? =

ZevPay Checkout currently supports Nigerian Naira (NGN) only.

= What is the difference between Inline and Standard checkout? =

**Inline** opens a payment modal on your checkout page — the customer stays on your site. **Standard** redirects the customer to a hosted ZevPay page to complete payment, then redirects them back.

= Can I choose which payment methods to show? =

Yes. In the plugin settings, you can select specific methods (bank transfer, PayID, card) or leave it empty to use whatever you configured in your ZevPay Dashboard.

= Do I need to set up webhooks? =

Yes. Webhooks provide reliable, server-to-server payment confirmation. Copy the webhook URL from the plugin settings page into your ZevPay Dashboard.

= Is this plugin compatible with WooCommerce Blocks? =

Yes. The plugin supports both the classic checkout shortcode and the new WooCommerce Blocks checkout.

== Screenshots ==

1. Plugin settings page
2. Inline checkout modal on the payment page
3. Standard checkout redirect flow

== Changelog ==

= 1.0.0 =
* Initial release
* Inline modal and standard (redirect) checkout modes
* Configurable payment methods
* Webhook verification via ZevPay PHP SDK
* WooCommerce Blocks support
* HPOS compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release of ZevPay Checkout for WooCommerce.
