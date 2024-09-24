<?php

namespace Geekabel\MobileMoneyPayment\Tests\Enum;

use Geekabel\MobileMoneyPayment\Enum\PaymentStatus;
use PHPUnit\Framework\TestCase;

class PaymentStatusTest extends TestCase
{
    public function testToString()
    {
        $this->assertEquals('SUCCESS', PaymentStatus::SUCCESS->toString());
        $this->assertEquals('PENDING', PaymentStatus::PENDING->toString());
    }

    public function testFromString()
    {
        $this->assertEquals(PaymentStatus::SUCCESS, PaymentStatus::fromString('SUCCESS'));
        $this->assertEquals(PaymentStatus::PENDING, PaymentStatus::fromString('pending'));
    }

    public function testFromStringInvalid()
    {
        $this->expectException(\ValueError::class);
        PaymentStatus::fromString('INVALID_STATUS');
    }
}
