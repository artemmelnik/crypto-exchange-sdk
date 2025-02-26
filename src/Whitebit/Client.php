<?php

namespace CryptoExchange\Whitebit;

use CryptoExchange\Exceptions\ApiException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

class Client
{
    private string $baseUrl = 'https://whitebit.com';
    private string $apiKey;
    private string $apiSecret;
    private GuzzleClient $httpClient;

    public function __construct(string $apiKey, string $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->httpClient = new GuzzleClient(['base_uri' => $this->baseUrl]);
    }

    public function request(string $method, string $endpoint, array $params = []): array
    {
        $nonce = $this->generateNonce();
        $payload = [
            'request' => $endpoint,
            'nonce' => $nonce,
            'params' => $params
        ];

        $payloadBase64 = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha512', $payloadBase64, $this->apiSecret);

        $headers = [
            'X-TXC-APIKEY' => $this->apiKey,
            'X-TXC-PAYLOAD' => $payloadBase64,
            'X-TXC-SIGNATURE' => $signature,
            'Content-Type' => 'application/json'
        ];

        try {
            $response = $this->httpClient->request(
                $method,
                $endpoint,
                ['headers' => $headers]
            );

            $data = json_decode($response->getBody()->getContents(), true);

            if (isset($data['error'])) {
                throw new WhitebitException(
                    $data['error']['message'] ?? 'Unknown error',
                    $data['error']['code'] ?? 0
                );
            }

            return $data;

        } catch (RequestException $e) {
            throw new ApiException(
                "HTTP request failed: " . $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Collateral Account Balance
     * @param string|null $ticker
     * @return array
     */
    public function getCollateralBalance(?string $ticker = null): array
    {
        $params = [];
        if ($ticker !== null) {
            $params['ticker'] = $ticker;
        }
        return $this->request('GET', '/api/v4/trade-account/balance', $params);
    }

    /**
     * Create Collateral Market Order
     * @param array $params
     * @return array
     */
    public function createCollateralMarketOrder(array $params): array
    {
        $this->validateParams($params, ['market', 'side', 'amount']);
        return $this->request('POST', '/api/v4/order/collateral/market', $params);
    }

    private function generateNonce(): int
    {
        return (int) round(microtime(true) * 1000);
    }

    private function validateParams(array $params, array $required): void
    {
        foreach ($required as $field) {
            if (!isset($params[$field])) {
                throw new \InvalidArgumentException("Missing required parameter: $field");
            }
        }
    }
}
