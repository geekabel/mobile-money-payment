<?php

namespace Geekabel\MobileMoneyPayment\Tests\Service;

use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Geekabel\MobileMoneyPayment\Enum\PaymentStatus;
use Geekabel\MobileMoneyPayment\Model\PaymentResponse;
use Geekabel\MobileMoneyPayment\Service\TmoneyService;
use Symfony\Component\HttpClient\Response\MockResponse;

class TmoneyServiceTest extends TestCase
{
    private MockHttpClient $httpClient;
    private LoggerInterface $logger;
    private TmoneyService $tmoneyService;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tmoneyService = new TmoneyService(
            $this->httpClient,
            $this->logger,
            'username',
            'password',
            'alias',
            'https://api.tmoney.com'
        );
    }

    public function testPaySuccessful()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['data' => ['token' => 'mock_token']])),
            new MockResponse(json_encode([
                'code' => '0',
                'message' => 'Success',
                'refTmoney' => 'TM123456',
            ])),
        ]);

        $result = $this->tmoneyService->pay('1234567890', 100.00, 'REF123', 'Test payment');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('TM123456', $result->transactionId);
    }

    public function testPayFailed()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['data' => ['token' => 'mock_token']])),
            new MockResponse(json_encode([
                'code' => '1',
                'message' => 'Payment failed',
            ])),
        ]);

        $result = $this->tmoneyService->pay('1234567890', 100.00, 'REF123', 'Test payment');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Payment failed', $result->message);
    }

    public function testCheckStatusSuccessful()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['data' => ['token' => 'mock_token']])),
            new MockResponse(json_encode([
                'code' => '0',
                'message' => 'Success',
                'refTmoney' => 'TM123456',
            ])),
        ]);

        $result = $this->tmoneyService->checkStatus('REF123');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(PaymentStatus::SUCCESS, $result->status);
    }

    public function testCheckStatusPending()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['data' => ['token' => 'mock_token']])),
            new MockResponse(json_encode([
                'code' => '1',
                'message' => 'Pending',
            ])),
        ]);

        $result = $this->tmoneyService->checkStatus('REF123');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals(PaymentStatus::PENDING, $result->status);
    }
}
