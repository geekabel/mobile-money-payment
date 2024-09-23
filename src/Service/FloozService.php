<?php

declare(strict_types=1);

namespace Geekabel\MobileMoneyPayment\Service;

use Geekabel\MobileMoneyPayment\Exception\PaymentException;
use Geekabel\MobileMoneyPayment\Interface\FloozCounterManagerInterface;
use Geekabel\MobileMoneyPayment\Interface\PaymentServiceInterface;
use Geekabel\MobileMoneyPayment\Model\PaymentResponse;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FloozService implements PaymentServiceInterface
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private FloozCounterManagerInterface $counterManager;
    private string $username;
    private string $password;
    private string $key;
    private string $mrchname;
    private string $partnermsisdn;
    private string $apiUrl;

    public function __construct(
        HttpClientInterface $client,
        LoggerInterface $logger,
        FloozCounterManagerInterface $counterManager,
        string $username,
        string $password,
        string $key,
        string $mrchname,
        string $partnermsisdn,
        string $apiUrl
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->counterManager = $counterManager;
        $this->username = $username;
        $this->password = $password;
        $this->key = $key;
        $this->mrchname = $mrchname;
        $this->partnermsisdn = $partnermsisdn;
        $this->apiUrl = $apiUrl;
    }

    public function pay(string $phone, float $amount, string $reference, string $description = ''): PaymentResponse
    {
        try {
            $token = $this->getAccessToken();
            if ($token === null || $token === '' || $token === '0') {
                throw new PaymentException("Failed to obtain access token");
            }

            $data = [
                "msisdn" => $phone,
                "key" => $this->key,
                "mrchrefid" => $reference,
                "mrchname" => $this->mrchname,
                "amount" => (string) $amount,
                "partnermsisdn" => $this->partnermsisdn,
            ];

            $response = $this->client->request(
                'POST',
                $this->apiUrl . '/Flooz/DebitService/Debit',
                [
                    'json' => $data,
                    'headers' => [
                        'Authorization' => $token,
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            $result = $response->toArray();
            $this->logger->info("Flooz Debit || phone:$phone, amount:$amount, ref:$reference, response:" . json_encode($result));

            return new PaymentResponse(
                success: $result['code'] === '0',
                message: $result['message'],
                transactionId: $result['refid'] ?? null,
                status: $result['code'] === '0' ? 'SUCCESS' : 'FAILURE',
                rawResponse: $result
            );
        } catch (\Exception $e) {
            $this->logger->error("Flooz Debit error: " . $e->getMessage());

            return new PaymentResponse(
                success: false,
                message: $e->getMessage(),
                status: 'ERROR'
            );
        }
    }

    public function checkStatus(string $reference): PaymentResponse
    {
        try {
            $token = $this->getAccessToken();
            if ($token === null || $token === '' || $token === '0') {
                throw new PaymentException("Failed to obtain access token");
            }

            $data = [
                "mrchrefid" => $reference,
                "partnermsisdn" => $this->partnermsisdn,
            ];

            $response = $this->client->request(
                'POST',
                $this->apiUrl . "/Flooz/DebitService/Verify",
                [
                    'json' => $data,
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => $token,
                    ],
                ]
            );

            $result = $response->toArray();
            $this->logger->info("Flooz Status Check: ref -> $reference");

            return new PaymentResponse(
                success: $result['code'] === '0',
                message: $result['message'],
                transactionId: $result['refid'] ?? null,
                status: $result['code'] === '0' ? 'SUCCESS' : 'PENDING',
                rawResponse: $result
            );
        } catch (\Exception $e) {
            $this->logger->error("Flooz Status Check error: " . $e->getMessage());

            return new PaymentResponse(
                success: false,
                message: $e->getMessage(),
                status: 'ERROR'
            );
        }
    }

    private function getAccessToken(): ?string
    {
        try {
            $counter = $this->counterManager->getAndIncrementCounter();
            $response = $this->client->request(
                'GET',
                $this->apiUrl . "/token?index=$counter&username=$this->username&password=$this->password",
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            $result = $response->toArray();

            return $result['key'] ?? null;
        } catch (\Exception $e) {
            $this->logger->error("Flooz Token error: " . $e->getMessage());

            return null;
        }
    }
}
