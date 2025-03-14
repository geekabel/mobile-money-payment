<?php

namespace Geekabel\MobileMoneyPayment\Tests\Service;

use Geekabel\MobileMoneyPayment\Enum\PaymentStatus;
use Geekabel\MobileMoneyPayment\Model\PaymentResponse;
use Geekabel\MobileMoneyPayment\Service\TmoneyService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;
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
            'https://api.tmoney.com',
            'https://cashout.tmoney.com',
            'https://cashout-status.tmoney.com'
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
    
    public function testCashOutSuccessful()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['data' => ['token' => 'mock_token']])),
            new MockResponse(json_encode([
                'code' => '0',
                'message' => 'Success',
                'refTmoney' => 'TM789012',
            ])),
        ]);

        $result = $this->tmoneyService->cashOut('1234567890', 100.00, 'REF456', 'Test cashout');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('TM789012', $result->transactionId);
        $this->assertEquals(PaymentStatus::SUCCESS, $result->status);
    }
    
    public function testCashOutFailed()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['data' => ['token' => 'mock_token']])),
            new MockResponse(json_encode([
                'code' => '1',
                'message' => 'CashOut failed',
            ])),
        ]);

        $result = $this->tmoneyService->cashOut('1234567890', 100.00, 'REF456', 'Test cashout');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('CashOut failed', $result->message);
        $this->assertEquals(PaymentStatus::FAILURE, $result->status);
    }
    
    public function testCashOutStatusSuccessful()
    {
        // Need to handle URL with query parameters correctly
        $this->httpClient = new MockHttpClient(function ($method, $url, $options) {
            if (strpos($url, '/login') !== false) {
                return new MockResponse(json_encode(['data' => ['token' => 'mock_token']]));
            } else if (strpos($url, 'idTrans=TM789012') !== false) {
                return new MockResponse(json_encode([
                    'code' => '0',
                    'message' => 'Success',
                    'refTmoney' => 'TM789012',
                ]));
            }
            return new MockResponse('{}', ['http_code' => 404]);
        });
        
        // Recreate service with the new mock client
        $this->tmoneyService = new TmoneyService(
            $this->httpClient,
            $this->logger,
            'username',
            'password',
            'alias',
            'https://api.tmoney.com',
            'https://cashout.tmoney.com',
            'https://cashout-status.tmoney.com'
        );

        $result = $this->tmoneyService->cashOutStatus('TM789012');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals(PaymentStatus::SUCCESS, $result->status);
    }
    
    public function testCashOutStatusPending()
    {
        // Need to handle URL with query parameters correctly
        $this->httpClient = new MockHttpClient(function ($method, $url, $options) {
            if (strpos($url, '/login') !== false) {
                return new MockResponse(json_encode(['data' => ['token' => 'mock_token']]));
            } else if (strpos($url, 'idTrans=TM789012') !== false) {
                return new MockResponse(json_encode([
                    'code' => '1',
                    'message' => 'Pending',
                ]));
            }
            return new MockResponse('{}', ['http_code' => 404]);
        });
        
        // Recreate service with the new mock client
        $this->tmoneyService = new TmoneyService(
            $this->httpClient,
            $this->logger,
            'username',
            'password',
            'alias',
            'https://api.tmoney.com',
            'https://cashout.tmoney.com',
            'https://cashout-status.tmoney.com'
        );

        $result = $this->tmoneyService->cashOutStatus('TM789012');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals(PaymentStatus::PENDING, $result->status);
    }
    
    public function testTokenFailure()
    {
        $this->httpClient->setResponseFactory([
            new MockResponse(json_encode(['error' => 'Invalid credentials']), ['http_code' => 401]),
        ]);

        $result = $this->tmoneyService->pay('1234567890', 100.00, 'REF123', 'Test payment');

        $this->assertInstanceOf(PaymentResponse::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals(PaymentStatus::ERROR, $result->status);
    }
}