<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\Core\State\StateInterface;

class ChatHistoryService {

  protected $state;
  protected $loggingService;

  public function __construct(StateInterface $state, LoggingService $logging_service) {
    $this->state = $state;
    $this->loggingService = $logging_service;
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

    $this->loggingService->logSystemEvent('chat_history_save', 'Saved chat message to history', [
      'user_id' => $uid,
      'file_id' => $fid,
    ]);
  }

  public function getHistory(int $uid, int $fid) {
    $history = $this->state->get('pdf_gpt_chat_history', []);
    $filtered_history = array_filter($history, function ($item) use ($uid, $fid) {
      return $item['uid'] === $uid && $item['fid'] === $fid;
    });

    $this->loggingService->logSystemEvent('chat_history_retrieve', 'Retrieved chat history', [
      'user_id' => $uid,
      'file_id' => $fid,
      'history_count' => count($filtered_history),
    ]);

    return $filtered_history;
  }
}