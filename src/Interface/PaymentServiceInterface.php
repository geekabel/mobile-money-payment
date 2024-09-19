<?php

namespace Geekabel\MobileMoneyPayment\Interface;

use Geekabel\MobileMoneyPayment\Model\PaymentResponse;


interface PaymentServiceInterface
{
    /**
     * Execute a payment transaction.
     *
     * @param string $phone The customer's phone number
     * @param float $amount The amount to be paid
     * @param string $reference A unique reference for this transaction
     * @param string $description Optional description of the transaction
     * @return PaymentResponse
     */
    public function pay(string $phone, float $amount, string $reference, string $description = ''): PaymentResponse;

    /**
     * Check the status of a payment transaction.
     *
     * @param string $reference The unique reference of the transaction to check
     * @return PaymentResponse
     */
    public function checkStatus(string $reference): PaymentResponse;
}