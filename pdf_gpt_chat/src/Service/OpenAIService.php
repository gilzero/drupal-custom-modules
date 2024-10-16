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

  public function __construct(ClientInterface $http_client, ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, LoggerChannelFactoryInterface $logger_factory) {
    $this->httpClient = $http_client;
    $this->configFactory = $config_factory;
    $this->cache = $cache;
    $this->logger = $logger_factory->get('pdf_gpt_chat');
  }

  public function query(string $prompt) {
    $cid = 'pdf_gpt_chat:openai_response:' . md5($prompt);
    if ($cache = $this->cache->get($cid)) {
      return $cache->data;
    }

    $config = $this->configFactory->get('pdf_gpt_chat.settings');
    $api_key = $config->get('openai_api_key');
    if (!$api_key) {
      throw new \Exception('OpenAI API key is not configured.');
    }

    $model = $config->get('openai_model') ?: 'gpt-3.5-turbo';
    $max_tokens = $config->get('max_tokens') ?: 4096;
    $temperature = $config->get('temperature') ?: 0.7;
    $system_prompt = $config->get('system_prompt') ?: 'You are a helpful assistant that answers questions about PDF documents.';

    try {
      $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => $model,
          'messages' => [
            ['role' => 'system', 'content' => $system_prompt],
            ['role' => 'user', 'content' => $prompt],
          ],
          'max_tokens' => $max_tokens,
          'temperature' => $temperature,
        ],
      ]);

      $result = json_decode($response->getBody(), TRUE);
      $content = $result['choices'][0]['message']['content'] ?? 'No response generated.';

      $this->cache->set($cid, $content);

      return $content;
    }
    catch (RequestException $e) {
      $this->logger->error('OpenAI API request failed: @error', ['@error' => $e->getMessage()]);
      throw new \Exception('Failed to get a response from OpenAI.');
    }
  }
}