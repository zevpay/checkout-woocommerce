# ZevPay Checkout for WooCommerce

Official WooCommerce payment gateway plugin for [ZevPay Checkout](https://docs.zevpaycheckout.com). Accept payments via bank transfer, PayID, and card.

## Features

- **Inline Modal** — Opens the ZevPay payment form in a modal overlay on your checkout page
- **Standard (Redirect)** — Redirects customers to the ZevPay hosted checkout page
- Configurable payment methods (bank transfer, PayID, card) or use your Dashboard settings
- Optional order details forwarding in standard mode
- WooCommerce Blocks support
- HPOS (High-Performance Order Storage) compatible
- Webhook verification via the ZevPay PHP SDK
- Debug logging

## Requirements

- WordPress 5.8+
- WooCommerce 7.0+
- PHP 7.4+

## Installation

1. Upload the `zevpay-checkout-for-woocommerce` folder to `/wp-content/plugins/`
2. Activate the plugin through **Plugins** in WordPress
3. Go to **WooCommerce > Settings > Payments > ZevPay Checkout**
4. Enter your API keys from the [ZevPay Dashboard](https://dashboard.zevpaycheckout.com)
5. Copy the webhook URL into your Dashboard webhook settings
6. Choose your checkout mode (Inline or Standard)

## Configuration

| Setting | Description |
|---------|-------------|
| Public API Key | Your `pk_live_*` or `pk_test_*` key |
| Secret API Key | Your `sk_live_*` or `sk_test_*` key |
| Webhook Secret | Signing secret from ZevPay Dashboard |
| Checkout Mode | Inline (modal) or Standard (redirect) |
| Payment Methods | Select specific methods or leave empty for Dashboard defaults |
| Pass Order Details | Forward line items and customer info in Standard mode |
| Logging | Enable debug logs in WooCommerce > Status > Logs |

## How It Works

### Inline Mode

1. Customer selects ZevPay Checkout at checkout
2. Redirected to order-pay page with a "Pay with ZevPay" button
3. Clicking the button opens the payment modal
4. Customer completes payment in the modal
5. AJAX call verifies the payment via the ZevPay API
6. Order is marked as paid and customer is redirected to the thank-you page
7. Webhook confirms asynchronously

### Standard Mode

1. Customer selects ZevPay Checkout at checkout
2. Plugin initializes a session via the ZevPay API
3. Customer is redirected to the ZevPay hosted checkout page
4. Customer completes payment on the hosted page
5. Redirected back to WooCommerce with the session verified
6. Order is marked as paid
7. Webhook confirms asynchronously

## License

GPL-2.0-or-later
