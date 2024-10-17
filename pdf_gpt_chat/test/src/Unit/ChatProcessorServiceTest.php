<?php

namespace Drupal\Tests\pdf_gpt_chat\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\pdf_gpt_chat\Service\ChatProcessorService;
use Drupal\pdf_gpt_chat\Service\PdfParserService;
use Drupal\pdf_gpt_chat\Service\OpenAIService;
use Drupal\pdf_gpt_chat\Service\ChatHistoryService;
use Drupal\pdf_gpt_chat\Service\MessageFormatterService;
use Drupal\pdf_gpt_chat\Service\LoggingService;
use Drupal\file\Entity\File;

class ChatProcessorServiceTest extends UnitTestCase {

  protected $chatProcessor;
  protected $pdfParser;
  protected $openAI;
  protected $chatHistory;
  protected $messageFormatter;
  protected $loggingService;

  protected function setUp(): void {
    parent::setUp();

    $this->pdfParser = $this->createMock(PdfParserService::class);
    $this->openAI = $this->createMock(OpenAIService::class);
    $this->chatHistory = $this->createMock(ChatHistoryService::class);
    $this->messageFormatter = $this->createMock(MessageFormatterService::class);
    $this->loggingService = $this->createMock(LoggingService::class);

    $this->chatProcessor = new ChatProcessorService(
      $this->pdfParser,
      $this->openAI,
      $this->chatHistory,
      $this->messageFormatter,
      $this->loggingService
    );
  }

  public function testProcessChat() {
    $file = $this->createMock(File::class);
    $file->method('id')->willReturn(1);
    $file->method('getOwnerId')->willReturn(1);

    $this->pdfParser->expects($this->once())
      ->method('extractText')
      ->willReturn('Sample PDF content');

    $this->openAI->expects($this->once())
      ->method('query')
      ->willReturn('AI response');

    $this->messageFormatter->expects($this->once())
      ->method('formatMessage')
      ->willReturn('<div>Formatted message</div>');

    $result = $this->chatProcessor->processChat($file, 'Test question');

    $this->assertEquals('<div>Formatted message</div>', $result);
  }
}