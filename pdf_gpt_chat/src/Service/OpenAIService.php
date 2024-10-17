<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class OpenAIService {

  protected $httpClient;
  protected $configFactory;
  protected $cache;
  protected $logger;
  protected $loggingService;

  public function __construct(
    ClientInterface $http_client,
    ConfigFactoryInterface $config_factory,
    CacheBackendInterface $cache,
    LoggerChannelFactoryInterface $logger_factory,
    LoggingService $logging_service
  ) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->cache = $cache;
    $this->logger = $logger_factory->get('pdf_gpt_chat');
    $this->loggingService = $logging_service;
  }

  public function query(string $prompt, array $images = []) {
    $cid = 'pdf_gpt_chat:openai_response:' . md5($prompt . implode('', $images));
    if ($cache = $this->cache->get($cid)) {
      $this->loggingService->logSystemEvent('openai_cache_hit', 'OpenAI response retrieved from cache');
      return $cache->data;
    }

    $config = $this->configFactory->get('pdf_gpt_chat.settings');
    $api_key = $config->get('openai_api_key');
    if (!$api_key) {
      $this->loggingService->logError('OpenAI API key is not configured.');
      throw new \Exception('OpenAI API key is not configured.');
    }

    $model = $config->get('openai_model') ?: 'gpt-4o';
    $max_tokens = $config->get('max_tokens') ?: 4096;
    $temperature = $config->get('temperature') ?: 0.7;
    $system_prompt = $config->get('system_prompt') ?: 'You are a helpful assistant that answers questions about PDF documents.';

    try {
      $this->loggingService->logSystemEvent('openai_api_request', 'Sending request to OpenAI API', [
        'model' => $model,
        'max_tokens' => $max_tokens,
        'temperature' => $temperature,
        'image_count' => count($images),
      ]);

      $messages = [
        ['role' => 'system', 'content' => $system_prompt],
        ['role' => 'user', 'content' => [
          ['type' => 'text', 'text' => $prompt],
        ]],
      ];

      foreach ($images as $image) {
        $messages[1]['content'][] = [
          'type' => 'image_url',
          'image_url' => ['url' => "data:image/png;base64,$image"],
        ];
      }

      $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 60, // Increase timeout to 60 seconds
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'messages' => $messages,
          'max_tokens' => $max_tokens,
          'temperature' => $temperature,
        ],
      ]);

      $result = json_decode($response->getBody(), TRUE);
      $content = $result['choices'][0]['message']['content'] ?? 'No response generated.';

      $this->cache->set($cid, $content);

      $this->loggingService->logSystemEvent('openai_api_response', 'Received response from OpenAI API', [
        'response_length' => strlen($content),
      ]);

      return $content;
    }
    catch (RequestException $e) {
      $this->loggingService->logError('OpenAI API request failed: ' . $e->getMessage());
      throw new \Exception('Failed to get a response from OpenAI: ' . $e->getMessage());
    }
  }
}