<?php

declare(strict_types=1);

namespace Geekabel\MobileMoneyPayment\Model;

class PaymentResponse
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?string $transactionId = null,
        public readonly ?string $status = null,
        public readonly ?array $rawResponse = null
    ) {
    }
}
