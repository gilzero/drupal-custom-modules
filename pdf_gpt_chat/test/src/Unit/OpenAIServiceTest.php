<?php

namespace Drupal\Tests\pdf_gpt_chat\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\pdf_gpt_chat\Service\OpenAIService;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\pdf_gpt_chat\Service\LoggingService;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Prophecy\Argument;

class OpenAIServiceTest extends UnitTestCase {

  protected $httpClient;
  protected $configFactory;
  protected $cache;
  protected $loggerFactory;
  protected $loggingService;
  protected $openAIService;

  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->prophesize(ClientInterface::class);
    $this->configFactory = $this->prophesize(ConfigFactoryInterface::class);
    $this->cache = $this->prophesize(CacheBackendInterface::class);
    $this->loggerFactory = $this->prophesize(LoggerChannelFactoryInterface::class);
    $this->loggingService = $this->prophesize(LoggingService::class);

    $this->openAIService = new OpenAIService(
      $this->httpClient->reveal(),
      $this->configFactory->reveal(),
      $this->cache->reveal(),
      $this->loggerFactory->reveal(),
      $this->loggingService->reveal()
    );
  }

  public function testQuery() {
    $config = $this->prophesize(\Drupal\Core\Config\ImmutableConfig::class);
    $config->get('openai_api_key')->willReturn('test_api_key');
    $config->get('openai_model')->willReturn('gpt-3.5-turbo');
    $config->get('max_tokens')->willReturn(4096);
    $config->get('temperature')->willReturn(0.7);
    $config->get('system_prompt')->willReturn('You are a helpful assistant.');

    $this->configFactory->get('pdf_gpt_chat.settings')->willReturn($config->reveal());

    $this->cache->get(Argument::any())->willReturn(FALSE);

    $response = new Response(200, [], json_encode([
      'choices' => [
        [
          'message' => [
            'content' => 'Test response',
          ],
        ],
      ],
    ]));

    $this->httpClient->post(Argument::any(), Argument::any())->willReturn($response);

    $result = $this->openAIService->query('Test prompt');

    $this->assertEquals('Test response', $result);
  }

  public function testQueryWithCacheHit() {
    $cachedData = (object) ['data' => 'Cached response'];
    $this->cache->get(Argument::any())->willReturn($cachedData);

    $result = $this->openAIService->query('Test prompt');

    $this->assertEquals('Cached response', $result);
  }

  public function testQueryWithApiError() {
    $config = $this->prophesize(\Drupal\Core\Config\ImmutableConfig::class);
    $config->get('openai_api_key')->willReturn('test_api_key');
    $config->get('openai_model')->willReturn('gpt-3.5-turbo');
    $config->get('max_tokens')->willReturn(4096);
    $config->get('temperature')->willReturn(0.7);
    $config->get('system_prompt')->willReturn('You are a helpful assistant.');

    $this->configFactory->get('pdf_gpt_chat.settings')->willReturn($config->reveal());

    $this->cache->get(Argument::any())->willReturn(FALSE);

    $this->httpClient->post(Argument::any(), Argument::any())->willThrow(new \GuzzleHttp\Exception\RequestException('API Error', new \GuzzleHttp\Psr7\Request('POST', 'test')));

    $this->expectException(\Exception::class);
    $this->expectExceptionMessage('Failed to get a response from OpenAI.');

    $this->openAIService->query('Test prompt');
  }
}