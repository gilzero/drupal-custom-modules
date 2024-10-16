<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\pdf_gpt_chat\Service\LoggingService;

class ErrorHandlerService {
  protected $loggingService;

  public function __construct(LoggingService $logging_service) {
    $this->loggingService = $logging_service;
  }

  public function handleAjaxError(\Exception $e) {
    $this->loggingService->logError('AJAX error: ' . $e->getMessage(), [
      'exception' => $e,
    ]);

    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new AppendCommand('#pdf-gpt-chat-log', '<div class="error-message">' . $e->getMessage() . '</div>'));
    return $ajax_response;
  }
}