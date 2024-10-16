<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class LoggingService {
  protected $logger;

  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('pdf_gpt_chat');
  }

  public function logSystemEvent($type, $message, array $context = []) {
    $this->logger->notice('@type: @message', [
      '@type' => $type,
      '@message' => $message,
    ] + $context);
  }

  public function logInteraction($userId, $fileId, $prompt, $response) {
    $this->logger->info('User @user interacted with file @file: Prompt: @prompt, Response: @response', [
      '@user' => $userId,
      '@file' => $fileId,
      '@prompt' => $prompt,
      '@response' => substr($response, 0, 100) . (strlen($response) > 100 ? '...' : ''),
    ]);
  }

  public function logError($message, array $context = []) {
    $this->logger->error('@message', ['@message' => $message] + $context);
  }
}