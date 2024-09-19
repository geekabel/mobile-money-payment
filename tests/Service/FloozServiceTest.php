<?php

namespace MobileMoneyPayment\Tests\Service;

use Geekabel\MobileMoneyPayment\Interface\FloozCounterManagerInterface;
use Geekabel\MobileMoneyPayment\Model\PaymentResponse;
use Geekabel\MobileMoneyPayment\Service\FloozService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class FloozServiceTest extends TestCase
{
    private MockHttpClient $httpClient;
    private LoggerInterface $logger;
    private FloozCounterManagerInterface $counterManager;
    private FloozService $floozService;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->counterManager = new class () implements FloozCounterManagerInterface {
            private int $counter = 1049;

            public function getAndIncrementCounter(): int
            {
                return ++$this->counter;
            }
        };
        $this->floozService = new FloozService(
            $this->httpClient,
            $this->logger,
            $this->counterManager,
            'username',
            'password',
            'key',
            'merchant',
            'partner',
            'https://api.flooz.com'
        );
    }

    public function testPaySuccessful()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['key' => 'mock_token'])),
            new MockResponse(json_encode([
                'code' => '0',
                'message' => 'Success',
                'refid' => 'FL123456',
            ])),
        ]);

        $result = $this->floozService->pay('1234567890', 100.00, 'REF123', 'Test payment');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('FL123456', $result->transactionId);
    }

    public function testPayFailed()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['key' => 'mock_token'])),
            new MockResponse(json_encode([
                'code' => '1',
                'message' => 'Payment failed',
            ])),
        ]);

        $result = $this->floozService->pay('1234567890', 100.00, 'REF123', 'Test payment');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertFalse($result->success);
        $this->assertEquals('Payment failed', $result->message);
    }

    public function testCheckStatusSuccessful()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['key' => 'mock_token'])),
            new MockResponse(json_encode([
                'code' => '0',
                'message' => 'Success',
                'refid' => 'FL123456',
            ])),
        ]);

        $result = $this->floozService->checkStatus('REF123');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertTrue($result->success);
        $this->assertEquals('SUCCESS', $result->status);
    }

    public function testCheckStatusPending()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['key' => 'mock_token'])),
            new MockResponse(json_encode([
                'code' => '1',
                'message' => 'Pending',
            ])),
        ]);

        $result = $this->floozService->checkStatus('REF123');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertFalse($result->success);
        $this->assertEquals('PENDING', $result->status);
    }
}
