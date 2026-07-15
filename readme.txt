=== ZevPay Checkout for WooCommerce ===
Contributors: arowolodaniel
Tags: payments, payment gateway, nigeria, bank transfer, woocommerce
Requires at least: 5.8
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept payments via bank transfer, PayID, and card with ZevPay Checkout.

== Description ==

ZevPay Checkout for WooCommerce lets your customers pay using bank transfer, PayID, and card, directly from your WooCommerce store.

**Two checkout modes:**

* **Inline Modal**: opens the ZevPay payment form in a modal overlay on your checkout page. Customers never leave your site.
* **Standard (Redirect)**: redirects customers to the ZevPay hosted checkout page. Optionally forwards order details (line items, customer info) to the hosted page.

**Features:**

* Simple setup, just enter your API keys
* Supports bank transfer, PayID, and card payments
* Choose which payment methods to offer, or use your ZevPay Dashboard settings
* WooCommerce Blocks support
* HPOS (High-Performance Order Storage) compatible
* Webhook signature verification for reliable payment confirmation
* Uses the WordPress HTTP API, no bundled HTTP libraries
* Debug logging for troubleshooting
* Fully translatable

A [ZevPay Checkout](https://zevpaycheckout.com) merchant account is required to use this plugin.

== External Services ==

This plugin connects to the ZevPay Checkout API to process payments. It is not usable without a ZevPay Checkout merchant account.

**api.zevpaycheckout.com** (ZevPay Checkout API)

* When: initializing a payment session at checkout and verifying payment status afterwards.
* Data sent: order amount and currency, order ID and order key, a payment reference, the customer's billing email and name, and (if "Pass Order Details" is enabled in Standard mode) order line items (product name, short description, quantity, unit price).

**js.zevpaycheckout.com** (ZevPay inline payment SDK)

* When: the customer opens the payment modal on the pay-for-order page (Inline mode only).
* Data sent: the browser loads the payment SDK script and submits the payment details above directly to ZevPay.

**Hosted checkout page** (Standard mode)

* When: the customer is redirected to the ZevPay hosted page on zevpaycheckout.com to complete payment, then returned to your store.

These services are operated by ZevPay: [Terms of Service](https://www.zevpaycheckout.com/legal/terms) and [Privacy Policy](https://www.zevpaycheckout.com/legal/privacy).

== Installation ==

1. Upload the `zevpay-checkout-for-woocommerce` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **WooCommerce > Settings > Payments > ZevPay Checkout**
4. Enter your Public Key and Secret Key from your [ZevPay Dashboard](https://dashboard.zevpaycheckout.com)
5. Copy the webhook URL shown on the settings page into your ZevPay Dashboard webhook settings, and paste the webhook secret back into the plugin settings
6. Choose your preferred checkout mode (Inline or Standard)

== Frequently Asked Questions ==

= What currencies are supported? =

ZevPay Checkout currently supports Nigerian Naira (NGN) only.

= What is the difference between Inline and Standard checkout? =

**Inline** opens a payment modal on your checkout page, so the customer stays on your site. **Standard** redirects the customer to a hosted ZevPay page to complete payment, then redirects them back.

= Can I choose which payment methods to show? =

Yes. In the plugin settings, you can select specific methods (bank transfer, PayID, card) or leave it empty to use whatever you configured in your ZevPay Dashboard.

= Do I need to set up webhooks? =

Yes. Webhooks provide reliable, server-to-server payment confirmation. Copy the webhook URL from the plugin settings page into your ZevPay Dashboard.

= Is this plugin compatible with WooCommerce Blocks? =

Yes. The plugin supports both the classic checkout shortcode and the new WooCommerce Blocks checkout.

= Where do I get API keys? =

Create a merchant account at [zevpaycheckout.com](https://zevpaycheckout.com), complete verification, then copy your keys from the Dashboard's API settings page.

== Screenshots ==

1. Gateway settings under WooCommerce > Settings > Payments
2. Inline checkout: customers pay in a modal without leaving your site
3. Standard checkout: redirect to the ZevPay hosted payment page
4. ZevPay dashboard: track payments and settlements

== Changelog ==

= 1.0.0 =
* Initial release
* Inline modal and standard (redirect) checkout modes
* Configurable payment methods
* Webhook signature verification
* WooCommerce Blocks support
* HPOS compatibility

== Upgrade Notice ==

= 1.0.0 =
Initial release of ZevPay Checkout for WooCommerce.
