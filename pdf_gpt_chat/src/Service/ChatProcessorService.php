<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\file\Entity\File;

class ChatProcessorService {
  protected $pdfParser;
  protected $openAI;
  protected $chatHistory;
  protected $messageFormatter;
  protected $loggingService;

  public function __construct(
    PdfParserService $pdf_parser,
    OpenAIService $openai,
    ChatHistoryService $chat_history,
    MessageFormatterService $message_formatter,
    LoggingService $logging_service
  ) {
    $this->pdfParser = $pdf_parser;
    $this->openAI = $openai;
    $this->chatHistory = $chat_history;
    $this->messageFormatter = $message_formatter;
    $this->loggingService = $logging_service;
  }

  public function processChat(File $file, string $question) {
    $this->loggingService->logSystemEvent('chat_process_start', 'Starting chat process', [
      'file_id' => $file->id(),
      'user_id' => $file->getOwnerId(),
    ]);

    try {
      $pdf_text = $this->pdfParser->extractText($file);
      $prompt = $this->preparePrompt($pdf_text, $question);
      $response = $this->openAI->query($prompt);
      $output = $this->messageFormatter->formatMessage($question, $response);
      $this->chatHistory->saveMessage($file->getOwnerId(), $file->id(), $question, $response);

      $this->loggingService->logInteraction($file->getOwnerId(), $file->id(), $question, $response);

      $this->loggingService->logSystemEvent('chat_process_end', 'Chat process completed successfully', [
        'file_id' => $file->id(),
        'user_id' => $file->getOwnerId(),
      ]);

      return $output;
    } catch (\Exception $e) {
      $this->loggingService->logError('Error processing chat: ' . $e->getMessage(), [
        'file_id' => $file->id(),
        'user_id' => $file->getOwnerId(),
        'exception' => $e,
      ]);
      throw $e;
    }
  }

  protected function preparePrompt($pdf_text, $question) {
    $context = substr($pdf_text, 0, 3000);
    return "Context from PDF: $context\n\nQuestion: $question\n\nPlease answer the question based on the given context from the PDF.";
  }
}