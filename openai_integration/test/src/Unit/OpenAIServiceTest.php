<?php

namespace Drupal\Tests\openai_integration\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\openai_integration\Service\OpenAIService;
use Drupal\openai_integration\Service\OpenAIAPIClientInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;

class OpenAIServiceTest extends UnitTestCase {
  protected $openAIService;
  protected $mockApiClient;
  protected $mockSession;
  protected $mockLogger;
  protected $mockConfigFactory;
  protected $mockMessenger;

  protected function setUp(): void {
    parent::setUp();

    $this->mockApiClient = $this->createMock(OpenAIAPIClientInterface::class);
    $this->mockSession = $this->createMock(SessionInterface::class);
    $this->mockLogger = $this->createMock(LoggerChannelFactoryInterface::class);
    $this->mockConfigFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->mockMessenger = $this->createMock(MessengerInterface::class);

    $this->openAIService = new OpenAIService(
      $this->mockApiClient,
      $this->mockSession,
      $this->mockLogger,
      $this->mockConfigFactory,
      $this->mockMessenger
    );
  }

  public function testGenerateResponse() {
    $prompt = "Test prompt";
    $expectedResponse = "Test response";

    $this->mockApiClient->expects($this->once())
      ->method('sendRequest')
      ->willReturn(['choices' => [['message' => ['content' => $expectedResponse]]]]);

    $this->mockSession->expects($this->any())
      ->method('get')
      ->willReturn([]);

    $this->mockConfigFactory->expects($this->any())
      ->method('get')
      ->willReturn($this->createMock(\Drupal\Core\Config\ImmutableConfig::class));

    $response = $this->openAIService->generateResponse($prompt);
    $this->assertEquals($expectedResponse, $response);
  }
}