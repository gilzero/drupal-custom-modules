<?php

namespace Drupal\pdf_gpt_chat\Service;

use Drupal\file\Entity\File;

class ChatProcessorService {
  protected $pdfParser;
  protected $openAI;
  protected $chatHistory;
  protected $messageFormatter;

  public function __construct(
    PdfParserService $pdf_parser,
    OpenAIService $openai,
    ChatHistoryService $chat_history,
    MessageFormatterService $message_formatter
  ) {
    $this->pdfParser = $pdf_parser;
    $this->openAI = $openai;
    $this->chatHistory = $chat_history;
    $this->messageFormatter = $message_formatter;
  }

  public function processChat(File $file, string $question) {
    $pdf_text = $this->pdfParser->extractText($file);
    $prompt = $this->preparePrompt($pdf_text, $question);
    $response = $this->openAI->query($prompt);
    $output = $this->messageFormatter->formatMessage($question, $response);
    $this->chatHistory->saveMessage($file->getOwnerId(), $file->id(), $question, $response);
    return $output;
  }

  protected function preparePrompt($pdf_text, $question) {
    $context = substr($pdf_text, 0, 3000);
    return "Context from PDF: $context\n\nQuestion: $question\n\nPlease answer the question based on the given context from the PDF.";
  }
}