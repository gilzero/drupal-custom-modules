<?php

/**
 * @file
 * Contains \Drupal\pdf_gpt_chat\Service\OpenAIService.php
 */

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

    $api_key = $this->configFactory->get('pdf_gpt_chat.settings')->get('openai_api_key');
    if (!$api_key) {
      throw new \Exception('OpenAI API key is not configured.');
    }

    try {
      $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
        'headers' => [
          'Authorization' => 'Bearer ' . $api_key,
          'Content-Type' => 'application/json',
        ],
        'json' => [
          'model' => 'gpt-4o-mini', // or 'gpt-3.5-turbo'
          'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant that answers questions about PDF documents.'],
            ['role' => 'user', 'content' => $prompt],
          ],
          'max_tokens' => 150,
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