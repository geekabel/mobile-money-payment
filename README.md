# Mobile Money Payment Package

## Overview

The Mobile Money Payment package provides a flexible and extensible solution for integrating mobile money payment services into PHP applications. It currently supports Tmoney and Flooz payment services, with an architecture that allows easy addition of new payment providers.

## Features

- Support for multiple payment services (currently Tmoney and Flooz)
- Extensible architecture using the Strategy pattern
- Standardized payment responses across different services
- Flexible Flooz counter management system
- Easy integration with various PHP applications, including Symfony

## Requirements

- PHP 8.2 or higher
- Symfony HTTP Client

## Installation

Install the package via Composer:

```bash
composer geekabel/mobile-money-payment
```

## Basic Usage

### Setting up the Payment Manager

```php
use MobileMoneyPayment\PaymentManager;
use MobileMoneyPayment\Service\TmoneyService;
use MobileMoneyPayment\Service\FloozService;
use MobileMoneyPayment\Service\DefaultFloozCounterManager;

// Create service instances
$tmoneyService = new TmoneyService(
    $httpClient,
    $logger,
    'tmoney_username',
    'tmoney_password',
    'tmoney_alias',
    'https://tmoney-api-url.com'
);

$floozService = new FloozService(
    $httpClient,
    $logger,
    new DefaultFloozCounterManager(),
    'flooz_username',
    'flooz_password',
    'flooz_key',
    'flooz_merchant_name',
    'flooz_partner_msisdn',
    'https://flooz-api-url.com'
);

// Create and set up the Payment Manager
$paymentManager = new PaymentManager();
$paymentManager->addService('tmoney', $tmoneyService);
$paymentManager->addService('flooz', $floozService);
```

### Making a Payment

```php
$response = $paymentManager->pay('tmoney', '1234567890', 100.00, 'REF123', 'Payment for order #123');

if ($response->success) {
    echo "Payment successful! Transaction ID: " . $response->transactionId;
} else {
    echo "Payment failed: " . $response->message;
}
```

### Checking Payment Status

```php
$status = $paymentManager->checkStatus('flooz', 'REF123');

echo "Payment status: " . $status->status;
```

## Extending the Package

### Adding a New Payment Service

1. Create a new class that implements `PaymentServiceInterface`:

```php
use MobileMoneyPayment\Interface\PaymentServiceInterface;
use MobileMoneyPayment\Model\PaymentResponse;

class NewPaymentService implements PaymentServiceInterface
{
    public function pay(string $phone, float $amount, string $reference, string $description = ''): PaymentResponse
    {
        // Implement payment logic
    }

    public function checkStatus(string $reference): PaymentResponse
    {
        // Implement status check logic
    }
}
```

2. Add the new service to the Payment Manager:

```php
$newService = new NewPaymentService(/* ... */);
$paymentManager->addService('new_service', $newService);
```

### Custom Flooz Counter Manager

1. Create a class that implements `FloozCounterManagerInterface`:

```php
use MobileMoneyPayment\Interface\FloozCounterManagerInterface;

class CustomFloozCounterManager implements FloozCounterManagerInterface
{
    public function getAndIncrementCounter(): int
    {
        // Implement custom counter logic
    }
}
```

2. Use the custom manager when creating the Flooz service:

```php
$customCounterManager = new CustomFloozCounterManager();
$floozService = new FloozService(
    $httpClient,
    $logger,
    $customCounterManager,
    // ... other parameters
);
```

## Advanced Usage

### Error Handling

The package uses `PaymentException` for specific payment-related errors. It's recommended to catch these exceptions:

```php
use MobileMoneyPayment\Exception\PaymentException;

try {
    $response = $paymentManager->pay('tmoney', '1234567890', 100.00, 'REF123');
} catch (PaymentException $e) {
    echo "Payment error: " . $e->getMessage();
} catch (\Exception $e) {
    echo "Unexpected error: " . $e->getMessage();
}
```

### Logging

The package accepts a PSR-3 compatible logger. You can provide your own logger implementation for custom logging behavior:

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('payment');
$logger->pushHandler(new StreamHandler('path/to/your.log', Logger::DEBUG));

$tmoneyService = new TmoneyService(
    $httpClient,
    $logger,
    // ... other parameters
);
```
## Symfony Integration

For detailed instructions on how to integrate this package with Symfony 6.4, 7.0, 7.1, and superior versions, please refer to our [Symfony Integration Guide](docs/SYMFONY_INTEGRATION.md).
## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
