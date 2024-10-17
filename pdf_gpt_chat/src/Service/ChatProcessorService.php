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
      $pdf_images = $this->pdfParser->convertPdfToImages($file, 250);
      $totalImages = count($pdf_images);
      $chunks = array_chunk($pdf_images, 250);
      $responses = [];

      foreach ($chunks as $index => $chunk) {
        $chunkPrompt = $this->preparePrompt($question, $index + 1, count($chunks));
        $response = $this->openAI->query($chunkPrompt, $chunk);
        $responses[] = $response;
      }

      $combinedResponse = $this->combineResponses($responses);
      $output = $this->messageFormatter->formatMessage($question, $combinedResponse);
      $this->chatHistory->saveMessage($file->getOwnerId(), $file->id(), $question, $combinedResponse);

      $this->loggingService->logInteraction($file->getOwnerId(), $file->id(), $question, $combinedResponse);

      $this->loggingService->logSystemEvent('chat_process_end', 'Chat process completed successfully', [
        'file_id' => $file->id(),
        'user_id' => $file->getOwnerId(),
        'total_images' => $totalImages,
        'chunks_processed' => count($chunks),
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

  protected function preparePrompt($question, $chunkNumber, $totalChunks) {
    return "This is part $chunkNumber of $totalChunks of the PDF. Please answer the following question based on the content of the provided PDF images: $question";
  }

  protected function combineResponses($responses) {
    return "Combined response from " . count($responses) . " parts of the PDF:\n\n" . implode("\n\n", $responses);
  }
}