<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;

class ErrorHandlerService {
  public function handleAjaxError(\Exception $e) {
    $ajax_response = new AjaxResponse();
    $ajax_response->addCommand(new AppendCommand('#pdf-gpt-chat-log', '<div class="error-message">' . $e->getMessage() . '</div>'));
    return $ajax_response;
  }
}