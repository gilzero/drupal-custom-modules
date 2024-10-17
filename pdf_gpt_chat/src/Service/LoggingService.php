<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class LoggingService {
  protected $logger;

  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->logger = $logger_factory->get('pdf_gpt_chat');
  }

  public function logSystemEvent($type, $message, array $context = []) {
    $this->logger->notice('@type: @message | Context: @context', [
      '@type' => $type,
      '@message' => $message,
      '@context' => json_encode($context),
    ]);
  }

  public function logInteraction($userId, $fileId, $prompt, $response, $requestId = null) {
    $this->logger->info('User @user interacted with file @file | Request ID: @requestId | Prompt: @prompt | Response: @response', [
      '@user' => $userId,
      '@file' => $fileId,
      '@requestId' => $requestId ?? 'N/A',
      '@prompt' => $prompt,
      '@response' => substr($response, 0, 100) . (strlen($response) > 100 ? '...' : ''),
    ]);
  }

  public function logError($message, array $context = []) {
    $this->logger->error('@message | Context: @context', [
      '@message' => $message,
      '@context' => json_encode($context),
    ]);
  }
}