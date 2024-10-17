<?php

namespace Drupal\openai_integration\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class OpenAIAPIClient implements OpenAIAPIClientInterface {
    protected $httpClient;
    protected $apiKey;
    protected $baseUrl = 'https://api.openai.com/v1';
    protected $logger;

    public function __construct(ClientInterface $httpClient, ConfigFactoryInterface $configFactory, LoggerChannelFactoryInterface $loggerFactory) {
        $this->httpClient = $httpClient;
        $this->refreshSettings($configFactory);
        $this->logger = $loggerFactory->get('openai_integration');
    }

    public function refreshSettings(ConfigFactoryInterface $configFactory) {
        $this->apiKey = $configFactory->get('openai_integration.settings')->get('openai_api_key');
    }

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function sendRequest($methodName, $payload = [], $method = 'POST') {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('API key is not set');
        }

        try {
            $options = [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
            ];
            
            $response = $this->httpClient->request($method, $this->baseUrl . $methodName, $options);
            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException("Unexpected API response status: " . $response->getStatusCode());
            }            
            $responseBody = json_decode($response->getBody()->getContents(), true);

            $this->logRequest($method, $this->baseUrl . $methodName, $responseBody);
    
            return $responseBody;
        } catch (GuzzleException $e) {
            return $this->handleException($e, $method, $this->baseUrl . $methodName);
        }
    }

    protected function logRequest($method, $url, $responseBody) {
        if (isset($responseBody['sensitive'])) {
            unset($responseBody['sensitive']);
        }
        $this->logger->info('API request successful', [
                'method' => $method,
                'url' => $url,
                'response' => $responseBody
        ]);
    }

    protected function handleException(GuzzleException $e, $method, $url) {
        $response = $e->getResponse();
        $statusCode = $response ? $response->getStatusCode() : 0;

        $this->logger->error('API request failed', [
            'method' => $method,
            'url' => $url,
            'error' => $e->getMessage(),
            'code' => $statusCode
        ]);

        throw new \RuntimeException("API Error: {$e->getMessage()}", $statusCode, $e);
    }
}