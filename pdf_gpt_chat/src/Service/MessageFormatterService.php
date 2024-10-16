<?php

/**
 * @file
 * Contains \Drupal\pdf_gpt_chat\Service\MessageFormatterService.php
 */

namespace Drupal\pdf_gpt_chat\Service;

use Parsedown;

class MessageFormatterService {

  protected $parsedown;

  public function __construct() {
    $this->parsedown = new Parsedown();
  }

  public function formatMessage(string $question, string $answer) {
    $formatted = "<div class='chat-message user-message'><strong>You:</strong> " . nl2br(htmlspecialchars($question)) . "</div>";
    $formatted .= "<div class='chat-message ai-message'><strong>AI:</strong> <div class='markdown-content'>" . $this->parsedown->text($answer) . "</div></div>";
    return $formatted;
  }

}