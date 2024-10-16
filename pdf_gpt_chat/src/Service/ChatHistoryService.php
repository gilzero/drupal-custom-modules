<?php

/**
 * @file
 * Contains \Drupal\pdf_gpt_chat\Service\ChatHistoryService.php
 */

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\Core\State\StateInterface;

class ChatHistoryService {

  protected $state;

  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  public function saveMessage(int $uid, int $fid, string $question, string $answer) {
    $history = $this->state->get('pdf_gpt_chat_history', []);
    $history[] = [
      'uid' => $uid,
      'fid' => $fid,
      'question' => $question,
      'answer' => $answer,
      'timestamp' => time(),
    ];
    $this->state->set('pdf_gpt_chat_history', $history);
  }

  public function getHistory(int $uid, int $fid) {
    $history = $this->state->get('pdf_gpt_chat_history', []);
    $filtered_history = array_filter($history, function ($item) use ($uid, $fid) {
      return $item['uid'] === $uid && $item['fid'] === $fid;
    });
    return $filtered_history;
  }

}