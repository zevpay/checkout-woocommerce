# ZevPay PHP SDK

Official PHP SDK for the [ZevPay Checkout](https://zevpaycheckout.com) REST API.

## Requirements

- PHP 8.1 or later
- [Guzzle](https://docs.guzzlephp.org/) 7.0+

## Installation

```bash
composer require zevpay/zevpay-php
```

## Quick start

```php
use ZevPay\ZevPay;

$zevpay = new ZevPay('sk_test_your_secret_key');

// Initialize a checkout session
$session = $zevpay->checkout->initialize([
    'amount' => 500000, // ₦5,000 in kobo
    'email' => 'customer@example.com',
    'reference' => 'ORDER-123',
    'callback_url' => 'https://yoursite.com/callback',
]);

echo $session['checkout_url'];
```

## Configuration

```php
$zevpay = new ZevPay('sk_live_xxx', [
    'base_url' => 'https://api.zevpaycheckout.com', // default
    'timeout' => 30,     // request timeout in seconds (default)
    'max_retries' => 2,  // retries on 5xx errors (default)
]);
```

## Checkout sessions

```php
// Initialize
$session = $zevpay->checkout->initialize([
    'amount' => 500000,
    'email' => 'customer@example.com',
    'currency' => 'NGN',
    'reference' => 'ORDER-123',
    'callback_url' => 'https://yoursite.com/callback',
    'metadata' => ['order_id' => '123'],
]);

// Verify
$result = $zevpay->checkout->verify($session['session_id']);
if ($result['status'] === 'completed') {
    echo 'Payment confirmed!';
}

// Get details
$details = $zevpay->checkout->get('ses_abc123');
```

## Transfers

```php
// Bank transfer
$transfer = $zevpay->transfers->create([
    'type' => 'bank_transfer',
    'amount' => 1000000, // ₦10,000
    'account_number' => '0123456789',
    'bank_code' => '044',
    'account_name' => 'John Doe',
    'narration' => 'Payout',
    'reference' => 'TXN-123',
]);

// PayID transfer
$transfer = $zevpay->transfers->create([
    'type' => 'payid',
    'amount' => 500000,
    'pay_id' => 'johndoe',
    'narration' => 'Payment',
]);

// List transfers
$transfers = $zevpay->transfers->list(['page' => 1, 'status' => 'completed']);

// Verify
$result = $zevpay->transfers->verify('TXN-123');

// List banks
$banks = $zevpay->transfers->listBanks();

// Resolve account
$account = $zevpay->transfers->resolveAccount([
    'account_number' => '0123456789',
    'bank_code' => '044',
]);

// Calculate charges
$charges = $zevpay->transfers->calculateCharges(['amount' => 1000000]);

// Get balance
$balance = $zevpay->transfers->getBalance();
```

## Invoices

```php
// Create
$invoice = $zevpay->invoices->create([
    'customer_name' => 'Jane Doe',
    'customer_email' => 'jane@example.com',
    'due_date' => '2026-04-01',
    'line_items' => [
        ['description' => 'Web Design', 'quantity' => 1, 'unit_price' => 5000000],
        ['description' => 'Hosting (1yr)', 'quantity' => 1, 'unit_price' => 1200000],
    ],
    'tax_rate' => 7.5,
    'note' => 'Thank you for your business',
]);

// Send, list, cancel
$zevpay->invoices->send($invoice['public_id']);
$invoices = $zevpay->invoices->list(['status' => 'sent']);
$zevpay->invoices->cancel($invoice['public_id']);
```

## Static PayIDs

```php
$payid = $zevpay->staticPayId->create([
    'pay_id' => 'mystore',
    'name' => 'My Store',
    'description' => 'Accept payments to my store',
]);

$zevpay->staticPayId->deactivate($payid['id']);
$zevpay->staticPayId->reactivate($payid['id']);
```

## Dynamic PayIDs

```php
$dpayid = $zevpay->dynamicPayId->create([
    'amount' => 1000000,
    'name' => 'Donation Drive',
    'expires_in_minutes' => 60,
]);

$zevpay->dynamicPayId->deactivate($dpayid['id']);
```

## Virtual accounts

```php
$va = $zevpay->virtualAccounts->create([
    'amount' => 1000000,
    'validity_minutes' => 60,
]);

echo $va['account_number']; // Customer pays to this
```

## Wallet

```php
$wallet = $zevpay->wallet->get();
$members = $zevpay->wallet->listMembers();
$zevpay->wallet->addMember(['pay_id' => 'johndoe']);
$zevpay->wallet->removeMember('johndoe');
```

## Webhook verification

```php
use ZevPay\Webhook;

$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_ZEVPAY_SIGNATURE'] ?? '';
$secret = getenv('ZEVPAY_WEBHOOK_SECRET');

try {
    $event = Webhook::constructEvent($payload, $signature, $secret);

    switch ($event['event']) {
        case 'charge.success':
            // Handle successful payment
            break;
        case 'transfer.success':
            // Handle successful transfer
            break;
        case 'transfer.failed':
            // Handle failed transfer
            break;
        case 'invoice.paid':
            // Handle paid invoice
            break;
    }

    http_response_code(200);
    echo 'OK';
} catch (\ZevPay\Exceptions\ZevPayException $e) {
    http_response_code(400);
    echo 'Invalid signature';
}
```

## Error handling

```php
use ZevPay\Exceptions\ValidationException;
use ZevPay\Exceptions\AuthenticationException;
use ZevPay\Exceptions\NotFoundException;
use ZevPay\Exceptions\ConflictException;
use ZevPay\Exceptions\RateLimitException;
use ZevPay\Exceptions\ApiException;

try {
    $zevpay->transfers->create([/* ... */]);
} catch (ValidationException $e) {
    // 400 — invalid parameters
    echo $e->errorCode;  // e.g. 'VALIDATION_ERROR'
    echo $e->getMessage();
    print_r($e->details); // field-level errors
} catch (AuthenticationException $e) {
    // 401 — invalid API key
} catch (NotFoundException $e) {
    // 404 — resource not found
} catch (ConflictException $e) {
    // 409 — duplicate transaction
} catch (RateLimitException $e) {
    // 429 — rate limit exceeded
    echo $e->retryAfter; // seconds to wait
} catch (ApiException $e) {
    // 500+ — server error
}
```

## Amounts

All amounts are in **kobo** (minor currency units):

| Naira | Kobo |
|-------|------|
| ₦1 | 100 |
| ₦1,000 | 100,000 |
| ₦10,000 | 1,000,000 |

## Testing

```bash
composer test
```

## License

MIT
