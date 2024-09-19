<?php

namespace Geekabel\MobileMoneyPayment\Tests;

use Geekabel\MobileMoneyPayment\Exception\PaymentException;
use Geekabel\MobileMoneyPayment\Interface\PaymentServiceInterface;
use Geekabel\MobileMoneyPayment\Model\PaymentResponse;
use Geekabel\MobileMoneyPayment\PaymentManager;
use PHPUnit\Framework\TestCase;

class PaymentManagerTest extends TestCase
{
    private PaymentManager $paymentManager;
    private PaymentServiceInterface $mockTmoneyService;
    private PaymentServiceInterface $mockFloozService;

    protected function setUp(): void
    {
        $this->paymentManager = new PaymentManager();

        // Create stub implementations instead of mocks
        $this->mockTmoneyService = $this->createStub(PaymentServiceInterface::class);
        $this->mockFloozService = $this->createStub(PaymentServiceInterface::class);

        $this->paymentManager->addPaymentService('tmoney', $this->mockTmoneyService);
        $this->paymentManager->addPaymentService('flooz', $this->mockFloozService);
    }

    public function testPayWithValidService()
    {
        $expectedResponse = new PaymentResponse(true, 'Success', 'TRX123', 'SUCCESS');
        $this->mockTmoneyService->method('pay')->willReturn($expectedResponse);

        $response = $this->paymentManager->pay('tmoney', '1234567890', 100.00, 'REF123', 'Test payment');

        $this->assertSame($expectedResponse, $response);
    }

    public function testPayWithInvalidService()
    {
        $this->expectException(PaymentException::class);
        $this->paymentManager->pay('invalid_service', '1234567890', 100.00, 'REF123', 'Test payment');
    }

    public function testCheckStatusWithValidService()
    {
        $expectedResponse = new PaymentResponse(true, 'Success', 'TRX123', 'SUCCESS');
        $this->mockFloozService->method('checkStatus')->willReturn($expectedResponse);

        $response = $this->paymentManager->checkStatus('flooz', 'REF123');

        $this->assertSame($expectedResponse, $response);
    }

    public function testCheckStatusWithInvalidService()
    {
        $this->expectException(PaymentException::class);
        $this->paymentManager->checkStatus('invalid_service', 'REF123');
    }

    public function testAddPaymentService()
    {
        $newMockService = $this->createStub(PaymentServiceInterface::class);
        $this->paymentManager->addPaymentService('new_service', $newMockService);

        $expectedResponse = new PaymentResponse(true, 'Success', 'TRX123', 'SUCCESS');
        $newMockService->method('pay')->willReturn($expectedResponse);

        $response = $this->paymentManager->pay('new_service', '1234567890', 100.00, 'REF123', 'Test payment');

        $this->assertSame($expectedResponse, $response);
    }
}
