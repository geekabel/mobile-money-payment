<?php

declare(strict_types=1);

namespace Geekabel\MobileMoneyPayment\Service;

use Geekabel\MobileMoneyPayment\Exception\PaymentException;
use Geekabel\MobileMoneyPayment\Interface\PaymentServiceInterface;
use Geekabel\MobileMoneyPayment\Model\PaymentResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TmoneyService implements PaymentServiceInterface
{
    private HttpClientInterface $client;
    private LoggerInterface $logger;
    private string $username;
    private string $password;
    private string $alias;
    private string $apiUrl;

    public function __construct(
        HttpClientInterface $client,
        LoggerInterface $logger,
        string $username,
        string $password,
        string $alias,
        string $apiUrl
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->username = $username;
        $this->password = $password;
        $this->alias = $alias;
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
                "idRequete" => Uuid::v4()->toRfc4122(),
                "numeroClient" => "228" . $phone,
                "montant" => $amount,
                "refCommande" => $reference,
                "dateHeureRequete" => (new \DateTime())->format('Y-m-d H:i:s'),
                "description" => $description,
            ];

            $response = $this->client->request(
                'POST',
                $this->apiUrl . '/tmoney-middleware/debit',
                [
                    'json' => $data,
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        "Authorization" => "Bearer " . $token,
                    ],
                ]
            );

            $result = $response->toArray();
            $this->logger->info("Tmoney Debit || phone:$phone, amount:$amount, ref:$reference, response:" . json_encode($result));

            return new PaymentResponse(
                success: $result['code'] === '0',
                message: $result['message'],
                transactionId: $result['refTmoney'] ?? null,
                status: $result['code'] === '0' ? 'SUCCESS' : 'FAILURE',
                rawResponse: $result
            );
        } catch (\Exception $e) {
            $this->logger->error("Tmoney Debit error: " . $e->getMessage());

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

            $response = $this->client->request(
                'GET',
                $this->apiUrl . "/tmoney-middleware/transactionid?idRequete=" . $reference,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                        "Authorization" => "Bearer " . $token,
                    ],
                ]
            );

            $result = $response->toArray();
            $this->logger->info("Tmoney Status Check: ref -> $reference");

            return new PaymentResponse(
                success: $result['code'] === '0',
                message: $result['message'],
                transactionId: $result['refTmoney'] ?? null,
                status: $result['code'] === '0' ? 'SUCCESS' : 'PENDING',
                rawResponse: $result
            );
        } catch (\Exception $e) {
            $this->logger->error("Tmoney Status Check error: " . $e->getMessage());

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
            $response = $this->client->request(
                'POST',
                $this->apiUrl . '/login',
                [
                    'json' => [
                        "nomUtilisateur" => $this->username,
                        "motDePasse" => $this->password,
                    ],
                    'headers' => [
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ],
                ]
            );

            $result = $response->toArray();

            return $result['data']['token'] ?? null;
        } catch (\Throwable $th) {
            $this->logger->error("Tmoney Token error: " . $th->getMessage());

            return null;
        }
    }
}
