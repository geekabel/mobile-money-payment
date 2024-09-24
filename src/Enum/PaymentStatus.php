<?php

declare(strict_types=1);

namespace Geekabel\MobileMoneyPayment\Enum;

enum PaymentStatus: string
{
    case INITIATED = 'INITIATED';
    case PENDING = 'PENDING';
    case SUCCESS = 'SUCCESS';
    case FAILURE = 'FAILURE';
    case ERROR = 'ERROR';
    case CANCELLED = 'CANCELLED';
    case REFUNDED = 'REFUNDED';
    case PARTIALLY_REFUNDED = 'PARTIALLY_REFUNDED';

    /**
     * Convert the enum to a string representation.
     *
     * @return string
     */
    public function toString(): string
    {
        return $this->value;
    }

    /**
     * Create a PaymentStatus from a string.
     *
     * @param string $status
     * @return self
     * @throws \ValueError If the string does not match any case
     */
    public static function fromString(string $status): self
    {
        foreach (self::cases() as $case) {
            if (strtoupper($status) === $case->value) {
                return $case;
            }
        }

        throw new \ValueError("\"$status\" is not a valid backing value for enum " . self::class);
    }
}
