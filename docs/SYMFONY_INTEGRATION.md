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
            $cashOutUrl: '%env(TMONEY_CASHOUT_URL)%'
            $cashOutStatusUrl: '%env(TMONEY_CASHOUT_STATUS_URL)%'

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

### Step 2: Import Your Configuration in services.yaml

Update your `config/services.yaml` to import the mobile money payment configuration:

```yaml
imports:
    - { resource: packages/mobile_money_payment.yaml }

parameters:
    app.tmoney.username: '%env(TMONEY_USERNAME)%'
    app.tmoney.password: '%env(TMONEY_PASSWORD)%'
    app.tmoney.alias: '%env(TMONEY_ALIAS)%'
    app.tmoney.api_url: '%env(TMONEY_API_URL)%'
    app.tmoney.cashout_url: '%env(TMONEY_CASHOUT_URL)%'
    app.tmoney.cashout_status_url: '%env(TMONEY_CASHOUT_STATUS_URL)%'
    app.flooz.username: '%env(FLOOZ_USERNAME)%'
    app.flooz.password: '%env(FLOOZ_PASSWORD)%'
    app.flooz.key: '%env(FLOOZ_KEY)%'
    app.flooz.merchant_name: '%env(FLOOZ_MERCHANT_NAME)%'
    app.flooz.partner_msisdn: '%env(FLOOZ_PARTNER_MSISDN)%'
    app.flooz.api_url: '%env(FLOOZ_API_URL)%'

services:
    # Default configuration for services in *this* file
    _defaults:
        autowire: true
        autoconfigure: true

    # ... your other services configuration
```

### Step 3: Set Environment Variables

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

### Step 4: Configure Monolog

Create or update `config/packages/monolog.yaml`:

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
        // Generate a unique reference
        $reference = 'REF' . Uuid::v4()->toRfc4122();
        $response = $paymentManager->pay('tmoney', '1234567890', 100.00, 'REF' . uniqid(), 'Test payment');

        if ($response->isSuccess()) {
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
        $provider = $request->query->get('provider', 'tmoney'); // provider here can be "Moov" or "Togocom"
        
        // Inject the payment manager directly if you prefer
        $paymentManager = $this->container->get('mobile_money_payment.payment_manager');
        $response = $paymentManager->checkStatus($provider, $reference);

        return $this->json([
            'success' => $response->success,
            'status' => $response->status,
            'message' => $response->message
        ]);
    }

    #[Route('/send-money', name: 'send_money', methods: ['POST'])]
    public function sendMoney(PaymentManager $paymentManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true)
        // Get the Tmoney service from the manager
        $tmoneyService = $paymentManager->getService('tmoney');
        $phone  =  $data['phone'] ?? ''
        $amount = (float) ($data['amount'] ?? 0)
        // Generate a unique reference
        $reference = 'SEND' . Uuid::v4()->toRfc4122();
        // Process the cashout operation
        $response = $tmoneyService->cashOut($phone, $amount, $reference, 'Money transfer to customer');
        
        if ($response->isSuccess()) {
            return $this->json([
                'success' => true,
                'message' => 'Money sent successfully',
                'transactionId' => $response->transactionId
            ]);
        } else {
            return $this->json([
                'success' => false,
                'message' => 'Transfer failed: ' . $response->message
            ], 400);
        }
    }

    #[Route('/check-cashout-status/{transactionId}', name: 'check_cashout_status', methods: ['GET'])]
    public function checkCashoutStatus(string $transactionId, PaymentManager $paymentManager): JsonResponse
    {
        $tmoneyService = $paymentManager->getService('tmoney');
        $response = $tmoneyService->cashOutStatus($transactionId);
        
        return $this->json([
            'success' => $response->isSuccess(),
            'status' => $response->status,
            'message' => $response->message
        ])
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

            if ($response->isSuccess()) {
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

    public function sendMoney(string $phone, float $amount, string $reference, string $description = ''): array
    {
        try {
            $response = $this->tmoneyService->cashOut($phone, $amount, $reference, $description);
            
            if ($response->isSuccess()) {
                $this->logger->info('Money sent successfully', [
                    'phone' => $phone,
                    'amount' => $amount,
                    'reference' => $reference,
                    'transactionId' => $response->transactionId
                ]);
                
                return ['success' => true, 'transactionId' => $response->transactionId];
            } else {
                $this->logger->warning('Money transfer failed', [
                    'phone' => $phone,
                    'reference' => $reference,
                    'message' => $response->message
                ]);
                
                return ['success' => false, 'message' => $response->message];
            }
        } catch (PaymentException $e) {
            $this->logger->error('Money transfer error', [
                'phone' => $phone,
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'message' => 'An error occurred during money transfer'];
        }
    }
    
    public function checkCashoutStatus(string $transactionId): array
    {
        try {
            $response = $this->tmoneyService->cashOutStatus($transactionId);
            
            return [
                'success' => $response->isSuccess(),
                'status' => $response->status,
                'message' => $response->message
            ];
        } catch (PaymentException $e) {
            $this->logger->error('Cashout status check error', [
                'transactionId' => $transactionId,
                'error' => $e->getMessage()
            ]);
            
            return ['success' => false, 'message' => 'An error occurred while checking cashout status'];
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

This integration guide is specifically designed for Symfony 7.1 and superior versions. The configuration takes advantage of modern PHP 8.2+ features and Symfony 7.1's improved dependency injection system.
Key compatibility requirements:

- PHP 8.2 or higher
- Symfony 7.1 or higher
- Symfony HTTP Client component 7.1+
- PSR-3 compatible logger
