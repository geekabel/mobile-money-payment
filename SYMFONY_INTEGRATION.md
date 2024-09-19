# Mobile Money Payment Package - Symfony Integration Guide

This guide covers the integration of the Mobile Money Payment package with Symfony 6.4, 7.0, 7.1, and superior versions.

## Installation

1. Install the package via Composer:

```bash
composer require geekabel/mobile-money-payment
```

2. If not already done, install Symfony HTTP Client:

```bash
composer require symfony/http-client
```

## Configuration

### Step 1: Create Service Definitions

Create a new file `config/packages/mobile_money_payment.yaml` and add the following content:

```yaml
services:
    mobile_money_payment.tmoney_service:
        class: MobileMoneyPayment\Service\TmoneyService
        arguments:
            $client: '@http_client'
            $logger: '@monolog.logger.payment'
            $username: '%env(TMONEY_USERNAME)%'
            $password: '%env(TMONEY_PASSWORD)%'
            $alias: '%env(TMONEY_ALIAS)%'
            $apiUrl: '%env(TMONEY_API_URL)%'

    mobile_money_payment.flooz_counter_manager:
        class: MobileMoneyPayment\Service\DefaultFloozCounterManager
        arguments:
            $counterFile: '%kernel.project_dir%/var/flooz_counter.txt'

    mobile_money_payment.flooz_service:
        class: MobileMoneyPayment\Service\FloozService
        arguments:
            $client: '@http_client'
            $logger: '@monolog.logger.payment'
            $counterManager: '@mobile_money_payment.flooz_counter_manager'
            $username: '%env(FLOOZ_USERNAME)%'
            $password: '%env(FLOOZ_PASSWORD)%'
            $key: '%env(FLOOZ_KEY)%'
            $mrchname: '%env(FLOOZ_MERCHANT_NAME)%'
            $partnermsisdn: '%env(FLOOZ_PARTNER_MSISDN)%'
            $apiUrl: '%env(FLOOZ_API_URL)%'

    mobile_money_payment.payment_manager:
        class: MobileMoneyPayment\PaymentManager
        calls:
            - [addService, ['tmoney', '@mobile_money_payment.tmoney_service']]
            - [addService, ['flooz', '@mobile_money_payment.flooz_service']]
```

### Step 2: Set Environment Variables

Add the following to your `.env` file:

```
TMONEY_USERNAME=your_tmoney_username
TMONEY_PASSWORD=your_tmoney_password
TMONEY_ALIAS=your_tmoney_alias
TMONEY_API_URL=https://tmoney-api-url.com

FLOOZ_USERNAME=your_flooz_username
FLOOZ_PASSWORD=your_flooz_password
FLOOZ_KEY=your_flooz_key
FLOOZ_MERCHANT_NAME=your_flooz_merchant_name
FLOOZ_PARTNER_MSISDN=your_flooz_partner_msisdn
FLOOZ_API_URL=https://flooz-api-url.com
```

### Step 3: Configure Monolog (if not already done)

In `config/packages/monolog.yaml`, add a channel for payment logging:

```yaml
monolog:
    channels: ['payment']
    handlers:
        payment:
            type: stream
            path: "%kernel.logs_dir%/payment.log"
            level: debug
            channels: ["payment"]
```

## Usage

### In a Controller

Here's an example of how to use the payment manager in a Symfony controller:

```php
use MobileMoneyPayment\PaymentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    #[Route('/make-payment', name: 'make_payment', methods: ['POST'])]
    public function makePayment(PaymentManager $paymentManager): JsonResponse
    {
        $response = $paymentManager->pay('tmoney', '1234567890', 100.00, 'REF' . uniqid(), 'Test payment');

        if ($response->success) {
            return $this->json([
                'success' => true,
                'message' => 'Payment successful',
                'transactionId' => $response->transactionId
            ]);
        } else {
            return $this->json([
                'success' => false,
                'message' => 'Payment failed: ' . $response->message
            ], 400);
        }
    }

    #[Route('/check-status/{reference}', name: 'check_status', methods: ['GET'])]
    public function checkStatus(string $reference, PaymentManager $paymentManager): JsonResponse
    {
        $response = $paymentManager->checkStatus('flooz', $reference);

        return $this->json([
            'success' => $response->success,
            'status' => $response->status,
            'message' => $response->message
        ]);
    }
}
```

### In a Service

If you prefer to encapsulate payment logic in a service:

```php
use MobileMoneyPayment\PaymentManager;
use MobileMoneyPayment\Exception\PaymentException;
use Psr\Log\LoggerInterface;

class PaymentService
{
    public function __construct(
        private PaymentManager $paymentManager,
        private LoggerInterface $logger
    ) {}

    public function processPayment(string $provider, string $phone, float $amount, string $reference): array
    {
        try {
            $response = $this->paymentManager->pay($provider, $phone, $amount, $reference);

            if ($response->success) {
                $this->logger->info('Payment successful', [
                    'provider' => $provider,
                    'reference' => $reference,
                    'transactionId' => $response->transactionId
                ]);

                return ['success' => true, 'transactionId' => $response->transactionId];
            } else {
                $this->logger->warning('Payment failed', [
                    'provider' => $provider,
                    'reference' => $reference,
                    'message' => $response->message
                ]);

                return ['success' => false, 'message' => $response->message];
            }
        } catch (PaymentException $e) {
            $this->logger->error('Payment error', [
                'provider' => $provider,
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'message' => 'An error occurred during payment processing'];
        }
    }
}
```

Then, in your `services.yaml`:

```yaml
services:
    App\Service\PaymentService:
        arguments:
            $paymentManager: '@mobile_money_payment.payment_manager'
            $logger: '@monolog.logger.payment'
```

## Advanced Configuration

### Custom Flooz Counter Manager

If you want to use a custom Flooz counter manager (e.g., using Doctrine ORM), create a new class:

```php
use Doctrine\ORM\EntityManagerInterface;
use MobileMoneyPayment\Interface\FloozCounterManagerInterface;

class DoctrineFloozCounterManager implements FloozCounterManagerInterface
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    public function getAndIncrementCounter(): int
    {
        // Implement using Doctrine ORM
    }
}
```

Then, update your `mobile_money_payment.yaml`:

```yaml
services:
    mobile_money_payment.flooz_counter_manager:
        class: App\Service\DoctrineFloozCounterManager
        arguments:
            $entityManager: '@doctrine.orm.entity_manager'

    mobile_money_payment.flooz_service:
        class: MobileMoneyPayment\Service\FloozService
        arguments:
            # ... other arguments
            $counterManager: '@mobile_money_payment.flooz_counter_manager'
            # ... rest of the arguments
```

## Symfony Flex Support (Optional)

To make the package easier to install and configure in Symfony applications, you can add Symfony Flex support. Create a `symfony.lock` file in your package root:

```json
{
    "your-vendor/mobile-money-payment": {
        "version": "1.0",
        "recipe": {
            "repo": "github.com/your-vendor/recipes",
            "branch": "main",
            "version": "1.0",
            "files": {
                "config/packages/mobile_money_payment.yaml": "%CONFIG_DIR%/packages/mobile_money_payment.yaml"
            }
        }
    }
}
```

This allows Symfony Flex to automatically create the configuration file when the package is installed.

## Compatibility Notes

- This package is compatible with Symfony 6.4, 7.0, 7.1, and superior versions.
- Ensure your Symfony project uses PHP 8.2 or higher, as required by the package.
- The package uses Symfony's HTTP Client, which is compatible with these Symfony versions.

By following this guide, you should be able to integrate the Mobile Money Payment package seamlessly into your Symfony application, regardless of whether you're using version 6.4, 7.0, 7.1, or a higher version.
