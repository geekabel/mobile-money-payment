<?php
declare(strict_types=1);

namespace Geekabel\MobileMoneyPayment;

use Geekabel\MobileMoneyPayment\Model\PaymentResponse;
use Geekabel\MobileMoneyPayment\Exception\PaymentException;
use Geekabel\MobileMoneyPayment\Interface\PaymentServiceInterface;

class PaymentManager
{
    private array $paymentServices = [];

    /**
     * Add a payment service to the manager.
     *
     * @param string $name The name of the service (e.g., 'tmoney', 'flooz')
     * @param PaymentServiceInterface $service The service instance
     */
    public function addPaymentService(string $name, PaymentServiceInterface $service): void
    {
        $this->paymentServices[$name] = $service;
    }

    /**
     * Execute a payment using the specified service.
     *
     * @param string $serviceName The name of the service to use
     * @param string $phone The customer's phone number
     * @param float $amount The amount to be paid
     * @param string $reference A unique reference for this transaction
     * @param string $description Optional description of the transaction
     * @return PaymentResponse
     * @throws PaymentException If the specified service is not found
     */
    public function pay(string $serviceName, string $phone, float $amount, string $reference, string $description = ''): PaymentResponse
    {
        $service = $this->getPaymentService($serviceName);
        return $service->pay($phone, $amount, $reference, $description);
    }

    /**
     * Check the status of a payment using the specified service.
     *
     * @param string $serviceName The name of the service to use
     * @param string $reference The unique reference of the transaction to check
     * @return PaymentResponse
     * @throws PaymentException If the specified service is not found
     */
    public function checkStatus(string $serviceName, string $reference): PaymentResponse
    {
        $service = $this->getPaymentService($serviceName);
        return $service->checkStatus($reference);
    }

    /**
     * Get a payment service by name.
     *
     * @param string $name The name of the service to retrieve
     * @return PaymentServiceInterface
     * @throws PaymentException If the specified service is not found
     */
    private function getPaymentService(string $name): PaymentServiceInterface
    {
        if (!isset($this->paymentServices[$name])) {
            throw new PaymentException("Payment service '$name' not found.");
        }
        return $this->paymentServices[$name];
    }
}