<?php

declare(strict_types=1);

namespace Geekabel\MobileMoneyPayment\Model;

use Geekabel\MobileMoneyPayment\Enum\PaymentStatus;

class PaymentResponse
{
    public function __construct(
        private readonly bool $success,
        public readonly string $message,
        public readonly PaymentStatus $status,
        public readonly ?string $transactionId = null,
        public readonly ?array $rawResponse = null
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
